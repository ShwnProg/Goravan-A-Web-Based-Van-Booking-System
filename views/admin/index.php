<?php
ob_start();
$title = 'DASHBOARD';
?>
<?php
$content = ob_get_clean();
include '../layout/admin_layout.php';
?>