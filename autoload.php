<?php
session_start();
define('BASE_URL', '/GROAVAN');
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/helpers/csrf_helper.php";
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