<?php
ob_start();
$title = 'PAYMENTS';
?>
<?php
$content = ob_get_clean();
include '../layout/admin_layout.php';
?>