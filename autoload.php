<?php
session_start();
define('BASE_URL', '/GROAVAN');
define('LOCATIONS', [
    'Maasin City'   => [10.1322, 124.8426],
    'Bontoc'        => [10.2167, 124.8833],
    'Sogod'         => [10.3833, 124.9833],
    'Malitbog'      => [10.1667, 124.8167],
    'Padre Burgos'  => [10.0167, 125.0167],
    'Limasawa'      => [9.9000,  125.1000],
    'Liloan'        => [10.1000, 124.7167],
    'Macrohon'      => [10.0667, 124.9167],
    'San Juan'      => [10.2333, 125.1667],
    'Silago'        => [10.5167, 125.1833],
    'Hinunangan'    => [10.4000, 125.2000],
    'Hinundayan'    => [10.3667, 125.1333],
    'St. Bernard'   => [10.4833, 125.1333],
    'San Ricardo'   => [10.2667, 125.2167],
    'Tomas Oppus'   => [10.2500, 124.9833],
    'San Francisco' => [10.2000, 125.0167],
    'Libagon'       => [10.1500, 124.9667],
    'Anahawan'      => [10.1000, 125.0333],
    'Bato'          => [10.3333, 124.9667],
    'Pintuyan'      => [10.0833, 125.1833],
]);
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/helpers/csrf_helper.php";
require_once __DIR__ .'/helpers/encryption.php';

spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . "/classes/",
    ];

    foreach ($paths as $path) {
        $file = $path . $class . ".php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    die("Class $class not found.");
});

$database = new Database();
$conn = $database->GetConnection();

// if($conn){
//     echo "Connected";
// }
?>