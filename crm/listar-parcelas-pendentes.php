<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Lista TODAS as parcelas de projeto (qualquer status), com o nome do
 * cliente e a descrição do contrato. Nome do endpoint ficou de um tempo
 * em que só listava as não-pagas — hoje serve vários consumidores com
 * necessidades diferentes (radar de cobranças, KPIs "Em atraso"/
 * "Projetos sem fatura", "Previsão do mês", "Faturamento por mês",
 * receita prevista da tela de Despesas), e cada um filtra por status
 * conforme sua própria regra no JS/SQL de quem consome.
 *
 * Antes excluía status='pago' aqui na fonte — parecia razoável ("pago
 * não é mais pendente"), mas quebrava qualquer cálculo de RECEITA TOTAL
 * do mês (Previsão do mês, Faturamento por mês): uma parcela que acabou
 * de ser paga simplesmente sumia da soma, fazendo a receita do mês
 * CAIR no momento exato em que o dinheiro entrou — o oposto do que
 * devia acontecer. O filtro por vencimento (mês) já é o que define o
 * período; status não deveria decidir se a parcela existe ou não.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDb();
    $stmt = $db->query("
        SELECT
            p.id, p.contrato_id, p.numero, p.valor, p.vencimento, p.status,
            c.descricao AS contrato_descricao,
            cl.nome AS cliente_nome
        FROM parcelas p
        JOIN contratos c ON c.id = p.contrato_id
        JOIN clientes cl ON cl.id = c.cliente_id
        ORDER BY p.vencimento ASC
    ");
    echo json_encode(['sucesso' => true, 'parcelas' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao listar parcelas', 'detalhe' => $e->getMessage()]);
}
