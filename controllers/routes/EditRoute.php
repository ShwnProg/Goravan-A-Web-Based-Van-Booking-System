<?php
require_once '../../autoload.php';

if (!csrf_check()) {
    $_SESSION['error'] = 'Invalid CSRF token.';
    header('Location: ../../views/admin/routes.php');
    exit;
}

$routeId = (int) ($_POST['route_id'] ?? 0);
$origin = trim($_POST['origin'] ?? '');
$destination = trim($_POST['destination'] ?? '');
$fare = (double) ($_POST['fare'] ?? 0);
$status = (int) ($_POST['is_active'] ?? 1);

$stops = array_values(array_filter(
    array_map('trim', $_POST['stops'] ?? []),
    fn($s) => $s !== ''
));

if (!$routeId || !$origin || !$destination) {
    $_SESSION['error'] = 'Missing required fields.';
    header('Location: ../../views/admin/routes.php');
    exit;
}
if ($fare <= 0) {
    $_SESSION['error'] = 'Fare must be positive integer.';
    header('Location: ../../views/admin/routes.php');
    exit;
}
if ($origin === $destination) {
    $_SESSION['error'] = 'Origin and destination cannot be the same.';
    header('Location: ../../views/admin/routes.php');
    exit;
}

$route = new Routes($conn);
$route->id = $routeId;
$route->origin = $origin;
$route->destination = $destination;
$route->status = $status;
$route->fare=$fare;
$route->stops = $stops;

if ($route->IsRouteExist()) {
    $_SESSION['error'] = 'That route already exists.';
    header('Location: ../../views/admin/routes.php');
    exit;
}
$result = $route->EditRoute();

$_SESSION[$result['success'] ? 'success' : 'error'] = $result['success']
    ? 'Route updated successfully.'
    : 'Failed to update route.';

header('Location: ../../views/admin/routes.php');
exit;