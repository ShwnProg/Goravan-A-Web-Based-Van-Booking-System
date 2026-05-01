<?php
require_once '../../autoload.php';

$routeId  = (int)($_POST['route_id']  ?? 0);
$isActive = (int)($_POST['is_active'] ?? 0);

if (!$routeId) {
    header('Location: ../../views/admin/routes.php');
    exit;
}

$route         = new Routes($conn);
$route->id     = $routeId;
$route->status = $isActive;

$route->ToggleRoute();

header('Location: ../../views/admin/routes.php');
exit;