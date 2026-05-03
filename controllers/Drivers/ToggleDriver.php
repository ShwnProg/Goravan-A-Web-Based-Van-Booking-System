<?php
require_once '../../autoload.php';

$driver_id = (int) ($_POST['driver_id'] ?? 0);
$status = trim($_POST['status'] ?? 'active');

if (!$driver_id) {
    header('Location: ../../views/admin/drivers.php');
    exit;
}

if (!in_array($status, ['active', 'inactive'])) {
    $status = 'active';
}

$driver         = new Drivers($conn);
$driver->id     = $driver_id;
$driver->status = $status;

$driver->ToggleDriver();

$_SESSION['success'] = 'Status updated successfully.';
header('Location: ../../views/admin/drivers.php');
exit;
?>
