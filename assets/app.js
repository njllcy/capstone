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

function getRootPath() {
    return window.location.pathname.split('/').length > 2 ? '../' : '';
}
