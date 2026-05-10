# Quick Update Guide for Remaining Measurement Pages

Apply these changes to: `weight.php`, `bloodpressure.php`, `temperature.php`, `oximeter.php`

---

## 1. Add Skip Button

**Find this section** (in the main UI div, after the Done button):
```html
<button class="btn-done" id="doneBtn" style="display:none;margin-top:8px;" onclick="saveDone()">Done ✓</button>
```

**Add this line immediately after:**
```html
<button class="btn-back" id="skipBtn" onclick="skipStep()" style="margin-top:8px;">Skip for Testing</button>
```

---

## 2. Add Error Screen

**Find the closing `</div></div>` of the main card** (before the success overlay).

**Add this HTML before those closing tags:**
```html
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
```

---

## 3. Add JavaScript Functions

**Find the `<script>` section** and add these functions before the WebSocket connection code:

```javascript
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
```

---

## 4. Update WebSocket Connection Logic

**Find the `connect()` function** and update it:

```javascript
/* WEBSOCKET */
let ws;
let connectionAttempts = 0;
const MAX_CONNECTION_ATTEMPTS = 3;

function connect(){
  connectionAttempts++;
  
  ws = new WebSocket(`ws://${location.hostname}:8765`);

  ws.onopen = () => {
    connectionAttempts = 0; // Reset on successful connection
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

    // ... rest of your existing onmessage code ...
  };
}
```

---

## 5. Hide Skip Button When Done

**In the section where you show the Done button**, add this line:

```javascript
document.getElementById("skipBtn").style.display = "none";
```

**Example (in weight.php, when weight is received):**
```javascript
if (data.weight !== undefined && data.weight > 0) {
  document.getElementById("weight").textContent = data.weight;
  // ... other code ...
  document.getElementById("startBtn").style.display = "none";
  document.getElementById("doneBtn").style.display = "block";
  document.getElementById("skipBtn").style.display = "none"; // ADD THIS LINE
}
```

---

## Summary of Changes Per File

### weight.php
- ✅ Add skip button
- ✅ Add error screen HTML
- ✅ Add skip/retry/goBack functions
- ✅ Update WebSocket connection with error handling
- ✅ Hide skip button when measurement complete

### bloodpressure.php
- ✅ Add skip button
- ✅ Add error screen HTML
- ✅ Add skip/retry/goBack functions
- ✅ Update WebSocket connection with error handling
- ✅ Hide skip button when measurement complete

### temperature.php
- ✅ Add skip button
- ✅ Add error screen HTML
- ✅ Add skip/retry/goBack functions
- ✅ Update WebSocket connection with error handling
- ✅ Hide skip button when measurement complete

### oximeter.php
- ✅ Add skip button
- ✅ Add error screen HTML
- ✅ Add skip/retry/goBack functions
- ✅ Update WebSocket connection with error handling
- ✅ Hide skip button when measurement complete

---

## Testing After Updates

1. **Test skip button:** Click "Skip for Testing" → should go to home.php
2. **Test error screen:** Disconnect WebSocket → should show error screen after 3 attempts
3. **Test retry:** Click "Retry Connection" → should attempt to reconnect
4. **Test go back:** Click "Go Back" → should return to home.php
5. **Test normal flow:** Complete measurement → skip button should hide, done button should show

---

## Quick Copy-Paste Sections

### Skip Button HTML
```html
<button class="btn-back" id="skipBtn" onclick="skipStep()" style="margin-top:8px;">Skip for Testing</button>
```

### Error Screen HTML
```html
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
```

### JavaScript Functions
```javascript
function skipStep(){ window.location.href = "../home.php"; }
function goBack(){ window.location.href = "../home.php"; }
function retryConnection(){
  document.getElementById("errorScreen").style.display = "none";
  document.getElementById("mainUI").style.display = "block";
  connect();
}
function showErrorScreen(message){
  document.getElementById("mainUI").style.display = "none";
  document.getElementById("errorScreen").style.display = "block";
  document.getElementById("errorMessage").textContent = message;
}
```

### Connection Attempt Variables
```javascript
let connectionAttempts = 0;
const MAX_CONNECTION_ATTEMPTS = 3;
```

### Hide Skip Button Code
```javascript
document.getElementById("skipBtn").style.display = "none";
```
