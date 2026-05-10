<?php
// patient/save_patient.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Kiosk-Token');

require_once 'config.php';

// Token check — applied to all API endpoints
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

$body = json_decode(file_get_contents('php://input'), true);

$first_name    = trim($body['first_name']    ?? '');
$last_name     = trim($body['last_name']     ?? '');
$date_of_birth = trim($body['date_of_birth'] ?? '');
$age           = intval($body['age']          ?? 0);
$gender        = trim($body['gender']         ?? '');
$phone         = trim($body['phone']          ?? '');
$barangay      = trim($body['barangay']       ?? '');
$face_image    = $body['face_image']          ?? null; // base64 string (optional)

if (!$first_name || !$last_name || !$date_of_birth) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// --- Server-side validation (§3.4) ---

// 13.1 gender must be exactly 'Male' or 'Female'
if ($gender !== 'Male' && $gender !== 'Female') {
    echo json_encode(['success' => false, 'error' => 'Validation failed: gender']);
    exit;
}

// 13.2 date_of_birth must be a valid date and not in the future
$dob = DateTime::createFromFormat('Y-m-d', $date_of_birth);
if (!$dob || $dob->format('Y-m-d') !== $date_of_birth || $dob > new DateTime('today')) {
    echo json_encode(['success' => false, 'error' => 'Validation failed: date_of_birth']);
    exit;
}

// 13.3 age must be an integer between 0 and 150
$age_raw = $body['age'] ?? null;
// Accept only PHP integers or numeric strings with no decimal point
if (is_float($age_raw) || $age_raw === null ||
    (!is_int($age_raw) && !(is_string($age_raw) && preg_match('/^\d+$/', $age_raw)))) {
    echo json_encode(['success' => false, 'error' => 'Validation failed: age']);
    exit;
}
$age = intval($age_raw);
if ($age < 0 || $age > 150) {
    echo json_encode(['success' => false, 'error' => 'Validation failed: age']);
    exit;
}

// 13.4 phone: max 20 chars, pattern /^[\d\s\-+()]*$/ (empty allowed)
if (strlen($phone) > 20 || ($phone !== '' && !preg_match('/^[\d\s\-+()]*$/', $phone))) {
    echo json_encode(['success' => false, 'error' => 'Validation failed: phone']);
    exit;
}

// 13.5 barangay: max 100 chars, pattern /^[a-zA-Z0-9\s]*$/ (empty allowed)
if (strlen($barangay) > 100 || ($barangay !== '' && !preg_match('/^[a-zA-Z0-9\s]*$/', $barangay))) {
    echo json_encode(['success' => false, 'error' => 'Validation failed: barangay']);
    exit;
}

// --- End validation ---

try {
    // Check if patient already exists (same name + DOB)
    $check = $pdo->prepare("
        SELECT patient_id FROM patients
        WHERE first_name = ? AND last_name = ? AND date_of_birth = ?
        LIMIT 1
    ");
    $check->execute([$first_name, $last_name, $date_of_birth]);
    $existing = $check->fetch();

    if ($existing) {
        // Update existing patient — store null for face_image initially; file path set below
        $stmt = $pdo->prepare("
            UPDATE patients SET
                age           = ?,
                gender        = ?,
                phone         = ?,
                barangay      = ?,
                updated_at    = NOW()
            WHERE patient_id  = ?
        ");
        $stmt->execute([$age, $gender, $phone, $barangay, $existing['patient_id']]);
        $patient_id = $existing['patient_id'];
    } else {
        // Insert new patient — face_image is null here; file path set below
        $stmt = $pdo->prepare("
            INSERT INTO patients
                (first_name, last_name, date_of_birth, age, gender, phone, barangay, face_image, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, NULL, NOW(), NOW())
        ");
        $stmt->execute([$first_name, $last_name, $date_of_birth, $age, $gender, $phone, $barangay]);
        $patient_id = $pdo->lastInsertId();
    }

    // Save face image to filesystem and store only the file path in DB
    if ($face_image) {
        // Strip data URI prefix if present: "data:image/jpeg;base64,..."
        $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $face_image);
        $imageData = base64_decode($base64);
        if ($imageData !== false) {
            $uploadDir = __DIR__ . '/../uploads/faces/';
            file_put_contents($uploadDir . $patient_id . '.jpg', $imageData);
            $face_path = 'uploads/faces/' . $patient_id . '.jpg';
            $pdo->prepare("UPDATE patients SET face_image = ? WHERE patient_id = ?")
                ->execute([$face_path, $patient_id]);
        }
    }

    // Create a new health record session for this visit
    $rec = $pdo->prepare("
        INSERT INTO health_records (patient_id, visit_date, created_at)
        VALUES (?, CURDATE(), NOW())
    ");
    $rec->execute([$patient_id]);
    $record_id = $pdo->lastInsertId();

    echo json_encode([
        'success'    => true,
        'patient_id' => (int)$patient_id,
        'record_id'  => (int)$record_id,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>