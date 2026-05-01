<?php
require_once '../../autoload.php';

if (!csrf_check()) {
    $_SESSION['error'] = 'Invalid CSRF token.';
    header('Location: ../../views/admin/routes.php');
    exit;
}

$routeId = (int)($_POST['route_id'] ?? 0);

if (!$routeId) {
    $_SESSION['error'] = 'Invalid route.';
    header('Location: ../../views/admin/routes.php');
    exit;
}

$route     = new Routes($conn);
$route->id = $routeId;

$result = $route->DeleteRoute();

$_SESSION[$result['success'] ? 'success' : 'error'] = $result['success']
    ? 'Route deleted successfully.'
    : 'Failed to delete route.';

header('Location: ../../views/admin/routes.php');
exit;