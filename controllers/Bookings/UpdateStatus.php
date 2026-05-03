<?php
/**
 * Booking Update Status Controller
 * File: /GROAVAN/controllers/Bookings/UpdateStatus.php
 */

require_once '../../autoload.php';

// BASE_URL = '/GROAVAN' (defined in autoload.php)
// Use it for every redirect — no relative paths, no path computation.
// define('BOOKINGS_PAGE', BASE_URL . '/views/admin/bookings.php');

/* ── CSRF guard ──────────────────────────────────────────────────── */
if (!csrf_check()) {
    $_SESSION['error'] = 'Invalid or expired CSRF token. Please refresh and try again.';
    header('Location: ../../views/admin/bookings.php');
    exit;
}

/* ── Method guard ────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/admin/bookings.php');
    ;
    exit;
}

/* ── Sanitize + validate input ───────────────────────────────────── */
$booking_id = (int) ($_POST['booking_id'] ?? 0);
$new_status = trim(strtolower($_POST['status'] ?? ''));
$allowed = ['approved', 'rejected', 'cancelled'];

if (!$booking_id || !in_array($new_status, $allowed, true)) {
    $_SESSION['error'] = 'Invalid booking ID or status.';
    header('Location: ../../views/admin/bookings.php');
    exit;
}

/* ── Fetch current booking ───────────────────────────────────────── */
$bookingObj = new Bookings($conn);
$bookingObj->id = $booking_id;
$rows = $bookingObj->GetBookingByID();

if (empty($rows)) {
    $_SESSION['error'] = 'Booking not found.';
    header('Location: ../../views/admin/bookings.php');
    exit;
}

$booking = $rows[0];
$seat_id = (int) $booking['seat_id_fk'];
$cur_status = $booking['status'];

/* ── Lifecycle guards ────────────────────────────────────────────── */
if (in_array($new_status, ['approved', 'rejected'], true) && $cur_status !== 'pending') {
    $_SESSION['error'] = "Only pending bookings can be {$new_status}.";
    header('Location: ../../views/admin/bookings.php');
    exit;
}

if ($new_status === 'cancelled' && !in_array($cur_status, ['pending', 'approved'], true)) {
    $_SESSION['error'] = 'This booking cannot be cancelled.';
    header('Location: ../../views/admin/bookings.php');
    exit;
}

/* ── Update status ───────────────────────────────────────────────── */
$bookingObj->status = $new_status;
$result = $bookingObj->UpdateStatus();

if (!$result['success']) {
    $_SESSION['error'] = 'Failed to update booking status. Please try again.';
    header('Location: ../../views/admin/bookings.php');
    exit;
}

/* ── Sync seat (non-fatal if schema differs) ─────────────────────── */
if ($seat_id) {
    try {
        $conn->prepare("
            UPDATE seats
            SET    is_available = :val, updated_at = NOW()
            WHERE  seat_id_pk   = :id
        ")->execute([
                    ':val' => ($new_status === 'approved') ? 0 : 1,
                    ':id' => $seat_id,
                ]);
    } catch (PDOException $e) {
        error_log('[UpdateStatus] Seat sync failed: ' . $e->getMessage());
    }
}

/* ── Done ────────────────────────────────────────────────────────── */
$_SESSION['success'] = 'Booking ' . $new_status . ' successfully.';
header('Location: ../../views/admin/bookings.php');
exit;