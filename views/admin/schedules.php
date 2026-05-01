<?php
ob_start();
$title = 'SCHEDULES';
?>
<?php
$content = ob_get_clean();
include '../layout/admin_layout.php';
?>