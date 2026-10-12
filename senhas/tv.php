<!doctype html>
<html lang="pt-PT">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Painel de Senhas</title>
<link rel="icon" href="logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
  body { background: var(--roxo-escuro); color: #fff; height: 100vh; overflow: hidden; }
  #inicio { position: fixed; inset: 0; z-index: 10; background: radial-gradient(circle at 50% 40%, var(--roxo-claro), var(--roxo-escuro)); display: flex; flex-direction: column; gap: 1.5rem; align-items: center; justify-content: center; }
  #inicio img { width: 140px; height: 140px; border-radius: 32px; }
  #inicio button { font: inherit; font-size: 1.6rem; font-weight: 600; padding: 1.1rem 2.5rem; border: 0; border-radius: 999px; background: var(--petroleo); color: #fff; cursor: pointer; box-shadow: 0 10px 30px rgba(3, 154, 173, .4); }

  .tv { display: flex; flex-direction: column; height: 100vh; }
  .tv-topo { display: flex; align-items: center; gap: 1.2vw; padding: 1.2vw 2.5vw; background: rgba(0, 0, 0, .25); }
  .tv-topo img { width: 5vw; height: 5vw; min-width: 48px; min-height: 48px; border-radius: 1.2vw; }
  .tv-topo .t { font-size: 2.2vw; font-weight: 700; }
  .tv-topo .espaco { flex: 1; }
  .tv-topo .relogio { text-align: right; }
  .tv-topo .hora { font-size: 3vw; font-weight: 700; color: var(--turquesa); line-height: 1; }
  .tv-topo .data { font-size: 1.2vw; color: #c9bfdb; }

  .layout { flex: 1; display: flex; min-height: 0; }
  .principal { flex: 2; display: flex; flex-direction: column; align-items: center; justify-content: center; background: radial-gradient(circle at 50% 45%, var(--roxo-claro), var(--roxo) 70%); transition: background .3s; }
  .principal .rotulo { font-size: 2.2vw; color: var(--turquesa); letter-spacing: .35em; font-weight: 600; }
  .principal .codigo { font-size: 17vw; font-weight: 800; line-height: 1.05; }
  .principal .balcao { font-size: 5vw; font-weight: 600; background: var(--petroleo); padding: .3vw 3vw; border-radius: 999px; margin-top: 1vw; }
  .principal .balcao:empty { display: none; }

  .lateral { flex: 1; padding: 2vw 2.5vw; background: rgba(0, 0, 0, .22); }
  .lateral h2 { color: var(--turquesa); font-size: 1.6vw; letter-spacing: .2em; margin: 0 0 1vw; }
  .item { display: flex; justify-content: space-between; align-items: center; font-size: 3.2vw; font-weight: 700; padding: 1.2vw 0; border-bottom: 1px solid rgba(255, 255, 255, .12); }
  .item .b { font-size: 2vw; font-weight: 600; color: var(--turquesa); }

  .pisca { animation: pisca .7s 5; }
  @keyframes pisca { 50% { background: var(--petroleo); } }
</style>
</head>
<body>
<div id="inicio">
  <img src="logo.png" alt="Logótipo">
  <button onclick="iniciar()">Iniciar painel (ecrã inteiro + som)</button>
</div>

<div class="tv">
  <div class="tv-topo">
    <img src="logo.png" alt="Logótipo">
    <div class="t">Atendimento</div>
    <div class="espaco"></div>
    <div class="relogio">
      <div class="hora" id="hora">--:--</div>
      <div class="data" id="data"></div>
    </div>
  </div>

  <div class="layout">
    <div class="principal" id="principal">
      <div class="rotulo">SENHA</div>
      <div class="codigo" id="codigo">—</div>
      <div class="balcao" id="balcao"></div>
    </div>
    <div class="lateral">
      <h2>ÚLTIMAS CHAMADAS</h2>
      <div id="lista"></div>
    </div>
  </div>
</div>

<script>
let audioCtx = null;
let assinaturaAnterior = null;

function iniciar() {
  audioCtx = new (window.AudioContext || window.webkitAudioContext)();
  document.getElementById('inicio').style.display = 'none';
  if (document.documentElement.requestFullscreen) document.documentElement.requestFullscreen();
}

function beep() {
  if (!audioCtx) return;
  [[880, 0], [660, 0.35]].forEach(([freq, atraso]) => {
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.frequency.value = freq;
    osc.connect(gain); gain.connect(audioCtx.destination);
    const t = audioCtx.currentTime + atraso;
    gain.gain.setValueAtTime(0.0001, t);
    gain.gain.exponentialRampToValueAtTime(0.4, t + 0.05);
    gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.3);
    osc.start(t); osc.stop(t + 0.32);
  });
}

function falar(s) {
  if (!audioCtx || !('speechSynthesis' in window)) return;
  const letra = s.codigo.charAt(0);
  const num = parseInt(s.codigo.slice(1), 10);
  const u = new SpeechSynthesisUtterance('Senha ' + letra + ' ' + num + ', balcão ' + s.balcao);
  u.lang = 'pt-PT';
  setTimeout(() => speechSynthesis.speak(u), 800);
}

function render(ultimas) {
  const el = document.getElementById('lista');
  if (!ultimas.length) {
    document.getElementById('codigo').textContent = '—';
    document.getElementById('balcao').textContent = '';
    el.innerHTML = '';
    return;
  }
  document.getElementById('codigo').textContent = ultimas[0].codigo;
  document.getElementById('balcao').textContent = 'Balcão ' + ultimas[0].balcao;
  el.innerHTML = ultimas.slice(1).map(s =>
    '<div class="item"><span>' + s.codigo + '</span><span class="b">Balcão ' + s.balcao + '</span></div>'
  ).join('');
}

async function atualizar() {
  try {
    const r = await fetch('api_estado_atual.php', { cache: 'no-store' });
    const d = await r.json();
    render(d.ultimas);
    if (assinaturaAnterior !== null && d.assinatura !== assinaturaAnterior && d.ultimas.length) {
      const p = document.getElementById('principal');
      p.classList.remove('pisca'); void p.offsetWidth; p.classList.add('pisca');
      beep();
      falar(d.ultimas[0]);
    }
    assinaturaAnterior = d.assinatura;
  } catch (e) { /* ignora falhas pontuais */ }
}

function relogio() {
  const agora = new Date();
  document.getElementById('hora').textContent =
    agora.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
  document.getElementById('data').textContent =
    agora.toLocaleDateString('pt-PT', { weekday: 'long', day: 'numeric', month: 'long' });
}

setInterval(atualizar, 2000);
setInterval(relogio, 1000);
atualizar();
relogio();
</script>
</body>
</html>