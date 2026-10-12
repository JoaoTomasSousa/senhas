<?php
date_default_timezone_set('Europe/Lisbon');

// Valores por omissão do Laragon: utilizador root, sem password
$pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=db_senhas;charset=utf8mb4',
    'root',
    '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

function json_out($dados, $codigo = 200) {
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($dados);
    exit;
}

function e($txt) {
    return htmlspecialchars((string)$txt, ENT_QUOTES, 'UTF-8');
}
