<?php
// results/get_record.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, X-Kiosk-Token');

require_once 'config.php';

// Token check — applied to all API endpoints
$token = $_SERVER['HTTP_X_KIOSK_TOKEN'] ?? '';
if (KIOSK_API_TOKEN && $token !== KIOSK_API_TOKEN) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$patient_id = intval($_GET['patient_id'] ?? 0);
$record_id  = intval($_GET['record_id']  ?? 0);

if (!$patient_id) {
    echo json_encode(['success' => false, 'error' => 'No patient_id']);
    exit;
}

try {
    // Fetch patient info
    $pStmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ? LIMIT 1");
    $pStmt->execute([$patient_id]);
    $patient = $pStmt->fetch();

    if (!$patient) {
        echo json_encode(['success' => false, 'error' => 'Patient not found']);
        exit;
    }

    // Fetch health record — specific record_id OR latest for this patient
    if ($record_id) {
        $rStmt = $pdo->prepare("SELECT * FROM health_records WHERE record_id = ? AND patient_id = ? LIMIT 1");
        $rStmt->execute([$record_id, $patient_id]);
    } else {
        $rStmt = $pdo->prepare("SELECT * FROM health_records WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1");
        $rStmt->execute([$patient_id]);
    }
    $record = $rStmt->fetch();

    echo json_encode([
        'success' => true,
        'patient' => $patient,
        'record'  => $record ?: null,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>