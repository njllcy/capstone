<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="kiosk-token" content="<?= htmlspecialchars(getenv('KIOSK_API_TOKEN') ?: '') ?>">
<title>HealthKiosk Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
<script src="assets/app.js"></script>

<style>
/* Page-specific dashboard styles */
body{
  overflow:hidden;
}

/* DASHBOARD */
.dashboard{
  flex:1;
  padding:10px;
  display:grid;
  grid-template-columns:repeat(4, 1fr);
  gap:10px;
  overflow:hidden;
}

/* DASHBOARD CARD (overrides shared .card for dashboard layout) */
.card{
  text-align:center;
  cursor:pointer;
  transition:0.2s;
  border-left:5px solid var(--accent);
  max-width:none;
  box-shadow:0 6px 15px rgba(0,0,0,0.06);
  position:relative;
}

.card:hover{
  transform:translateY(-3px);
}

.icon{ font-size:24px; margin-bottom:6px; }
.title{ font-size:12px; font-weight:700; margin-bottom:4px; }
.desc{ font-size:10px; color:var(--text2); }

/* COLORS */
.id{border-left-color:#5dade2;}
.face{border-left-color:#48c9b0;}
.height{border-left-color:#af7ac5;}
.weight{border-left-color:#f5b041;}
.oxygen{border-left-color:#1abc9c;}
.temp{border-left-color:#e74c3c;}
.bp{border-left-color:#c0392b;}

/* STEP COMPLETION BADGES */
.card.done {
  border-left-color: #16a34a;
}

.card.done::after {
  content: '✓';
  position: absolute;
  top: 6px;
  right: 8px;
  color: #16a34a;
  font-weight: 700;
  font-size: 12px;
}

.card.disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

/* RESPONSIVE */
@media (max-width:480px){
  .dashboard{
    grid-template-columns:repeat(4, 1fr);
    gap:6px;
    padding:8px;
  }
  .card{ padding:8px; }
  .icon{ font-size:20px; }
  .title{ font-size:11px; }
  .desc{ font-size:9px; }
}

@media (max-width:360px){
  .dashboard{
    grid-template-columns:repeat(2, 1fr);
  }
}
</style>
</head>

<body>

<?php include 'includes/header.php'; ?>

<!-- DASHBOARD -->
<div class="dashboard">

  <div class="card id" id="card-id" onclick="go('patient/scanid.php')">
    <div class="icon">🪪</div>
    <div class="title">ID</div>
    <div class="desc">Scan ID</div>
  </div>

  <div class="card face" id="card-face" onclick="go('patient/facecapture.php')">
    <div class="icon">📸</div>
    <div class="title">Face</div>
    <div class="desc">Capture</div>
  </div>

  <div class="card height" id="card-height" onclick="go('measurements/height.php')">
    <div class="icon">📏</div>
    <div class="title">Height</div>
    <div class="desc">Measure</div>
  </div>

  <div class="card weight" id="card-weight" onclick="go('measurements/weight.php')">
    <div class="icon">⚖️</div>
    <div class="title">Weight</div>
    <div class="desc">Measure</div>
  </div>

  <div class="card oxygen" id="card-spo2" onclick="go('measurements/oximeter.php')">
    <div class="icon">🫁</div>
    <div class="title">SpO2</div>
    <div class="desc">Oxygen</div>
  </div>

  <div class="card temp" id="card-temp" onclick="go('measurements/temperature.php')">
    <div class="icon">🌡️</div>
    <div class="title">Temp</div>
    <div class="desc">Body</div>
  </div>

  <div class="card bp" id="card-bp" onclick="go('measurements/bloodpressure.php')">
    <div class="icon">❤️</div>
    <div class="title">BP</div>
    <div class="desc">Blood Pressure</div>
  </div>

  <div id="card-summary" onclick="go('results/summary.php')" style="display:flex; align-items:center; justify-content:space-between; padding:14px 16px; background:#1e2d3d; color:white; border-radius:12px; cursor:pointer;">
    <div>
      <div style="font-weight:700; font-size:14px;">Show Summary</div>
      <div style="font-size:10px; opacity:0.7;">Generate Summary Report</div>
    </div>
    <div style="font-size:20px;">🖨️</div>
  </div>

</div>

<script>
/* ROUTE FUNCTION */
function go(page){
  window.location.href = page;
}

/* STEP COMPLETION CHECK */
document.addEventListener('DOMContentLoaded', () => {
  const stepMap = {
    'card-id':     () => !!getSession('patient_id'),
    'card-face':   () => !!getSession('patient_id'),   // face saved server-side
    'card-height': () => !!getSession('height'),
    'card-weight': () => !!getSession('weight'),
    'card-spo2':   () => !!getSession('spo2'),
    'card-temp':   () => !!getSession('temp'),
    'card-bp':     () => !!getSession('systolic'),
  };

  let completedVitals = 0;

  Object.entries(stepMap).forEach(([cardId, isDone]) => {
    if (isDone()) {
      const card = document.getElementById(cardId);
      if (card) {
        card.classList.add('done');   // green border via CSS
        completedVitals++;
      }
    }
  });

  // Disable summary if fewer than 3 vitals recorded
  const summaryCard = document.getElementById('card-summary');
  if (completedVitals < 3 && summaryCard) {
    summaryCard.classList.add('disabled');
    summaryCard.title = 'Complete at least 3 measurements first';
    summaryCard.onclick = null;
  }
});

startIdleTimer();
</script>

<!-- Idle timeout overlay — controlled by app.js -->
<div id="idleOverlay" style="display:none; position:fixed; inset:0;
     background:rgba(0,0,0,0.75); z-index:9999;
     align-items:center; justify-content:center; flex-direction:column; gap:12px;">
  <div style="background:#fff; border-radius:14px; padding:32px 28px; text-align:center;">
    <div style="font-size:40px; margin-bottom:8px;">⏱️</div>
    <div style="font-size:18px; font-weight:700; color:#1e2d3d;">Session ending in</div>
    <div id="idleCounter" style="font-size:52px; font-weight:700;
         color:#2563eb; font-family:'Space Mono',monospace;">10</div>
    <div style="font-size:13px; color:#8fa4b8; margin-top:4px;">seconds</div>
    <button onclick="startIdleTimer()"
            style="margin-top:16px; padding:10px 24px; background:#2563eb;
                   color:#fff; border:none; border-radius:8px;
                   font-size:14px; font-weight:600; cursor:pointer;">
      I'm still here
    </button>
  </div>
</div>

</body>
</html>