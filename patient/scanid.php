<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="kiosk-token" content="<?= htmlspecialchars(getenv('KIOSK_API_TOKEN') ?: '') ?>">
<title>Scan ID</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<script src="../assets/app.js"></script>
<style>
/* Page-specific styles */

/* SCAN AREA */
.scan-wrap{margin-bottom:12px;}
.scan{border:2px dashed #c8d2df;border-radius:10px;padding:25px;text-align:center;cursor:pointer;transition:border-color 0.3s,background 0.3s;}
.scan:hover{border-color:var(--accent);background:#f5f8ff;}
.scan.detected{border-color:var(--green);border-style:solid;background:#f0fdf4;}
.scan-icon-big{font-size:40px;margin-bottom:6px;}
.scan-text{font-weight:700;font-size:14px;color:var(--text);}
.scan-hint{font-size:11px;color:var(--text3);margin-top:4px;}

/* FORM */
.form{display:none;flex-direction:column;gap:8px;margin-top:12px;}
input,select{padding:9px 10px;border:1px solid #cfd8e3;border-radius:6px;font-size:13px;width:100%;outline:none;transition:border-color 0.2s;}
input:focus,select:focus{border-color:var(--accent);}
input[readonly]{background:#f5f7fa;color:var(--text3);}
input[disabled],select[disabled]{background:#f5f7fa;color:var(--text3);cursor:not-allowed;}
.field-label{font-size:11px;color:var(--text3);text-align:left;margin-bottom:2px;}
.field-group{display:flex;flex-direction:column;}
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
      <span class="step-icon">💳</span>
      <h3>Scan Your ID</h3>
      <p>This system reads your ID to automatically fill in your personal information. Please follow the steps for a smooth process.</p>
      <button class="btn-next" onclick="nextStep(25)">Next →</button>
    </div>

    <div class="step">
      <span class="step-icon">🪪</span>
      <h3>Step 1 — Prepare Your ID</h3>
      <p>Get your valid ID ready before scanning.</p>
      <div class="instruction-box">
        <ul>
          <li>Use a government-issued ID (PhilHealth, National ID.)</li>
          <li>Make sure the ID is clean and not damaged</li>
          <li>Remove any plastic covering if it causes glare</li>
          <li>Check that the ID is not expired</li>
        </ul>
      </div>
      <button class="btn-next" onclick="nextStep(50)">Next →</button>
    </div>

    <div class="step">
      <span class="step-icon">📍</span>
      <h3>Step 2 — Place Your ID</h3>
      <p>Place your ID face-up on the designated scanning area of the kiosk.</p>
      <div class="instruction-box">
        <ul>
          <li>Align the ID within the marked border</li>
          <li>Face of the ID should face upward</li>
          <li>Keep the ID flat — do not tilt or angle it</li>
          <li>Wait for the scan indicator to light up</li>
        </ul>
      </div>
      <button class="btn-next" onclick="nextStep(75)">Next →</button>
    </div>

    <div class="step">
      <span class="step-icon">✏️</span>
      <h3>Step 3 — Manual Input (Optional)</h3>
      <p>If scanning fails, you can manually enter your information using the form provided.</p>
      <div class="instruction-box">
        <ul>
          <li>Tap "Manually Input" button after scanning</li>
          <li>Fill in all required fields completely</li>
          <li>Double-check your name spelling</li>
          <li>Select the correct barangay from the list</li>
        </ul>
      </div>
      <button class="btn-next" onclick="nextStep(100)">Next →</button>
    </div>

    <div class="step">
      <span class="step-icon">✅</span>
      <h3>Step 4 — Confirm and Submit</h3>
      <p>Once all your details are filled in correctly, press <strong>Done</strong> to save your information and continue.</p>
      <button class="btn-start" onclick="startSystem()">Start</button>
    </div>

  </div>

  <!-- MAIN CONTENT -->
  <div id="mainContent" style="display:none;" class="fade-section hidden">

    <div class="scan-wrap">
      <div class="scan" id="scanArea" onclick="startScan()">
        <div class="scan-icon-big">💳</div>
        <div class="scan-text" id="scanText">Tap to Scan ID</div>
        <div class="scan-hint">Place your ID on the scanner below</div>
      </div>
    </div>

    <button class="btn-manual" onclick="toggleForm()">✏️ Manually Input</button>

    <div class="form" id="form">
      <div class="field-group">
        <div class="field-label">First Name</div>
        <input id="fname" placeholder="First Name" oninput="this.value=this.value.replace(/[^a-zA-Z\s]/g,'')">
      </div>
      <div class="field-group">
        <div class="field-label">Last Name</div>
        <input id="lname" placeholder="Last Name" oninput="this.value=this.value.replace(/[^a-zA-Z\s]/g,'')">
      </div>
      <div class="field-group">
        <div class="field-label">Province</div>
        <select disabled><option>Pangasinan</option></select>
      </div>
      <div class="field-group">
        <div class="field-label">Municipality</div>
        <select disabled><option>Pozorrubio</option></select>
      </div>
      <div class="field-group">
        <div class="field-label">Barangay</div>
        <select id="barangay">
          <option value="">-- Select Barangay --</option>
          <option>Batakil</option><option>Bantugan</option><option>Buneg</option>
          <option>Casanfernandoan</option><option>Castano</option><option>Dilan</option>
          <option>Haway</option><option>Imbalbalatong</option><option>Inoman</option>
          <option>Laoac</option><option>Maambal</option><option>Malasin</option>
          <option>Malokiat</option><option>Manaol</option><option>Nama</option>
          <option>Nantangalan</option><option>Palacpalac</option>
          <option>Poblacion District 1</option><option>Poblacion District 2</option>
          <option>Poblacion District 3</option><option>Poblacion District 4</option>
          <option>Rosario</option><option>Sugcong</option><option>Talogtog</option>
          <option>Tulnac</option><option>Villegas</option>
        </select>
      </div>
      <div class="field-group">
        <div class="field-label">Date of Birth</div>
        <input type="date" id="birth" onchange="calcAge()">
      </div>
      <div class="field-group">
        <div class="field-label">Age</div>
        <input id="age" readonly placeholder="Auto-calculated">
      </div>
      <div class="field-group">
        <div class="field-label">Phone Number</div>
        <input id="number" placeholder="09XXXXXXXXX" inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,11)">
      </div>
      <div class="field-group">
        <div class="field-label">Gender</div>
        <select id="gender"><option>Male</option><option>Female</option></select>
      </div>
      <button class="btn-done" id="doneBtn" onclick="done()" style="margin-top:4px;">Done ✓</button>
    </div>

  </div>
</div>
</div>

<!-- SAVING OVERLAY -->
<div class="saving-overlay" id="savingOverlay">
  <div class="saving-box">
    <div class="spinner"></div>
    <p>Saving patient data...</p>
    <small>Please wait</small>
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
/* STEPS */
let currentStep=0;
const steps=document.querySelectorAll(".step");
function nextStep(percent){
  steps[currentStep].classList.remove("active");
  currentStep++;
  steps[currentStep].classList.add("active");
  document.getElementById("stepBar").style.width=percent+"%";
  document.getElementById("headerStep").textContent="Step "+(currentStep+1)+" of 5";
}

/* START SYSTEM */
function startSystem(){
  const instr=document.getElementById("stepsWrapper");
  const main=document.getElementById("mainContent");
  instr.classList.remove("visible");instr.classList.add("hidden");
  setTimeout(()=>{
    instr.style.display="none";
    document.getElementById("stepProgressTop").style.display="none";
    document.getElementById("headerStep").textContent="Scan Mode";
    document.getElementById("dot").style.display="block";
    main.style.display="block";
    requestAnimationFrame(()=>requestAnimationFrame(()=>{
      main.classList.remove("hidden");main.classList.add("visible");
    }));
    connectSensor();
  },500);
}

/* SCAN */
let ws;
function startScan(){
  document.getElementById("scanText").textContent="Detecting ID...";
  document.getElementById("scanArea").style.borderColor="#2563eb";
  if(ws && ws.readyState===WebSocket.OPEN){
    ws.send(JSON.stringify({action:"scan_id"}));
  }
}

/* FORM TOGGLE */
function toggleForm(){
  const f=document.getElementById("form");
  f.style.display=(f.style.display==="flex")?"none":"flex";
}

/* AGE */
function calcAge(){
  const b=new Date(document.getElementById("birth").value);
  const t=new Date();
  let age=t.getFullYear()-b.getFullYear();
  if(t.getMonth()<b.getMonth()||(t.getMonth()===b.getMonth()&&t.getDate()<b.getDate())) age--;
  document.getElementById("age").value=age;
}

/* WEBSOCKET */
function connectSensor(){
  ws=new WebSocket(`ws://${location.hostname}:8765`);
  ws.onopen=()=>{ document.getElementById("dot").style.background="#16a34a"; };
  ws.onclose=()=>{ document.getElementById("dot").style.background="#dc2626"; setTimeout(connectSensor,2000); };
  ws.onmessage=(e)=>{
    try {
      const data=JSON.parse(e.data);
      if(data.type==="id_detected"){
        document.getElementById("scanText").textContent="ID Detected ✓";
        document.getElementById("scanArea").classList.add("detected");
        document.getElementById("form").style.display="flex";
        if(data.fname) document.getElementById("fname").value=data.fname;
        if(data.lname) document.getElementById("lname").value=data.lname;
      }
    } catch(err){}
  };
}

/* ── DONE — Validate → Save to DB → sessionStorage → Redirect to home.php ── */
async function done(){
  const fname    = document.getElementById("fname").value.trim();
  const lname    = document.getElementById("lname").value.trim();
  const barangay = document.getElementById("barangay").value;
  const birth    = document.getElementById("birth").value;
  const age      = document.getElementById("age").value;
  const number   = document.getElementById("number").value;
  const gender   = document.getElementById("gender").value;

  if(!fname || !lname || !barangay || !birth || !age || !number){
    alert("Please complete all required fields.");
    return;
  }
  if(number.length < 11){
    alert("Please enter a valid 11-digit phone number.");
    return;
  }

  // Disable button to prevent double submit
  const btn = document.getElementById("doneBtn");
  btn.disabled = true;

  // Show saving spinner
  document.getElementById("savingOverlay").style.display = "flex";

  // Always save to sessionStorage first (fallback)
  setSession("fname",     fname);
  setSession("lname",     lname);
  setSession("barangay",  barangay);
  setSession("birth",     birth);
  setSession("age",       age);
  setSession("number",    number);
  setSession("gender",    gender);

  // Save to database via api/save_patient.php
  try {
    const json = await apiFetch("../api/save_patient.php", {
      first_name:    fname,
      last_name:     lname,
      date_of_birth: birth,
      age:           parseInt(age),
      gender:        gender,
      phone:         number,
      barangay:      barangay
    });

    if(json.success){
      // Store IDs in sessionStorage so all subsequent pages can reference this visit
      setSession("patient_id", json.patient_id);
      setSession("record_id",  json.record_id);
    } else {
      console.warn("DB save failed:", json.error);
      // Continue anyway — sessionStorage personal data is the fallback
    }
  } catch(e) {
    console.warn("Could not reach ../api/save_patient.php:", e);
    // Continue anyway
  }

  // Hide spinner, show success
  document.getElementById("savingOverlay").style.display = "none";
  document.getElementById("success").style.display = "flex";

  // Redirect to home.php (one level up from /patient/)
  setTimeout(() => { window.location.href = "../home.php"; }, 1600);
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