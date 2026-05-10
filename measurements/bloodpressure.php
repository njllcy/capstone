<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="kiosk-token" content="<?= htmlspecialchars(getenv('KIOSK_API_TOKEN') ?: '') ?>">
<title>Blood Pressure</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<script src="../assets/app.js"></script>
<style>
/* Page-specific styles */
.display{text-align:center;padding:20px 10px;}

.bp-icon{font-size:52px;display:inline-block;animation:pulse 1.5s infinite;}
.bp-icon.paused{animation:none;}

.bp{font-size:46px;font-weight:700;color:var(--accent);font-family:var(--mono);margin-top:6px;}
.pulse-row{font-size:13px;color:var(--text3);margin-top:4px;font-family:var(--mono);}
.unit{font-size:12px;color:var(--text3);margin-bottom:4px;}
.status{margin-top:8px;font-size:13px;color:#555;min-height:20px;}

/* BP LABELS */
.bp-labels{display:flex;justify-content:center;gap:20px;margin-top:10px;}
.bp-label-box{text-align:center;}
.bp-label-val{font-size:28px;font-weight:700;color:var(--accent);font-family:var(--mono);}
.bp-label-name{font-size:10px;color:var(--text3);text-transform:uppercase;}

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
      <span class="step-icon">🩺</span>
      <h3>Blood Pressure Measurement</h3>
      <p>This test measures your systolic and diastolic blood pressure along with your pulse. Please follow all instructions for an accurate reading.</p>
      <button class="btn-next" onclick="nextStep(20)">Next →</button>
    </div>

    <div class="step">
      <span class="step-icon">🛑</span>
      <h3>Step 1 — Before You Begin</h3>
      <p>Prepare yourself for the best results:</p>
      <div class="instruction-box">
        <ul>
          <li>Rest quietly for at least 5 minutes</li>
          <li>Avoid caffeine or smoking 30 min before</li>
          <li>Empty your bladder if needed</li>
          <li>Do not talk during the measurement</li>
        </ul>
      </div>
      <button class="btn-next" onclick="nextStep(40)">Next →</button>
    </div>

    <div class="step">
      <span class="step-icon">💺</span>
      <h3>Step 2 — Sit Properly</h3>
      <p>Sit in an upright position with your back supported and feet flat on the floor.</p>
      <div class="instruction-box">
        <ul>
          <li>Do not cross your legs</li>
          <li>Sit still — do not move during the test</li>
          <li>Keep your arm at heart level</li>
        </ul>
      </div>
      <button class="btn-next" onclick="nextStep(60)">Next →</button>
    </div>

    <div class="step">
      <span class="step-icon">💪</span>
      <h3>Step 3 — Wear the Cuff</h3>
      <p>Place the blood pressure cuff on your <strong>left upper arm</strong>.</p>
      <div class="instruction-box">
        <ul>
          <li>Cuff should be 2–3 cm above the elbow</li>
          <li>Snug but not too tight (1 finger gap)</li>
          <li>Tube should run along the inner arm</li>
          <li>Roll up sleeve to bare your arm</li>
        </ul>
      </div>
      <button class="btn-next" onclick="nextStep(80)">Next →</button>
    </div>

    <div class="step">
      <span class="step-icon">✅</span>
      <h3>Step 4 — Ready</h3>
      <p>Stay completely still, breathe normally, and keep your arm relaxed. The cuff will inflate and deflate automatically.</p>
      <button class="btn-start" onclick="startApp()">Start Measurement</button>
    </div>

  </div>

  <!-- MAIN UI -->
  <div id="mainUI" style="display:none;" class="fade-section hidden">
    <div class="display">
      <div class="bp-icon" id="bpIcon">🩺</div>

      <div class="bp-labels">
        <div class="bp-label-box">
          <div class="bp-label-val" id="systolic">--</div>
          <div class="bp-label-name">Systolic</div>
        </div>
        <div style="font-size:32px;font-weight:300;color:var(--text3);padding-top:4px;">/</div>
        <div class="bp-label-box">
          <div class="bp-label-val" id="diastolic">--</div>
          <div class="bp-label-name">Diastolic</div>
        </div>
      </div>
      <div class="unit">mmHg</div>
      <div class="pulse-row" id="pulseRow">Pulse: -- bpm</div>
      <div class="status" id="statusText">Ready</div>
      <div class="progress">
        <div class="bar" id="bar"></div>
      </div>
      <button class="btn-start" id="btn" onclick="startMeasure()" style="margin-top:10px;">Start Measurement</button>
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

/* START APP */
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

/* WEBSOCKET */
let ws;
let connectionAttempts = 0;
const MAX_CONNECTION_ATTEMPTS = 3;

function connect(){
  connectionAttempts++;
  ws=new WebSocket(`ws://${location.hostname}:8765`);
  ws.onopen=()=>{
    connectionAttempts = 0;
    document.getElementById("dot").style.background="#16a34a";
    document.getElementById("statusText").textContent="Sensor Connected ✓";
  };
  ws.onerror=()=>{
    if(connectionAttempts >= MAX_CONNECTION_ATTEMPTS){
      showErrorScreen("Failed to connect to sensor after " + MAX_CONNECTION_ATTEMPTS + " attempts.");
    }
  };
  ws.onclose=()=>{
    document.getElementById("dot").style.background="#dc2626";
    if(connectionAttempts >= MAX_CONNECTION_ATTEMPTS){
      showErrorScreen("Connection to sensor lost. Please check hardware.");
    } else {
      document.getElementById("statusText").textContent="Reconnecting...";
      setTimeout(connect,2000);
    }
  };
  ws.onmessage=(e)=>{
    const data=JSON.parse(e.data);
    if(data.error) { 
      showErrorScreen(data.status || "Hardware sensor error occurred."); 
      return; 
    }
    if(data.systolic) document.getElementById("systolic").textContent=data.systolic;
    if(data.diastolic) document.getElementById("diastolic").textContent=data.diastolic;
    if(data.pulse) document.getElementById("pulseRow").textContent="Pulse: "+data.pulse+" bpm";
    if(data.status==="done") onDone();
  };
}

/* START MEASURE */
function startMeasure(){
  document.getElementById("btn").disabled=true;
  document.getElementById("statusText").textContent="Cuff inflating... please stay still.";
  document.getElementById("bar").style.width="30%";
  document.getElementById("bpIcon").classList.remove("paused");
  ws.send(JSON.stringify({action:"start_bp"}));

  // Animate bar during measurement
  setTimeout(()=>{ document.getElementById("bar").style.width="60%"; document.getElementById("statusText").textContent="Measuring..."; },3000);
}

/* ON DONE */
function onDone(){
  document.getElementById("statusText").textContent="Measurement Complete ✓";
  document.getElementById("bpIcon").classList.add("paused");
  document.getElementById("bar").style.width="100%";
  document.getElementById("doneBtn").style.display="block";
  document.getElementById("skipBtn").style.display="none";
}

/* SAVE */
async function saveDone(){
  const systolic  = document.getElementById("systolic").textContent;
  const diastolic = document.getElementById("diastolic").textContent;
  const pulse     = document.getElementById("pulseRow").textContent.replace(/[^0-9]/g,"");
  const pid = getSession("patient_id");
  const rid = getSession("record_id");

  // 1. Save to sessionStorage immediately
  setSession("systolic",  systolic);
  setSession("diastolic", diastolic);
  setSession("pulse",     pulse);

  // 2. Try to save to DB
  const errorBanner = document.getElementById("errorBanner");
  const retryBtn = document.getElementById("retryBtn");
  
  try {
    const json = await apiFetch("../api/save_vitals.php", {
      patient_id: parseInt(pid),
      record_id: parseInt(rid),
      systolic_bp: parseInt(systolic),
      diastolic_bp: parseInt(diastolic),
      pulse_bpm: parseInt(pulse)
    });

    if (!json.success) throw new Error(json.error || "Save failed");
    if (json.record_id) setSession("record_id", json.record_id);

    // 3. Success — show overlay and redirect
    document.getElementById("success").style.display="flex";
    setTimeout(()=>{window.location.href="../home.php";},1800);

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