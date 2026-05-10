<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="kiosk-token" content="<?= htmlspecialchars(getenv('KIOSK_API_TOKEN') ?: '') ?>">
<title>Weight Measurement</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<script src="../assets/app.js"></script>
<style>
/* Page-specific styles */
.display{text-align:center;padding:20px 10px;}

.scale-icon{font-size:52px;display:inline-block;animation:wobble 2s infinite;}
.scale-icon.paused{animation:none;}

.weight{font-size:52px;font-weight:700;color:var(--accent);font-family:var(--mono);margin-top:6px;}
.unit{font-size:13px;color:var(--text3);margin-bottom:4px;}
.status{margin-top:8px;font-size:13px;color:#555;min-height:20px;}
.stepBox{margin-top:4px;font-size:12px;color:var(--text3);min-height:18px;}

.progress{width:100%;height:8px;background:#e0e7ef;border-radius:10px;overflow:hidden;margin-top:14px;margin-bottom:14px;}
.bar{width:0%;height:100%;background:var(--accent);transition:width 0.6s ease;}
</style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="main">
<div class="card">
  <div class="dot" id="dot"></div>

  <div class="step-progress-top" id="stepProgressTop">
    <div class="step-bar-top" id="stepBar"></div>
  </div>

  <!-- STEPS -->
  <div id="stepsWrapper" class="fade-section visible">

    <div class="step active">
      <span class="step-icon">⚖️</span>
      <h3>Weight Measurement</h3>
      <p>This test measures your body weight accurately using a digital scale. Please follow all steps carefully before starting.</p>
      <button class="btn-next" onclick="nextStep(25)">Next →</button>
    </div>

    <div class="step">
      <span class="step-icon">👟</span>
      <h3>Step 1 — Step Off the Scale</h3>
      <p>After pressing Start, step off the scale completely and wait for it to calibrate.</p>
      <div class="instruction-box">
        <ul>
          <li>Do not stand on the scale yet</li>
          <li>Remove heavy items (bag, jacket)</li>
          <li>Wait for the beep or signal before stepping on</li>
        </ul>
      </div>
      <button class="btn-next" onclick="nextStep(50)">Next →</button>
    </div>

    <div class="step">
      <span class="step-icon">⏱️</span>
      <h3>Step 2 — Wait 8 Seconds</h3>
      <p>The system will count down 8 seconds while the scale resets. Step on only after the countdown ends.</p>
      <div class="instruction-box">
        <ul>
          <li>Watch the on-screen countdown</li>
          <li>Do not touch the scale during calibration</li>
          <li>Step on immediately when prompted</li>
        </ul>
      </div>
      <button class="btn-next" onclick="nextStep(75)">Next →</button>
    </div>

    <div class="step">
      <span class="step-icon">🧍</span>
      <h3>Step 3 — Stand Upright</h3>
      <p>Once on the scale, distribute your weight evenly and look straight ahead. Stay completely still.</p>
      <div class="instruction-box">
        <ul>
          <li>Stand in the center of the scale</li>
          <li>Keep both feet flat and still</li>
          <li>Look straight — do not look down</li>
          <li>Arms relaxed at your sides</li>
        </ul>
      </div>
      <button class="btn-next" onclick="nextStep(100)">Next →</button>
    </div>

    <div class="step">
      <span class="step-icon">✅</span>
      <h3>Step 4 — Ready to Measure</h3>
      <p>The system will automatically read your weight. Stay still until the reading is complete and shown on screen.</p>
      <button class="btn-start" onclick="startApp()">Start Measurement</button>
    </div>

  </div>

  <!-- WEIGHT UI -->
  <div id="mainUI" style="display:none;" class="fade-section hidden">
    <div class="display">
      <div class="scale-icon" id="scaleIcon">⚖️</div>
      <div class="weight" id="weight">--.-</div>
      <div class="unit">kg</div>
      <div class="status" id="statusText">Connecting...</div>
      <div class="stepBox" id="stepBox"></div>
      <div class="progress">
        <div class="bar" id="bar"></div>
      </div>
      <!-- START button — shown after instructions, sends action on click -->
      <button class="btn-start" id="startBtn" onclick="startMeasure()" style="margin-top:10px;" disabled>▶ Start</button>
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
      <p id="errorMessage" style="color:#555;font-size:13px;margin-bottom:16px;">Unable to connect to weight sensor.</p>
      <button class="btn-start" onclick="retryConnection()">🔄 Retry Connection</button>
      <button class="btn-back" onclick="skipStep()" style="margin-top:8px;">Skip for Testing</button>
      <button class="btn-back" onclick="goBack()" style="margin-top:8px;">← Go Back</button>
    </div>
  </div>

</div>
</div>

<div class="success-overlay" id="success">
  <div class="success-box">
    <div class="check"></div>
    <p>Saved Successfully!</p>
    <small>Redirecting to home...</small>
  </div>
</div>

<script>
/* STEPS */
let stepIndex=0;
const steps=document.querySelectorAll(".step");
function nextStep(percent){
  steps[stepIndex].classList.remove("active");
  stepIndex++;
  if(stepIndex<steps.length) steps[stepIndex].classList.add("active");
  document.getElementById("stepBar").style.width=percent+"%";
  document.getElementById("headerStep").textContent="Step "+(stepIndex+1)+" of 5";
}

/* START APP — transition to measure screen and connect WS */
function startApp(){
  const wrapper=document.getElementById("stepsWrapper");
  const mainUI=document.getElementById("mainUI");
  wrapper.classList.remove("visible");wrapper.classList.add("hidden");
  setTimeout(()=>{
    wrapper.style.display="none";
    document.getElementById("stepProgressTop").style.display="none";
    document.getElementById("headerStep").textContent="Measuring Mode";
    document.getElementById("dot").style.display="block";
    mainUI.style.display="block";
    requestAnimationFrame(()=>requestAnimationFrame(()=>{
      mainUI.classList.remove("hidden");mainUI.classList.add("visible");
    }));
    connect();
  },500);
}

/* SKIP STEP (for testing) */
function skipStep(){
  window.location.href = "../home.php";
}

/* GO BACK */
function goBack(){
  window.location.href = "../home.php";
}

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

/* WEBSOCKET — connects on screen load, Start button enabled when ready */
let ws;
let connectionAttempts = 0;
const MAX_CONNECTION_ATTEMPTS = 3;

function connect(){
  connectionAttempts++;
  
  ws = new WebSocket(`ws://${location.hostname}:8765`);

  ws.onopen = () => {
    connectionAttempts = 0;
    document.getElementById("dot").style.background = "#16a34a";
    document.getElementById("statusText").textContent = "Sensor Connected — Press Start";
    document.getElementById("startBtn").disabled = false;
  };

  ws.onerror = () => {
    if (connectionAttempts >= MAX_CONNECTION_ATTEMPTS) {
      showErrorScreen("Unable to connect to sensor after " + MAX_CONNECTION_ATTEMPTS + " attempts. Please check hardware connection.");
    }
  };

  ws.onclose = () => {
    document.getElementById("dot").style.background = "#dc2626";
    
    if (connectionAttempts >= MAX_CONNECTION_ATTEMPTS) {
      showErrorScreen("Sensor connection lost. Please check hardware and try again.");
    } else {
      document.getElementById("statusText").textContent = "Reconnecting...";
      document.getElementById("startBtn").disabled = true;
      setTimeout(connect, 2000);
    }
  };

  ws.onmessage = (e) => {
    const data = JSON.parse(e.data);

    // ── ERROR from hardware ──
    if (data.error) {
      showErrorScreen(data.status || "Hardware sensor error occurred.");
      return;
    }

    if(data.status){
      document.getElementById("statusText").textContent = data.status;
      document.getElementById("stepBox").textContent = data.status;

      const s = data.status.toLowerCase();
      if(s.includes("taring"))                                    document.getElementById("bar").style.width = "30%";
      else if(s.includes("step on") || s.includes("ready"))      document.getElementById("bar").style.width = "55%";
      else if(s.includes("stability") || s.includes("stabiliz")) document.getElementById("bar").style.width = "75%";
      else if(s.includes("done"))                                 document.getElementById("bar").style.width = "100%";
      
      if(s.includes("error")){
        showErrorScreen(data.status);
      }
    }

    // ✅ Weight result — display immediately
    if(data.weight !== undefined){
      document.getElementById("weight").textContent = data.weight;
      document.getElementById("bar").style.width = "100%";
      document.getElementById("statusText").textContent = "Done ✓";
      document.getElementById("stepBox").textContent = "";
      document.getElementById("scaleIcon").classList.add("paused");
      document.getElementById("startBtn").style.display = "none";
      document.getElementById("doneBtn").style.display = "block";
      document.getElementById("skipBtn").style.display = "none";
    }
  };
}

/* START MEASURE — user presses Start → send action, disable button */
function startMeasure(){
  const btn = document.getElementById("startBtn");
  btn.disabled = true;
  btn.textContent = "Measuring...";
  document.getElementById("bar").style.width = "15%";
  document.getElementById("statusText").textContent = "Starting...";
  ws.send(JSON.stringify({ action: "start_weight" }));
}

/* SAVE */
async function saveDone(){
  const weight = document.getElementById("weight").textContent;
  const pid = getSession("patient_id");
  const rid = getSession("record_id");

  // 1. Save to sessionStorage immediately
  setSession("weight", weight);

  // 2. Try to save to DB
  const errorBanner = document.getElementById("errorBanner");
  const retryBtn = document.getElementById("retryBtn");
  
  try {
    const json = await apiFetch("../api/save_vitals.php", {
      patient_id: parseInt(pid),
      record_id: parseInt(rid),
      weight_kg: parseFloat(weight)
    });

    if (!json.success) throw new Error(json.error || "Save failed");
    if (json.record_id) setSession("record_id", json.record_id);

    // 3. Success — show overlay and redirect
    document.getElementById("success").style.display = "flex";
    setTimeout(()=>{ window.location.href = "../home.php"; }, 1800);

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