<?php
/**
 * Cliente HTTP pra API do ASAAS. Lê ASAAS_API_KEY e ASAAS_BASE_URL do
 * config-crm.php (mesmo arquivo de fora da pasta pública que guarda as
 * credenciais do banco).
 */

require_once __DIR__ . '/db.php'; // já carrega o config-crm.php e define as constantes

class AsaasClient
{
    private function request(string $method, string $path, ?array $body = null): array
    {
        $ch = curl_init(ASAAS_BASE_URL . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'access_token: ' . ASAAS_API_KEY,
                'User-Agent: Newsiga-CRM/1.0',
            ],
            CURLOPT_POSTFIELDS => $body ? json_encode($body) : null,
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erroCurl = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("Falha de conexão com o ASAAS: $erroCurl");
        }

        $data = json_decode($response, true) ?? [];
        if ($status >= 400) {
            $msg = $data['errors'][0]['description'] ?? $response;
            throw new RuntimeException("ASAAS $method $path falhou ($status): $msg");
        }
        return $data;
    }

    public function buscarClientePorDocumento(string $cpfCnpj): ?array
    {
        $resultado = $this->request('GET', '/customers?cpfCnpj=' . urlencode(preg_replace('/\D/', '', $cpfCnpj)));
        return $resultado['data'][0] ?? null;
    }

    public function criarOuBuscarCliente(string $nome, string $cpfCnpj, ?string $email = null): array
    {
        $existente = $this->buscarClientePorDocumento($cpfCnpj);
        if ($existente) {
            return $existente;
        }
        return $this->request('POST', '/customers', [
            'name' => $nome,
            'cpfCnpj' => preg_replace('/\D/', '', $cpfCnpj),
            'email' => $email,
            // Produção: notificações LIGADAS — o ASAAS deve avisar o
            // cliente de verdade (boleto/e-mail de cobrança). Ficava
            // desligado só durante os testes em sandbox, pra não
            // notificar cliente real por engano com dado de teste.
            'notificationDisabled' => false,
        ]);
    }

    public function atualizarCliente(string $customerId, array $campos): array
    {
        return $this->request('PUT', "/customers/$customerId", $campos);
    }

    public function criarCobranca(string $customerId, float $valor, string $vencimento, string $externalReference, string $descricao = ''): array
    {
        return $this->request('POST', '/payments', [
            'customer' => $customerId,
            'billingType' => 'BOLETO', // libera Pix automaticamente junto — mesmo padrão das assinaturas antigas
            'value' => $valor,
            'dueDate' => $vencimento,
            'description' => $descricao,
            'externalReference' => $externalReference,
        ]);
    }

    public function listarAssinaturasDoCliente(string $customerId): array
    {
        $resultado = $this->request('GET', '/subscriptions?customer=' . urlencode($customerId));
        return $resultado['data'] ?? [];
    }

    public function atualizarCobranca(string $paymentId, array $campos): array
    {
        return $this->request('PUT', "/payments/$paymentId", $campos);
    }

    public function buscarCobranca(string $paymentId): array
    {
        return $this->request('GET', "/payments/$paymentId");
    }

    public function excluirCobranca(string $paymentId): array
    {
        return $this->request('DELETE', "/payments/$paymentId");
    }

    public function listarCobrancasDoCliente(string $customerId, ?string $status = null): array
    {
        $path = '/payments?customer=' . urlencode($customerId);
        if ($status) {
            $path .= '&status=' . urlencode($status);
        }
        $resultado = $this->request('GET', $path);
        return $resultado['data'] ?? [];
    }

    public function buscarAssinatura(string $subscriptionId): array
    {
        return $this->request('GET', "/subscriptions/$subscriptionId");
    }

    public function suspenderAssinatura(string $subscriptionId): array
    {
        return $this->request('PUT', "/subscriptions/$subscriptionId", ['status' => 'INACTIVE']);
    }
}
