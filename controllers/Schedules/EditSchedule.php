<?php
require_once '../../autoload.php';

if (!csrf_check()) {
    $_SESSION['error'] = 'Invalid CSRF token.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

$schedule_id    = (int)   ($_POST['schedule_id']    ?? 0);
$route_id       = (int)   ($_POST['route_id']       ?? 0);
$driver_id      = (int)   ($_POST['driver_id']      ?? 0);
$van_id         = (int)   ($_POST['van_id']         ?? 0);
$departure_date = trim(    $_POST['departure_date']  ?? '');
$departure_time = trim(    $_POST['departure_time']  ?? '');
$trip_status    = trim(    $_POST['trip_status']     ?? 'boarding');

if (!$schedule_id) {
    $_SESSION['error'] = 'Invalid schedule ID.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

if (!$route_id || !$driver_id || !$van_id || !$departure_date || !$departure_time) {
    $_SESSION['error'] = 'All fields are required.';
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

// ── No-changes detection ──────────────────────────────────────────────────────
// Fetch the current record and compare every field before touching the DB.
$schedule = new Schedules($conn);
$schedule->id = $schedule_id;

$current = $schedule->GetScheduleByID();

if (empty($current)) {
    $_SESSION['error'] = 'Schedule not found.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

$current = $current[0]; // GetScheduleByID returns an array of one row

$noChanges =
    (int)    $current['route_id_fk']    === $route_id       &&
    (int)    $current['driver_id_fk']   === $driver_id      &&
    (int)    $current['van_id_fk']      === $van_id         &&
             $current['departure_date'] === $departure_date &&
             $current['departure_time'] === $departure_time &&
             $current['trip_status']    === $trip_status;

if ($noChanges) {
    $_SESSION['no_changes'] = 'No changes were made.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

// ── Conflict check (skip if only status changed on same van/driver/time) ──────
$schedule->route_id       = $route_id;
$schedule->driver_id      = $driver_id;
$schedule->van_id         = $van_id;
$schedule->departure_date = $departure_date;
$schedule->departure_time = $departure_time;
$schedule->trip_status    = $trip_status;

if ($schedule->HasVanConflict() || $schedule->HasDriverConflict()) {
    $_SESSION['error'] = 'Van or driver conflict at selected time.';
    header('Location: ../../views/admin/schedules.php');
    exit;
}

// ── Update ────────────────────────────────────────────────────────────────────
$result = $schedule->EditSchedule();

$_SESSION[$result['success'] ? 'success' : 'error'] = $result['success']
    ? 'Schedule updated successfully.'
    : 'Failed to update schedule: ' . ($result['error'] ?? 'Unknown error.');

header('Location: ../../views/admin/schedules.php');
exit;
?>