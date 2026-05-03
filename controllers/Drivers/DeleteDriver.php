<?php
require_once '../../autoload.php';

if (!csrf_check()) {
    $_SESSION['error'] = 'Invalid CSRF token.';
    header('Location: ../../views/admin/drivers.php');
    exit;
}

$driver_id = (int) ($_POST['driver_id'] ?? 0);
if (!$driver_id) {
    $_SESSION['error'] = 'Invalid driver ID.';
    header('Location: ../../views/admin/drivers.php');
    exit;
}

$driver = new Drivers($conn);
$driver->id = $driver_id;

$result = $driver->DeleteDriver();

$_SESSION[$result['success'] ? 'success' : 'error'] = $result['success']
    ? 'Driver deleted successfully.'
    : 'Failed to delete driver: ' . ($result['error'] ?? 'Unknown error.');

header('Location: ../../views/admin/drivers.php');
exit;
?>

