<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>HealthKiosk — Patient Vital Signs</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet" />
  <style>
    :root {
      --bg: #f0f4f8;
      --surface: #ffffff;
      --surface2: #f7f9fc;
      --border: #dde3ec;
      --border2: #c8d2df;
      --accent: #2563eb;
      --accent-light: #eff4ff;
      --accent2: #0891b2;
      --accent2-light: #ecfeff;
      --success: #16a34a;
      --success-light: #f0fdf4;
      --warning: #d97706;
      --warning-light: #fffbeb;
      --danger: #dc2626;
      --text: #1e2d3d;
      --text2: #4a5f75;
      --text3: #8fa4b8;
      --radius: 12px;
      --radius-sm: 8px;
      --font: 'DM Sans', sans-serif;
      --mono: 'Space Mono', monospace;
      --header-h: 48px;
      --bar-h: 32px;
      --touch-min: 44px;
    }
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; -webkit-tap-highlight-color: transparent; }

    html, body {
      font-family: var(--font);
      background: var(--bg);
      color: var(--text);
      height: 100dvh; width: 100vw;
      overflow: hidden;
      display: flex; flex-direction: column;
      font-size: 14px;
      -webkit-text-size-adjust: 100%;
    }

    /* ── HEADER ── */
    .header {
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      padding: 0 12px;
      display: flex; align-items: center; justify-content: space-between;
      flex-shrink: 0; height: var(--header-h);
      gap: 8px;
    }
    .logo { display: flex; align-items: center; gap: 7px; }
    .logo-icon {
      width: 30px; height: 30px; background: var(--accent);
      border-radius: 8px; display: flex; align-items: center;
      justify-content: center; color: white; font-size: 15px; flex-shrink: 0;
    }
    .logo-name { font-size: 14px; font-weight: 700; color: var(--text); }

    .header-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

    .clock-block { display: flex; flex-direction: column; align-items: flex-end; gap: 1px; }
    .clock {
      font-family: var(--mono); font-size: 13px; font-weight: 700;
      color: var(--accent); background: var(--accent-light);
      padding: 3px 7px; border-radius: 6px; white-space: nowrap;
    }
    .clock-date { font-size: 9px; color: var(--text3); font-family: var(--mono); white-space: nowrap; }

    /* ── WS BADGE ── */
    .ws-badge {
      display: flex; align-items: center; gap: 4px;
      font-size: 9px; padding: 4px 8px; border-radius: 100px;
      font-weight: 700; font-family: var(--mono); white-space: nowrap;
      border: 1px solid transparent;
    }
    .ws-badge.ok   { background: var(--success-light); color: var(--success); border-color: #bbf7d0; }
    .ws-badge.err  { background: #fef2f2; color: var(--danger); border-color: #fecaca; }
    .ws-badge.conn { background: var(--accent-light); color: var(--accent); border-color: #bfdbfe; }
    .ws-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
    .ws-badge.ok .ws-dot   { background: var(--success); }
    .ws-badge.err .ws-dot  { background: var(--danger); }
    .ws-badge.conn .ws-dot { background: var(--accent); animation: sensorPulse 0.8s ease-in-out infinite; }
    @keyframes sensorPulse { 0%,100%{opacity:1} 50%{opacity:0.3} }

    /* ── MAIN ── */
    .main {
      flex: 1; display: flex; align-items: center; justify-content: center;
      padding: 10px 10px 6px;
      overflow: hidden;
    }

    /* ── START SCREEN ── */
    #screen-start {
      max-width: 100%; width: 100%;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      min-height: 100%; gap: 0;
    }
    .start-hero {
      display: flex; flex-direction: column; align-items: center;
      gap: 12px; text-align: center; margin-bottom: 28px;
    }
    .start-logo-big {
      width: 64px; height: 64px; background: var(--accent);
      border-radius: 16px; display: flex; align-items: center;
      justify-content: center; font-size: 30px;
      box-shadow: 0 6px 24px rgba(37,99,235,0.22);
    }
    .start-title    { font-size: 28px; font-weight: 700; letter-spacing: -0.8px; color: var(--text); }
    .start-subtitle { font-size: 13px; color: var(--text2); max-width: 280px; line-height: 1.6; }

    /* ── SWIPE BUTTON ── */
    .swipe-wrapper {
      position: relative; width: 280px; height: 56px;
      background: var(--accent-light); border: 1.5px solid #bfdbfe;
      border-radius: 100px; display: flex; align-items: center;
      overflow: hidden; user-select: none; touch-action: none;
    }
    .swipe-track-text {
      position: absolute; left: 0; right: 0; text-align: center;
      font-size: 13px; font-weight: 600; color: var(--accent);
      pointer-events: none; transition: opacity 0.2s;
    }
    .swipe-arrows {
      position: absolute; right: 16px; display: flex; gap: 3px;
      pointer-events: none;
    }
    .swipe-arrows span { font-size: 14px; color: var(--accent); }
    .swipe-handle {
      position: absolute; left: 4px; width: 48px; height: 48px;
      border-radius: 50%; background: var(--accent);
      display: flex; align-items: center; justify-content: center;
      color: white; font-size: 20px; cursor: grab;
      box-shadow: 0 3px 12px rgba(37,99,235,0.3); z-index: 2;
    }
    .swipe-handle.released { transition: left 0.3s ease; }
    .swipe-wrapper.complete .swipe-track-text { opacity: 0; }
    .swipe-wrapper.complete .swipe-arrows { display: none; }
    .swipe-wrapper.complete .swipe-handle {
      background: var(--success);
      box-shadow: 0 3px 12px rgba(22,163,74,0.35);
    }

    /* ── SENSOR BAR ── */
    .sensor-bar {
      background: var(--surface); border-top: 1px solid var(--border);
      padding: 0 12px; display: flex; align-items: center; gap: 8px;
      flex-shrink: 0; height: var(--bar-h); overflow: hidden;
    }
    .sensor-bar-label {
      font-size: 8px; text-transform: uppercase; letter-spacing: 1px;
      color: var(--text3); font-weight: 700; flex-shrink: 0;
    }
    .sensor-items { display: flex; align-items: center; gap: 10px; flex: 1; overflow: hidden; }
    .sensor-item  { display: flex; align-items: center; gap: 4px; font-size: 9px; color: var(--text2); white-space: nowrap; }
    .sensor-dot   { width: 5px; height: 5px; border-radius: 50%; background: var(--border2); flex-shrink: 0; transition: all 0.3s; }
    .sensor-dot.connected { background: var(--success); }
    .sensor-dot.active    { background: var(--accent); animation: sensorPulse 0.6s ease-in-out infinite; }
    .sensor-dot.error     { background: var(--danger); }

    /* ── TOAST ── */
    .db-save-toast {
      position: fixed; bottom: calc(var(--bar-h) + 10px); right: 12px;
      background: var(--success); color: white; padding: 7px 14px;
      border-radius: 8px; font-size: 11px; font-weight: 700;
      display: none; z-index: 999;
    }
    .db-save-toast.show { display: block; animation: fadein 0.3s ease; }
    @keyframes fadein { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:translateY(0)} }

    @media (max-width: 359px) {
      .start-title { font-size: 24px; }
      .swipe-wrapper { width: 250px; }
    }
    @media (max-height: 580px) and (orientation: landscape) {
      .start-logo-big { width: 48px; height: 48px; font-size: 22px; }
      .start-title { font-size: 22px; }
      .start-hero { gap: 8px; margin-bottom: 16px; }
    }
  </style>
</head>
<body>

<!-- HEADER -->
<header class="header">
  <div class="logo">
    <div class="logo-icon">🏥</div>
    <div class="logo-name">HealthKiosk</div>
  </div>
  <div class="header-right">
    <div class="clock-block">
      <div class="clock" id="clock">--:--</div>
      <div class="clock-date" id="clockDate">---</div>
    </div>
    <!-- WS Badge — fixed: id references now match JS -->
    <div class="ws-badge conn" id="wsBadge">
      <span class="ws-dot"></span>
      <span id="wsLabel">CONNECTING</span>
    </div>
  </div>
</header>

<!-- MAIN -->
<main class="main">
  <div id="screen-start">
    <div class="start-hero">
      <div class="start-logo-big">🏥</div>
      <div class="start-title">HealthKiosk</div>
      <div class="start-subtitle">Patient Vital Signs Station — Swipe to begin your health assessment.</div>
    </div>

    <!-- Swipe button -->
    <div class="swipe-wrapper" id="swipeWrapper">
      <div class="swipe-track-text" id="swipeText">Swipe to begin</div>
      <div class="swipe-arrows">
        <span>›</span><span>›</span><span>›</span>
      </div>
      <div class="swipe-handle" id="swipeHandle">›</div>
    </div>
  </div>
</main>

<!-- SENSOR BAR -->
<div class="sensor-bar">
  <span class="sensor-bar-label">Sensors</span>
  <div class="sensor-items">
    <div class="sensor-item">
      <span class="sensor-dot" id="bar-motion"></span>
      <span id="bar-motion-val">Motion</span>
    </div>
    <div class="sensor-item">
      <span class="sensor-dot" id="bar-temp"></span>
      <span id="bar-temp-val">Temp</span>
    </div>
    <div class="sensor-item">
      <span class="sensor-dot" id="bar-presence"></span>
      <span id="bar-presence-val">Presence</span>
    </div>
    <div class="sensor-item">
      <span class="sensor-dot" id="bar-ws"></span>
      <span id="bar-ws-val">WebSocket</span>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="db-save-toast" id="toast">✓ Connected</div>

<script>
/* ── CONFIG ── */
const WS_URL    = 'ws://localhost:8765';
const NEXT_PAGE = 'patient/scanid.php';   // ← Auto-redirect to ID scan (autonomous flow)

/* ── CLOCK ── */
const DAYS   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
function updateClock() {
  const n = new Date();
  const h = String(n.getHours()).padStart(2,'0');
  const m = String(n.getMinutes()).padStart(2,'0');
  document.getElementById('clock').textContent = `${h}:${m}`;
  document.getElementById('clockDate').textContent =
    `${DAYS[n.getDay()]} ${MONTHS[n.getMonth()]} ${n.getDate()}, ${n.getFullYear()}`;
}
updateClock();
setInterval(updateClock, 1000);

/* ── TOAST ── */
let toastTimer;
function showToast(msg) {
  clearTimeout(toastTimer);
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  toastTimer = setTimeout(() => t.classList.remove('show'), 2800);
}

/* ── NAVIGATE to home.php ── */
function goNext() {
  setTimeout(() => { window.location.href = NEXT_PAGE; }, 800);
}

/* ── SWIPE HANDLE ── */
(function () {
  const wrapper = document.getElementById('swipeWrapper');
  const handle  = document.getElementById('swipeHandle');
  const text    = document.getElementById('swipeText');

  const PAD = 4, THUMB = 48, THRESHOLD = 0.85;
  let dragging = false, startX = 0, done = false;

  function maxX() { return wrapper.offsetWidth - THUMB - PAD * 2; }

  function setPos(rawDx) {
    if (done) return;
    const x   = Math.max(0, Math.min(rawDx, maxX()));
    const pct = x / maxX();
    handle.style.left  = (PAD + x) + 'px';
    text.style.opacity = Math.max(0, 1 - pct * 2.5);
    if (pct >= THRESHOLD) complete();
  }

  function complete() {
    if (done) return;
    done = true;
    dragging = false;
    handle.style.left    = (PAD + maxX()) + 'px';
    handle.textContent   = '✓';
    handle.style.fontSize = '22px';
    wrapper.classList.add('complete');
    text.style.opacity = '1';
    text.textContent   = 'Loading…';
    text.style.color   = 'var(--success)';
    goNext();   // ← navigates to home.php
  }

  function reset() {
    if (done) return;
    dragging = false;
    handle.classList.add('released');
    handle.style.left  = PAD + 'px';
    text.style.opacity = '1';
    setTimeout(() => { handle.classList.remove('released'); }, 300);
  }

  /* Touch */
  handle.addEventListener('touchstart', e => {
    if (done) return;
    dragging = true; startX = e.touches[0].clientX;
    handle.style.transition = ''; e.preventDefault();
  }, { passive: false });
  window.addEventListener('touchmove', e => {
    if (!dragging) return;
    setPos(e.touches[0].clientX - startX);
  }, { passive: true });
  window.addEventListener('touchend', () => {
    if (!dragging) return;
    const cur = parseFloat(handle.style.left || PAD) - PAD;
    if (cur / maxX() < THRESHOLD) reset(); else complete();
    dragging = false;
  });

  /* Mouse */
  handle.addEventListener('mousedown', e => {
    if (done) return;
    dragging = true; startX = e.clientX;
    handle.style.transition = ''; e.preventDefault();
  });
  window.addEventListener('mousemove', e => {
    if (!dragging) return;
    setPos(e.clientX - startX);
  });
  window.addEventListener('mouseup', () => {
    if (!dragging) return;
    const cur = parseFloat(handle.style.left || PAD) - PAD;
    if (cur / maxX() < THRESHOLD) reset(); else complete();
    dragging = false;
  });

  /* Keyboard fallback */
  document.addEventListener('keydown', e => {
    if (['Enter', ' ', 'ArrowRight'].includes(e.key)) complete();
  });
})();

/* ── SENSOR UI HELPERS ── */
function setWsBadge(state) {
  /* state: 'ok' | 'err' | 'conn' */
  const badge = document.getElementById('wsBadge');  // now exists in HTML
  const label = document.getElementById('wsLabel');  // now exists in HTML
  badge.className = 'ws-badge ' + state;
  label.textContent = state === 'ok' ? 'ONLINE' : state === 'conn' ? 'CONNECTING' : 'OFFLINE';

  const barDot = document.getElementById('bar-ws');
  barDot.className = 'sensor-dot ' + (state === 'ok' ? 'connected' : state === 'conn' ? 'active' : 'error');
  document.getElementById('bar-ws-val').textContent = state === 'ok' ? 'WS: OK' : state === 'conn' ? 'WS: …' : 'WS: ERR';
}

function updateSensorBar(id, value, unit) {
  const barDot = document.getElementById('bar-' + id);
  const barVal = document.getElementById('bar-' + id + '-val');
  if (!barDot || !barVal) return;
  barDot.className = 'sensor-dot active';
  barVal.textContent = id.charAt(0).toUpperCase() + id.slice(1) + ': ' + value + unit;
  setTimeout(() => { barDot.className = 'sensor-dot connected'; }, 600);
}

/* ── WEBSOCKET ── */
let ws = null, reconnectDelay = 2000, reconnectTimer = null;

function handleMsg(raw) {
  try {
    const msg = JSON.parse(raw);
    if (msg.type === 'sensor' || msg.type === 'data') {
      if (msg.motion   !== undefined) updateSensorBar('motion',   msg.motion ? 'YES' : 'NO', '');
      if (msg.temp     !== undefined) updateSensorBar('temp',     parseFloat(msg.temp).toFixed(1), '°C');
      if (msg.presence !== undefined) updateSensorBar('presence', msg.presence ? 'YES' : 'NO', '');
    }
    if (msg.trigger || msg.type === 'trigger') {
      showToast('👤 Presence detected — opening…');
      goNext();
    }
  } catch { /* non-JSON */ }
}

function connectWS() {
  clearTimeout(reconnectTimer);
  setWsBadge('conn');
  try { ws = new WebSocket(WS_URL); } catch { setWsBadge('err'); scheduleReconnect(); return; }

  ws.addEventListener('open', () => {
    reconnectDelay = 2000;
    setWsBadge('ok');
    showToast('✓ Sensor connected');
    ws.send(JSON.stringify({ type: 'identify', role: 'kiosk-start' }));
  });
  ws.addEventListener('message', e => handleMsg(e.data));
  ws.addEventListener('close', () => { setWsBadge('err'); scheduleReconnect(); });
  ws.addEventListener('error', () => { setWsBadge('err'); ws.close(); });
}

function scheduleReconnect() {
  reconnectTimer = setTimeout(() => {
    reconnectDelay = Math.min(reconnectDelay * 1.5, 15000);
    connectWS();
  }, reconnectDelay);
}

connectWS();
document.addEventListener('visibilitychange', () => {
  if (!document.hidden && (!ws || ws.readyState > 1)) connectWS();
});
</script>
</body>
</html>