<?php
require 'config.php';
$balcaoId = (int)($_GET['balcao'] ?? 0);

$balcao = null;
if ($balcaoId) {
    $st = $pdo->prepare('SELECT b.id, b.numero, s.nome AS servico FROM balcoes b JOIN servicos s ON s.id = b.servico_id WHERE b.id = ?');
    $st->execute([$balcaoId]);
    $balcao = $st->fetch();
}
$lista = $pdo->query('SELECT b.id, b.numero, s.nome AS servico FROM balcoes b JOIN servicos s ON s.id = b.servico_id ORDER BY b.numero')->fetchAll();
?>
<!doctype html>
<html lang="pt-PT">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Painel do Balcão</title>
<link rel="icon" href="logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<header class="topo">
  <img src="logo.png" alt="Logótipo">
  <div>
    <div class="titulo">
      <?= $balcao ? 'Balcão ' . (int)$balcao['numero'] : 'Painel do Balcão' ?>
    </div>
    <div class="subtitulo"><?= $balcao ? e($balcao['servico']) : 'Escolha o seu posto' ?></div>
  </div>
  <div class="espaco"></div>
  <?php if ($balcao): ?><a class="link" href="balcao.php">Trocar balcão</a><?php endif; ?>
</header>

<main class="conteudo" style="max-width:640px">
<?php if (!$balcao): ?>
  <div class="lista-balcoes">
    <?php foreach ($lista as $b): ?>
      <a href="balcao.php?balcao=<?= (int)$b['id'] ?>">
        <span>Balcão <?= (int)$b['numero'] ?></span>
        <small><?= e($b['servico']) ?></small>
      </a>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="painel">
    <div class="rotulo">A atender</div>
    <div class="codigo-grande" id="atual">—</div>
    <div class="cliente-nome" id="cliente-nome"></div>
    <div class="cliente-id" id="cliente-id"></div>
    <div class="espera">Em espera: <strong id="espera">0</strong></div>
  </div>

  <div class="acoes-balcao">
    <button class="btn btn-primario" onclick="acao('proximo')">Chamar Próximo</button>
    <button class="btn btn-secundario" onclick="acao('rechamar')">Rechamar</button>
    <button class="btn btn-escuro" onclick="acao('concluir')">Concluir Atendimento</button>
    <button class="btn btn-perigo" onclick="acao('cancelar')">Cliente não compareceu</button>
  </div>
<?php endif; ?>
</main>

<?php if ($balcao): ?>
<script>
const BALCAO_ID = <?= (int)$balcao['id'] ?>;

async function acao(nome) {
  const fd = new FormData();
  fd.append('balcao_id', BALCAO_ID);
  fd.append('acao', nome);
  try {
    const r = await fetch('api_balcao.php', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.erro) { alert(d.erro); return; }
    if (nome === 'proximo' && !d.atual) alert('Não há senhas em espera.');
    mostrar(d);
  } catch (e) { alert('Erro de comunicação.'); }
}

function mostrar(d) {
  document.getElementById('atual').textContent = d.atual ? d.atual : '—';
  document.getElementById('cliente-nome').textContent = d.cliente_nome ? d.cliente_nome : '';
  document.getElementById('cliente-id').textContent = d.cliente_id ? 'Cliente nº ' + d.cliente_id : '';
  document.getElementById('espera').textContent = d.espera;
}

setInterval(() => acao('estado'), 3000);
acao('estado');
</script>
<?php endif; ?>
</body>
</html>