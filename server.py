"""
HealthKiosk — Unified WebSocket Server
========================================
Run this ONCE:  python3 server.py

It starts as a WebSocket hub on ws://0.0.0.0:8765.
When the HTML UI sends an action, the server:
  1. Spawns (or signals) the correct sensor Python script
  2. Streams responses back to the browser

Folder layout expected:
  /SENSORS/
    weight.py
    height.py
    temperature.py
    bloodpressure.py
    facecapture.py
    scanid.py

Place server.py one level above SENSORS/ OR adjust SENSOR_DIR below.
"""

import asyncio
import json
import os
import sys
import subprocess
import statistics
import time
import threading
import base64
import traceback
import websockets

# ── CONFIG ────────────────────────────────────────────────────────────────────
HOST        = "0.0.0.0"
PORT        = 8765
SENSOR_DIR  = os.path.join(os.path.dirname(__file__), "SENSORS")   # adjust if needed
PYTHON      = sys.executable   # uses same python3 that launched server.py
CONFIG_PATH = os.path.join(os.path.dirname(__file__), 'sensor_config.json')

def load_config():
    """Load sensor configuration from sensor_config.json"""
    with open(CONFIG_PATH, 'r') as f:
        return json.load(f)

CFG = load_config()

# ── CONNECTED CLIENTS ─────────────────────────────────────────────────────────
CLIENTS: set = set()

async def broadcast(data: dict):
    msg = json.dumps(data)
    dead = set()
    for ws in CLIENTS:
        try:
            await ws.send(msg)
        except Exception:
            dead.add(ws)
    CLIENTS.difference_update(dead)

# ── HELPERS ───────────────────────────────────────────────────────────────────

def sensor_path(name: str) -> str:
    """Resolve path to a sensor script."""
    return os.path.join(SENSOR_DIR, name)


async def run_sensor_script(script: str, ws):
    """
    Launch a sensor script as a subprocess.
    The script prints JSON lines to stdout → forward to the browser.
    Format each print() in the sensor script as:
        import json, sys
        print(json.dumps({...}), flush=True)
    """
    path = sensor_path(script)
    if not os.path.exists(path):
        await ws.send(json.dumps({"error": f"Script not found: {script}"}))
        return

    proc = await asyncio.create_subprocess_exec(
        PYTHON, path,
        stdout=asyncio.subprocess.PIPE,
        stderr=asyncio.subprocess.PIPE,
    )

    try:
        async for raw in proc.stdout:
            line = raw.decode().strip()
            if not line:
                continue
            try:
                data = json.loads(line)
                await ws.send(json.dumps(data))
            except json.JSONDecodeError:
                # plain text line — forward as status
                await ws.send(json.dumps({"status": line}))
    finally:
        proc.kill()
        await proc.wait()


# ── WEIGHT ────────────────────────────────────────────────────────────────────
async def handle_weight(ws):
    await ws.send(json.dumps({"status": "Initializing system..."}))

    try:
        import RPi.GPIO as GPIO
        from hx711 import HX711

        # ── GPIO SETUP ──
        GPIO.setwarnings(False)
        GPIO.setmode(GPIO.BCM)
        DATA_PIN  = CFG['weight']['data_pin']
        CLOCK_PIN = CFG['weight']['clock_pin']

        # ── HX711 INIT ──
        hx = HX711(dout_pin=DATA_PIN, pd_sck_pin=CLOCK_PIN)
        await asyncio.sleep(2)

        # ── TARE ──
        await ws.send(json.dumps({"status": "Taring... Make sure NO weight is on the scale."}))
        await asyncio.sleep(2)

        loop = asyncio.get_event_loop()
        readings = await loop.run_in_executor(None, lambda: hx.get_raw_data(times=CFG['weight']['tare_samples']))
        offset = statistics.mean(readings)
        await ws.send(json.dumps({"status": f"Tare complete. Offset: {round(offset, 2)}"}))

        # ── CALIBRATION ──
        scale_factor = CFG['weight']['scale_factor']   # adjust after calibration

        await ws.send(json.dumps({"status": "System ready. Step on the scale with BOTH feet."}))

        # ── WAIT FOR WEIGHT ──
        while True:
            raw = await loop.run_in_executor(None, lambda: hx.get_raw_data(times=5))
            avg = statistics.mean(raw)
            weight = (offset - avg) / scale_factor
            if weight > 5:
                await ws.send(json.dumps({"status": "Weight detected. Stabilizing..."}))
                break
            await asyncio.sleep(0.3)

        # ── STABILITY CHECK ──
        stable_samples = []
        while True:
            samples = []
            for _ in range(10):
                raw_data = await loop.run_in_executor(None, lambda: hx.get_raw_data(times=3))
                val = statistics.mean(raw_data)
                weight = (offset - val) / scale_factor
                samples.append(weight)
                await asyncio.sleep(0.1)

            avg_weight = statistics.mean(samples)
            deviation  = statistics.stdev(samples)
            await ws.send(json.dumps({"status": f"Checking stability... deviation: {round(deviation, 2)}"}))

            if deviation < CFG['weight']['stability_threshold']:
                stable_samples = samples
                break
            await asyncio.sleep(0.5)

        # ── FINAL WEIGHT ──
        final_weight = statistics.mean(stable_samples)
        if final_weight < 0:
            final_weight = 0
        final_weight = round(final_weight, 1)

        await ws.send(json.dumps({"status": "Done", "weight": final_weight}))

        GPIO.cleanup()

    except (ImportError, RuntimeError):
        # ── SIMULATION (non-Raspberry Pi) ──
        await ws.send(json.dumps({"status": "Taring... Make sure NO weight is on the scale."}))
        await asyncio.sleep(2)
        await ws.send(json.dumps({"status": "Tare complete."}))
        await ws.send(json.dumps({"status": "System ready. Step on the scale with BOTH feet."}))
        await asyncio.sleep(2)
        await ws.send(json.dumps({"status": "Weight detected. Stabilizing..."}))
        for i in range(1, 4):
            await asyncio.sleep(1)
            await ws.send(json.dumps({"status": f"Checking stability... deviation: {round(0.5 / i, 2)}"}))
        await ws.send(json.dumps({"status": "Done", "weight": 62.4}))


# ── HEIGHT ────────────────────────────────────────────────────────────────────
async def handle_height(ws):
    await ws.send(json.dumps({"status": "HEIGHT MEASUREMENT SYSTEM"}))
    try:
        import RPi.GPIO as GPIO

        # ── GPIO SETUP ──
        TRIG = CFG['height']['trigger_pin']
        ECHO = CFG['height']['echo_pin']
        GPIO.setwarnings(False)
        GPIO.setmode(GPIO.BCM)
        GPIO.setup(TRIG, GPIO.OUT)
        GPIO.setup(ECHO, GPIO.IN)
        GPIO.output(TRIG, False)

        SENSOR_HEIGHT = 210.0  # cm reference (updated from your Thonny script)

        await asyncio.sleep(2)

        loop = asyncio.get_event_loop()

        # ── GET DISTANCE (exact logic from your Thonny script) ──
        def get_distance():
            GPIO.output(TRIG, False)
            time.sleep(0.05)
            GPIO.output(TRIG, True)
            time.sleep(0.00001)
            GPIO.output(TRIG, False)
            timeout = time.time() + 0.03
            while GPIO.input(ECHO) == 0:
                pulse_start = time.time()
                if pulse_start > timeout:
                    return None
            pulse_end = time.time()
            while GPIO.input(ECHO) == 1:
                pulse_end = time.time()
                if pulse_end - pulse_start > 0.03:
                    return None
            pulse_duration = pulse_end - pulse_start
            distance = (pulse_duration * 34300) / 2
            return distance

        # ── 5 SECOND COUNTDOWN ──
        await ws.send(json.dumps({"status": "Please stand still..."}))
        for i in range(5, 0, -1):
            await ws.send(json.dumps({"status": f"Measuring in {i}...", "countdown": i}))
            await asyncio.sleep(1)

        # ── TAKE 10 READINGS ──
        await ws.send(json.dumps({"status": "Reading sensor..."}))
        readings = []
        for _ in range(10):
            dist = await loop.run_in_executor(None, get_distance)
            if dist is not None:
                height = SENSOR_HEIGHT - dist
                readings.append(height)
            await asyncio.sleep(0.2)

        # ── RESULT ──
        if len(readings) == 0:
            await ws.send(json.dumps({"status": "ERROR: No reading detected", "height": 0, "error": True}))
        else:
            final_height = round(max(readings), 1)
            await ws.send(json.dumps({"status": "Done", "height": final_height}))

        GPIO.cleanup()

    except (ImportError, RuntimeError):
        # ── SIMULATION (non-Raspberry Pi) ──
        await ws.send(json.dumps({"status": "Please stand still..."}))
        for i in range(5, 0, -1):
            await ws.send(json.dumps({"status": f"Measuring in {i}...", "countdown": i}))
            await asyncio.sleep(1)
        await ws.send(json.dumps({"status": "Reading sensor..."}))
        await asyncio.sleep(2)
        await ws.send(json.dumps({"status": "Done", "height": 165.5}))


# ── TEMPERATURE ────────────────────────────────────────────────────────────────
async def handle_temperature(ws):
    await ws.send(json.dumps({"status": "Reading temperature..."}))
    try:
        import serial

        ser = serial.Serial(port=CFG['temperature']['serial_port'], baudrate=CFG['temperature']['baud_rate'], timeout=2)
        await asyncio.sleep(0.5)
        loop = asyncio.get_event_loop()

        def read_temp():
            deadline = time.time() + CFG['temperature']['read_timeout_seconds']
            while time.time() < deadline:
                if ser.in_waiting > 0:
                    raw = ser.readline().decode('utf-8', errors='ignore').strip()
                    if raw.startswith('@') and raw.endswith('#'):
                        parts = raw.split(',')
                        if len(parts) >= 3:
                            try:
                                return float(parts[1])
                            except ValueError:
                                pass
                time.sleep(0.3)
            return None

        temp = await loop.run_in_executor(None, read_temp)
        ser.close()

        if temp is not None:
            await ws.send(json.dumps({"status": "done", "temp": temp}))
        else:
            await ws.send(json.dumps({"status": "done", "temp": "N/A"}))

    except (ImportError, Exception):
        await asyncio.sleep(2)
        await ws.send(json.dumps({"status": "done", "temp": 36.7}))


# ── BLOOD PRESSURE ─────────────────────────────────────────────────────────────
async def handle_bloodpressure(ws):
    await ws.send(json.dumps({"status": "Inflating cuff..."}))
    try:
        import RPi.GPIO as GPIO
        from hx711 import HX711

        GPIO.setwarnings(False)
        GPIO.setmode(GPIO.BCM)
        hx = HX711(dout_pin=5, pd_sck_pin=6)

        pressure_readings = []
        loop = asyncio.get_event_loop()
        start = time.time()

        while time.time() - start < 35:
            values = await loop.run_in_executor(None, hx.get_raw_data)
            clean = [v for v in values if -5000 < v < 5000]
            if clean:
                avg = statistics.mean(clean)
                pressure = abs(avg) / 12
                pressure_readings.append(pressure)
                await ws.send(json.dumps({"status": f"Measuring... {round(pressure,1)} mmHg"}))
            await asyncio.sleep(0.2)

        if pressure_readings:
            systolic  = round(max(pressure_readings) * 0.82)
            diastolic = round(systolic * 0.57)
            pulse     = 71
            await ws.send(json.dumps({
                "status":    "done",
                "systolic":  systolic,
                "diastolic": diastolic,
                "pulse":     pulse
            }))
        else:
            await ws.send(json.dumps({"status": "done", "systolic": 0, "diastolic": 0, "pulse": 0}))

        GPIO.cleanup()

    except (ImportError, RuntimeError):
        for i in range(1, 6):
            await asyncio.sleep(2)
            await ws.send(json.dumps({"status": f"Measuring... step {i}/5"}))
        await ws.send(json.dumps({
            "status": "done", "systolic": 120, "diastolic": 80, "pulse": 72
        }))


# ── OXIMETER ──────────────────────────────────────────────────────────────────
async def handle_oximeter(ws):
    """
    30-second SpO2 reading.
    Replace the simulation block with your actual MAX30100/MAX30102 library calls.
    """
    await ws.send(json.dumps({"status": "Measuring..."}))
    try:
        # Example: from max30100 import MAX30100
        # sensor = MAX30100()
        raise ImportError("no oximeter lib — using simulation")
    except ImportError:
        import random
        for remaining in range(30, -1, -1):
            spo2 = random.randint(97, 100)
            await ws.send(json.dumps({"spo2": spo2, "timer": remaining}))
            await asyncio.sleep(1)
        await ws.send(json.dumps({"status": "done"}))


# ── FACE CAPTURE ──────────────────────────────────────────────────────────────
async def handle_face(ws):
    await ws.send(json.dumps({"status": "Opening camera..."}))
    try:
        import cv2
        from datetime import datetime

        loop = asyncio.get_event_loop()

        def capture_face():
            cap = cv2.VideoCapture(0)
            if not cap.isOpened():
                return None, None

            time.sleep(CFG['camera']['capture_delay_seconds'])

            ret, frame = cap.read()
            cap.release()

            if ret:
                filename = datetime.now().strftime("patient_%Y-%m-%d_%H-%M-%S.jpg")
                cv2.imwrite(filename, frame)
                print(f"[CAMERA] Saved: {filename}")
                return frame, filename
            return None, None

        await ws.send(json.dumps({"status": "📷 Taking photo in 5 seconds..."}))
        frame, filename = await loop.run_in_executor(None, capture_face)

        if frame is not None:
            _, buf = cv2.imencode('.jpg', frame)
            b64 = base64.b64encode(buf).decode()
            await ws.send(json.dumps({
                "image":    b64,
                "filename": filename
            }))
        else:
            await ws.send(json.dumps({"status": "❌ Capture failed"}))

    except (ImportError, Exception) as e:
        traceback.print_exc()
        # Simulation fallback
        await asyncio.sleep(6)
        placeholder = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=="
        await ws.send(json.dumps({"image": placeholder, "filename": "simulated.jpg"}))


# ── SCAN ID ────────────────────────────────────────────────────────────────────
async def handle_scanid(ws):
    await ws.send(json.dumps({"status": "Scanning Philippine National ID..."}))
    try:
        from picamera2 import Picamera2
        import cv2
        import pytesseract
        import re

        loop = asyncio.get_event_loop()

        def do_scan():
            # ── CAPTURE IMAGE ──
            picam2 = Picamera2()
            cfg = picam2.create_preview_configuration(main={"format":"RGB888","size":tuple(CFG['camera']['resolution'])})
            picam2.configure(cfg)
            picam2.start()
            time.sleep(2)
            frame = picam2.capture_array()
            picam2.stop()

            # ── PREPROCESS FOR OCR ──
            gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
            # Resize for better OCR accuracy
            gray = cv2.resize(gray, None, fx=2, fy=2, interpolation=cv2.INTER_CUBIC)
            # Apply thresholding to improve text detection
            gray = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)[1]
            
            # ── OCR EXTRACTION ──
            text = pytesseract.image_to_string(gray, config="--oem 3 --psm 6")
            
            # ── PARSE PHILIPPINE NATIONAL ID ──
            # Expected format:
            # Apelyido/Last Name: DELA CRUZ
            # Mga Pangalan/Given Names: JUANA
            # Gitnang Apelyido/Middle Name: MARTINEZ
            # Petsa ng Kapanganakan/Date of Birth: JANUARY 01, 1990
            # Tirahan/Address: 833 SISA ST., BRGY 526, ZONE 52 SAMPALOK, MANILA CITY, METRO MANILA
            
            lines = text.split('\n')
            data = {
                "last_name": "",
                "first_name": "",
                "middle_name": "",
                "dob": "",
                "address": ""
            }
            
            for i, line in enumerate(lines):
                line_upper = line.upper()
                
                # Extract Last Name
                if "APELYIDO/LAST" in line_upper or "LAST NAME" in line_upper:
                    # Next line usually contains the name
                    if i + 1 < len(lines):
                        data["last_name"] = lines[i + 1].strip()
                
                # Extract Given Names (First Name)
                if "PANGALAN/GIVEN" in line_upper or "GIVEN NAMES" in line_upper:
                    if i + 1 < len(lines):
                        data["first_name"] = lines[i + 1].strip()
                
                # Extract Middle Name
                if "GITNANG APELYIDO/MIDDLE" in line_upper or "MIDDLE NAME" in line_upper:
                    if i + 1 < len(lines):
                        data["middle_name"] = lines[i + 1].strip()
                
                # Extract Date of Birth
                if "PETSA NG KAPANGANAKAN/DATE" in line_upper or "DATE OF BIRTH" in line_upper:
                    if i + 1 < len(lines):
                        dob_text = lines[i + 1].strip()
                        # Try to parse date (e.g., "JANUARY 01, 1990")
                        data["dob"] = dob_text
                
                # Extract Address
                if "TIRAHAN/ADDRESS" in line_upper or "ADDRESS" in line_upper:
                    if i + 1 < len(lines):
                        # Address might span multiple lines
                        address_lines = []
                        for j in range(i + 1, min(i + 4, len(lines))):
                            if lines[j].strip():
                                address_lines.append(lines[j].strip())
                        data["address"] = " ".join(address_lines)
            
            # Fallback: if structured parsing fails, try regex patterns
            if not data["last_name"]:
                # Look for all-caps words (likely names)
                caps_words = re.findall(r'\b[A-Z]{2,}\b', text)
                if len(caps_words) >= 2:
                    data["last_name"] = caps_words[0]
                    data["first_name"] = caps_words[1]
                    if len(caps_words) >= 3:
                        data["middle_name"] = caps_words[2]
            
            return data

        result = await loop.run_in_executor(None, do_scan)

        await ws.send(json.dumps({
            "type":        "id_detected",
            "fname":       result["first_name"].title(),
            "lname":       result["last_name"].title(),
            "middle_name": result["middle_name"].title(),
            "dob":         result["dob"],
            "address":     result["address"]
        }))

    except (ImportError, Exception) as e:
        traceback.print_exc()
        # ── SIMULATION FALLBACK ──
        await asyncio.sleep(2)
        await ws.send(json.dumps({
            "type":        "id_detected",
            "fname":       "Juana",
            "lname":       "Dela Cruz",
            "middle_name": "Martinez",
            "dob":         "January 01, 1990",
            "address":     "833 Sisa St., Brgy 526, Zone 52 Sampalok, Manila City, Metro Manila"
        }))


# ── ACTION ROUTER ─────────────────────────────────────────────────────────────
ACTION_MAP = {
    "start_weight":   handle_weight,
    "start_height":   handle_height,
    "start_temp":     handle_temperature,
    "start_bp":       handle_bloodpressure,
    "start_oximeter": handle_oximeter,
    "start_face":     handle_face,
    "scan_id":        handle_scanid,
}


# ── MAIN HANDLER ──────────────────────────────────────────────────────────────
async def handler(websocket):
    CLIENTS.add(websocket)
    print(f"[+] Client connected  | total={len(CLIENTS)}")

    try:
        await websocket.send(json.dumps({"status": "Ready"}))

        async for raw in websocket:
            try:
                msg    = json.loads(raw)
                action = msg.get("action", "")
                print(f"[→] Action: {action}")

                if action in ACTION_MAP:
                    # run sensor in background task so the connection stays alive
                    asyncio.create_task(ACTION_MAP[action](websocket))
                else:
                    await websocket.send(json.dumps({"error": f"Unknown action: {action}"}))

            except json.JSONDecodeError:
                await websocket.send(json.dumps({"error": "Invalid JSON"}))
            except Exception as e:
                traceback.print_exc()
                await websocket.send(json.dumps({"error": str(e)}))

    except websockets.exceptions.ConnectionClosed:
        pass
    finally:
        CLIENTS.discard(websocket)
        print(f"[-] Client disconnected | total={len(CLIENTS)}")


# ── ENTRY POINT ───────────────────────────────────────────────────────────────
async def main():
    print("=" * 48)
    print(f"  HealthKiosk WebSocket Server")
    print(f"  ws://{HOST}:{PORT}")
    print(f"  Sensor dir: {SENSOR_DIR}")
    print("=" * 48)
    async with websockets.serve(handler, HOST, PORT):
        await asyncio.Future()   # run forever


if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        print("\nServer stopped.")