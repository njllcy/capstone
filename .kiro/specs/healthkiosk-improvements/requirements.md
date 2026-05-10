# HealthKiosk — Requirements

> Based on findings in `findings.md`. Review and approve these before implementation begins.

---

## Phase 1 — Critical Bug Fixes

### REQ-1: Rewrite `api/save_vitals.php`
**Problem:** The file contains a copy of `save_patient.php`. It inserts a new patient instead of saving vitals, so weight, height, BP, temperature, and SpO₂ are never stored in the database.

**What it should do:**
- Accept a POST request with JSON body containing: `patient_id`, `record_id`, and any combination of vital fields (`weight_kg`, `height_cm`, `temperature_c`, `spo2_percent`, `systolic_bp`, `diastolic_bp`, `pulse_bpm`)
- UPDATE the existing `health_records` row — do not insert a new patient
- Auto-calculate `bmi` when both `weight_kg` and `height_cm` are present
- Auto-evaluate and store status flags (`temp_status`, `spo2_status`, `bp_status`, `pulse_status`, `bmi_status`) using standard clinical thresholds
- Return `{ success: true, record_id: <id> }` on success, `{ success: false, error: "..." }` on failure

---

### REQ-2: Fix the database schema — column name mismatch
**Problem:** `database.sql` defines the patients primary key as `id`, but all API files query `WHERE patient_id = ?`. This means every patient lookup fails.

**What needs to change:**
- Rename `patients.id` → `patients.patient_id` in `database.sql`
- Update the `health_records` foreign key to reference `patients.patient_id`
- All API files must use `patient_id` consistently — no bare `id` references for the patients table

---

### REQ-3: Fix missing columns in `health_records`
**Problem:** `api/save_patient.php` inserts `visit_date` and `created_at` into `health_records`, but those columns don't exist in `database.sql`. Every new visit INSERT fails.

**What needs to change:**
- Add `visit_date DATE` to `health_records` in `database.sql`
- Add `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP` to `health_records`
- Keep the existing `recorded_at` column

---

### REQ-4: Consolidate to one database config file
**Problem:** Two config files exist — `api/config.php` and `db_config.php` at the project root — both defining `$pdo`. Different API files include different ones, making it easy to change one and break the other.

**What needs to change:**
- Keep `api/config.php` as the single canonical config
- Delete root-level `db_config.php`
- Update all `require_once` paths across every API file to point to `api/config.php`

---

### REQ-5: Fix broken redirects and wrong API paths in measurement pages
**Problem (a):** `measurements/height.php` redirects to `home.html` — that file doesn't exist. The correct file is `home.php`.

**Problem (b):** All measurement pages call `fetch("save_vitals.php", ...)` — a relative path that resolves incorrectly from inside the `measurements/` folder. The correct path is `../api/save_vitals.php`.

**What needs to change:**
- Fix the redirect in `height.php` from `home.html` → `../home.php`
- Fix the `fetch()` path in all 5 measurement pages to `../api/save_vitals.php`
- Verify all measurement pages redirect to `../home.php` on completion

---

### REQ-6: Unify session handling — remove PHP `$_SESSION`, use `sessionStorage`
**Problem:** `api/save_patient.php` uses PHP `$_SESSION` to track the current patient, but the frontend uses browser `sessionStorage`. These two mechanisms don't communicate. If the PHP session expires or isn't started, the face image update logic silently breaks.

**What needs to change:**
- Remove `session_start()` and all `$_SESSION` usage from `api/save_patient.php`
- `patient_id` and `record_id` must be returned in the JSON response and stored in `sessionStorage` by the frontend
- `patient/facecapture.php` must pass `patient_id` in the POST body instead of relying on the PHP session
- All pages read `patient_id` and `record_id` exclusively from `sessionStorage`

---

### REQ-7: Remove duplicate `patient/save_patient.php`
**Problem:** There are two versions of the patient save logic — one in `/api/save_patient.php` and one in `/patient/save_patient.php`. It's unclear which one is actually called, and having two means fixes applied to one won't apply to the other.

**What needs to change:**
- Confirm which file the frontend actually calls
- Remove the unused duplicate
- All frontend references must point to the single file in `/api/`

---

## Phase 2 — Security

### REQ-8: Move database credentials out of source code
**Problem:** `api/config.php` hardcodes `user = 'root'` and `pass = ''`. Anyone who reads the source code has full database access.

**What needs to change:**
- Create a `.env` file at the project root with `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- Create a `.env.example` with placeholder values for documentation
- Create `.gitignore` to exclude `.env` from version control
- Update `api/config.php` to read credentials via `getenv()` with safe fallbacks
- The root MySQL user with empty password must no longer be used

---

### REQ-9: Restrict CORS to localhost only
**Problem:** All API endpoints send `Access-Control-Allow-Origin: *`, allowing any website on any domain to make requests to the API.

**What needs to change:**
- Change all API files to `header('Access-Control-Allow-Origin: http://localhost')`
- Remove the wildcard `*` from all CORS headers

---

### REQ-10: Add a simple API token to protect endpoints
**Problem:** All API endpoints are publicly accessible with no authentication. Anyone on the same network can read or write patient data.

**What needs to change:**
- Define a `KIOSK_API_TOKEN` value in `.env`
- All API endpoints check for an `X-Kiosk-Token` request header and return HTTP 401 if it's missing or wrong
- All frontend `fetch()` calls include the token in the `X-Kiosk-Token` header

---

### REQ-11: Move face images to the filesystem
**Problem:** Face images are stored as base64 in a `LONGTEXT` database column. This bloats the database, slows queries, and the raw base64 is also stored in browser `sessionStorage` where it's visible in DevTools.

**What needs to change:**
- `api/save_patient.php` decodes the base64 image and saves it as a `.jpg` file under `uploads/faces/{patient_id}.jpg`
- Create `uploads/faces/.htaccess` to block direct browser access to the folder
- Change `patients.face_image` column from `LONGTEXT` to `VARCHAR(255)` — store only the file path
- `sessionStorage` must no longer hold the raw base64 image — only the `patient_id` reference
- Summary and print pages load the face via `<img src="...">` using the stored path

---

### REQ-12: Add server-side input validation
**Problem:** `save_patient.php` only applies `trim()` to most fields. There is no server-side check on format, length, or allowed values.

**What needs to change:**
- `phone`: max 20 chars, digits/spaces/dashes only (empty is allowed)
- `barangay`: max 100 chars, alphanumeric + spaces (empty is allowed)
- `gender`: must be exactly `Male` or `Female`
- `date_of_birth`: valid date format, must not be in the future
- `age`: integer between 0 and 150
- Return `{ success: false, error: "Validation failed: <field>" }` on any violation

---

## Phase 3 — Code Quality

### REQ-13: Extract shared CSS into one file
**Problem:** The same ~150 lines of CSS (variables, reset, header, card, button, overlay styles) are copy-pasted across all 10+ PHP files. A style change requires editing every file.

**What needs to change:**
- Create `assets/style.css` with all shared styles
- All PHP files link to it instead of repeating the styles inline
- Page-specific styles stay inline in each file
- Visual appearance must be unchanged after extraction

---

### REQ-14: Extract shared header HTML into a PHP include
**Problem:** The logo + clock header block is copy-pasted in every file.

**What needs to change:**
- Create `includes/header.php` with the header HTML
- All pages replace their header block with a single `include` call
- Clock must still update correctly on all pages

---

### REQ-15: Extract shared JavaScript into one file
**Problem:** `updateClock()`, toast helpers, and other utilities are copy-pasted across every page.

**What needs to change:**
- Create `assets/app.js` with: `updateClock()`, clock interval, `showToast(msg)`, and `sessionStorage` helpers (`getSession`, `setSession`, `clearSession`)
- All pages include `assets/app.js` and remove their local copies of these functions

---

### REQ-16: Show visible errors when API saves fail
**Problem:** If `save_vitals.php` fails, measurement pages silently swallow the error with `console.warn` and redirect anyway. The patient and operator never know the data wasn't saved.

**What needs to change:**
- Each measurement page shows a visible red error banner if the API call fails
- The banner includes a "Retry" option
- The page does not redirect to home if the save failed

---

### REQ-17: Move sensor calibration values to a config file
**Problem:** `server.py` has hardcoded values for scale factor, GPIO pin numbers, and serial port paths. Recalibrating a sensor or deploying to a new unit requires editing Python source code.

**What needs to change:**
- Create `sensor_config.json` with all hardware constants: weight scale factor, height sensor GPIO pins, temperature serial port and baud rate, camera resolution
- Create `sensor_config.example.json` with documented default values
- `server.py` reads all hardware config from `sensor_config.json` at startup
- No hardware constants remain hardcoded in `server.py`

---

## Phase 4 — UX

### REQ-18: Add idle timeout with automatic session reset
**Problem:** If a patient walks away mid-session, the kiosk stays on their data indefinitely. The next patient sees the previous patient's information.

**What needs to change:**
- All pages (except `index.php`) detect inactivity for 120 seconds (no touch, mouse, or keyboard events)
- After 120 seconds, show a 10-second countdown warning overlay: "Session ending in 10s..."
- If no interaction during the countdown, clear `sessionStorage` and redirect to `index.php`
- Any user interaction resets the timer
- The timeout duration is a configurable constant in `assets/app.js`

---

### REQ-19: Clear session after a patient's visit ends
**Problem:** `sessionStorage` is never explicitly cleared. If a patient finishes and the next patient uses the same browser session, stale data from the previous visit is still present.

**What needs to change:**
- `results/print.php` has a "Done — New Patient" button
- Clicking it clears all kiosk `sessionStorage` keys and redirects to `index.php`
- A "Skip Print / Done" button is also available for patients who don't want a printout
- Navigating back after clearing redirects to `index.php` — no stale data is shown

---

### REQ-20: Show step completion status on the dashboard
**Problem:** `home.php` shows all 8 cards with no indication of which steps are complete. A patient can go to "Show Summary" before taking any measurements.

**What needs to change:**
- Each card on `home.php` shows a ✓ badge if that step's data exists in `sessionStorage`
- Cards with completed data show a green border
- The "Show Summary" card is visually disabled if fewer than 3 vitals are recorded, with a tooltip explaining why
- This is purely client-side — no server call needed

---

### REQ-21: Add print confirmation step
**Problem:** `results/print.php` appears to trigger printing without any confirmation. A patient who accidentally navigates there would trigger a print job.

**What needs to change:**
- Print is not triggered automatically on page load
- The page shows the receipt preview with a "Print Receipt" button and a "Skip / Done" button
- "Print Receipt" sends the WebSocket print command, waits for confirmation, then shows the "Done — New Patient" button
- "Skip / Done" goes directly to session clear and redirect to `index.php`
