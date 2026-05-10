# HealthKiosk — System Improvements Spec

## Overview

This spec covers all fixes and improvements identified during the project audit in `findings.md`. The goal is to make the existing system work correctly, securely, and be easier to maintain — without changing the tech stack or adding new features.

Reference: #[[file:findings.md]]

---

## Scope

**In scope:**
- Fixing critical bugs that prevent vitals from being saved
- Fixing broken redirects and wrong API paths
- Securing database credentials and API endpoints
- Cleaning up duplicated code (CSS, HTML, JS)
- Improving UX with idle timeout, session reset, and step tracking

**Out of scope:**
- Switching databases (Firebase, PostgreSQL, Supabase) — MySQL stays
- Adding an admin dashboard or reporting interface
- Multi-kiosk sync or cloud backup
- HTTPS/WSS configuration (requires server-level setup outside this codebase)
- Adding new measurement types or features

---

## Phases

| Phase | Focus | Requirements |
|-------|-------|--------------|
| 1 | Critical Bug Fixes | REQ-1 through REQ-7 |
| 2 | Security Hardening | REQ-8 through REQ-12 |
| 3 | Code Quality | REQ-13 through REQ-17 |
| 4 | UX Improvements | REQ-18 through REQ-21 |

Full requirements are in `requirements.md`. Tasks will be created after requirements are reviewed and approved.

---

## Files Affected

| File | Action |
|------|--------|
| `api/config.php` | Modify — use `getenv()` for credentials |
| `api/save_vitals.php` | Rewrite — currently has wrong content |
| `api/save_patient.php` | Modify — remove PHP session, add validation, fix face image |
| `api/get_record.php` | Modify — fix column name, fix config path |
| `database.sql` | Modify — fix column names, add missing columns, update face_image type |
| `db_config.php` | Delete — duplicate config |
| `patient/save_patient.php` | Delete — duplicate of `api/save_patient.php` |
| `measurements/height.php` | Modify — fix redirect, fix API path, add error UI |
| `measurements/weight.php` | Modify — fix API path, add error UI |
| `measurements/temperature.php` | Modify — fix API path, add error UI |
| `measurements/oximeter.php` | Modify — fix API path, add error UI |
| `measurements/bloodpressure.php` | Modify — fix API path, add error UI |
| `patient/scanid.php` | Modify — add token header, use shared CSS/header |
| `patient/facecapture.php` | Modify — remove base64 from sessionStorage, add token header |
| `results/summary.php` | Modify — add step completion check, use shared CSS/header |
| `results/print.php` | Modify — add confirmation, add session clear button |
| `home.php` | Modify — add step completion badges |
| `server.py` | Modify — read hardware config from `sensor_config.json` |
| `.env` | Create — DB credentials and API token |
| `.env.example` | Create — documentation template |
| `.gitignore` | Create — exclude `.env` and `uploads/` |
| `assets/style.css` | Create — shared CSS |
| `assets/app.js` | Create — shared JS (clock, toast, session helpers, idle timeout) |
| `includes/header.php` | Create — shared header HTML |
| `uploads/faces/` | Create — face image storage |
| `uploads/faces/.htaccess` | Create — block direct browser access |
| `sensor_config.json` | Create — hardware calibration values |
| `sensor_config.example.json` | Create — documented defaults |
