<?php

require_once '../../autoload.php';

// Validate CSRF
if (!csrf_check()) {
    $_SESSION['error'] = 'Invalid CSRF token.';
    header('Location: ../../views/admin/bookings.php');
    exit;
}

// Validate method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: ../../views/admin/bookings.php');
    exit;
}

// Get input
$booking_id = (int) ($_POST['booking_id'] ?? 0);
$new_status = trim($_POST['status'] ?? '');

// Validate input
if (!$booking_id || !$new_status) {
    $_SESSION['error'] = 'Invalid input.';
    header('Location: ../../views/admin/bookings.php');
    exit;
}

// Update booking status
$bookingObj = new Bookings($conn);
$bookingObj->id = $booking_id;
$bookingObj->status = $status;
$updated = $bookingObj->UpdateStatus();

$_SESSION[$updated ? 'success' : 'error'] = $updated
    ? 'Booking status updated successfully.'
    : 'Failed to update booking status.';

header('Location: ../../views/admin/bookings.php');
exit;
