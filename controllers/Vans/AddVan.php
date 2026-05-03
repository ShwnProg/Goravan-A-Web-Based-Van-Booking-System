<?php
require_once '../../autoload.php';

if (!csrf_check()) {
    $_SESSION['error'] = 'Invalid CSRF token.';
    header('Location: ../../views/admin/vans.php');
    exit;
}

$plate_number = strtoupper(trim($_POST['plate_number'] ?? ''));
$model        = trim($_POST['model'] ?? '');
$capacity     = (int) ($_POST['capacity'] ?? 0);
$status       = trim($_POST['status'] ?? 'active');

if (!$plate_number) {
    $_SESSION['error'] = 'Plate number is required.';
    header('Location: ../../views/admin/vans.php');
    exit;
}

if (!preg_match('/^[A-Z0-9\- ]{3,20}$/', $plate_number)) {
    $_SESSION['error'] = 'Invalid plate number format.';
    header('Location: ../../views/admin/vans.php');
    exit;
}

if (!$model) {
    $_SESSION['error'] = 'Van model is required.';
    header('Location: ../../views/admin/vans.php');
    exit;
}

if ($capacity <= 0 || $capacity > 14) {
    $_SESSION['error'] = 'Capacity must be between 1 and 14.';
    header('Location: ../../views/admin/vans.php');
    exit;
}

if (!in_array($status, ['active', 'inactive'])) {
    $status = 'active';
}

$van               = new Vans($conn);
$van->plate_number = $plate_number;
$van->model        = $model;
$van->capacity     = $capacity;
$van->status       = $status;

if ($van->IsPlateExist()) {
    $_SESSION['error'] = 'A van with that plate number already exists.';
    header('Location: ../../views/admin/vans.php');
    exit;
}

$result = $van->AddVan();

$_SESSION[$result['success'] ? 'success' : 'error'] = $result['success']
    ? 'Van added successfully.'
    : 'Failed to add van: ' . ($result['error'] ?? 'Unknown error.');

header('Location: ../../views/admin/vans.php');
exit;