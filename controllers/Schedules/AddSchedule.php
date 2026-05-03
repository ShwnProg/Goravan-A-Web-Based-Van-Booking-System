<?php
require_once '../../autoload.php';

if (!csrf_check()) {
    $_SESSION['error'] = 'Invalid CSRF token.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

$route_id = (int) ($_POST['route_id'] ?? 0);
$driver_id = (int) ($_POST['driver_id'] ?? 0);
$van_id = (int) ($_POST['van_id'] ?? 0);
$departure_date = trim($_POST['departure_date'] ?? '');
$departure_time = trim($_POST['departure_time'] ?? '');
$trip_status = trim($_POST['trip_status'] ?? 'boarding');

if (!$route_id || !$driver_id || !$van_id || !$departure_date || !$departure_time) {
    $_SESSION['error'] = 'All fields are required.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

if (empty($departure_date) || empty($departure_time)) {
    $_SESSION['error'] = 'Date and time are required.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}
if (strtotime($departure_date . ' ' . $departure_time) === false) {
    $_SESSION['error'] = 'Invalid date/time format.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

if (!in_array($trip_status, ['boarding', 'departed', 'arrived', 'cancelled'])) {
    $_SESSION['error'] = 'Invalid status.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

$schedule = new Schedules($conn);
$schedule->route_id = $route_id;
$schedule->driver_id = $driver_id;
$schedule->van_id = $van_id;
$schedule->departure_date = $departure_date;
$schedule->departure_time = $departure_time;
$schedule->trip_status = $trip_status;

if ($schedule->HasVanConflict() || $schedule->HasDriverConflict()) {
    $_SESSION['error'] = 'Van or driver already scheduled at that time.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

$result = $schedule->AddSchedule();

$_SESSION[$result['success'] ? 'success' : 'error'] = $result['success']
    ? 'Schedule added successfully.'
    : 'Failed to add schedule: ' . ($result['error'] ?? 'Unknown error.');

header('Location: ../../views/admin/schedules.php');
exit;
?>

