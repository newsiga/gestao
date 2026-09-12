# Integração CRM Newsiga → Google Agenda (via único: CRM cria eventos)

Escopo: quando um **compromisso** é criado/editado/cancelado no `crm.newsiga.com.br`, o sistema reflete isso como evento na Google Agenda de `felipe.valenca@newsiga.com.br`. Sem sincronização de volta (Agenda → CRM).

Autenticação: OAuth2 de usuário, usando a conta Google Workspace da Newsiga. Como é uso interno (só você autoriza, uso só seu), a tela de consentimento pode ficar em modo **Internal**, o que evita todo o processo de verificação do Google — é a vantagem de usar o Workspace aqui.

Agenda: os eventos vão para uma agenda **adicional e dedicada**, "CRM Newsiga" — dentro da sua própria conta Google, mas separada da sua agenda pessoal. Isso permite ligar/desligar essa camada na visualização do Google Agenda sem misturar com compromissos pessoais.

---

## 1. Configuração no Google Cloud Console

1. Criar (ou reaproveitar) um projeto no [console.cloud.google.com](https://console.cloud.google.com), associado ao domínio Workspace da Newsiga.
2. Ativar a **Google Calendar API** (APIs & Services → Library → Google Calendar API → Enable).
3. Configurar a **OAuth consent screen**:
   - User Type: **Internal** (disponível porque é conta Workspace — restringe a autorização a usuários do domínio `newsiga.com.br`, sem precisar de revisão do Google).
   - Escopo necessário: `https://www.googleapis.com/auth/calendar.events` (permite criar/editar/excluir eventos, sem acesso a mais do que isso).
4. Criar **Credenciais → OAuth Client ID**, tipo "Web application".
   - Authorized redirect URI: `https://crm.newsiga.com.br/google/oauth-callback.php`
5. Baixar o `client_id` e `client_secret` — vão para o `config-crm.php` (mesmo padrão que vocês já usam pra guardar a chave do ASAAS).

---

## 2. Criar a agenda "CRM Newsiga"

Mais simples fazer manualmente, uma vez, pela própria interface do Google Agenda do que via API:

1. Acesse [calendar.google.com](https://calendar.google.com) logado como `felipe.valenca@newsiga.com.br`.
2. No painel esquerdo, "Outras agendas" → **+** → **Criar nova agenda**.
3. Nome: `CRM Newsiga`. Salvar.
4. Entrar nas configurações dessa agenda (⋮ → Configurações e compartilhamento) e copiar o **ID da agenda**, na seção "Integrar agenda". Tem um formato parecido com `abcdef123456@group.calendar.google.com`.
5. Guardar esse ID como `GOOGLE_CRM_CALENDAR_ID` no `config-crm.php`, junto com `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET`.

Fazendo assim, o escopo `calendar.events` (do passo 1) já é suficiente — ele permite gerenciar eventos em qualquer agenda que sua conta tenha acesso, incluindo essa nova. Não precisa pedir o escopo mais amplo `calendar` (que permitiria criar/excluir agendas inteiras via API, desnecessário aqui).

---

## 3. Biblioteca

```bash
composer require google/apiclient:^2.15
```

(Mesmo composer.json do projeto CRM, se já existir; senão criar um.)

---

## 4. Schema do banco (MySQL/MariaDB)

```sql
-- Token OAuth — linha única, já que é uma conta só (Felipe)
CREATE TABLE google_oauth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    access_token TEXT NOT NULL,
    refresh_token TEXT NOT NULL,
    expires_at DATETIME NOT NULL,
    scope VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Compromissos do CRM
CREATE TABLE compromissos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    tipo ENUM('reuniao', 'visita_tecnica', 'apresentacao', 'outro') NOT NULL DEFAULT 'reuniao',
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    data_hora_inicio DATETIME NOT NULL,
    data_hora_fim DATETIME NOT NULL,
    responsavel VARCHAR(100) NOT NULL,
    status ENUM('agendado', 'realizado', 'cancelado') NOT NULL DEFAULT 'agendado',
    google_event_id VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);
```

---

## 5. Fluxo de autorização (feito uma vez)

**`/google/auth.php`** — redireciona para a tela de consentimento do Google:

```php
<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config-crm.php'; // define GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri('https://crm.newsiga.com.br/google/oauth-callback.php');
$client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);
$client->setAccessType('offline');   // necessário para receber refresh_token
$client->setPrompt('consent');       // força reenvio do refresh_token mesmo em reautorização

header('Location: ' . $client->createAuthUrl());
exit;
```

**`/google/oauth-callback.php`** — recebe o `code`, troca por tokens e salva:

```php
<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config-crm.php';
require __DIR__ . '/../db.php'; // sua conexão PDO existente

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri('https://crm.newsiga.com.br/google/oauth-callback.php');

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

if (isset($token['error'])) {
    die('Erro na autorização: ' . htmlspecialchars($token['error_description'] ?? $token['error']));
}

$stmt = $pdo->prepare("
    INSERT INTO google_oauth_tokens (access_token, refresh_token, expires_at, scope)
    VALUES (:access_token, :refresh_token, :expires_at, :scope)
");
$stmt->execute([
    'access_token'  => $token['access_token'],
    'refresh_token' => $token['refresh_token'], // só vem na primeira autorização (ou com prompt=consent)
    'expires_at'    => date('Y-m-d H:i:s', time() + $token['expires_in']),
    'scope'         => $token['scope'],
]);

echo 'Google Agenda conectada com sucesso. Pode fechar esta aba.';
```

Isso é rodado **uma vez** (você acessa `/google/auth.php` manualmente, autoriza, pronto). Depois disso o sistema usa o `refresh_token` salvo pra sempre gerar novo `access_token` sozinho.

---

## 6. Serviço reutilizável — `GoogleCalendarService.php`

```php
<?php
class GoogleCalendarService
{
    private Google_Service_Calendar $service;
    private string $calendarId = GOOGLE_CRM_CALENDAR_ID; // agenda dedicada "CRM Newsiga", definida no config-crm.php

    public function __construct(PDO $pdo)
    {
        $client = new Google_Client();
        $client->setClientId(GOOGLE_CLIENT_ID);
        $client->setClientSecret(GOOGLE_CLIENT_SECRET);

        $row = $pdo->query("SELECT * FROM google_oauth_tokens ORDER BY id DESC LIMIT 1")->fetch();
        if (!$row) {
            throw new Exception('Google Agenda ainda não foi autorizada. Acesse /google/auth.php');
        }

        $client->setAccessToken([
            'access_token'  => $row['access_token'],
            'refresh_token' => $row['refresh_token'],
            'expires_in'    => max(0, strtotime($row['expires_at']) - time()),
        ]);

        if ($client->isAccessTokenExpired()) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($row['refresh_token']);
            $pdo->prepare("UPDATE google_oauth_tokens SET access_token = ?, expires_at = ? WHERE id = ?")
                ->execute([
                    $newToken['access_token'],
                    date('Y-m-d H:i:s', time() + $newToken['expires_in']),
                    $row['id'],
                ]);
        }

        $this->service = new Google_Service_Calendar($client);
    }

    public function criarEvento(array $compromisso): string
    {
        $event = new Google_Service_Calendar_Event([
            'summary'     => $compromisso['titulo'],
            'description' => $compromisso['descricao'] ?? '',
            'start'       => ['dateTime' => $this->toRfc3339($compromisso['data_hora_inicio']), 'timeZone' => 'America/Recife'],
            'end'         => ['dateTime' => $this->toRfc3339($compromisso['data_hora_fim']), 'timeZone' => 'America/Recife'],
        ]);

        $created = $this->service->events->insert($this->calendarId, $event);
        return $created->getId();
    }

    public function atualizarEvento(string $googleEventId, array $compromisso): void
    {
        $event = $this->service->events->get($this->calendarId, $googleEventId);
        $event->setSummary($compromisso['titulo']);
        $event->setDescription($compromisso['descricao'] ?? '');
        $event->setStart(new Google_Service_Calendar_EventDateTime([
            'dateTime' => $this->toRfc3339($compromisso['data_hora_inicio']), 'timeZone' => 'America/Recife',
        ]));
        $event->setEnd(new Google_Service_Calendar_EventDateTime([
            'dateTime' => $this->toRfc3339($compromisso['data_hora_fim']), 'timeZone' => 'America/Recife',
        ]));

        $this->service->events->update($this->calendarId, $googleEventId, $event);
    }

    public function cancelarEvento(string $googleEventId): void
    {
        $this->service->events->delete($this->calendarId, $googleEventId);
    }

    private function toRfc3339(string $mysqlDateTime): string
    {
        return (new DateTime($mysqlDateTime))->format(DateTime::RFC3339);
    }
}
```

---

## 7. Integração com o CRUD de compromissos existente

Nos pontos onde o CRM já salva/edita/exclui um compromisso, só adicionar a chamada:

```php
// Ao criar
$googleEventId = $googleCalendar->criarEvento($dadosDoCompromisso);
$pdo->prepare("UPDATE compromissos SET google_event_id = ? WHERE id = ?")
    ->execute([$googleEventId, $compromissoId]);

// Ao editar
if ($compromisso['google_event_id']) {
    $googleCalendar->atualizarEvento($compromisso['google_event_id'], $dadosAtualizados);
}

// Ao cancelar/excluir
if ($compromisso['google_event_id']) {
    $googleCalendar->cancelarEvento($compromisso['google_event_id']);
}
```

**Importante**: envolver essas chamadas em `try/catch` e não deixar uma falha na API do Google derrubar o salvamento do compromisso no CRM — se a Agenda falhar, salva mesmo assim no banco e loga o erro pra tentar de novo depois (ex: um campo `google_sync_status` se quiser robustez extra futuramente).

