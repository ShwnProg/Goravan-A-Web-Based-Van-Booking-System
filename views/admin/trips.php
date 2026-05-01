<?php
ob_start();
$title = 'TRIPS';
?>
<?php
$content = ob_get_clean();
include '../layout/admin_layout.php';
?>