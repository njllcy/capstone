<?php
// =============================================
// save_vitals.php
// Updates vital signs for an existing health record.
// Returns: { success, record_id } or { success, error }
// =============================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Kiosk-Token');

require_once 'config.php';

// API token check
$token = $_SERVER['HTTP_X_KIOSK_TOKEN'] ?? '';
if (KIOSK_API_TOKEN && $token !== KIOSK_API_TOKEN) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST only']);
    exit;
}

// Parse JSON body
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$patient_id = intval($data['patient_id'] ?? 0);
$record_id  = intval($data['record_id']  ?? 0);

if (!$patient_id || !$record_id) {
    echo json_encode(['success' => false, 'error' => 'patient_id and record_id are required']);
    exit;
}

// Optional vital fields accepted from the request
$allowed_fields = [
    'weight_kg',
    'height_cm',
    'temperature_c',
    'spo2_percent',
    'systolic_bp',
    'diastolic_bp',
    'pulse_bpm',
];

// Build dynamic SET clause — only include fields present in the request body
$set_parts = [];
$params    = [];

foreach ($allowed_fields as $field) {
    if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
        $set_parts[] = "$field = ?";
        $params[]    = $data[$field];
    }
}

// Grab the values we may need for derived calculations
$weight_kg   = isset($data['weight_kg'])   && $data['weight_kg']   !== '' ? floatval($data['weight_kg'])   : null;
$height_cm   = isset($data['height_cm'])   && $data['height_cm']   !== '' ? floatval($data['height_cm'])   : null;
$temp_c      = isset($data['temperature_c']) && $data['temperature_c'] !== '' ? floatval($data['temperature_c']) : null;
$spo2        = isset($data['spo2_percent']) && $data['spo2_percent'] !== '' ? floatval($data['spo2_percent']) : null;
$systolic    = isset($data['systolic_bp']) && $data['systolic_bp']  !== '' ? intval($data['systolic_bp'])   : null;
$pulse       = isset($data['pulse_bpm'])   && $data['pulse_bpm']    !== '' ? intval($data['pulse_bpm'])     : null;

// Auto-calculate BMI when both weight and height are present
if ($weight_kg !== null && $height_cm !== null && $height_cm > 0) {
    $height_m = $height_cm / 100.0;
    $bmi      = $weight_kg / ($height_m * $height_m);
    $set_parts[] = 'bmi = ?';
    $params[]    = round($bmi, 2);
} else {
    $bmi = null;
}

// Evaluate temp_status
if ($temp_c !== null) {
    if ($temp_c < 36.1) {
        $temp_status = 'Low';
    } elseif ($temp_c <= 37.2) {
        $temp_status = 'Normal';
    } else {
        $temp_status = 'Fever';
    }
    $set_parts[] = 'temp_status = ?';
    $params[]    = $temp_status;
}

// Evaluate spo2_status
if ($spo2 !== null) {
    if ($spo2 >= 95) {
        $spo2_status = 'Normal';
    } elseif ($spo2 >= 90) {
        $spo2_status = 'Low';
    } else {
        $spo2_status = 'Critical';
    }
    $set_parts[] = 'spo2_status = ?';
    $params[]    = $spo2_status;
}

// Evaluate bp_status (based on systolic)
if ($systolic !== null) {
    if ($systolic < 120) {
        $bp_status = 'Normal';
    } elseif ($systolic <= 129) {
        $bp_status = 'Elevated';
    } else {
        $bp_status = 'High';
    }
    $set_parts[] = 'bp_status = ?';
    $params[]    = $bp_status;
}

// Evaluate pulse_status
if ($pulse !== null) {
    if ($pulse < 60) {
        $pulse_status = 'Low';
    } elseif ($pulse <= 100) {
        $pulse_status = 'Normal';
    } else {
        $pulse_status = 'High';
    }
    $set_parts[] = 'pulse_status = ?';
    $params[]    = $pulse_status;
}

// Evaluate bmi_status
if ($bmi !== null) {
    if ($bmi < 18.5) {
        $bmi_status = 'Underweight';
    } elseif ($bmi < 25.0) {
        $bmi_status = 'Normal';
    } elseif ($bmi < 30.0) {
        $bmi_status = 'Overweight';
    } else {
        $bmi_status = 'Obese';
    }
    $set_parts[] = 'bmi_status = ?';
    $params[]    = $bmi_status;
}

// Nothing to update
if (empty($set_parts)) {
    echo json_encode(['success' => false, 'error' => 'No vital fields provided']);
    exit;
}

// Append WHERE clause parameters
$params[] = $record_id;
$params[] = $patient_id;

$sql = 'UPDATE health_records SET ' . implode(', ', $set_parts)
     . ' WHERE record_id = ? AND patient_id = ?';

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'record_id' => $record_id]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
