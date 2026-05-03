<?php
require_once '../../autoload.php';

header('Content-Type: application/json');

if (!csrf_check()) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid CSRF token.'
    ]);
    exit;
}

$van_id = (int)($_POST['van_id'] ?? 0);
$status = $_POST['status'] ?? 'active';

if (!$van_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid van ID.'
    ]);
    exit;
}

if (!in_array($status, ['active', 'inactive'])) {
    $status = 'active';
}

$van = new Vans($conn);
$van->id = $van_id;
$van->status = $status;

$result = $van->ToggleVan();

echo json_encode([
    'success' => $result['success'],
    'message' => $result['success'] ? 'Updated successfully' : 'Update failed'
]);
exit;