<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="kiosk-token" content="<?= htmlspecialchars(getenv('KIOSK_API_TOKEN') ?: '') ?>">
<title>Print Patient Record</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<script src="../assets/app.js"></script>

<style>
/* Page-specific styles */

/* ── MAIN ── */
.main{align-items:flex-start;padding:20px 15px 40px;}

/* ── PRINT CARD ── */
.print-card{
  width:100%;
  max-width:680px;
  background:var(--surface);
  border-radius:14px;
  box-shadow:0 6px 20px rgba(0,0,0,0.1);
  overflow:hidden;
}

/* ── CARD HEADER BANNER ── */
.card-banner{
  background:var(--accent);
  color:#fff;
  padding:18px 24px 16px;
  display:flex;
  justify-content:space-between;
  align-items:flex-end;
}
.banner-left h1{font-size:18px;font-weight:700;letter-spacing:0.3px;}
.banner-left p{font-size:11px;opacity:0.8;margin-top:3px;}
.banner-right{text-align:right;}
.banner-right .rec-id{font-family:var(--mono);font-size:11px;opacity:0.85;}
.banner-right .rec-date{font-size:10px;opacity:0.7;margin-top:2px;}

/* ── SECTIONS ── */
.section{padding:18px 24px;border-bottom:1px solid var(--border);}
.section:last-child{border-bottom:none;}

.section-title{
  font-size:10px;
  font-weight:700;
  text-transform:uppercase;
  letter-spacing:1px;
  color:var(--accent);
  margin-bottom:12px;
  display:flex;
  align-items:center;
  gap:6px;
}

/* ── PATIENT INFO GRID ── */
.info-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:10px;
}
.info-grid.three{grid-template-columns:1fr 1fr 1fr;}

.info-label{font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;}
.info-value{font-size:14px;font-weight:600;color:var(--text);}
.info-value.mono{font-family:var(--mono);}

/* FACE PHOTO */
.patient-top{display:flex;gap:18px;align-items:flex-start;}
.face-photo{
  width:80px;height:80px;
  border-radius:50%;
  border:3px solid var(--border);
  object-fit:cover;
  flex-shrink:0;
  background:#e0e7ef;
  display:flex;align-items:center;justify-content:center;
  font-size:28px;
  overflow:hidden;
}
.face-photo img{width:100%;height:100%;object-fit:cover;border-radius:50%;}
.patient-details{flex:1;}

/* ── VITALS GRID ── */
.vitals-grid{
  display:grid;
  grid-template-columns:repeat(2,1fr);
  gap:10px;
}

.vital-card{
  background:#f8fafc;
  border:1px solid var(--border);
  border-radius:10px;
  padding:12px 14px;
  display:flex;
  align-items:center;
  gap:12px;
}
.vital-icon{font-size:24px;flex-shrink:0;}

.vital-label{font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;}
.vital-value{font-size:20px;font-weight:700;color:var(--accent);font-family:var(--mono);line-height:1;}
.vital-unit{font-size:10px;color:var(--text3);margin-top:2px;}

/* STATUS BADGE */
.status-badge{
  display:inline-block;
  padding:2px 8px;
  border-radius:20px;
  font-size:10px;
  font-weight:600;
  margin-top:4px;
}
.badge-normal{background:#dcfce7;color:#16a34a;}
.badge-high{background:#fee2e2;color:#dc2626;}
.badge-low{background:#fef9c3;color:#ca8a04;}

/* ── FOOTER STRIP ── */
.card-footer{
  background:#f8fafc;
  border-top:1px solid var(--border);
  padding:12px 24px;
  display:flex;
  justify-content:space-between;
  align-items:center;
}
.footer-note{font-size:10px;color:var(--text3);}

/* ── PRINT BUTTON ── */
.btn-wrap{
  display:flex;
  justify-content:center;
  margin-top:20px;
  gap:10px;
}
.btn-print{
  background:var(--accent);
  color:#fff;
  border:none;
  border-radius:8px;
  padding:12px 32px;
  font-size:14px;
  font-weight:700;
  cursor:pointer;
  display:flex;
  align-items:center;
  gap:8px;
  transition:opacity 0.2s;
  width:auto;
}
.btn-print:hover{opacity:0.88;}

/* ── PRINT STYLES ── */
@media print {
  body{background:#fff !important;}
  .header,.btn-wrap{display:none !important;}
  .main{padding:0 !important;}
  .print-card{
    box-shadow:none !important;
    border-radius:0 !important;
    max-width:100% !important;
  }
  .card-footer{display:none !important;}

  /* Force colors for print */
  .card-banner{-webkit-print-color-adjust:exact;print-color-adjust:exact;background:var(--accent) !important;}
  .vital-card{-webkit-print-color-adjust:exact;print-color-adjust:exact;}
  .status-badge{-webkit-print-color-adjust:exact;print-color-adjust:exact;}
  .section-title{-webkit-print-color-adjust:exact;print-color-adjust:exact;}
}
</style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="main">
  <div style="width:100%;max-width:680px;">

    <div class="print-card" id="printArea">

      <!-- BANNER -->
      <div class="card-banner">
        <div class="banner-left">
          <h1>🏥 HealthKiosk — Patient Record</h1>
          <p>Pozorrubio, Pangasinan · Community Health Monitoring System</p>
        </div>
        <div class="banner-right">
          <div class="rec-id" id="recId">ID: --</div>
          <div class="rec-date" id="recDate">--</div>
        </div>
      </div>

      <!-- PATIENT INFORMATION -->
      <div class="section">
        <div class="section-title">👤 Patient Information</div>
        <div class="patient-top">
          <div class="face-photo" id="facePhotoWrap">
            <span id="faceEmoji">🧑</span>
          </div>
          <div class="patient-details">
            <div class="info-grid three">
              <div class="info-item">
                <div class="info-label">Full Name</div>
                <div class="info-value" id="fullName">--</div>
              </div>
              <div class="info-item">
                <div class="info-label">Age</div>
                <div class="info-value" id="ageVal">--</div>
              </div>
              <div class="info-item">
                <div class="info-label">Gender</div>
                <div class="info-value" id="genderVal">--</div>
              </div>
              <div class="info-item">
                <div class="info-label">Date of Birth</div>
                <div class="info-value" id="dobVal">--</div>
              </div>
              <div class="info-item">
                <div class="info-label">Phone</div>
                <div class="info-value" id="phoneVal">--</div>
              </div>
              <div class="info-item">
                <div class="info-label">Barangay</div>
                <div class="info-value" id="barangayVal">--</div>
              </div>
            </div>
          </div>
        </div>
        <div class="info-grid" style="margin-top:10px;">
          <div class="info-item">
            <div class="info-label">Municipality</div>
            <div class="info-value">Pozorrubio</div>
          </div>
          <div class="info-item">
            <div class="info-label">Province</div>
            <div class="info-value">Pangasinan</div>
          </div>
        </div>
      </div>

      <!-- ANTHROPOMETRICS -->
      <div class="section">
        <div class="section-title">📏 Body Measurements</div>
        <div class="vitals-grid">

          <div class="vital-card">
            <div class="vital-icon">⚖️</div>
            <div class="vital-body">
              <div class="vital-label">Body Weight</div>
              <div class="vital-value" id="weightVal">--</div>
              <div class="vital-unit">kg</div>
              <span class="status-badge" id="weightBadge"></span>
            </div>
          </div>

          <div class="vital-card">
            <div class="vital-icon">📏</div>
            <div class="vital-body">
              <div class="vital-label">Height</div>
              <div class="vital-value" id="heightVal">--</div>
              <div class="vital-unit">cm</div>
            </div>
          </div>

          <div class="vital-card" style="grid-column:1/-1;">
            <div class="vital-icon">📊</div>
            <div class="vital-body">
              <div class="vital-label">BMI (Body Mass Index)</div>
              <div class="vital-value" id="bmiVal">--</div>
              <div class="vital-unit">kg/m²</div>
              <span class="status-badge" id="bmiBadge"></span>
            </div>
          </div>

        </div>
      </div>

      <!-- VITAL SIGNS -->
      <div class="section">
        <div class="section-title">🩺 Vital Signs</div>
        <div class="vitals-grid">

          <div class="vital-card">
            <div class="vital-icon">🌡️</div>
            <div class="vital-body">
              <div class="vital-label">Body Temperature</div>
              <div class="vital-value" id="tempVal">--</div>
              <div class="vital-unit">°C</div>
              <span class="status-badge" id="tempBadge"></span>
            </div>
          </div>

          <div class="vital-card">
            <div class="vital-icon">🫀</div>
            <div class="vital-body">
              <div class="vital-label">SpO₂ (Blood Oxygen)</div>
              <div class="vital-value" id="spo2Val">--</div>
              <div class="vital-unit">%</div>
              <span class="status-badge" id="spo2Badge"></span>
            </div>
          </div>

          <div class="vital-card">
            <div class="vital-icon">🩺</div>
            <div class="vital-body">
              <div class="vital-label">Blood Pressure</div>
              <div class="vital-value" id="bpVal">--/--</div>
              <div class="vital-unit">mmHg (Systolic / Diastolic)</div>
              <span class="status-badge" id="bpBadge"></span>
            </div>
          </div>

          <div class="vital-card">
            <div class="vital-icon">💓</div>
            <div class="vital-body">
              <div class="vital-label">Pulse Rate</div>
              <div class="vital-value" id="pulseVal">--</div>
              <div class="vital-unit">bpm</div>
              <span class="status-badge" id="pulseBadge"></span>
            </div>
          </div>

        </div>
      </div>

      <!-- FOOTER -->
      <div class="card-footer">
        <div class="footer-note">Generated by HealthKiosk · Community Health Monitoring System</div>
        <div class="footer-note" id="printTime">--</div>
      </div>

    </div>

    <!-- BUTTONS -->
    <div class="btn-wrap">
      <button class="btn-back" onclick="window.location.href='home.html'">← Back to Home</button>
      <button class="btn-back" onclick="window.location.href='print.html'">Print Record</button>
    </div>

  </div>
</div>

<script>
/* ── LOAD DATA FROM DB (with sessionStorage fallback) ── */
window.addEventListener("DOMContentLoaded", async ()=>{

  const patient_id = getSession("patient_id");
  const record_id  = getSession("record_id");

  let fname, lname, age, gender, dob, phone, barangay;
  let weight, height, temp, spo2, systolic, diastolic, pulse;

  // Try to load from DB first
  if(patient_id){
    try {
      const url = `../api/get_record.php?patient_id=${patient_id}${record_id ? '&record_id='+record_id : ''}`;
      const res = await fetch(url);
      const json = await res.json();

      if(json.success){
        const p = json.patient;
        const r = json.record || {};

        fname    = p.first_name;
        lname    = p.last_name;
        age      = p.age;
        gender   = p.gender;
        dob      = p.date_of_birth;
        phone    = p.phone;
        barangay = p.barangay;
        height    = r.height_cm;
        temp      = r.temperature_c;
        spo2      = r.spo2_percent;
        systolic  = r.systolic_bp;
        diastolic = r.diastolic_bp;
        pulse     = r.pulse_bpm;
      }
    } catch(e){ console.warn("DB fetch failed, using sessionStorage:", e); }
  }

  // Fallback to sessionStorage if DB returned nothing
  fname    = fname    || getSession("fname")     || "--";
  lname    = lname    || getSession("lname")     || "--";
  age      = age      || getSession("age")       || "--";
  gender   = gender   || getSession("gender")    || "--";
  dob      = dob      || getSession("birth")     || "--";
  phone    = phone    || getSession("number")    || "--";
  barangay = barangay || getSession("barangay")  || "--";
  weight   = weight   || getSession("weight")    || "--";
  height   = height   || getSession("height")    || "--";
  temp     = temp     || getSession("temp")      || "--";
  spo2     = spo2     || getSession("spo2")      || "--";
  systolic = systolic || getSession("systolic")  || "--";
  diastolic= diastolic|| getSession("diastolic") || "--";
  pulse    = pulse    || getSession("pulse")     || "--";

  /* ── Populate Patient Info ── */
  document.getElementById("fullName").textContent  = (fname!=="--"||lname!=="--") ? fname+" "+lname : "--";
  document.getElementById("ageVal").textContent    = age!=="--" ? age+" yrs" : "--";
  document.getElementById("genderVal").textContent = gender;
  document.getElementById("dobVal").textContent    = dob!=="--" ? formatDate(dob) : "--";
  document.getElementById("phoneVal").textContent  = phone;
  document.getElementById("barangayVal").textContent = barangay;

  /* Face photo — served via PHP proxy (base64 never stored in sessionStorage) */
  const pid = patient_id || getSession('patient_id');
  if (pid) {
    const wrap = document.getElementById('facePhotoWrap');
    wrap.innerHTML = '';
    const img = document.createElement('img');
    img.src = `../api/face_image.php?patient_id=${pid}`;
    img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%;';
    wrap.appendChild(img);
  }

  /* Record ID & date */
  const recId="REC-"+Date.now().toString().slice(-6);
  const now=new Date();
  document.getElementById("recId").textContent=recId;
  document.getElementById("recDate").textContent=now.toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'})+" · "+now.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
  document.getElementById("printTime").textContent="Printed: "+now.toLocaleString();

  /* ── Populate Measurements ── */
  document.getElementById("weightVal").textContent = weight;
  document.getElementById("heightVal").textContent = height;

  /* BMI */
  if(weight!=="--" && height!=="--"){
    const w=parseFloat(weight);
    const h=parseFloat(height)/100;
    if(w>0&&h>0){
      const bmi=(w/(h*h)).toFixed(1);
      document.getElementById("bmiVal").textContent=bmi;
      const {text,cls}=bmiStatus(parseFloat(bmi));
      setBadge("bmiBadge",text,cls);
      setBadge("weightBadge",text,cls);
    }
  }

  /* ── Populate Vitals ── */
  document.getElementById("tempVal").textContent  = temp;
  document.getElementById("spo2Val").textContent  = spo2;
  document.getElementById("bpVal").textContent    = systolic!=="--"&&diastolic!=="--" ? systolic+"/"+diastolic : "--/--";
  document.getElementById("pulseVal").textContent = pulse;

  /* Status badges */
  if(temp!=="--"){const t=parseFloat(temp);if(t>=36.1&&t<=37.2)setBadge("tempBadge","Normal","badge-normal");else if(t>37.2)setBadge("tempBadge","Fever","badge-high");else setBadge("tempBadge","Low","badge-low");}
  if(spo2!=="--"){const s=parseFloat(spo2);if(s>=95)setBadge("spo2Badge","Normal","badge-normal");else if(s>=90)setBadge("spo2Badge","Low","badge-low");else setBadge("spo2Badge","Critical","badge-high");}
  if(systolic!=="--"){const sys=parseFloat(systolic);if(sys<120)setBadge("bpBadge","Normal","badge-normal");else if(sys<130)setBadge("bpBadge","Elevated","badge-low");else setBadge("bpBadge","High","badge-high");}
  if(pulse!=="--"){const p=parseFloat(pulse);if(p>=60&&p<=100)setBadge("pulseBadge","Normal","badge-normal");else if(p<60)setBadge("pulseBadge","Low","badge-low");else setBadge("pulseBadge","High","badge-high");}
});

/* ── HELPERS ── */
function setBadge(id,text,cls){
  const el=document.getElementById(id);
  if(el){el.textContent=text;el.className="status-badge "+cls;}
}

function bmiStatus(bmi){
  if(bmi<18.5) return {text:"Underweight",cls:"badge-low"};
  if(bmi<25)   return {text:"Normal",cls:"badge-normal"};
  if(bmi<30)   return {text:"Overweight",cls:"badge-low"};
  return {text:"Obese",cls:"badge-high"};
}

function formatDate(d){
  if(!d||d==="--") return "--";
  const dt=new Date(d);
  return isNaN(dt)?d:dt.toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'});
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