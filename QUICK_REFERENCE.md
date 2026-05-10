# HealthKiosk - Quick Reference Card

---

## 🎯 What Was Done

| Feature | Status | File(s) |
|---------|--------|---------|
| Fixed Icons | ✅ Complete | `home.php` |
| Autonomous Flow | ✅ Complete | `index.php` |
| Height Sensor | ✅ Complete | `server.py`, `height.php` |
| Philippine ID OCR | ✅ Complete | `server.py` |
| Skip Buttons | ✅ Template Ready | `height.php` + guide |
| Error Screens | ✅ Template Ready | `height.php` + guide |

---

## 🚀 Quick Start Testing

### 1. Test Autonomous Flow
```
1. Open: http://localhost/index.php
2. Swipe to start
3. Should go to: patient/scanid.php (NOT home.php)
```

### 2. Test Skip Button
```
1. Go to any measurement page
2. Click "Skip for Testing"
3. Should return to home.php without saving data
```

### 3. Test Error Screen
```
1. Stop server.py
2. Go to height.php
3. Should show error screen after 3 connection attempts
```

---

## 📋 Remaining Tasks

### Apply to These 6 Files:
1. `measurements/weight.php`
2. `measurements/bloodpressure.php`
3. `measurements/temperature.php`
4. `measurements/oximeter.php`
5. `patient/facecapture.php`
6. `patient/scanid.php`

### What to Add:
- ✅ Skip button
- ✅ Error screen HTML
- ✅ Error handling functions
- ✅ Connection retry logic

### Reference:
See `UPDATE_MEASUREMENT_PAGES.md` for step-by-step instructions.

---

## 🔧 Installation Commands

### Install Tesseract OCR (for ID scanning)
```bash
sudo apt-get update
sudo apt-get install tesseract-ocr tesseract-ocr-eng
pip3 install pytesseract
tesseract --version  # Verify
```

### Restart WebSocket Server
```bash
pkill -f server.py
python3 server.py
```

---

## 🧪 Testing Checklist

### Flow Tests
- [ ] index.php → scanid.php (autonomous)
- [ ] Skip buttons work on all pages
- [ ] Error screens appear when disconnected
- [ ] Retry buttons work
- [ ] Complete flow: index → scanid → face → measurements → summary → print

### Hardware Tests
- [ ] Height sensor (GPIO 23, 24)
- [ ] Weight sensor
- [ ] Temperature sensor
- [ ] Blood pressure sensor
- [ ] SpO2 sensor
- [ ] Camera (face + ID scan)

### OCR Tests
- [ ] Scan real Philippine National ID
- [ ] Verify all fields extracted correctly
- [ ] Test with multiple IDs

---

## 📁 Important Files

### Documentation
- `FINAL_IMPLEMENTATION_REPORT.md` - Complete report
- `IMPLEMENTATION_SUMMARY.md` - Detailed changes
- `UPDATE_MEASUREMENT_PAGES.md` - Update guide
- `QUICK_REFERENCE.md` - This file

### Modified Code
- `index.php` - Autonomous flow
- `server.py` - Height sensor + OCR
- `measurements/height.php` - Skip + error screens (template)

---

## 🐛 Troubleshooting

| Problem | Solution |
|---------|----------|
| Icons showing as □ | Clear cache, hard refresh (Ctrl+Shift+R) |
| Not going to scanid.php | Check `NEXT_PAGE` in index.php |
| Height sensor not working | Check GPIO pins 23, 24 |
| OCR not working | Install Tesseract, check camera |
| Skip button not working | Check JavaScript console |
| Error screen not showing | Check WebSocket connection |

---

## 📞 Key Configuration

### Height Sensor (sensor_config.json)
```json
{
  "height": {
    "trigger_pin": 23,
    "echo_pin": 24,
    "sensor_mount_height_cm": 210.0,
    "num_readings": 10,
    "countdown_seconds": 5
  }
}
```

### Autonomous Flow (index.php)
```javascript
const NEXT_PAGE = 'patient/scanid.php';
```

---

## ✅ Quick Copy-Paste

### Skip Button
```html
<button class="btn-back" id="skipBtn" onclick="skipStep()" style="margin-top:8px;">Skip for Testing</button>
```

### Skip Function
```javascript
function skipStep(){ window.location.href = "../home.php"; }
```

### Error Screen
```html
<div id="errorScreen" style="display:none;">
  <div style="font-size:52px;">⚠️</div>
  <h3 style="color:#dc2626;">Sensor Error</h3>
  <p id="errorMessage">Unable to connect to sensor.</p>
  <button onclick="retryConnection()">🔄 Retry</button>
  <button onclick="skipStep()">Skip</button>
  <button onclick="goBack()">← Back</button>
</div>
```

---

## 🎯 Next Action

**Priority 1:** Apply skip buttons + error screens to remaining 6 pages  
**Priority 2:** Install Tesseract OCR on Raspberry Pi  
**Priority 3:** Test with actual hardware sensors  

**Estimated Time:** 1-2 hours

---

**Last Updated:** May 10, 2026  
**Status:** Core features complete, ready for final updates
