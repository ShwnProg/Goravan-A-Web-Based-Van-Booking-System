<?php
require_once '../../autoload.php';

if (!csrf_check()) {
    $_SESSION['error'] = 'Invalid CSRF token.';
    header('Location: ../../views/admin/vans.php');
    exit;
}

$van_id       = (int)   ($_POST['van_id']       ?? 0);
$plate_number = strtoupper(trim($_POST['plate_number'] ?? ''));
$model        = trim(   ($_POST['model']         ?? ''));
$capacity     = (int)   ($_POST['capacity']      ?? 0);
$status       = trim(   ($_POST['status']        ?? 'active'));

if (!$van_id || !$plate_number || !$model) {
    $_SESSION['error'] = 'Missing required fields.';
    header('Location: ../../views/admin/vans.php');
    exit;
}

if (!preg_match('/^[A-Z0-9\- ]{3,20}$/', $plate_number)) {
    $_SESSION['error'] = 'Invalid plate number format.';
    header('Location: ../../views/admin/vans.php');
    exit;
}

if ($capacity <= 0 || $capacity > 30) {
    $_SESSION['error'] = 'Capacity must be between 1 and 30.';
    header('Location: ../../views/admin/vans.php');
    exit;
}

if (!in_array($status, ['active', 'inactive'])) {
    $status = 'active';
}

$van               = new Vans($conn);
$van->id           = $van_id;
$van->plate_number = $plate_number;
$van->model        = $model;
$van->capacity     = $capacity;
$van->status       = $status;

$van_info = $van->GetVanByID();

if (empty($van_info)) {
    $_SESSION['error'] = 'Van not found.';
    header('Location: ../../views/admin/vans.php');
    exit;
}

$existing = $van_info[0];

$samePlate    = strtolower($existing['plate_number']) === strtolower($plate_number);
$sameModel    = strtolower($existing['model'])        === strtolower($model);
$sameCapacity = (int) $existing['capacity']           === $capacity;
$sameStatus   = $existing['status']                   === $status;

if ($samePlate && $sameModel && $sameCapacity && $sameStatus) {
    $_SESSION['no_changes'] = 'No changes were made.';
    header('Location: ../../views/admin/vans.php');
    exit;
}

if (!$samePlate && $van->IsPlateExistExcept()) {
    $_SESSION['error'] = 'A van with that plate number already exists.';
    header('Location: ../../views/admin/vans.php');
    exit;
}

$result = $van->EditVan();

$_SESSION[$result['success'] ? 'success' : 'error'] = $result['success']
    ? 'Van updated successfully.'
    : 'Failed to update van: ' . ($result['error'] ?? 'Unknown error.');

header('Location: ../../views/admin/vans.php');
exit;