<?php
ob_start();
$title = 'USERS';
?>
<?php
$content = ob_get_clean();
include '../layout/admin_layout.php';
?>