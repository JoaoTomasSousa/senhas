<?php
require 'config.php';
$servicos = $pdo->query('SELECT id, nome, prefixo FROM servicos ORDER BY id')->fetchAll();
function icone($d) {
    return '<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}
$icones = [
    // Tesouraria: nota
    'T' => icone('<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/>'),
    // Balcão: pessoa
    'B' => icone('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'),
    // Empresas: edifício
    'E' => icone('<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 6h.01M12 6h.01M16 6h.01M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M16 14h.01"/>'),
    // Sinistros: escudo
    'S' => icone('<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'),
    '_' => icone('<circle cx="12" cy="12" r="9"/>'),
];
$logoPath = __DIR__ . '/logo.png';
$logoData = is_file($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : 'logo.png';
?>
<!doctype html>
<html lang="pt-PT">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quiosque de Senhas</title>
<link rel="icon" href="logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="quiosque">

<header class="topo">
  <img src="logo.png" alt="Logótipo">
  <div>
    <div class="titulo">Atendimento</div>
    <div class="subtitulo">Tire a sua senha</div>
  </div>
</header>

<main class="conteudo">
  <div class="campo">
    <input type="text" id="nome" aria-label="O seu nome" maxlength="60" placeholder="Escreva o seu nome" autocomplete="off">
  </div>

  <div class="grelha">
    <?php foreach ($servicos as $s): ?>
      <button class="servico" data-id="<?= (int)$s['id'] ?>">
        <div class="icone"><?= $icones[$s['prefixo']] ?? $icones['_'] ?></div>
        <div class="nome"><?= e($s['nome']) ?></div>
      </button>
    <?php endforeach; ?>
  </div>
</main>

<div id="overlay">
  <div class="talao">
    <div class="cabeca"><img src="logo.png" alt="Logótipo"></div>
    <div class="corpo">
      <div class="rotulo">A sua senha</div>
      <div class="codigo" id="t-codigo"></div>
      <div class="nome" id="t-nome"></div>
      <div class="meta" id="t-cliente"></div>
      <div class="servico-nome" id="t-servico"></div>
    </div>
    <div class="corte"></div>
    <div class="info" id="t-info"></div>
    <div class="acoes no-print">
      <button class="btn btn-secundario" onclick="imprimir()">Imprimir</button>
      <button class="btn btn-primario" onclick="fechar()">OK</button>
    </div>
  </div>
</div>

<script>
const LOGO_DATA = <?= json_encode($logoData) ?>;
const IMPRIMIR_AUTOMATICO = false; // mude para true para imprimir logo ao emitir a senha
let timer = null;
let ultimaSenha = null;

function imprimir() {
  if (!ultimaSenha) return;
  const s = ultimaSenha;
  const esc = t => String(t).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const logo = LOGO_DATA;
  const html = '<!doctype html><html><head><meta charset="utf-8"><title>Senha ' + esc(s.codigo) + '</title><style>' +
    '@page{size:80mm auto;margin:4mm}' +
    'body{font-family:Arial,sans-serif;text-align:center;color:#000;margin:0}' +
    'img{width:26mm;height:26mm;border-radius:4mm;-webkit-print-color-adjust:exact;print-color-adjust:exact}' +
    '.r{font-size:10pt;letter-spacing:2px;margin-top:3mm}' +
    '.c{font-size:48pt;font-weight:bold;line-height:1.1}' +
    '.n{font-size:16pt;font-weight:bold;margin-top:2mm}' +
    '.m{font-size:10pt;margin-top:1mm}' +
    '.s{font-size:14pt;margin-top:3mm;border:1px solid #000;display:inline-block;padding:1mm 4mm;border-radius:6mm}' +
    '.l{border-top:2px dashed #000;margin:4mm 0 2mm}' +
    '</style></head><body>' +
    '<img src="' + logo + '" alt="">' +
    '<div class="r">A SUA SENHA</div>' +
    '<div class="c">' + esc(s.codigo) + '</div>' +
    '<div class="n">' + esc(s.nome) + '</div>' +
    '<div class="m">Cliente nº ' + esc(s.cliente_id) + '</div>' +
    '<div class="s">' + esc(s.servico) + '</div>' +
    '<div class="l"></div>' +
    '<div class="m">' + esc(s.a_frente) + ' senha(s) à sua frente</div>' +
    '<div class="m">' + esc(s.data) + ' · ' + esc(s.hora) + '</div>' +
    '</body></html>';

  const antigo = document.getElementById('print-frame');
  if (antigo) antigo.remove();
  const f = document.createElement('iframe');
  f.id = 'print-frame';
  f.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0';
  document.body.appendChild(f);

  const doc = f.contentWindow.document;
  doc.open(); doc.write(html); doc.close();

  const imprimirAgora = () => { f.contentWindow.focus(); f.contentWindow.print(); };
  const img = doc.querySelector('img');
  if (img && !img.complete) { img.onload = imprimirAgora; img.onerror = imprimirAgora; }
  else { setTimeout(imprimirAgora, 150); }
}

function fechar() {
  document.getElementById('overlay').style.display = 'none';
  document.getElementById('nome').value = '';
  clearTimeout(timer);
}
document.querySelectorAll('.servico').forEach(btn => {
  btn.addEventListener('click', async (ev) => {
    const nome = document.getElementById('nome').value.trim();
    if (nome.length < 2) {
      alert('Por favor, escreva o seu nome.');
      document.getElementById('nome').focus();
      return;
    }
    // Onda de cor a partir do ponto clicado + botão fica colorido
    const ret = btn.getBoundingClientRect();
    const x = ev.clientX ? ev.clientX - ret.left : ret.width / 2;
    const y = ev.clientY ? ev.clientY - ret.top : ret.height / 2;
    const d = 2 * Math.hypot(Math.max(x, ret.width - x), Math.max(y, ret.height - y));
    const onda = document.createElement('span');
    onda.className = 'onda';
    onda.style.cssText = 'width:' + d + 'px;height:' + d + 'px;left:' + (x - d / 2) + 'px;top:' + (y - d / 2) + 'px';
    btn.appendChild(onda);
    setTimeout(() => onda.remove(), 750);
    btn.classList.add('ativo');
    btn.disabled = true;
    try {
      const fd = new FormData();
      fd.append('servico_id', btn.dataset.id);
      fd.append('nome', nome);
      const r = await fetch('api_emitir.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.erro) { alert(d.erro); return; }
      document.getElementById('t-codigo').textContent = d.codigo;
      document.getElementById('t-nome').textContent = d.nome;
      document.getElementById('t-cliente').textContent = 'Cliente nº ' + d.cliente_id;
      document.getElementById('t-servico').textContent = d.servico;
      document.getElementById('t-info').textContent =
        d.a_frente + ' senha(s) à sua frente · ' + d.hora;
      document.getElementById('overlay').style.display = 'flex';
      d.data = new Date().toLocaleDateString('pt-PT');
      ultimaSenha = d;
      timer = setTimeout(fechar, 10000);
      if (IMPRIMIR_AUTOMATICO) imprimir();
    } catch (e) {
      alert('Erro ao emitir senha.');
    } finally {
      btn.disabled = false;
      setTimeout(() => btn.classList.remove('ativo'), 700);
    }
  });
});
</script>
</body>
</html>