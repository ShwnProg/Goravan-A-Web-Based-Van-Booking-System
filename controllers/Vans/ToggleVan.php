<?php
require_once '../../autoload.php';

if (!csrf_check()) {
    $_SESSION['error'] = 'Invalid CSRF token.';
    header('Location: ../../views/admin/vans.php');
    exit;
}

$van_id = (int)  ($_POST['van_id'] ?? 0);
$status = trim(  ($_POST['status'] ?? 'active'));

if (!$van_id) {
    $_SESSION['error'] = 'Invalid van selected.';
    header('Location: ../../views/admin/vans.php');
    exit;
}

if (!in_array($status, ['active', 'inactive'])) {
    $status = 'active';
}

$van         = new Vans($conn);
$van->id     = $van_id;
$van->status = $status;

$result = $van->ToggleVan();

$_SESSION[$result['success'] ? 'success' : 'error'] = $result['success']
    ? 'Status updated successfully.'
    : 'Failed to update van status.';
header('Location: ../../views/admin/vans.php');
exit;