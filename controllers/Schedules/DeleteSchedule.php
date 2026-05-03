<?php

require_once '../../autoload.php';

// ── Auth guard ────────────────────────────────────────────────────────────────
if (empty($_SESSION['is_login'])) {
    header('Location: ../../views/auth/login.php');
    exit;
}

// ── Method & CSRF ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    $_SESSION['error'] = 'Invalid request or CSRF token.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

// ── Validate input ────────────────────────────────────────────────────────────
$schedule_id = (int) ($_POST['schedule_id'] ?? 0);

if (!$schedule_id) {
    $_SESSION['error'] = 'Invalid schedule ID.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

// ── Delete ────────────────────────────────────────────────────────────────────
$schedule       = new Schedules($conn);
$schedule->id   = $schedule_id;
$result         = $schedule->DeleteSchedule();

$_SESSION[$result['success'] ? 'success' : 'error'] = $result['success']
    ? 'Schedule deleted successfully.'
    : 'Failed to delete schedule.';

header('Location: ../../views/admin/schedules.php');
exit;