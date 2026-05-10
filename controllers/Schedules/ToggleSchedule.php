<?php
require_once '../../autoload.php';

header('Content-Type: application/json');

/* ── CSRF CHECK ───────────────────────────────────────────────────────────── */
if (!csrf_check()) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid CSRF token.'
    ]);
    exit;
}

/* ── INPUTS ───────────────────────────────────────────────────────────────── */
$schedule_id = (int) ($_POST['schedule_id'] ?? 0);
$new_status = trim($_POST['status'] ?? '');

/* ── VALIDATION ───────────────────────────────────────────────────────────── */
if (!$schedule_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid schedule ID.'
    ]);
    exit;
}

if (!in_array($new_status, ['boarding', 'departed', 'cancelled'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status.'
    ]);
    exit;
}

/* ── UPDATE STATUS ────────────────────────────────────────────────────────── */
$schedule = new Schedules($conn);
$schedule->id = $schedule_id;
$schedule->trip_status = $new_status;

$result = $schedule->UpdateStatus();

/* ── RESPONSE ─────────────────────────────────────────────────────────────── */
echo json_encode([
    'success' => $result['success'],
    'message' => $result['success']
        ? 'Schedule status updated successfully.'
        : 'Failed to update schedule status.'
]);

exit;
?>

