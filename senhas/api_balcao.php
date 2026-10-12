<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['erro' => 'Método inválido'], 405);
}

$balcaoId = (int)($_POST['balcao_id'] ?? 0);
$acao = $_POST['acao'] ?? 'estado';

$st = $pdo->prepare('SELECT id, servico_id FROM balcoes WHERE id = ?');
$st->execute([$balcaoId]);
$balcao = $st->fetch();
if (!$balcao) json_out(['erro' => 'Balcão inválido'], 400);

function senhaAtual(PDO $pdo, int $balcaoId) {
    $st = $pdo->prepare("
        SELECT s.id, s.codigo, c.id AS cliente_id, c.nome AS cliente_nome
        FROM senhas s
        JOIN clientes c ON c.id = s.cliente_id
        WHERE s.balcao_id = ? AND s.estado = 'chamado' AND s.data_criacao >= CURDATE()
        ORDER BY s.ultima_chamada DESC
        LIMIT 1
    ");
    $st->execute([$balcaoId]);
    return $st->fetch();
}

try {
    switch ($acao) {
        case 'proximo':
            $pdo->beginTransaction();
            // Conclui automaticamente a senha que estava a ser atendida
            $pdo->prepare("UPDATE senhas SET estado = 'concluido' WHERE balcao_id = ? AND estado = 'chamado'")
                ->execute([$balcaoId]);

            $st = $pdo->prepare("SELECT id FROM senhas WHERE servico_id = ? AND estado = 'espera' AND data_criacao >= CURDATE() ORDER BY id LIMIT 1 FOR UPDATE");
            $st->execute([$balcao['servico_id']]);
            $prox = $st->fetch();
            if ($prox) {
                $pdo->prepare("UPDATE senhas SET estado = 'chamado', balcao_id = ?, ultima_chamada = NOW(3) WHERE id = ?")
                    ->execute([$balcaoId, $prox['id']]);
            }
            $pdo->commit();
            break;

        case 'rechamar':
            $atual = senhaAtual($pdo, $balcaoId);
            if ($atual) {
                $pdo->prepare('UPDATE senhas SET ultima_chamada = NOW(3) WHERE id = ?')->execute([$atual['id']]);
            }
            break;

        case 'concluir':
        case 'cancelar':
            $novo = $acao === 'concluir' ? 'concluido' : 'cancelado';
            $pdo->prepare("UPDATE senhas SET estado = ? WHERE balcao_id = ? AND estado = 'chamado'")
                ->execute([$novo, $balcaoId]);
            break;
    }

    $atual = senhaAtual($pdo, $balcaoId);
    $st = $pdo->prepare("SELECT COUNT(*) FROM senhas WHERE servico_id = ? AND estado = 'espera' AND data_criacao >= CURDATE()");
    $st->execute([$balcao['servico_id']]);

    json_out([
        'atual'        => $atual ? $atual['codigo'] : null,
        'cliente_nome' => $atual ? $atual['cliente_nome'] : null,
        'cliente_id'   => $atual ? (int)$atual['cliente_id'] : null,
        'espera'       => (int)$st->fetchColumn(),
    ]);
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_out(['erro' => 'Erro no servidor'], 500);
}