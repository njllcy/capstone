can we# HealthKiosk — Implementation Tasks

---

## Phase 1 — Critical Bug Fixes

- [x] 1. Update `database.sql` — fix schema
  - [x] 1.1 Rename `patients.id` to `patients.patient_id` as the primary key
  - [x] 1.2 Add `updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` to `patients`
  - [x] 1.3 Change `patients.face_image` column type from `LONGTEXT` to `VARCHAR(255)`
  - [x] 1.4 Add `visit_date DATE` column to `health_records`
  - [x] 1.5 Add `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP` column to `health_records`
  - [x] 1.6 Update the `health_records` foreign key to reference `patients(patient_id)`
  - [x] 1.7 Rename `health_records.id` to `health_records.record_id` as the primary key

- [x] 2. Rewrite `api/save_vitals.php`
  - [x] 2.1 Replace the entire file content — remove all patient INSERT logic
  - [x] 2.2 Accept POST JSON with `patient_id`, `record_id`, and optional vital fields: `weight_kg`, `height_cm`, `temperature_c`, `spo2_percent`, `systolic_bp`, `diastolic_bp`, `pulse_bpm`
  - [x] 2.3 Validate that `patient_id` and `record_id` are present non-zero integers; return error if missing
  - [x] 2.4 Build a dynamic SET clause — only include fields that are present in the request body
  - [x] 2.5 Auto-calculate `bmi` when both `weight_kg` and `height_cm` are present: `bmi = weight / (height_m²)`
  - [x] 2.6 Evaluate and set `temp_status`: Normal (36.1–37.2°C), Low (<36.1), Fever (>37.2)
  - [x] 2.7 Evaluate and set `spo2_status`: Normal (≥95%), Low (90–94%), Critical (<90%)
  - [x] 2.8 Evaluate and set `bp_status`: Normal (<120 systolic), Elevated (120–129), High (≥130)
  - [x] 2.9 Evaluate and set `pulse_status`: Normal (60–100 bpm), Low (<60), High (>100)
  - [x] 2.10 Evaluate and set `bmi_status`: Underweight (<18.5), Normal (18.5–24.9), Overweight (25–29.9), Obese (≥30)
  - [x] 2.11 Execute `UPDATE health_records SET ... WHERE record_id = ? AND patient_id = ?`
  - [x] 2.12 Return `{ success: true, record_id: <id> }` on success, `{ success: false, error: "..." }` on failure

- [x] 3. Consolidate database config — fix `api/config.php` and remove `db_config.php`
  - [x] 3.1 Update `api/config.php` to read credentials via `getenv()` with fallbacks (see design §3.1)
  - [x] 3.2 Add `define('KIOSK_API_TOKEN', getenv('KIOSK_API_TOKEN') ?: '')` to `api/config.php`
  - [x] 3.3 Update `api/save_patient.php`: change `require_once '../db_config.php'` to `require_once 'config.php'`
  - [x] 3.4 Update `api/get_record.php`: change `require_once '../db_config.php'` to `require_once 'config.php'`
  - [x] 3.5 Delete root-level `db_config.php`

- [x] 4. Fix `api/save_patient.php` — remove PHP session, fix column references
  - [x] 4.1 Remove `session_start()` and all `$_SESSION` reads and writes
  - [x] 4.2 Update the duplicate-check query to use `patients.patient_id` (not `id`)
  - [x] 4.3 Update the UPDATE query to use `WHERE patient_id = ?` (not `WHERE id = ?`)
  - [x] 4.4 Ensure `patient_id` and `record_id` are both returned in the JSON response
  - [x] 4.5 Update the `health_records` INSERT to include `visit_date` and `created_at` columns

- [x] 5. Fix `api/get_record.php` — column name and config path
  - [x] 5.1 Change `require_once '../db_config.php'` to `require_once 'config.php'`
  - [x] 5.2 Verify the patients query uses `WHERE patient_id = ?` (already correct, confirm no bare `id` references)
  - [x] 5.3 Update health records query to use `WHERE record_id = ?` (not `WHERE id = ?`)

- [x] 6. Fix broken redirects and wrong API paths in all measurement pages
  - [x] 6.1 In `measurements/height.php`: change `window.location.href = "home.html"` to `window.location.href = "../home.php"`
  - [x] 6.2 In `measurements/height.php`: change `fetch("save_vitals.php", ...)` to `fetch("../api/save_vitals.php", ...)`
  - [x] 6.3 In `measurements/weight.php`: change `fetch("save_vitals.php", ...)` to `fetch("../api/save_vitals.php", ...)` and redirect to `../home.php`
  - [x] 6.4 In `measurements/bloodpressure.php`: change `fetch("save_vitals.php", ...)` to `fetch("../api/save_vitals.php", ...)` and fix redirect to `../home.php`
  - [x] 6.5 In `measurements/temperature.php`: change `fetch("save_vitals.php", ...)` to `fetch("../api/save_vitals.php", ...)` and fix redirect to `../home.php`
  - [x] 6.6 In `measurements/oximeter.php`: change `fetch("save_vitals.php", ...)` to `fetch("../api/save_vitals.php", ...)` and fix redirect to `../home.php`

- [x] 7. Fix session handling in `patient/scanid.php` and `patient/facecapture.php`
  - [x] 7.1 In `patient/scanid.php`: confirm the `fetch()` call targets `../api/save_patient.php` (not `save_patient.php` in the same folder)
  - [x] 7.2 In `patient/scanid.php`: store `json.patient_id` and `json.record_id` from the response into `sessionStorage`
  - [x] 7.3 In `patient/facecapture.php`: read `patient_id` from `sessionStorage` and include it in the POST body to `save_face.php` (or `../api/save_patient.php`)
  - [x] 7.4 In `patient/facecapture.php`: remove any code that stores the raw base64 image in `sessionStorage`

- [x] 8. Remove duplicate `patient/save_patient.php`
  - [x] 8.1 Confirm `patient/scanid.php` calls `../api/save_patient.php` (after task 7.1)
  - [x] 8.2 Delete `patient/save_patient.php`

---

## Phase 2 — Security

- [x] 9. Create environment files and update `api/config.php`
  - [x] 9.1 Create `.env` at project root with keys: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `KIOSK_API_TOKEN`
  - [x] 9.2 Create `.env.example` with the same keys and placeholder values plus comments (see design §10)
  - [x] 9.3 Create `.gitignore` excluding `.env`, `uploads/faces/`, and `sensor_config.json`
  - [x] 9.4 Verify `api/config.php` now reads all credentials via `getenv()` (completed in task 3.1)

- [x] 10. Add API token check to all endpoints
  - [x] 10.1 Add the token validation block to `api/save_patient.php` immediately after `require_once 'config.php'` (see design §3.2)
  - [x] 10.2 Add the token validation block to `api/save_vitals.php`
  - [x] 10.3 Add the token validation block to `api/get_record.php`
  - [x] 10.4 Add the token validation block to `api/face_image.php` (new file, created in task 13)

- [x] 11. Restrict CORS headers on all API endpoints
  - [x] 11.1 In `api/save_patient.php`: change `Access-Control-Allow-Origin: *` to `http://localhost`
  - [x] 11.2 In `api/save_vitals.php`: change `Access-Control-Allow-Origin: *` to `http://localhost`
  - [x] 11.3 In `api/get_record.php`: change `Access-Control-Allow-Origin: *` to `http://localhost`

- [x] 12. Move face images to the filesystem
  - [x] 12.1 Create the `uploads/faces/` directory
  - [x] 12.2 Create `uploads/faces/.htaccess` with `Order Deny,Allow` / `Deny from all`
  - [x] 12.3 In `api/save_patient.php`: add face image decode-and-save logic — strip data URI prefix, `base64_decode`, `file_put_contents` to `uploads/faces/{patient_id}.jpg`
  - [x] 12.4 In `api/save_patient.php`: store only the file path string in `patients.face_image` (not the base64 data)
  - [x] 12.5 Create `api/face_image.php` — PHP proxy that reads `?patient_id=`, resolves the file path, and serves the image with `Content-Type: image/jpeg` (see design §8)
  - [x] 12.6 In `results/summary.php`: replace base64 `<img>` src with `../api/face_image.php?patient_id=<pid>` loaded from `sessionStorage`
  - [x] 12.7 In `results/print.php`: replace base64 `<img>` src with `../api/face_image.php?patient_id=<pid>` loaded from `sessionStorage`

- [x] 13. Add server-side input validation to `api/save_patient.php`
  - [x] 13.1 Validate `gender` is exactly `Male` or `Female`; return error if not
  - [x] 13.2 Validate `date_of_birth` is a valid date and not in the future; return error if not
  - [x] 13.3 Validate `age` is an integer between 0 and 150; return error if not
  - [x] 13.4 Validate `phone` matches `/^[\d\s\-+()]*$/` and is max 20 chars; return error if not
  - [x] 13.5 Validate `barangay` matches `/^[a-zA-Z0-9\s]*$/` and is max 100 chars; return error if not
  - [x] 13.6 Return `{ success: false, error: "Validation failed: <field>" }` for any violation

---

## Phase 3 — Code Quality

- [x] 14. Create `assets/style.css` with all shared styles
  - [x] 14.1 Create the `assets/` directory
  - [x] 14.2 Extract the `:root` CSS variables block into `assets/style.css`
  - [x] 14.3 Extract the CSS reset, `.header`, `.logo`, `.logo-icon`, `.logo-name`, `.clock-block`, `.clock`, `.clock-date` styles
  - [x] 14.4 Extract `.main`, `.card`, `.dot`, `.step-progress-top`, `.step-bar-top` styles
  - [x] 14.5 Extract `.step`, `.step-icon`, `.instruction-box`, `.fade-section` styles
  - [x] 14.6 Extract all button styles: `.btn-next`, `.btn-start`, `.btn-done`, `.btn-manual`, `button:disabled`
  - [x] 14.7 Extract `.saving-overlay`, `.saving-box`, `.spinner`, `.success-overlay`, `.success-box`, `.check` styles
  - [x] 14.8 Extract all `@keyframes` (spin, pop, scaleIn, float, pulse, wobble) and responsive `@media` breakpoints
  - [x] 14.9 In all pages inside `measurements/` and `patient/` and `results/`: replace the extracted CSS block with `<link rel="stylesheet" href="../assets/style.css">`
  - [x] 14.10 In root-level pages (`home.php`): replace the extracted CSS block with `<link rel="stylesheet" href="assets/style.css">`
  - [x] 14.11 Verify each page still renders correctly — no visual regressions

- [x] 15. Create `includes/header.php` and replace duplicated header HTML
  - [x] 15.1 Create the `includes/` directory
  - [x] 15.2 Create `includes/header.php` with the shared header HTML block (logo + clock divs) as shown in design §4.3
  - [x] 15.3 In all pages inside `measurements/`, `patient/`, `results/`: replace the `<header>` block with `<?php include '../includes/header.php'; ?>`
  - [x] 15.4 In root-level pages (`home.php`): replace the `<header>` block with `<?php include 'includes/header.php'; ?>`
  - [x] 15.5 For pages that need a step indicator or WS badge in the header, add those elements immediately after the include call

- [x] 16. Create `assets/app.js` and replace duplicated JavaScript
  - [x] 16.1 Create `assets/app.js` with the full content from design §4.2: constants, `updateClock()`, `showToast()`, session helpers (`getSession`, `setSession`, `clearSession`, `SESSION_KEYS`), `apiFetch()`, and idle timer functions (`startIdleTimer`, `showIdleWarning`, `hideIdleOverlay`, `getRootPath`)
  - [x] 16.2 In all pages inside `measurements/`, `patient/`, `results/`: add `<script src="../assets/app.js"></script>` in the `<head>` and remove the local `updateClock()` and clock `setInterval` code
  - [x] 16.3 In root-level pages (`home.php`): add `<script src="assets/app.js"></script>` and remove local clock code
  - [x] 16.4 In all pages: replace `fetch(url, { headers: {...} })` calls with `apiFetch(url, body)` from `app.js`
  - [x] 16.5 In all pages: replace `sessionStorage.getItem(key)` / `sessionStorage.setItem(key, val)` calls with `getSession(key)` / `setSession(key, val)`
  - [x] 16.6 Add `<meta name="kiosk-token" content="<?= htmlspecialchars(getenv('KIOSK_API_TOKEN') ?: '') ?>">` to the `<head>` of every PHP page

- [x] 17. Add visible error UI to all measurement pages
  - [x] 17.1 Add the error banner HTML and retry button to `measurements/height.php` (see design §11)
  - [x] 17.2 Add the error banner HTML and retry button to `measurements/weight.php`
  - [x] 17.3 Add the error banner HTML and retry button to `measurements/bloodpressure.php`
  - [x] 17.4 Add the error banner HTML and retry button to `measurements/temperature.php`
  - [x] 17.5 Add the error banner HTML and retry button to `measurements/oximeter.php`
  - [x] 17.6 In each measurement page: update the `saveDone()` function to use the try/catch pattern from design §11 — show error banner on failure, only redirect on success

- [ ] 18. Move sensor calibration to `sensor_config.json`
  - [x] 18.1 Create `sensor_config.json` at project root with all hardware constants (see design §9)
  - [x] 18.2 Create `sensor_config.example.json` with the same structure and documented comments
  - [x] 18.3 In `server.py`: add `load_config()` function that reads `sensor_config.json` at startup and stores result in `CFG`
  - [x] 18.4 In `server.py` `handle_weight`: replace hardcoded `DATA_PIN = 5`, `CLOCK_PIN = 6`, `scale_factor = 23000`, `tare_samples = 25`, `stability_threshold = 0.3` with `CFG['weight'][...]` values
  - [x] 18.5 In `server.py` `handle_height`: replace hardcoded `TRIG = 23`, `ECHO = 24`, `SENSOR_HEIGHT = 214.0` and countdown/reading counts with `CFG['height'][...]` values
  - [x] 18.6 In `server.py` `handle_temperature`: replace hardcoded `'/dev/serial0'`, `baudrate=9600`, timeout with `CFG['temperature'][...]` values
  - [x] 18.7 In `server.py` `handle_face` and `handle_scanid`: replace hardcoded camera resolution with `CFG['camera'][...]` values

---

## Phase 4 — UX Improvements

- [ ] 19. Add idle timeout overlay to all pages except `index.php`
  - [x] 19.1 Add the idle overlay HTML block (see design §5) to `home.php`
  - [x] 19.2 Add the idle overlay HTML block to all 5 pages in `measurements/`
  - [x] 19.3 Add the idle overlay HTML block to `patient/scanid.php` and `patient/facecapture.php`
  - [x] 19.4 Add the idle overlay HTML block to `results/summary.php` and `results/print.php`
  - [x] 19.5 In each of the above pages: call `startIdleTimer()` at the end of the page's `<script>` block (after `app.js` is loaded)

- [x] 20. Add session clear and "New Patient" flow to `results/print.php`
  - [x] 20.1 Add a "Skip / Done" button that calls `newPatient()` — always visible on the page
  - [x] 20.2 Add a hidden `#postPrintActions` div containing a "Done — New Patient" button
  - [x] 20.3 Implement `newPatient()` function: calls `clearSession()` then redirects to `../index.php`
  - [x] 20.4 After a successful print (`print_done` WS message), show `#postPrintActions` and hide the "Print Receipt" button
  - [x] 20.5 Ensure the print button does NOT auto-trigger on page load — it must wait for explicit user click

- [x] 21. Add step completion badges to `home.php` dashboard
  - [x] 21.1 Add `id` attributes to all 8 dashboard cards: `id="card-id"`, `id="card-face"`, `id="card-height"`, `id="card-weight"`, `id="card-spo2"`, `id="card-temp"`, `id="card-bp"`, `id="card-summary"`
  - [x] 21.2 Add `position: relative` to `.card` CSS so the `::after` badge can be positioned absolutely
  - [x] 21.3 Add page-specific CSS for `.card.done`, `.card.done::after`, and `.card.disabled` (see design §6)
  - [x] 21.4 Add the `stepMap` completion check script on `DOMContentLoaded` (see design §6)
  - [x] 21.5 Disable the summary card (`onclick = null`, add `.disabled` class) if fewer than 3 vitals are recorded, and set `title` tooltip

- [x] 22. Implement print confirmation state machine in `results/print.php`
  - [x] 22.1 Verify print is NOT triggered on page load (already the case — confirm and document)
  - [x] 22.2 Ensure the "Print Receipt" button calls `handlePrint()` which sends the WS print command (already exists — verify it matches the 3-state design from design §12)
  - [x] 22.3 On `print_done` WS message: update button text to "✓ Printed!", show `#postPrintActions`, hide the print button
  - [x] 22.4 On `print_error` WS message: show error toast, reset button to "🖨️ Print Receipt" (State 1)
  - [x] 22.5 On 15-second timeout with no WS response: show warning toast, reset button to State 1
