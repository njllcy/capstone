<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="kiosk-token" content="<?= htmlspecialchars(getenv('KIOSK_API_TOKEN') ?: '') ?>">
<title>Face Capture</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<script src="../assets/app.js"></script>
<style>
/* Page-specific styles */
.scene{opacity:0;transform:translateY(8px);transition:opacity 0.4s ease,transform 0.4s ease;display:none;}
.scene.active{display:block;opacity:1;transform:translateY(0);}
.scene.out{opacity:0;transform:translateY(-8px);}

.cam-wrap{text-align:center;padding:10px 0;}
.video{width:200px;height:200px;object-fit:cover;border-radius:50%;border:3px solid var(--accent);margin:10px auto;display:block;background:#000;}
.preview{width:200px;height:200px;object-fit:cover;border-radius:50%;border:3px solid var(--green);margin:10px auto;display:none;}
.cam-status{margin-top:8px;font-size:13px;color:#555;min-height:20px;}
.progress{width:100%;height:8px;background:#e0e7ef;border-radius:10px;overflow:hidden;margin-top:12px;margin-bottom:14px;}
.bar{width:0%;height:100%;background:var(--accent);transition:width 0.5s ease;}

.saving-overlay{display:none;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:20px 0;}
.saving-overlay.show{display:flex;}
.saving-text{font-size:13px;color:var(--text3);}

.success-scene{text-align:center;padding:10px 0;}
.check-circle{width:76px;height:76px;border-radius:50%;background:#f0fdf4;border:2px solid #86efac;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:34px;}
.success-scene h2{font-size:18px;color:var(--green);margin-bottom:8px;}
.success-scene p{font-size:13px;color:#555;margin-bottom:20px;line-height:1.6;}

.error-msg{background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;padding:8px 12px;font-size:12px;color:var(--red);margin-bottom:10px;display:none;}

canvas{display:none;}
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

  <!-- HOW-TO STEPS -->
  <div id="stepsWrapper" class="fade-section visible">

    <div class="step active">
      <span class="step-icon">📸</span>
      <h3>Face Capture</h3>
      <p>This step captures your photo for identity verification and health record purposes. Please follow the instructions carefully.</p>
      <button class="btn-next" onclick="nextStep(25)">Next →</button>
    </div>

    <div class="step">
      <span class="step-icon">💡</span>
      <h3>Step 1 — Lighting & Environment</h3>
      <p>Make sure you are in a well-lit area so the camera can clearly capture your face.</p>
      <div class="instruction-box">
        <ul>
          <li>Face a light source (window, lamp)</li>
          <li>Avoid bright lights directly behind you</li>
          <li>Do not use the kiosk in direct sunlight</li>
          <li>Remove hats, sunglasses, or face masks</li>
        </ul>
      </div>
      <button class="btn-next" onclick="nextStep(50)">Next →</button>
    </div>

    <div class="step">
      <span class="step-icon">🎯</span>
      <h3>Step 2 — Position Your Face</h3>
      <p>Center your face inside the circular frame on the camera screen.</p>
      <div class="instruction-box">
        <ul>
          <li>Look directly at the camera lens</li>
          <li>Keep your face fully inside the circle</li>
          <li>Maintain a neutral expression</li>
          <li>Keep an arm's length distance from the camera</li>
        </ul>
      </div>
      <button class="btn-next" onclick="nextStep(75)">Next →</button>
    </div>

    <div class="step">
      <span class="step-icon">✅</span>
      <h3>Step 3 — Ready to Capture</h3>
      <p>Stay still and look at the camera. Press <strong>Capture</strong> when you are ready. Your photo will be saved automatically.</p>
      <button class="btn-start" onclick="startCapture()">Open Camera</button>
    </div>

  </div>

  <!-- CAMERA SCENE -->
  <div class="scene" id="cameraScene">
    <div class="cam-wrap" id="camWrap">
      <video id="video" autoplay playsinline class="video"></video>
      <img id="preview" class="preview"/>
      <div class="cam-status" id="statusText">Ready to capture</div>
      <div class="progress">
        <div class="bar" id="bar"></div>
      </div>
      <button class="btn-start" id="captureBtn" onclick="capture()" style="margin-top:4px;">📷 Capture</button>
      <div id="errorMsg" class="error-msg"></div>
      <!-- After capture: Save & View Summary -->
      <button class="btn-done" id="doneBtn" style="display:none;margin-top:8px;" onclick="saveAndProceed()">💾 Save & View Summary</button>
    </div>

    <!-- Saving spinner (shown while uploading) -->
    <div class="saving-overlay" id="savingOverlay">
      <div class="spinner"></div>
      <div class="saving-text">Saving face image to database...</div>
    </div>

    <canvas id="canvas"></canvas>
  </div>

  <!-- SUCCESS SCENE -->
  <div class="scene" id="successScene">
    <div class="success-scene">
      <div class="check-circle">✓</div>
      <h2>Saved Successfully!</h2>
      <p>Your face image has been saved. Redirecting to your summary...</p>
      <button class="btn-done" onclick="goHome()">🏠 Back to Home</button>
    </div>
  </div>

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
  document.getElementById("headerStep").textContent="Step "+(stepIndex+1)+" of 4";
}

function startCapture(){
  const wrapper=document.getElementById("stepsWrapper");
  wrapper.classList.remove("visible");wrapper.classList.add("hidden");
  setTimeout(()=>{
    wrapper.style.display="none";
    document.getElementById("stepProgressTop").style.display="none";
    document.getElementById("headerStep").textContent="Camera Mode";
    document.getElementById("dot").style.display="block";
    goTo("cameraScene");
    initCamera();
  },500);
}

function goTo(id){
  const cur=document.querySelector(".scene.active");
  if(cur){
    cur.classList.add("out");
    setTimeout(()=>{cur.classList.remove("active","out");cur.style.display="none";},350);
  }
  setTimeout(()=>{
    const next=document.getElementById(id);
    next.style.display="block";
    requestAnimationFrame(()=>requestAnimationFrame(()=>next.classList.add("active")));
  },cur?370:0);
}

/* CAMERA */
let stream=null;
let capturedImageBase64=null;

async function initCamera(){
  try{
    stream=await navigator.mediaDevices.getUserMedia({video:true});
    document.getElementById("video").srcObject=stream;
    // No WebSocket in this flow — use canvas capture instead
    document.getElementById("dot").style.background="#16a34a";
    document.getElementById("statusText").textContent="Camera ready ✓";
  }catch(e){
    document.getElementById("statusText").textContent="Camera unavailable: "+e.message;
    document.getElementById("dot").style.background="var(--red)";
  }
}

/* CAPTURE — snapshot from video using canvas */
function capture(){
  const video=document.getElementById("video");
  const canvas=document.getElementById("canvas");

  canvas.width=video.videoWidth||320;
  canvas.height=video.videoHeight||320;
  const ctx=canvas.getContext("2d");
  ctx.drawImage(video,0,0,canvas.width,canvas.height);

  capturedImageBase64=canvas.toDataURL("image/jpeg",0.85); // base64 data URL

  // Show preview
  const preview=document.getElementById("preview");
  preview.src=capturedImageBase64;
  preview.style.display="block";
  document.getElementById("video").style.display="none";

  document.getElementById("bar").style.width="100%";
  document.getElementById("statusText").textContent="✓ Face captured! Review and save.";
  document.getElementById("captureBtn").style.display="none";
  document.getElementById("doneBtn").style.display="block";

  // Stop camera stream to free resource
  if(stream) stream.getTracks().forEach(t=>t.stop());
  // NOTE: raw base64 is NOT stored in sessionStorage — only patient_id is kept as reference
}

/* SAVE FACE IMAGE TO DB THEN GO TO SUMMARY */
async function saveAndProceed(){
  const patient_id=getSession("patient_id");
  const errorEl=document.getElementById("errorMsg");
  errorEl.style.display="none";

  if(!capturedImageBase64){
    errorEl.textContent="No image captured. Please try again.";
    errorEl.style.display="block";
    return;
  }

  // Show saving spinner
  document.getElementById("camWrap").style.display="none";
  document.getElementById("savingOverlay").classList.add("show");
  document.getElementById("headerStep").textContent="Saving...";

  let saveOk=false;

  if(patient_id){
    try{
      // POST face image to save_patient.php — it will decode and save to uploads/faces/
      const json=await apiFetch("../api/save_patient.php",{
        patient_id:parseInt(patient_id),
        face_image:capturedImageBase64
      });
      if(json.success) saveOk=true;
      else console.warn("Save face failed:",json.error);
    }catch(e){
      console.warn("../api/save_patient.php error:",e);
    }
  } else {
    console.warn("No patient_id in sessionStorage — face image not saved to DB.");
  }

  // Show success then redirect to summary
  document.getElementById("savingOverlay").classList.remove("show");
  goTo("successScene");
  document.getElementById("headerStep").textContent=saveOk?"Saved ✓":"Complete";
  document.getElementById("dot").style.background="#16a34a";

  // Redirect to summary after 1.5s
  setTimeout(()=>{
    window.location.href="../results/summary.php";
  },1500);
}

function goHome(){
  window.location.href="../home.php";
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