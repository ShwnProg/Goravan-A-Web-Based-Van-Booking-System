<?php
require_once '../../autoload.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../views/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    $_SESSION['error'] = 'Invalid CSRF token.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

$schedule_id = (int) ($_POST['schedule_id'] ?? 0);
$new_status = trim($_POST['status'] ?? '');

if (!$schedule_id || !in_array($new_status, ['boarding', 'departed', 'arrived', 'cancelled'])) {
    $_SESSION['error'] = 'Invalid parameters.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

$schedule = new Schedules($conn);
$schedule->id = $schedule_id;

if ($schedule->HasPendingBookings()) {
    $_SESSION['error'] = 'Resolve pending bookings before changing this schedule status.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

// Validate transition
$allowed = $schedule->canUpdateStatus($new_status);
if (!$allowed) {
    $_SESSION['error'] = 'Invalid status transition.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

$schedule->trip_status = $new_status;
$result = $schedule->UpdateStatus();

$_SESSION[$result['success'] ? 'success' : 'error'] = $result['success']
    ? 'Status updated successfully.'
    : ($result['message'] ?? 'Unable to update schedule status. Please try again.');

header('Location: ../../views/admin/schedules.php');
exit;
?>

