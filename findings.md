# HealthKiosk — Project Findings & Recommendations

> Scanned: May 9, 2026  
> Status: Read-only analysis — no files modified

---

## 1. Project Overview

**HealthKiosk** is a self-service patient vital signs station built for a Raspberry Pi kiosk. Patients scan their ID, capture a face photo, then take 5 measurements (height, weight, SpO₂, temperature, blood pressure). Results are printed as a thermal receipt.

**Tech Stack:**
- Frontend: Vanilla HTML/CSS/JS (no framework)
- Backend: PHP 7+ with PDO
- Real-time: Python asyncio WebSocket server (`server.py`) on port 8765
- Database: MySQL (`medical_kiosk`)
- Hardware: Raspberry Pi GPIO, HX711, ultrasonic sensor, serial UART, PiCamera2, Tesseract OCR

---

## 2. Database Connection — Current State

**File:** `api/config.php`

```php
$host   = 'localhost';
$dbname = 'medical_kiosk';
$user   = 'root';
$pass   = '';   // ← empty password
```

- Uses PDO with prepared statements (good)
- Error mode set to EXCEPTION (good)
- But: root user, empty password, hardcoded credentials (bad)

**There is also a second config file** at the project root: `db_config.php` (referenced by `api/get_record.php` and `patient/save_patient.php` via `require_once '../db_config.php'`). This is a duplicate/inconsistency — two config files doing the same thing.

---

## 3. Database Migration Recommendation

### Should you use Firebase?

**Short answer: Not recommended for this project. Stick with MySQL (or upgrade to a managed MySQL/PostgreSQL).**

Here's why:

| Factor | Firebase (Firestore) | MySQL (current) |
|--------|---------------------|-----------------|
| Data model | NoSQL (documents) | Relational (tables with FK) |
| Your data | Structured, relational (patients → health_records) | ✅ Perfect fit |
| Queries | Limited (no JOINs, no complex filters) | ✅ Full SQL support |
| PHP backend | Requires Firebase Admin SDK (extra setup) | ✅ Native PDO support |
| Offline/local | Requires internet connection | ✅ Works fully offline |
| Kiosk use case | Overkill, adds latency | ✅ Local = fast |
| Cost | Free tier limited, then pay-per-read | ✅ Free, self-hosted |
| HIPAA/privacy | Data leaves your server (Google cloud) | ✅ Data stays local |

**Firebase would be a step backward** for this specific project. Your data is relational (patients have health records, records have foreign keys), your kiosk is likely offline-capable, and you're already using PHP which works natively with MySQL.

### What I actually recommend:

**Option A — Keep MySQL, just improve the connection setup (easiest)**
- Move credentials to a `.env` file or PHP constants file outside the web root
- Use a non-root DB user with limited permissions
- This is the lowest-effort, highest-security improvement

**Option B — Upgrade to PostgreSQL (if you want a modern open-source DB)**
- Better JSON support, stricter data types, better for future scaling
- PDO supports it with minimal code changes

**Option C — Use PlanetScale or Supabase (if you want cloud + MySQL/PostgreSQL)**
- Supabase = PostgreSQL with a REST API, free tier, good for remote access
- Only makes sense if you need to sync data across multiple kiosk units

---

## 4. Bugs & Code Issues

### 🔴 Critical

**B1 — `api/save_vitals.php` has wrong content**
- The file is named `save_vitals.php` but its content is a copy of `save_patient.php`
- It inserts a patient record instead of saving vitals
- This means vitals (weight, height, BP, etc.) are never actually saved to the database
- All measurement pages call this endpoint and it silently fails or creates duplicate patients

**B2 — Two conflicting config files**
- `api/config.php` and `db_config.php` (root) both define `$pdo`
- `api/save_patient.php` uses `require_once 'config.php'` (correct path)
- `api/get_record.php` uses `require_once '../db_config.php'` (root file)
- `patient/save_patient.php` (a third copy?) uses `require_once '../db_config.php'`
- There appear to be duplicate API files — one in `/api/` and one in `/patient/`

**B3 — `patient_id` column name mismatch**
- `database.sql` defines the primary key as `id` in the `patients` table
- `api/get_record.php` queries `WHERE patient_id = ?` — this column doesn't exist
- `api/save_patient.php` also references `patient_id` as a column name
- This will cause all record lookups to fail

**B4 — `health_records` table missing `visit_date` and `created_at` columns**
- `api/save_patient.php` (the one in `/api/`) inserts `visit_date` and `created_at` into `health_records`
- But `database.sql` only defines `recorded_at` — no `visit_date` or `created_at`
- This INSERT will fail with a column not found error

### 🟡 Medium

**B5 — `measurements/height.php` redirects to `home.html` instead of `home.php`**
- Line: `window.location.href = "home.html";`
- The actual file is `home.php` — this redirect will 404

**B6 — `save_vitals.php` is called with a relative path from measurement pages**
- `measurements/height.php` calls `fetch("save_vitals.php", ...)` — wrong relative path
- Should be `../api/save_vitals.php`

**B7 — Session handling inconsistency**
- `api/save_patient.php` uses PHP `$_SESSION` to track patient
- But the frontend uses `sessionStorage` (browser) to pass `patient_id` between pages
- These are two different session mechanisms — they don't talk to each other
- If the PHP session expires or isn't started, the face image update logic breaks

---

## 5. Security Issues

### 🔴 Critical

**S1 — Empty database root password**
- `$pass = ''` with `$user = 'root'`
- Anyone with local access can connect to the DB with no credentials

**S2 — No authentication on any API endpoint**
- `api/save_patient.php`, `api/save_vitals.php`, `api/get_record.php` are all publicly accessible
- No token, no session check, no IP restriction
- Anyone on the same network can read or write patient data

### 🟡 High

**S3 — CORS wildcard on all API endpoints**
- `header('Access-Control-Allow-Origin: *')` on every API file
- Allows any website to make requests to your API

**S4 — Face images stored as base64 in LONGTEXT**
- No encryption at rest
- A single patient record can be several MB
- Bloats the DB and slows queries
- Better: save to filesystem, store only the file path in DB

**S5 — Patient data in browser `sessionStorage`**
- `patient_id`, `face_image` (base64!), all vitals stored in sessionStorage
- Accessible via browser DevTools by anyone who touches the kiosk
- Face image especially should never be in sessionStorage

**S6 — WebSocket uses `ws://` (unencrypted)**
- All sensor data and patient info sent over unencrypted WebSocket
- Should use `wss://` in production

**S7 — No input sanitization on server for phone/barangay fields**
- Only `trim()` is applied — no length limits, no format validation

---

## 6. Architecture & Code Quality Issues

### 🟡 Medium

**A1 — Massive CSS duplication across all pages**
- Every `.php` file in `measurements/`, `patient/`, `results/` has the same 100+ lines of CSS
- The header, card, button, and clock styles are copy-pasted everywhere
- Should be extracted to a shared `assets/style.css`

**A2 — Header HTML duplicated across all pages**
- The logo + clock header block is copy-pasted in every file
- Should be a PHP `include` or a JS component

**A3 — Clock JavaScript duplicated across all pages**
- Same `updateClock()` function copy-pasted in every file
- Should be in a shared `assets/app.js`

**A4 — No loading/error state for failed DB saves**
- If `save_vitals.php` fails, the measurement pages silently continue
- The `catch(e){ console.warn(...) }` swallows errors — the user never knows

**A5 — `server.py` has hardcoded sensor calibration values**
- `scale_factor = 23000` for weight, GPIO pins hardcoded
- Should be in a config file (e.g., `config.json` or `.env`) for easy recalibration per unit

**A6 — No patient session reset between visits**
- `sessionStorage` persists until the browser tab closes
- If a patient finishes and the next patient uses the same session, old data leaks
- There's no "clear session" step at the end of the flow (after print)

**A7 — `results/summary.php` and `results/print.php` appear to be the same page**
- Both show a summary/receipt view
- Unclear if one is a preview and one is the print trigger, or if they're duplicates

---

## 7. UX Issues

**U1 — No clear guided flow / step enforcement**
- From `home.php`, a patient can jump to any measurement in any order
- Nothing prevents printing a summary before taking any measurements
- No indication of which steps are complete vs pending

**U2 — No idle/timeout reset**
- If a patient walks away mid-session, the kiosk stays on their data
- Should auto-reset to `index.php` after ~2 minutes of inactivity

**U3 — No confirmation before printing**
- `results/print.php` appears to trigger print immediately
- Should show a preview and ask for confirmation

---

## 8. Missing Files

These files are referenced in the code but not present in the workspace:

| Referenced As | Referenced In | Notes |
|---------------|---------------|-------|
| `save_face.php` | `patient/facecapture.php` | Face image save endpoint |
| `SENSORS/weight.py` | `server.py` | Hardware sensor script |
| `SENSORS/height.py` | `server.py` | Hardware sensor script |
| `SENSORS/temperature.py` | `server.py` | Hardware sensor script |
| `SENSORS/bloodpressure.py` | `server.py` | Hardware sensor script |
| `SENSORS/facecapture.py` | `server.py` | Hardware sensor script |
| `SENSORS/scanid.py` | `server.py` | Hardware sensor script |
| `assets/` folder | — | No shared CSS/JS exists |

---

## 9. Priority Action List

### Fix First (Blockers)
1. Fix `api/save_vitals.php` — it has the wrong content (copy of save_patient)
2. Fix the `patient_id` vs `id` column name mismatch in `database.sql` or the API files
3. Fix `health_records` schema to match what the API actually inserts
4. Fix the `home.html` → `home.php` redirect in `measurements/height.php`
5. Fix the relative path for `save_vitals.php` calls in measurement pages

### Fix Soon (Security)
6. Move DB credentials to `.env` or a file outside the web root
7. Create a dedicated DB user with only INSERT/SELECT/UPDATE on `medical_kiosk`
8. Add a simple API key or IP whitelist to the API endpoints
9. Stop storing face images in sessionStorage — use a temp server-side reference

### Improve (Quality of Life)
10. Extract shared CSS/JS/header into include files
11. Add idle timeout + session reset after each patient visit
12. Add a guided flow with step completion tracking
13. Move sensor calibration values to a config file

---

## 10. Database Migration — Final Recommendation

**Stay with MySQL.** Here's the minimal improvement to the connection:

```php
// api/config.php — improved version (don't modify yet, just a reference)
$host   = getenv('DB_HOST')   ?: 'localhost';
$dbname = getenv('DB_NAME')   ?: 'medical_kiosk';
$user   = getenv('DB_USER')   ?: 'kiosk_user';   // dedicated user, not root
$pass   = getenv('DB_PASS')   ?: '';              // set via .env

// .env file (outside web root, never committed to git)
// DB_HOST=localhost
// DB_NAME=medical_kiosk
// DB_USER=kiosk_user
// DB_PASS=your_strong_password_here
```

This gives you:
- No hardcoded credentials in source code
- Easy to change per deployment
- Works with the existing PDO setup — zero other changes needed

---

*Let me know which issues you want to tackle first and I'll start making the changes.*
