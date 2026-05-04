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
$schedule_id    = (int)   ($_POST['schedule_id']    ?? 0);
$route_id       = (int)   ($_POST['route_id']       ?? 0);
$driver_id      = (int)   ($_POST['driver_id']      ?? 0);
$van_id         = (int)   ($_POST['van_id']         ?? 0);
$departure_date = trim(    $_POST['departure_date']  ?? '');
$departure_time = trim(    $_POST['departure_time']  ?? '');
$trip_status    = trim(    $_POST['trip_status']     ?? 'boarding');

/* ── VALIDATION ───────────────────────────────────────────────────────────── */
if (!$schedule_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid schedule ID.'
    ]);
    exit;
}

if (!$route_id || !$driver_id || !$van_id || !$departure_date || !$departure_time) {
    echo json_encode([
        'success' => false,
        'message' => 'All fields are required.'
    ]);
    exit;
}

if (strtotime($departure_date . ' ' . $departure_time) === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid date/time format.'
    ]);
    exit;
}

if (!in_array($trip_status, ['boarding', 'departed', 'arrived', 'cancelled'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status.'
    ]);
    exit;
}

/* ── LOAD SCHEDULE ────────────────────────────────────────────────────────── */
$schedule = new Schedules($conn);
$schedule->id = $schedule_id;

$current = $schedule->GetScheduleByID();

if (empty($current)) {
    echo json_encode([
        'success' => false,
        'message' => 'Schedule not found.'
    ]);
    exit;
}

$current = $current[0];

/* ── NO CHANGES CHECK ─────────────────────────────────────────────────────── */
$noChanges =
    (int)    $current['route_id_fk']    === $route_id       &&
    (int)    $current['driver_id_fk']   === $driver_id      &&
    (int)    $current['van_id_fk']      === $van_id         &&
             $current['departure_date'] === $departure_date &&
             $current['departure_time'] === $departure_time &&
             $current['trip_status']    === $trip_status;

if ($noChanges) {
    echo json_encode([
        'no_changes' => true,
        'message' => 'No changes were made.'
    ]);
    exit;
}

/* ── CONFLICT CHECK ───────────────────────────────────────────────────────── */
$schedule->route_id       = $route_id;
$schedule->driver_id      = $driver_id;
$schedule->van_id         = $van_id;
$schedule->departure_date = $departure_date;
$schedule->departure_time = $departure_time;
$schedule->trip_status    = $trip_status;

if ($schedule->HasVanConflict() || $schedule->HasDriverConflict()) {
    echo json_encode([
        'success' => false,
        'message' => 'Van or driver conflict at selected time.'
    ]);
    exit;
}

/* ── UPDATE ───────────────────────────────────────────────────────────────── */
$result = $schedule->EditSchedule();

/* ── RESPONSE ─────────────────────────────────────────────────────────────── */
if ($result['success']) {
    echo json_encode([
        'success' => true,
        'message' => 'Schedule updated successfully.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $result['error'] ?? 'Failed to update schedule.'
    ]);
}

exit;
?>