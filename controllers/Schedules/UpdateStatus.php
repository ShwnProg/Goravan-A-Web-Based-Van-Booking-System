<?php
require_once '../../autoload.php';

// ── Auth guard ────────────────────────────────────────────────────────────────
if (empty($_SESSION['is_login'])) {
    header('Location: ../../views/auth/login.php');
    exit;
}

// ── Method + CSRF ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    $_SESSION['error'] = 'Invalid request or CSRF token.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

// ── Validate input ────────────────────────────────────────────────────────────
$schedule_id = (int) ($_POST['schedule_id'] ?? 0);
$new_status  = trim($_POST['status'] ?? '');
$valid       = ['boarding', 'departed', 'cancelled'];

if (!$schedule_id || !in_array($new_status, $valid, true)) {
    $_SESSION['error'] = 'Invalid parameters.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

// ── Transition guard ──────────────────────────────────────────────────────────
$schedule     = new Schedules($conn);
$schedule->id = $schedule_id;

if (!$schedule->canUpdateStatus($new_status)) {
    $_SESSION['error'] = 'Invalid status transition.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

// ── Update ────────────────────────────────────────────────────────────────────
// arrived_at is stamped automatically inside UpdateStatus()
// when trip_status = 'arrived' via CASE WHEN in the SQL.
$schedule->trip_status = $new_status;
$result = $schedule->UpdateStatus();

$_SESSION[$result['success'] ? 'success' : 'error'] = $result['success']
    ? 'Status updated successfully.'
    : 'Failed to update status: ' . ($result['error'] ?? 'Unknown error.');

header('Location: ../../views/admin/schedules.php');
exit;
?>
