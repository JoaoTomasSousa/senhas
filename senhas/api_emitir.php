<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['erro' => 'Método inválido'], 405);
}

$servicoId = (int)($_POST['servico_id'] ?? 0);
$nome = trim($_POST['nome'] ?? '');
$nome = preg_replace('/\s+/', ' ', $nome);

if (mb_strlen($nome, 'UTF-8') < 2 || mb_strlen($nome, 'UTF-8') > 60) {
    json_out(['erro' => 'Nome inválido (2 a 60 caracteres).'], 400);
}

try {
    $pdo->beginTransaction();

    // Bloqueia a linha do serviço para evitar números repetidos
    $st = $pdo->prepare('SELECT id, nome, prefixo FROM servicos WHERE id = ? FOR UPDATE');
    $st->execute([$servicoId]);
    $servico = $st->fetch();
    if (!$servico) {
        $pdo->rollBack();
        json_out(['erro' => 'Serviço inválido'], 400);
    }

    // Regista o cliente (cada pedido gera um id novo)
    $st = $pdo->prepare('INSERT INTO clientes (nome) VALUES (?)');
    $st->execute([$nome]);
    $clienteId = (int)$pdo->lastInsertId();

    // Próximo número do dia
    $st = $pdo->prepare('SELECT COUNT(*) FROM senhas WHERE servico_id = ? AND data_criacao >= CURDATE()');
    $st->execute([$servicoId]);
    $numero = (int)$st->fetchColumn() + 1;
    $codigo = $servico['prefixo'] . str_pad($numero, 3, '0', STR_PAD_LEFT);

    $st = $pdo->prepare('INSERT INTO senhas (codigo, servico_id, cliente_id) VALUES (?, ?, ?)');
    $st->execute([$codigo, $servicoId, $clienteId]);
    $novoId = (int)$pdo->lastInsertId();

    // Quantas senhas estão à frente
    $st = $pdo->prepare("SELECT COUNT(*) FROM senhas WHERE servico_id = ? AND estado = 'espera' AND data_criacao >= CURDATE() AND id < ?");
    $st->execute([$servicoId, $novoId]);
    $aFrente = (int)$st->fetchColumn();

    $pdo->commit();

    json_out([
        'codigo'     => $codigo,
        'nome'       => $nome,
        'cliente_id' => $clienteId,
        'servico'    => $servico['nome'],
        'a_frente'   => $aFrente,
        'hora'       => date('H:i'),
    ]);
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_out(['erro' => 'Erro no servidor'], 500);
}