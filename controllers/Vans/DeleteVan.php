<?php
require_once '../../autoload.php';

if (!csrf_check()) {
    $_SESSION['error'] = 'Invalid CSRF token.';
    header('Location: ../../views/admin/vans.php');
    exit;
}

$van_id = (int) ($_POST['van_id'] ?? 0);

if (!$van_id) {
    $_SESSION['error'] = 'Invalid van.';
    header('Location: ../../views/admin/vans.php');
    exit;
}

$van     = new Vans($conn);
$van->id = $van_id;

$result = $van->DeleteVan();

$_SESSION[$result['success'] ? 'success' : 'error'] = $result['success']
    ? 'Van deleted successfully.'
    : 'Failed to delete van.';

header('Location: ../../views/admin/vans.php');
exit;