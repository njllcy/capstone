<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="kiosk-token" content="<?= htmlspecialchars(getenv('KIOSK_API_TOKEN') ?: '') ?>">
<title>Oximeter Measurement</title>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<script src="../assets/app.js"></script>
<style>
/* Page-specific styles */

/* MAIN UI */
.display{
  text-align:center;
  padding:20px 10px;
}

.heart{
  font-size:56px;
  display:inline-block;
  animation:beat 1s infinite;
}

.heart.paused{animation:none;}

@keyframes beat{
  0%{transform:scale(1);}
  50%{transform:scale(1.2);}
  100%{transform:scale(1);}
}

.spo2{
  font-size:52px;
  font-weight:700;
  color:var(--accent);
  font-family:var(--mono);
  margin-top:6px;
}

.unit{font-size:13px;color:var(--text3);margin-bottom:4px;}

.timer-block{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  margin-top:8px;
}

.timer{
  font-family:var(--mono);
  font-size:28px;
  font-weight:700;
  color:var(--text);
}

.timer-label{
  font-size:11px;
  color:var(--text3);
}

.status{
  margin-top:8px;
  font-size:13px;
  color:#555;
  min-height:20px;
}

/* CIRCULAR PROGRESS */
.circle-wrap{
  position:relative;
  width:120px;
  height:120px;
  margin:16px auto 10px;
}

.circle-wrap svg{
  transform:rotate(-90deg);
}

.circle-bg{fill:none;stroke:#e0e7ef;stroke-width:8;}
.circle-fill{
  fill:none;
  stroke:var(--accent);
  stroke-width:8;
  stroke-linecap:round;
  stroke-dasharray:314;
  stroke-dashoffset:314;
  transition:stroke-dashoffset 0.8s ease;
}

.circle-inner{
  position:absolute;
  top:50%;left:50%;
  transform:translate(-50%,-50%);
  text-align:center;
}

.circle-pct{
  font-family:var(--mono);
  font-size:18px;
  font-weight:700;
  color:var(--accent);
}

.circle-sec{
  font-size:10px;
  color:var(--text3);
}

/* LINEAR BAR */
.progress{
  width:100%;
  height:8px;
  background:#e0e7ef;
  border-radius:10px;
  overflow:hidden;
  margin-top:14px;
  margin-bottom:14px;
}

.bar{
  width:0%;
  height:100%;
  background:var(--accent);
  transition:width 0.8s ease;
}
</style>
</head>

<body>

<?php include '../includes/header.php'; ?>

<div class="main">
<div class="card">

  <div class="dot" id="dot"></div>

  <!-- TOP STEP PROGRESS BAR -->
  <div class="step-progress-top" id="stepProgressTop">
    <div class="step-bar-top" id="stepBar"></div>
  </div>

  <!-- HOW-TO STEPS -->
  <div id="stepsWrapper" class="fade-section visible">

    <!-- Step 0: Intro -->
    <div class="step active">
      <span class="step-icon">🫀</span>
      <h3>SpO₂ Oximeter Measurement</h3>
      <p>This test measures your blood oxygen saturation level (SpO₂) over 30 seconds. Please read the instructions carefully before starting.</p>
      <button class="btn-next" onclick="nextStep(25)">Next →</button>
    </div>

    <!-- Step 1 -->
    <div class="step">
      <span class="step-icon">💡</span>
      <h3>Step 1 — Prepare</h3>
      <p>Before attaching the oximeter, make sure your hand is:</p>
      <div class="instruction-box">
        <ul>
          <li>Clean and dry</li>
          <li>Warm (not cold)</li>
          <li>Nail polish removed if any</li>
          <li>Resting comfortably on a flat surface</li>
        </ul>
      </div>
      <button class="btn-next" onclick="nextStep(50)">Next →</button>
    </div>

    <!-- Step 2 -->
    <div class="step">
      <span class="step-icon">🖐️</span>
      <h3>Step 2 — Attach</h3>
      <p>Clip the oximeter onto your <strong>index finger</strong> or middle finger. Make sure it fits snugly but not too tight.</p>
      <div class="instruction-box">
        <ul>
          <li>Finger must be fully inserted</li>
          <li>Sensor should face the fingernail side</li>
          <li>Do not move your hand during the test</li>
        </ul>
      </div>
      <button class="btn-next" onclick="nextStep(75)">Next →</button>
    </div>

    <!-- Step 3: Ready -->
    <div class="step">
      <span class="step-icon">✅</span>
      <h3>Step 3 — Ready to Measure</h3>
      <p>Stay still and breathe normally. The system will take a 30-second reading and display your SpO₂ percentage.</p>
      <button class="btn-start" onclick="startApp()">Start Measurement</button>
    </div>

  </div>

  <!-- MAIN MEASUREMENT UI -->
  <div id="mainUI" style="display:none;" class="fade-section hidden">
    <div class="display">

      <div class="heart" id="heartIcon">❤️</div>

      <div class="spo2" id="spo2">--</div>
      <div class="unit">% SpO₂</div>

      <!-- CIRCULAR PROGRESS -->
      <div class="circle-wrap">
        <svg width="120" height="120" viewBox="0 0 120 120">
          <circle class="circle-bg" cx="60" cy="60" r="50"/>
          <circle class="circle-fill" id="circleFill" cx="60" cy="60" r="50"/>
        </svg>
        <div class="circle-inner">
          <div class="circle-pct" id="circlePercent">0%</div>
          <div class="circle-sec" id="circleSec">elapsed</div>
        </div>
      </div>

      <!-- TIMER -->
      <div class="timer-block">
        <div class="timer" id="timer">30</div>
        <div class="timer-label">sec<br>left</div>
      </div>

      <!-- LINEAR BAR -->
      <div class="progress">
        <div class="bar" id="bar"></div>
      </div>

      <div class="status" id="statusText">Ready</div>

      <button class="btn-start" id="btn" onclick="startMeasure()" style="margin-top:10px;">Start 30s Test</button>
      <button class="btn-done" id="doneBtn" style="display:none;margin-top:8px;" onclick="saveDone()">Done ✓</button>
      <button class="btn-back" id="skipBtn" onclick="skipStep()" style="margin-top:8px;">Skip for Testing</button>

      <!-- Error banner and retry button -->
      <div id="errorBanner" style="display:none; background:#fee2e2; border:1px solid #fca5a5;
           border-radius:8px; padding:10px 14px; font-size:13px; color:#dc2626; margin-top:10px;">
      </div>
      <button id="retryBtn" style="display:none;" class="btn-start" onclick="saveDone()">
        ↺ Retry Save
      </button>

    </div>
  </div>

  <!-- Error Screen -->
  <div id="errorScreen" style="display:none;" class="fade-section hidden">
    <div class="display">
      <div style="font-size:52px;margin-bottom:12px;">⚠️</div>
      <h3 style="color:#dc2626;margin-bottom:8px;">Sensor Error</h3>
      <p id="errorMessage" style="color:#555;font-size:13px;margin-bottom:16px;">Unable to connect to sensor.</p>
      <button class="btn-start" onclick="retryConnection()">🔄 Retry Connection</button>
      <button class="btn-back" onclick="skipStep()" style="margin-top:8px;">Skip for Testing</button>
      <button class="btn-back" onclick="goBack()" style="margin-top:8px;">← Go Back</button>
    </div>
  </div>

</div>
</div>

<!-- SUCCESS OVERLAY -->
<div class="success-overlay" id="success">
  <div class="success-box">
    <div class="check"></div>
    <p>Saved Successfully!</p>
    <small>Redirecting to home...</small>
  </div>
</div>

<script>

/* SKIP STEP (for testing) */
function skipStep(){ window.location.href = "../home.php"; }

/* GO BACK */
function goBack(){ window.location.href = "../home.php"; }

/* RETRY CONNECTION */
function retryConnection(){
  document.getElementById("errorScreen").style.display = "none";
  document.getElementById("mainUI").style.display = "block";
  connect();
}

/* SHOW ERROR SCREEN */
function showErrorScreen(message){
  document.getElementById("mainUI").style.display = "none";
  document.getElementById("errorScreen").style.display = "block";
  document.getElementById("errorMessage").textContent = message;
}

/* ─── STEPS ─── */
let stepIndex = 0;
const steps = document.querySelectorAll(".step");

function nextStep(percent){
  steps[stepIndex].classList.remove("active");
  stepIndex++;
  if(stepIndex < steps.length){
    steps[stepIndex].classList.add("active");
  }
  document.getElementById("stepBar").style.width = percent + "%";
  document.getElementById("headerStep").textContent = "Step " + (stepIndex + 1) + " of 4";
}

/* ─── START APP (fade transition) ─── */
function startApp(){
  const wrapper = document.getElementById("stepsWrapper");
  const mainUI = document.getElementById("mainUI");
  const progressTop = document.getElementById("stepProgressTop");

  wrapper.classList.remove("visible");
  wrapper.classList.add("hidden");

  setTimeout(()=>{
    wrapper.style.display = "none";
    progressTop.style.display = "none";
    document.getElementById("headerStep").textContent = "Measuring Mode";
    document.getElementById("dot").style.display = "block";

    mainUI.style.display = "block";
    requestAnimationFrame(()=>{
      requestAnimationFrame(()=>{
        mainUI.classList.remove("hidden");
        mainUI.classList.add("visible");
      });
    });

    connect();
  }, 500);
}

/* ─── WEBSOCKET ─── */
let ws;
let connectionAttempts = 0;
const MAX_CONNECTION_ATTEMPTS = 3;

function connect(){
  connectionAttempts++;
  ws = new WebSocket(`ws://${location.hostname}:8765`);

  ws.onopen = ()=>{
    connectionAttempts = 0;
    document.getElementById("dot").style.background = "#16a34a";
    document.getElementById("statusText").textContent = "Sensor Connected ✓";
  };

  ws.onerror = ()=>{
    if(connectionAttempts >= MAX_CONNECTION_ATTEMPTS){
      showErrorScreen("Failed to connect to sensor after " + MAX_CONNECTION_ATTEMPTS + " attempts.");
    }
  };

  ws.onclose = ()=>{
    document.getElementById("dot").style.background = "#dc2626";
    if(connectionAttempts >= MAX_CONNECTION_ATTEMPTS){
      showErrorScreen("Connection to sensor lost. Please check hardware.");
    } else {
      document.getElementById("statusText").textContent = "Reconnecting...";
      setTimeout(connect, 2000);
    }
  };

  ws.onmessage = (e)=>{
    const data = JSON.parse(e.data);

    if(data.error) { 
      showErrorScreen(data.status || "Hardware sensor error occurred."); 
      return; 
    }

    if(data.spo2 !== undefined){
      document.getElementById("spo2").textContent = data.spo2;
    }

    if(data.timer !== undefined){
      const t = data.timer;
      const elapsed = 30 - t;
      const pct = Math.round((elapsed / 30) * 100);

      document.getElementById("timer").textContent = t;
      document.getElementById("bar").style.width = pct + "%";

      // circular
      const circumference = 314;
      const offset = circumference - (pct / 100) * circumference;
      document.getElementById("circleFill").style.strokeDashoffset = offset;
      document.getElementById("circlePercent").textContent = pct + "%";
      document.getElementById("circleSec").textContent = elapsed + "s elapsed";
    }

    if(data.status === "done"){
      onDone();
    }
  };
}

/* ─── START MEASURE ─── */
function startMeasure(){
  document.getElementById("btn").disabled = true;
  document.getElementById("statusText").textContent = "Measuring... please stay still.";
  document.getElementById("heartIcon").classList.remove("paused");

  ws.send(JSON.stringify({ action: "start_oximeter" }));
}

/* ─── ON DONE ─── */
function onDone(){
  document.getElementById("statusText").textContent = "Measurement Complete ✓";
  document.getElementById("heartIcon").classList.add("paused");

  // fill bar fully
  document.getElementById("bar").style.width = "100%";
  document.getElementById("circleFill").style.strokeDashoffset = 0;
  document.getElementById("circlePercent").textContent = "100%";
  document.getElementById("timer").textContent = "0";

  document.getElementById("doneBtn").style.display = "block";
  document.getElementById("skipBtn").style.display = "none";
}

/* ─── SAVE / DONE ─── */
async function saveDone(){
  const spo2 = document.getElementById("spo2").textContent;
  const pid = getSession("patient_id");
  const rid = getSession("record_id");

  // 1. Save to sessionStorage immediately
  setSession("spo2", spo2);

  // 2. Try to save to DB
  const errorBanner = document.getElementById("errorBanner");
  const retryBtn = document.getElementById("retryBtn");
  
  try {
    const json = await apiFetch("../api/save_vitals.php", {
      patient_id: parseInt(pid),
      record_id: parseInt(rid),
      spo2_percent: parseFloat(spo2)
    });

    if (!json.success) throw new Error(json.error || "Save failed");
    if (json.record_id) setSession("record_id", json.record_id);

    // 3. Success — show overlay and redirect
    const overlay = document.getElementById("success");
    overlay.style.display = "flex";
    setTimeout(() => {
      window.location.href = "../home.php";
    }, 1800);

  } catch (e) {
    // 4. Failure — show error banner, do NOT redirect
    errorBanner.textContent = "⚠️ Could not save to database. " + e.message;
    errorBanner.style.display = "block";
    retryBtn.style.display = "block";
  }
}

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