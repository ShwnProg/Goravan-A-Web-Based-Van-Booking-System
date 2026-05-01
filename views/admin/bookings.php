<?php
ob_start();
$title = 'BOOKINGS';
?>
<?php
$content = ob_get_clean();
include '../layout/admin_layout.php';
?>