<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="kiosk-token" content="<?= htmlspecialchars(getenv('KIOSK_API_TOKEN') ?: '') ?>">
<title>Print — HealthKiosk</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<script src="../assets/app.js"></script>
<style>
/* Page-specific styles */

.header{padding:0 16px;position:sticky;top:0;z-index:10;}
.main{flex:1;display:flex;justify-content:center;padding:28px 15px 60px;align-items:flex-start;}
.wrap{width:100%;max-width:400px;}

/* THERMAL RECEIPT */
.receipt-wrap{position:relative;filter:drop-shadow(0 8px 24px rgba(0,0,0,0.20));}
.receipt{background:#fefefe;font-family:var(--mono);font-size:11.5px;color:#111;position:relative;}
.r-top-notch{display:block;width:100%;height:14px;overflow:hidden;}
.r-top-notch svg,.r-bot-notch svg{display:block;width:100%;}
.r-bot-notch{display:block;width:100%;height:14px;overflow:hidden;}
.r-body{padding:14px 22px 12px;}
.c{text-align:center;}
.bold{font-weight:700;}
.clinic{font-size:15px;font-weight:700;letter-spacing:1.5px;text-align:center;margin-bottom:2px;text-transform:uppercase;}
.clinic-sub{font-size:10px;text-align:center;color:#555;letter-spacing:0.3px;line-height:1.6;}
.dash{border:none;border-top:1px dashed #bbb;margin:10px 0;}
.row{display:flex;justify-content:space-between;margin:4px 0;font-size:11px;gap:8px;}
.row .lbl{color:#555;white-space:nowrap;flex-shrink:0;}
.row .val{font-weight:700;color:#111;text-align:right;word-break:break-word;}
.sec{font-size:9px;letter-spacing:2.5px;text-transform:uppercase;text-align:center;color:#777;margin:10px 0 8px;}
.big-val{font-size:24px;font-weight:700;text-align:center;letter-spacing:2px;margin:3px 0 0;line-height:1;}
.big-unit{font-size:9px;text-align:center;color:#777;margin-bottom:2px;}
.badge-sm{display:inline-block;font-size:9px;font-weight:700;padding:1px 5px;border-radius:2px;margin-left:3px;vertical-align:middle;}
.bn{background:#d1fae5;color:#065f46;}
.bh{background:#fee2e2;color:#991b1b;}
.bl{background:#fef3c7;color:#92400e;}
.barcode-wrap{text-align:center;margin:12px 0 4px;}
.barcode-wrap svg{display:inline-block;}
.barcode-id{font-size:9px;text-align:center;color:#666;letter-spacing:2px;margin-bottom:4px;}
.foot-note{font-size:9px;text-align:center;color:#999;line-height:1.7;}
.stars{text-align:center;font-size:11px;color:#ccc;letter-spacing:4px;margin-top:6px;}

/* WS PILL */
.ws-pill{font-size:10px;font-weight:600;padding:3px 9px;border-radius:20px;background:#f1f5f9;color:var(--text3);display:flex;align-items:center;gap:5px;border:1px solid var(--border);}
.ws-pill .dot{width:7px;height:7px;border-radius:50%;background:#d1d5db;flex-shrink:0;position:static;display:inline-block;}
.ws-pill.connecting .dot{background:#f59e0b;animation:blink 0.8s infinite;}
.ws-pill.connected .dot{background:#22c55e;}
.ws-pill.connected{color:#16a34a;}
.ws-pill.error .dot{background:#ef4444;}
.ws-pill.error{color:#dc2626;}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:0.2;}}

/* BUTTONS */
.btn-row{display:flex;gap:10px;margin-top:22px;justify-content:center;flex-wrap:wrap;}
.btn-print{background:var(--accent);color:#fff;border:none;border-radius:9px;padding:13px 30px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 4px 16px rgba(37,99,235,0.35);transition:background 0.15s,transform 0.1s;width:auto;}
.btn-print:hover{background:#1d4ed8;}
.btn-print:active{transform:scale(0.97);}
.btn-print.loading{pointer-events:none;opacity:0.8;}
.spin{width:15px;height:15px;border:2px solid rgba(255,255,255,0.35);border-top:2px solid #fff;border-radius:50%;animation:sp 0.7s linear infinite;display:none;}
.btn-print.loading .spin{display:block;}
@keyframes sp{to{transform:rotate(360deg);}}

.toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(80px);background:#1e2d3d;color:#fff;padding:11px 20px;border-radius:10px;font-size:13px;font-weight:600;transition:transform 0.3s cubic-bezier(0.34,1.56,0.64,1);z-index:99;white-space:nowrap;}
.toast.show{transform:translateX(-50%) translateY(0);}
</style>
</head>
<body>

<?php include '../includes/header.php'; ?>
<div style="display:flex;align-items:center;gap:10px;position:absolute;right:16px;top:50%;transform:translateY(-50%);">
  <div class="ws-pill" id="wsPill">
    <span class="dot"></span>
    <span id="wsLabel">WS</span>
  </div>
</div>

<div class="main">
<div class="wrap">

  <div class="receipt-wrap">

    <div class="r-top-notch">
      <svg viewBox="0 0 400 14" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
        <polygon points="0,14 0,8 6,14 12,8 18,14 24,8 30,14 36,8 42,14 48,8 54,14 60,8 66,14 72,8 78,14 84,8 90,14 96,8 102,14 108,8 114,14 120,8 126,14 132,8 138,14 144,8 150,14 156,8 162,14 168,8 174,14 180,8 186,14 192,8 198,14 204,8 210,14 216,8 222,14 228,8 234,14 240,8 246,14 252,8 258,14 264,8 270,14 276,8 282,14 288,8 294,14 300,8 306,14 312,8 318,14 324,8 330,14 336,8 342,14 348,8 354,14 360,8 366,14 372,8 378,14 384,8 390,14 396,8 400,14 400,0 0,0" fill="#fefefe"/>
      </svg>
    </div>

    <div class="receipt" id="printArea">
      <div class="r-body">

        <div class="clinic">HEALTHKIOSK</div>
        <div class="clinic-sub">Community Health Monitoring System<br>Pozorrubio, Pangasinan</div>

        <hr class="dash">

        <div class="c" style="font-size:10px;color:#555;line-height:1.8;">
          <span id="recDate">--</span><br>
          <span class="bold" style="letter-spacing:1px;" id="recId">REC-000000</span>
        </div>

        <hr class="dash">

        <div class="sec">PATIENT INFORMATION</div>
        <div class="row"><span class="lbl">NAME</span><span class="val" id="fullName">--</span></div>
        <div class="row"><span class="lbl">AGE</span><span class="val" id="ageVal">--</span></div>
        <div class="row"><span class="lbl">GENDER</span><span class="val" id="genderVal">--</span></div>
        <div class="row"><span class="lbl">BIRTHDAY</span><span class="val" id="dobVal">--</span></div>
        <div class="row"><span class="lbl">PHONE</span><span class="val" id="phoneVal">--</span></div>
        <div class="row"><span class="lbl">BARANGAY</span><span class="val" id="barangayVal">--</span></div>
        <div class="row"><span class="lbl">MUNICIPALITY</span><span class="val">Pozorrubio</span></div>
        <div class="row"><span class="lbl">PROVINCE</span><span class="val">Pangasinan</span></div>

        <hr class="dash">

        <div class="sec">BODY MEASUREMENTS</div>
        <div class="row">
          <span class="lbl">WEIGHT</span>
          <span class="val"><span id="weightVal">--</span> kg <span class="badge-sm" id="weightBadge"></span></span>
        </div>
        <div class="row">
          <span class="lbl">HEIGHT</span>
          <span class="val"><span id="heightVal">--</span> cm</span>
        </div>
        <div style="margin:10px 0 6px;">
          <div style="font-size:9px;color:#777;text-align:center;letter-spacing:1px;">BODY MASS INDEX (BMI)</div>
          <div class="big-val" id="bmiVal">--</div>
          <div class="big-unit">kg/m² &nbsp;<span class="badge-sm" id="bmiBadge"></span></div>
        </div>

        <hr class="dash">

        <div class="sec">VITAL SIGNS</div>
        <div class="row">
          <span class="lbl">TEMPERATURE</span>
          <span class="val"><span id="tempVal">--</span> &deg;C <span class="badge-sm" id="tempBadge"></span></span>
        </div>
        <div class="row">
          <span class="lbl">SpO2</span>
          <span class="val"><span id="spo2Val">--</span> % <span class="badge-sm" id="spo2Badge"></span></span>
        </div>
        <div class="row">
          <span class="lbl">PULSE RATE</span>
          <span class="val"><span id="pulseVal">--</span> bpm <span class="badge-sm" id="pulseBadge"></span></span>
        </div>
        <div style="margin:10px 0 6px;">
          <div style="font-size:9px;color:#777;text-align:center;letter-spacing:1px;">BLOOD PRESSURE</div>
          <div class="big-val" id="bpVal">--/--</div>
          <div class="big-unit">mmHg &nbsp;<span class="badge-sm" id="bpBadge"></span></div>
        </div>

        <hr class="dash">

        <div class="barcode-wrap">
          <svg id="barcodesvg" width="220" height="48" viewBox="0 0 220 48" xmlns="http://www.w3.org/2000/svg"></svg>
        </div>
        <div class="barcode-id" id="barcodeText">REC-000000</div>

        <hr class="dash">

        <div class="foot-note">
          This record is generated by HealthKiosk.<br>
          For medical advice, consult a licensed physician.<br>
          <span id="printTime">--</span>
        </div>
        <div class="stars">* * * * *</div>

      </div>
    </div>

    <div class="r-bot-notch">
      <svg viewBox="0 0 400 14" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
        <polygon points="0,0 0,6 6,0 12,6 18,0 24,6 30,0 36,6 42,0 48,6 54,0 60,6 66,0 72,6 78,0 84,6 90,0 96,6 102,0 108,6 114,0 120,6 126,0 132,6 138,0 144,6 150,0 156,6 162,0 168,6 174,0 180,6 186,0 192,6 198,0 204,6 210,0 216,6 222,0 228,6 234,0 240,6 246,0 252,6 258,0 264,6 270,0 276,6 282,0 288,6 294,0 300,6 306,0 312,6 318,0 324,6 330,0 336,6 342,0 348,6 354,0 360,6 366,0 372,6 378,0 384,6 390,0 396,6 400,0 400,14 0,14" fill="#fefefe"/>
      </svg>
    </div>

  </div>

  <div class="btn-row">
    <button class="btn-back" onclick="newPatient()">Skip / Done</button>
    
    <button class="btn-print" id="btnPrint" onclick="handlePrint()">
      <span class="spin" id="spin"></span>
      <span id="btnTxt">🖨️ Print Receipt</span>
    </button>
  </div>

  <div class="btn-row" id="postPrintActions" style="display:none;">
    <button class="btn-done" onclick="newPatient()">✓ Done — New Patient</button>
  </div>

</div>
</div>

<div class="toast" id="toast">Sent!</div>

<script>
/* ══════════ WEBSOCKET ══════════ */
const WS_URL="ws://localhost:8765";
let ws=null, reconnectTimer=null;

function setWsPill(state){
  const p=document.getElementById("wsPill");
  const l=document.getElementById("wsLabel");
  p.className="ws-pill "+state;
  l.textContent={disconnected:"WS",connecting:"Connecting...",connected:"Connected",error:"Error"}[state]||"WS";
}

function connectWS(){
  if(ws&&(ws.readyState===WebSocket.OPEN||ws.readyState===WebSocket.CONNECTING)) ws.close();
  clearTimeout(reconnectTimer);
  setWsPill("connecting");
  try{ ws=new WebSocket(WS_URL); }
  catch(e){ setWsPill("error"); return; }

  ws.onopen=()=>{
    setWsPill("connected");
    ws.send(JSON.stringify({type:"status_request"}));
  };
  ws.onmessage=(e)=>{
    try{ handleMsg(JSON.parse(e.data)); }
    catch(err){ console.warn("WS parse error",e.data); }
  };
  ws.onerror=()=>{ setWsPill("error"); };
  ws.onclose=()=>{
    setWsPill("disconnected");
    reconnectTimer=setTimeout(connectWS,5000);
  };
}

function handleMsg(data){
  /* Live vitals update from sensors */
  if(data.type==="vitals"){
    const map={temp:"temp",spo2:"spo2",systolic:"systolic",diastolic:"diastolic",pulse:"pulse",weight:"weight",height:"height"};
    Object.entries(map).forEach(([k,v])=>{ if(data[k]!==undefined) setSession(v,data[k]); });
    populateReceipt();
  }
  /* Thermal printer confirmed print */
  else if(data.type==="print_done"){
    showToast("✅ Printed successfully!");
    document.getElementById("btnPrint").style.display = "none";
    document.getElementById("postPrintActions").style.display = "flex";
  }
  /* Thermal printer error */
  else if(data.type==="print_error"){
    showToast("❌ "+(data.message||"Printer error. Check connection."));
    resetBtn();
  }
}

/* ══════════ PRINT — sends to thermal printer via WS ══════════ */
function handlePrint(){
  const btn=document.getElementById("btnPrint");

  /* Guard: must be connected */
  if(!ws||ws.readyState!==WebSocket.OPEN){
    showToast("⚠️ Not connected to printer server.");
    return;
  }

  btn.classList.add("loading");
  document.getElementById("btnTxt").textContent="Printing...";

  const payload={
    type:"print",
    patient:{
      name:      document.getElementById("fullName").textContent,
      age:       document.getElementById("ageVal").textContent,
      gender:    document.getElementById("genderVal").textContent,
      dob:       document.getElementById("dobVal").textContent,
      phone:     document.getElementById("phoneVal").textContent,
      barangay:  document.getElementById("barangayVal").textContent,
    },
    vitals:{
      weight: document.getElementById("weightVal").textContent,
      height: document.getElementById("heightVal").textContent,
      bmi:    document.getElementById("bmiVal").textContent,
      temp:   document.getElementById("tempVal").textContent,
      spo2:   document.getElementById("spo2Val").textContent,
      bp:     document.getElementById("bpVal").textContent,
      pulse:  document.getElementById("pulseVal").textContent,
    },
    record:{
      id:   document.getElementById("recId").textContent,
      date: document.getElementById("recDate").textContent,
    }
  };

  /* Send print command to thermal printer server */
  ws.send(JSON.stringify(payload));

  /* Safety timeout: reset button after 15s if no response from server */
  setTimeout(()=>{
    if(document.getElementById("btnPrint").classList.contains("loading")){
      showToast("⚠️ No response from printer.");
      resetBtn();
    }
  }, 15000);
}

function resetBtn(){
  const btn=document.getElementById("btnPrint");
  btn.classList.remove("loading");
  document.getElementById("btnTxt").textContent="🖨️ Print Receipt";
}

function newPatient(){
  clearSession();
  window.location.href = "../index.php";
}

/* ══════════ RECEIPT ══════════ */
async function populateReceipt(){
  const patient_id = getSession("patient_id");
  const record_id  = getSession("record_id");

  let fname, lname, age, gender, dob, phone, barangay;
  let weight, height, temp, spo2, sys, dia, pulse;

  // Try DB first
  if(patient_id){
    try {
      const url = `../api/get_record.php?patient_id=${patient_id}${record_id?'&record_id='+record_id:''}`;
      const res = await fetch(url);
      const json = await res.json();
      if(json.success){
        const p = json.patient;
        const r = json.record || {};
        fname    = p.first_name;   lname    = p.last_name;
        age      = p.age;          gender   = p.gender;
        dob      = p.date_of_birth;phone    = p.phone;
        barangay = p.barangay;
        weight   = r.weight_kg;    height   = r.height_cm;
        temp     = r.temperature_c;spo2     = r.spo2_percent;
        sys      = r.systolic_bp;  dia      = r.diastolic_bp;
        pulse    = r.pulse_bpm;
      }
    } catch(e){ console.warn("DB load failed:", e); }
  }

  // sessionStorage fallback
  const g=k=>getSession(k)||"--";
  fname    = fname    || g("fname");    lname    = lname    || g("lname");
  age      = age      || g("age");      gender   = gender   || g("gender");
  dob      = dob      || g("birth");    phone    = phone    || g("number");
  barangay = barangay || g("barangay");
  weight   = weight   || g("weight");   height   = height   || g("height");
  temp     = temp     || g("temp");     spo2     = spo2     || g("spo2");
  sys      = sys      || g("systolic"); dia      = dia      || g("diastolic");
  pulse    = pulse    || g("pulse");

  document.getElementById("fullName").textContent=(fname!=="--"||lname!=="--")?fname+" "+lname:"--";
  document.getElementById("ageVal").textContent=age!=="--"?age+" yrs":"--";
  document.getElementById("genderVal").textContent=gender;
  document.getElementById("dobVal").textContent=dob!=="--"?fmt(dob):"--";
  document.getElementById("phoneVal").textContent=phone;
  document.getElementById("barangayVal").textContent=barangay;
  document.getElementById("weightVal").textContent=weight;
  document.getElementById("heightVal").textContent=height;

  if(weight!=="--"&&height!=="--"){
    const w=parseFloat(weight),h=parseFloat(height)/100;
    if(w>0&&h>0){
      const bmi=(w/(h*h)).toFixed(1);
      document.getElementById("bmiVal").textContent=bmi;
      badge("bmiBadge",...bmiSt(+bmi));
      badge("weightBadge",...bmiSt(+bmi));
    }
  }

  document.getElementById("tempVal").textContent=temp;
  document.getElementById("spo2Val").textContent=spo2;
  document.getElementById("bpVal").textContent=(sys!=="--"&&dia!=="--")?sys+"/"+dia:"--/--";
  document.getElementById("pulseVal").textContent=pulse;

  if(temp!=="--"){const t=+temp;badge("tempBadge",t>=36.1&&t<=37.2?"Normal":t>37.2?"Fever":"Low",t>=36.1&&t<=37.2?"bn":t>37.2?"bh":"bl");}
  if(spo2!=="--"){const s=+spo2;badge("spo2Badge",s>=95?"Normal":s>=90?"Low":"Critical",s>=95?"bn":s>=90?"bl":"bh");}
  if(sys!=="--"){const v=+sys;badge("bpBadge",v<120?"Normal":v<130?"Elevated":"High",v<120?"bn":v<130?"bl":"bh");}
  if(pulse!=="--"){const p=+pulse;badge("pulseBadge",p>=60&&p<=100?"Normal":p<60?"Low":"High",p>=60&&p<=100?"bn":"bl");}
}

/* ══════════ HELPERS ══════════ */
function badge(id,text,cls){const e=document.getElementById(id);if(e){e.textContent=text;e.className="badge-sm "+cls;}}
function bmiSt(b){if(b<18.5)return["Underweight","bl"];if(b<25)return["Normal","bn"];if(b<30)return["Overweight","bl"];return["Obese","bh"];}
function fmt(d){if(!d||d==="--")return"--";const dt=new Date(d);return isNaN(dt)?d:dt.toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'});}
function showToast(msg){const t=document.getElementById("toast");t.textContent=msg;t.classList.add("show");setTimeout(()=>t.classList.remove("show"),3500);}

function drawBarcode(text){
  const svg=document.getElementById("barcodesvg");svg.innerHTML="";
  const str=text.replace(/[^A-Z0-9]/g,"");
  const bw=[2,1.5,3,1.5,2,1,2.5,1,2,1.5];
  let x=8;
  for(let i=0;i<str.length;i++){
    const code=str.charCodeAt(i);
    for(let b=0;b<5;b++){
      const w=bw[(code+b)%bw.length];
      const r=document.createElementNS("http://www.w3.org/2000/svg","rect");
      r.setAttribute("x",x.toFixed(1));r.setAttribute("y","3");r.setAttribute("width",w.toFixed(1));r.setAttribute("height","42");r.setAttribute("fill","#111");
      svg.appendChild(r);x+=w+(bw[(code+b+3)%bw.length]*0.7);
      if(x>212) break;
    }
    if(x>212) break;
  }
}

/* ══════════ INIT ══════════ */
window.addEventListener("DOMContentLoaded", async ()=>{
  const now=new Date();
  const recId="REC-"+Date.now().toString().slice(-6);
  document.getElementById("recId").textContent=recId;
  document.getElementById("barcodeText").textContent=recId;
  document.getElementById("recDate").textContent=
    now.toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'})+
    "   "+now.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
  document.getElementById("printTime").textContent="Printed: "+now.toLocaleString();
  await populateReceipt();
  drawBarcode(recId);
  connectWS();
  startIdleTimer();
});
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