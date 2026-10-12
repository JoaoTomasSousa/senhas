<?php
require 'config.php';

$st = $pdo->query("
    SELECT s.id, s.codigo, b.numero AS balcao, s.ultima_chamada
    FROM senhas s
    JOIN balcoes b ON b.id = s.balcao_id
    WHERE s.ultima_chamada >= CURDATE()
    ORDER BY s.ultima_chamada DESC
    LIMIT 5
");
$ultimas = $st->fetchAll();

// A assinatura muda sempre que há uma nova chamada ou rechamada
$assinatura = $ultimas ? ($ultimas[0]['id'] . '|' . $ultimas[0]['ultima_chamada']) : '';

json_out(['ultimas' => $ultimas, 'assinatura' => $assinatura]);
