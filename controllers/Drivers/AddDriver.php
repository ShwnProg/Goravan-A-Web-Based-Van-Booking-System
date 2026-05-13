<?php
require_once '../../autoload.php';

if (!csrf_check()) {
    $_SESSION['error'] = 'Invalid CSRF token.';
    header('Location: ../../views/admin/drivers.php');
    exit;
}

$full_name     = trim($_POST['full_name'] ?? '');
$license_number = strtoupper(trim($_POST['license_number'] ?? ''));
$contact_number = trim($_POST['contact_number'] ?? '');
$status        = trim($_POST['status'] ?? 'active');

if (!$full_name || !$license_number || !$contact_number) {
    $_SESSION['error'] = 'All fields are required.';
    header('Location: ../../views/admin/drivers.php');
    exit;
}

if (!preg_match('/^[A-Z0-9\-]{3,30}$/', $license_number)) {
    $_SESSION['error'] = 'Invalid license number format.';
    header('Location: ../../views/admin/drivers.php');
    exit;
}

if (!preg_match('/^09\d{9}$/', $contact_number)) {
    $_SESSION['error'] = 'Invalid contact number format.';
    header('Location: ../../views/admin/drivers.php');
    exit;
}

if (!in_array($status, ['active', 'inactive'])) {
    $status = 'active';
}

$driver               = new Drivers($conn);
$driver->full_name     = $full_name;
$driver->license_number = $license_number;
$driver->contact_number = $contact_number;
$driver->status        = $status;

if ($driver->IsLicenseExist()) {
    $_SESSION['error'] = 'A driver with that license number already exists.';
    header('Location: ../../views/admin/drivers.php');
    exit;
}

$result = $driver->AddDriver();

$_SESSION[$result['success'] ? 'success' : 'error'] = $result['success']
    ? 'Driver added successfully.'
    : ($result['message'] ?? 'Unable to add driver. Please check the details and try again.');

header('Location: ../../views/admin/drivers.php');
exit;
?>

