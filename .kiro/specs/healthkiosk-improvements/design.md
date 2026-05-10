# HealthKiosk — Technical Design

> This document describes how each requirement in `requirements.md` will be implemented.
> Review this before tasks are generated.

---

## 1. Project Structure After Changes

```
capstonekiosk/
├── api/
│   ├── config.php          ← single DB config, reads from .env
│   ├── save_patient.php    ← fixed: no PHP session, validates input, saves face to filesystem
│   ├── save_vitals.php     ← rewritten: UPDATEs health_records, calculates BMI + status flags
│   └── get_record.php      ← fixed: correct column names, correct config path
│
├── assets/
│   ├── style.css           ← NEW: all shared CSS extracted here
│   └── app.js              ← NEW: clock, toast, sessionStorage helpers, idle timeout
│
├── includes/
│   └── header.php          ← NEW: shared header HTML (logo + clock)
│
├── measurements/
│   ├── bloodpressure.php   ← fixed: API path, redirect, error UI
│   ├── height.php          ← fixed: API path, redirect, error UI
│   ├── oximeter.php        ← fixed: API path, redirect, error UI
│   ├── temperature.php     ← fixed: API path, redirect, error UI
│   └── weight.php          ← fixed: API path, redirect, error UI
│
├── patient/
│   ├── facecapture.php     ← fixed: no base64 in sessionStorage, passes patient_id in body
│   └── scanid.php          ← fixed: uses shared CSS/header, adds token header
│
├── results/
│   ├── summary.php         ← fixed: step completion check, shared CSS/header
│   └── print.php           ← fixed: confirmation buttons, session clear, "New Patient"
│
├── uploads/
│   └── faces/
│       └── .htaccess       ← NEW: deny direct browser access
│
├── database.sql            ← fixed: column names, missing columns, face_image type
├── home.php                ← fixed: step completion badges on dashboard cards
├── index.php               ← unchanged
├── server.py               ← fixed: reads hardware config from sensor_config.json
│
├── .env                    ← NEW: DB credentials + API token (not committed)
├── .env.example            ← NEW: documented placeholder values
├── .gitignore              ← NEW: excludes .env, uploads/, sensor_config.json
├── sensor_config.json      ← NEW: hardware calibration values
└── sensor_config.example.json  ← NEW: documented defaults
```

---

## 2. Database Design

### 2.1 Corrected `patients` Table

```sql
CREATE TABLE IF NOT EXISTS patients (
    patient_id    INT AUTO_INCREMENT PRIMARY KEY,   -- renamed from 'id'
    first_name    VARCHAR(100) NOT NULL,
    last_name     VARCHAR(100) NOT NULL,
    date_of_birth DATE,
    age           INT,
    gender        ENUM('Male','Female') NOT NULL,
    phone         VARCHAR(20),
    barangay      VARCHAR(100),
    municipality  VARCHAR(100) DEFAULT 'Pozorrubio',
    province      VARCHAR(100) DEFAULT 'Pangasinan',
    face_image    VARCHAR(255),                     -- changed from LONGTEXT, stores file path
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Changes from original:**
- `id` → `patient_id` (fixes REQ-2)
- `face_image` type: `LONGTEXT` → `VARCHAR(255)` storing a file path (fixes REQ-11)
- Added `updated_at` column to support the UPDATE path in `save_patient.php`

---

### 2.2 Corrected `health_records` Table

```sql
CREATE TABLE IF NOT EXISTS health_records (
    record_id       INT AUTO_INCREMENT PRIMARY KEY,
    patient_id      INT NOT NULL,
    record_code     VARCHAR(20),
    visit_date      DATE,                           -- added (fixes REQ-3)
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- added (fixes REQ-3)

    -- Body Measurements
    weight_kg       DECIMAL(5,2),
    height_cm       DECIMAL(5,2),
    bmi             DECIMAL(5,2),

    -- Vital Signs
    temperature_c   DECIMAL(5,2),
    spo2_percent    DECIMAL(5,2),
    systolic_bp     INT,
    diastolic_bp    INT,
    pulse_bpm       INT,

    -- Status Flags (auto-evaluated by save_vitals.php)
    temp_status     VARCHAR(20),
    spo2_status     VARCHAR(20),
    bp_status       VARCHAR(20),
    pulse_status    VARCHAR(20),
    bmi_status      VARCHAR(20),

    recorded_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
);
```

**Changes from original:**
- Added `visit_date DATE` (fixes REQ-3)
- Added `created_at TIMESTAMP` (fixes REQ-3)
- Foreign key updated to reference `patients.patient_id` (fixes REQ-2)

---

## 3. API Design

### 3.1 `api/config.php` — Single DB Config (REQ-4, REQ-8)

Reads all credentials from environment variables. Falls back to safe defaults for local dev.

```php
<?php
$host   = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'medical_kiosk';
$user   = getenv('DB_USER') ?: 'kiosk_user';
$pass   = getenv('DB_PASS') ?: '';

// API token for request authentication
define('KIOSK_API_TOKEN', getenv('KIOSK_API_TOKEN') ?: '');

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user, $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}
```

---

### 3.2 API Token Middleware Pattern (REQ-9, REQ-10)

Every API file includes this check immediately after `require_once 'config.php'`:

```php
// Token check — applied to all API endpoints
$token = $_SERVER['HTTP_X_KIOSK_TOKEN'] ?? '';
if (KIOSK_API_TOKEN && $token !== KIOSK_API_TOKEN) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
```

The frontend includes the token in every `fetch()` call:

```js
// In assets/app.js — token loaded from a meta tag injected by PHP
const KIOSK_TOKEN = document.querySelector('meta[name="kiosk-token"]')?.content ?? '';

async function apiFetch(url, options = {}) {
    return fetch(url, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            'X-Kiosk-Token': KIOSK_TOKEN,
            ...(options.headers ?? {})
        }
    });
}
```

Each PHP page injects the token into a `<meta>` tag:

```php
<meta name="kiosk-token" content="<?= htmlspecialchars(getenv('KIOSK_API_TOKEN') ?: '') ?>">
```

---

### 3.3 `api/save_vitals.php` — Rewritten (REQ-1)

**Request:**
```json
POST /api/save_vitals.php
X-Kiosk-Token: <token>
Content-Type: application/json

{
  "patient_id": 5,
  "record_id": 12,
  "weight_kg": 62.4,
  "height_cm": 165.5,
  "temperature_c": 36.7,
  "spo2_percent": 98,
  "systolic_bp": 120,
  "diastolic_bp": 80,
  "pulse_bpm": 72
}
```

**Logic:**
1. Validate `patient_id` and `record_id` are present integers
2. Build a dynamic `SET` clause from only the fields that are present in the request
3. If both `weight_kg` and `height_cm` are present, calculate `bmi = weight / (height_m²)`
4. Evaluate status flags using the thresholds below
5. UPDATE `health_records` WHERE `record_id = ? AND patient_id = ?`

**Clinical thresholds for status flags:**

| Vital | Normal | Low | High/Elevated |
|-------|--------|-----|---------------|
| Temperature (°C) | 36.1 – 37.2 | < 36.1 → "Low" | > 37.2 → "Fever" |
| SpO₂ (%) | ≥ 95 → "Normal" | 90–94 → "Low" | < 90 → "Critical" |
| Systolic BP (mmHg) | < 120 → "Normal" | — | 120–129 → "Elevated", ≥ 130 → "High" |
| Pulse (bpm) | 60–100 → "Normal" | < 60 → "Low" | > 100 → "High" |
| BMI (kg/m²) | 18.5–24.9 → "Normal" | < 18.5 → "Underweight" | 25–29.9 → "Overweight", ≥ 30 → "Obese" |

**Response:**
```json
{ "success": true, "record_id": 12 }
```

---

### 3.4 `api/save_patient.php` — Fixed (REQ-6, REQ-11, REQ-12)

**Changes:**
- Remove `session_start()` and all `$_SESSION` usage
- Add server-side validation (see REQ-12 thresholds below)
- Decode base64 face image and save to `uploads/faces/{patient_id}.jpg`
- Store only the file path in `patients.face_image`
- Return `patient_id` and `record_id` in the JSON response

**Validation rules:**

| Field | Rule |
|-------|------|
| `first_name`, `last_name` | Required, non-empty after trim |
| `gender` | Must be `Male` or `Female` |
| `date_of_birth` | Valid date, not in the future |
| `age` | Integer 0–150 |
| `phone` | Max 20 chars, pattern `/^[\d\s\-+()]*$/` (empty allowed) |
| `barangay` | Max 100 chars, pattern `/^[a-zA-Z0-9\s]*$/` (empty allowed) |

**Face image save logic:**
```php
if ($face_image) {
    // Strip data URI prefix if present: "data:image/jpeg;base64,..."
    $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $face_image);
    $imageData = base64_decode($base64);
    if ($imageData !== false) {
        $uploadDir = __DIR__ . '/../uploads/faces/';
        file_put_contents($uploadDir . $patient_id . '.jpg', $imageData);
        $face_path = 'uploads/faces/' . $patient_id . '.jpg';
        // UPDATE patients SET face_image = $face_path WHERE patient_id = $patient_id
    }
}
```

---

### 3.5 `api/get_record.php` — Fixed (REQ-2, REQ-4)

**Changes:**
- `require_once 'config.php'` (was `'../db_config.php'`)
- Query uses `WHERE patient_id = ?` against the now-correctly-named column
- Health records query uses `record_id` (not `id`) for the records table lookup

---

## 4. Shared Frontend Assets

### 4.1 `assets/style.css` (REQ-13)

Contains all styles that are currently copy-pasted across every page:

- CSS custom properties (`:root` variables)
- CSS reset (`*, *::before, *::after`)
- `.header`, `.logo`, `.logo-icon`, `.logo-name`
- `.clock-block`, `.clock`, `.clock-date`
- `.main`, `.card`
- `.step`, `.step-icon`, `.step-progress-top`, `.step-bar-top`
- `.btn-next`, `.btn-start`, `.btn-done`, `.btn-manual`, `button:disabled`
- `.fade-section`, `.fade-section.hidden`, `.fade-section.visible`
- `.dot` (WebSocket status indicator)
- `.saving-overlay`, `.saving-box`, `.spinner`
- `.success-overlay`, `.success-box`, `.check`
- `@keyframes spin`, `@keyframes pop`, `@keyframes scaleIn`
- Responsive breakpoints (`@media max-width: 480px`, `360px`)

Each page keeps only its own page-specific styles inline (e.g., `.bp-labels`, `.scan-wrap`, `.cam-wrap`).

**Link tag used in pages inside subdirectories:**
```html
<link rel="stylesheet" href="../assets/style.css">
```
**Link tag used in root-level pages (`index.php`, `home.php`):**
```html
<link rel="stylesheet" href="assets/style.css">
```

---

### 4.2 `assets/app.js` (REQ-14, REQ-15)

```js
// ── CONSTANTS ──────────────────────────────────────────────
const IDLE_TIMEOUT_SECONDS = 120;   // configurable
const IDLE_WARN_SECONDS    = 10;

// ── CLOCK ──────────────────────────────────────────────────
const DAYS   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

function updateClock() {
    const n = new Date();
    const h = String(n.getHours()).padStart(2,'0');
    const m = String(n.getMinutes()).padStart(2,'0');
    const clockEl = document.getElementById('clock');
    const dateEl  = document.getElementById('clockDate') || document.getElementById('date');
    if (clockEl) clockEl.textContent = `${h}:${m}`;
    if (dateEl)  dateEl.textContent  =
        `${DAYS[n.getDay()]} ${MONTHS[n.getMonth()]} ${n.getDate()}, ${n.getFullYear()}`;
}
updateClock();
setInterval(updateClock, 1000);

// ── TOAST ──────────────────────────────────────────────────
let _toastTimer;
function showToast(msg, duration = 2800) {
    clearTimeout(_toastTimer);
    const t = document.getElementById('toast');
    if (!t) return;
    t.textContent = msg;
    t.classList.add('show');
    _toastTimer = setTimeout(() => t.classList.remove('show'), duration);
}

// ── SESSION STORAGE HELPERS ────────────────────────────────
const SESSION_KEYS = [
    'patient_id','record_id','fname','lname','birth','age','gender',
    'number','barangay','height','weight','temp','spo2',
    'systolic','diastolic','pulse'
];

function getSession(key)       { return sessionStorage.getItem(key); }
function setSession(key, val)  { sessionStorage.setItem(key, val); }
function clearSession() {
    SESSION_KEYS.forEach(k => sessionStorage.removeItem(k));
}

// ── API FETCH HELPER (includes token) ─────────────────────
const KIOSK_TOKEN = document.querySelector('meta[name="kiosk-token"]')?.content ?? '';

async function apiFetch(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Kiosk-Token': KIOSK_TOKEN
        },
        body: JSON.stringify(body)
    });
    return res.json();
}

// ── IDLE TIMEOUT ───────────────────────────────────────────
// Call startIdleTimer() on every page except index.php
let _idleTimer, _warnTimer, _warnCountdown;

function startIdleTimer() {
    const reset = () => {
        clearTimeout(_idleTimer);
        clearTimeout(_warnTimer);
        clearInterval(_warnCountdown);
        hideIdleOverlay();
        _idleTimer = setTimeout(showIdleWarning, IDLE_TIMEOUT_SECONDS * 1000);
    };

    ['mousemove','touchstart','keydown','click'].forEach(e =>
        document.addEventListener(e, reset, { passive: true })
    );
    reset(); // start immediately
}

function showIdleWarning() {
    let remaining = IDLE_WARN_SECONDS;
    const overlay = document.getElementById('idleOverlay');
    const counter = document.getElementById('idleCounter');
    if (overlay) overlay.style.display = 'flex';

    _warnCountdown = setInterval(() => {
        remaining--;
        if (counter) counter.textContent = remaining;
        if (remaining <= 0) {
            clearInterval(_warnCountdown);
            clearSession();
            window.location.href = getRootPath() + 'index.php';
        }
    }, 1000);
}

function hideIdleOverlay() {
    const overlay = document.getElementById('idleOverlay');
    if (overlay) overlay.style.display = 'none';
}

// Returns relative path to root depending on current page depth
function getRootPath() {
    return window.location.pathname.split('/').length > 2 ? '../' : '';
}
```

---

### 4.3 `includes/header.php` (REQ-14)

```php
<?php
// Accepts optional $pageTitle variable from the including page
$pageTitle = $pageTitle ?? 'HealthKiosk';
?>
<header class="header">
  <div class="logo">
    <div class="logo-icon">🏥</div>
    <div class="logo-name">HealthKiosk</div>
  </div>
  <div class="header-right">
    <div class="clock-block">
      <div class="clock" id="clock">--:--</div>
      <div class="clock-date" id="date">---</div>
    </div>
  </div>
</header>
```

Pages that need a step indicator or WS badge add those elements after the include.

---

## 5. Idle Timeout Overlay (REQ-18)

Every page (except `index.php`) includes this overlay in its HTML. It is hidden by default and shown by `showIdleWarning()` in `app.js`:

```html
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
```

`startIdleTimer()` is called at the bottom of each page's `<script>` block.

---

## 6. Dashboard Step Completion (REQ-20)

`home.php` runs this on `DOMContentLoaded`:

```js
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
```

CSS additions to `home.php` (page-specific):
```css
.card.done  { border-left-color: #16a34a; }
.card.done::after { content: '✓'; position: absolute; top: 6px; right: 8px;
                    color: #16a34a; font-weight: 700; font-size: 12px; }
.card.disabled { opacity: 0.45; cursor: not-allowed; }
```

Each card gets an `id` attribute: `id="card-id"`, `id="card-face"`, etc.

---

## 7. Session Clear Flow (REQ-19)

After printing (or skipping print), `results/print.php` shows:

```html
<div class="btn-row" id="postPrintActions" style="display:none;">
  <button class="btn-done" onclick="newPatient()">✓ Done — New Patient</button>
</div>
```

```js
function newPatient() {
    clearSession();
    window.location.href = '../index.php';
}
```

The "Skip / Done" button is always visible:
```html
<button class="btn-back" onclick="newPatient()">Skip / Done</button>
```

After `clearSession()`, if the user presses Back, `home.php` detects no `patient_id` in `sessionStorage` and shows all cards as incomplete (no stale data visible).

---

## 8. Face Image Storage (REQ-11)

### Upload directory

```
uploads/
└── faces/
    ├── .htaccess       ← deny all direct access
    ├── 1.jpg
    ├── 2.jpg
    └── ...
```

`.htaccess` content:
```apache
Order Deny,Allow
Deny from all
```

### Serving face images

Since direct browser access is blocked, `results/summary.php` and `results/print.php` serve the image through a small PHP proxy:

**`api/face_image.php`** (new file):
```php
<?php
require_once 'config.php';
// Token check...
$patient_id = intval($_GET['patient_id'] ?? 0);
$path = __DIR__ . '/../uploads/faces/' . $patient_id . '.jpg';
if (!$patient_id || !file_exists($path)) {
    http_response_code(404); exit;
}
header('Content-Type: image/jpeg');
header('Cache-Control: private, max-age=3600');
readfile($path);
```

Usage in summary/print pages:
```html
<img src="../api/face_image.php?patient_id=<?= $patient_id ?>" alt="Patient photo">
```

Or via JS when `patient_id` is in `sessionStorage`:
```js
const pid = getSession('patient_id');
if (pid) document.getElementById('facePhoto').src = `../api/face_image.php?patient_id=${pid}`;
```

---

## 9. Sensor Config (REQ-17)

### `sensor_config.json`

```json
{
  "weight": {
    "data_pin": 5,
    "clock_pin": 6,
    "scale_factor": 23000,
    "tare_samples": 25,
    "stability_threshold": 0.3
  },
  "height": {
    "trigger_pin": 23,
    "echo_pin": 24,
    "sensor_mount_height_cm": 214.0,
    "num_readings": 10,
    "countdown_seconds": 5
  },
  "temperature": {
    "serial_port": "/dev/serial0",
    "baud_rate": 9600,
    "read_timeout_seconds": 10
  },
  "camera": {
    "resolution": [1240, 720],
    "capture_delay_seconds": 5
  }
}
```

### `server.py` loading pattern

```python
import json, os

CONFIG_PATH = os.path.join(os.path.dirname(__file__), 'sensor_config.json')

def load_config():
    with open(CONFIG_PATH, 'r') as f:
        return json.load(f)

CFG = load_config()

# Usage example:
# DATA_PIN  = CFG['weight']['data_pin']
# CLOCK_PIN = CFG['weight']['clock_pin']
# SCALE_FACTOR = CFG['weight']['scale_factor']
```

---

## 10. Environment Files

### `.env`
```
DB_HOST=localhost
DB_NAME=medical_kiosk
DB_USER=kiosk_user
DB_PASS=change_this_password
KIOSK_API_TOKEN=change_this_token_to_a_random_string
```

### `.env.example`
```
# Copy this file to .env and fill in your values
# Never commit .env to version control

DB_HOST=localhost
DB_NAME=medical_kiosk
DB_USER=kiosk_user          # create a dedicated MySQL user, not root
DB_PASS=                    # set a strong password
KIOSK_API_TOKEN=            # generate with: openssl rand -hex 32
```

### `.gitignore`
```
.env
uploads/faces/
sensor_config.json
```

---

## 11. Measurement Pages — Standardized Save Pattern (REQ-5, REQ-16)

All 5 measurement pages follow this identical pattern after the fix:

```js
async function saveDone() {
    const value   = document.getElementById('valueEl').textContent;
    const pid     = getSession('patient_id');
    const rid     = getSession('record_id');
    const field   = 'weight_kg';   // changes per page

    // 1. Save to sessionStorage immediately
    setSession('weight', value);

    // 2. Try to save to DB
    const errorBanner = document.getElementById('errorBanner');
    try {
        const json = await apiFetch('../api/save_vitals.php', {
            patient_id: parseInt(pid),
            record_id:  parseInt(rid),
            [field]:    parseFloat(value)
        });

        if (!json.success) throw new Error(json.error);

        // 3. Success — show overlay and redirect
        document.getElementById('success').style.display = 'flex';
        setTimeout(() => { window.location.href = '../home.php'; }, 1800);

    } catch (e) {
        // 4. Failure — show error banner, do NOT redirect
        errorBanner.textContent = '⚠️ Could not save to database. ' + e.message;
        errorBanner.style.display = 'block';
        document.getElementById('retryBtn').style.display = 'block';
    }
}
```

Error banner HTML (added to each measurement page):
```html
<div id="errorBanner" style="display:none; background:#fee2e2; border:1px solid #fca5a5;
     border-radius:8px; padding:10px 14px; font-size:13px; color:#dc2626; margin-top:10px;">
</div>
<button id="retryBtn" style="display:none;" class="btn-start" onclick="saveDone()">
  ↺ Retry Save
</button>
```

---

## 12. Print Confirmation Flow (REQ-21)

`results/print.php` page states:

```
State 1 (default): Receipt preview visible
  → "🖨️ Print Receipt" button  → sends WS print command → State 2
  → "Skip / Done" button       → clearSession() → index.php

State 2 (printing): Button shows spinner, "Printing..."
  → WS responds print_done     → State 3
  → WS responds print_error    → show error toast, return to State 1
  → 15s timeout                → show warning toast, return to State 1

State 3 (done): "✓ Printed!" message
  → "Done — New Patient" button → clearSession() → index.php
```

No auto-print on page load. Print is only triggered by explicit button press.

---

## 13. CORS Restriction (REQ-9)

All API files replace:
```php
header('Access-Control-Allow-Origin: *');
```
with:
```php
header('Access-Control-Allow-Origin: http://localhost');
```

---

## 14. Summary of New Files

| File | Purpose |
|------|---------|
| `assets/style.css` | Shared CSS — eliminates ~150 lines of duplication per page |
| `assets/app.js` | Shared JS — clock, toast, session helpers, idle timer, apiFetch |
| `includes/header.php` | Shared header HTML — logo + clock |
| `api/face_image.php` | Serves face images through PHP (bypasses .htaccess block) |
| `uploads/faces/.htaccess` | Blocks direct browser access to uploaded images |
| `.env` | DB credentials + API token (not committed) |
| `.env.example` | Documented template for `.env` |
| `.gitignore` | Excludes sensitive files from version control |
| `sensor_config.json` | Hardware calibration values for `server.py` |
| `sensor_config.example.json` | Documented defaults for sensor config |
