<?php
require_once 'config.php';

// Token check
$token = $_SERVER['HTTP_X_KIOSK_TOKEN'] ?? '';
if (KIOSK_API_TOKEN && $token !== KIOSK_API_TOKEN) {
    http_response_code(401); exit;
}

$patient_id = intval($_GET['patient_id'] ?? 0);
$path = __DIR__ . '/../uploads/faces/' . $patient_id . '.jpg';

if (!$patient_id || !file_exists($path)) {
    http_response_code(404); exit;
}

header('Content-Type: image/jpeg');
header('Cache-Control: private, max-age=3600');
readfile($path);
