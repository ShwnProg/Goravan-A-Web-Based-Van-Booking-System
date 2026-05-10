<?php
require_once '../../autoload.php';

if (!csrf_check()) {
    $_SESSION['error'] = 'Invalid or expired CSRF token. Please refresh and try again.';
    header('Location: ../../views/admin/bookings.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/admin/bookings.php');
    exit;
}

$booking_id = (int) ($_POST['booking_id'] ?? 0);
$new_status = trim(strtolower($_POST['status'] ?? ''));
$allowed = ['approved', 'rejected', 'cancelled'];

if (!$booking_id || !in_array($new_status, $allowed, true)) {
    $_SESSION['error'] = 'Invalid booking ID or status.';
    header('Location: ../../views/admin/bookings.php');
    exit;
}

$bookingObj = new Bookings($conn);
$rows = $bookingObj->GetBookingGroupByID($booking_id);

if (empty($rows)) {
    $_SESSION['error'] = 'Booking not found.';
    header('Location: ../../views/admin/bookings.php');
    exit;
}

$referenceCode = $rows[0]['reference_code'];
$currentStatuses = array_values(array_unique(array_column($rows, 'status')));
$isAllPending = count($currentStatuses) === 1 && $currentStatuses[0] === 'pending';
$isApprovable = count(array_diff($currentStatuses, ['pending', 'approved'])) === 0;
$isCancellable = count(array_diff($currentStatuses, ['pending', 'approved'])) === 0;

if ($new_status === 'approved' && !$isApprovable) {
    $_SESSION['error'] = 'Only pending or partially approved bookings can be approved.';
    header('Location: ../../views/admin/bookings.php');
    exit;
}

if ($new_status === 'rejected' && !$isAllPending) {
    $_SESSION['error'] = 'Only pending bookings can be rejected.';
    header('Location: ../../views/admin/bookings.php');
    exit;
}

if ($new_status === 'cancelled' && !$isCancellable) {
    $_SESSION['error'] = 'This booking cannot be cancelled.';
    header('Location: ../../views/admin/bookings.php');
    exit;
}

$bookingObj->status = $new_status;
$result = $bookingObj->UpdateStatusByReferenceCode($referenceCode);

if (!$result['success']) {
    $_SESSION['error'] = 'Failed to update booking status. Please try again.';
    header('Location: ../../views/admin/bookings.php');
    exit;
}

$_SESSION['success'] = 'Booking ' . $new_status . ' successfully. Updated ' . count($rows) . ' seat(s).';
header('Location: ../../views/admin/bookings.php');
exit;
