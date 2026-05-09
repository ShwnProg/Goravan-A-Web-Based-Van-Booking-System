<?php
require_once '../../autoload.php';
if (!isset($_SESSION['is_login'])) { header('Location: ../auth/login.php'); exit; }

ob_start();
$title       = 'Booking Details';
$active_page = 'bookings';
$page_css    = '../../assets/css/user-bookings.css';
$page_js     = '../../assets/js/user-bookings.js';

// Fetch booking details
$id = $_GET['id'] ?? null;
if (!$id) { header('Location: my-bookings.php'); exit; }

$bk = new Bookings($conn);
$bk->id = decrypt($id);
$booking = $bk->GetBookingByID();

if (!$booking) { header('Location: my-bookings.php'); exit; }
$booking = $booking[0]; // Assuming single result

?>

<!-- PAGE BODY -->
<div class="u-body">
    <!-- Back Button -->
    <div class="u-back-link">
        <a href="my-bookings.php" class="u-back-btn">
            <i class="fa-solid fa-arrow-left"></i> Back to My Bookings
        </a>
    </div>

    <!-- Booking Detail Card -->
    <div class="u-bk-detail">
        <div class="u-bk-header">
            <div>
                <div class="u-bk-ref"><?= htmlspecialchars($booking['reference_code']) ?></div>
                <div class="u-bk-route"><?= htmlspecialchars($booking['route_display']) ?></div>
            </div>
            <span class="u-badge <?= $booking['status'] ?>">
                <?= ucfirst($booking['status']) ?>
            </span>
        </div>

        <div class="u-bk-info">
            <div class="u-info-row">
                <span class="u-info-label"><i class="fa-solid fa-calendar-days"></i> Departure Date & Time:</span>
                <span class="u-info-value">
                    <?= date('M j, Y · g:i A', strtotime($booking['departure_date'] . ' ' . $booking['departure_time'])) ?>
                </span>
            </div>
            <div class="u-info-row">
                <span class="u-info-label"><i class="fa-solid fa-chair"></i> Seat Number:</span>
                <span class="u-info-value"><?= htmlspecialchars($booking['seat_number']) ?> (Row <?= $booking['seat_row'] ?>, Col <?= $booking['seat_col'] ?>)</span>
            </div>
            <div class="u-info-row">
                <span class="u-info-label"><i class="fa-solid fa-peso-sign"></i> Fare:</span>
                <span class="u-info-value">₱<?= number_format($booking['route_fare'], 2) ?></span>
            </div>
            <div class="u-info-row">
                <span class="u-info-label"><i class="fa-solid fa-user-tie"></i> Driver:</span>
                <span class="u-info-value"><?= htmlspecialchars($booking['driver_name']) ?></span>
            </div>
            <div class="u-info-row">
                <span class="u-info-label"><i class="fa-solid fa-bus"></i> Van:</span>
                <span class="u-info-value"><?= htmlspecialchars($booking['van_model']) ?> (<?= htmlspecialchars($booking['van_plate']) ?>)</span>
            </div>
            <div class="u-info-row">
                <span class="u-info-label"><i class="fa-solid fa-route"></i> Trip Status:</span>
                <span class="u-info-value"><?= ucfirst($booking['schedule_status']) ?></span>
            </div>
            <?php if ($booking['arrived_at']): ?>
            <div class="u-info-row">
                <span class="u-info-label"><i class="fa-solid fa-flag-checkered"></i> Arrived At:</span>
                <span class="u-info-value"><?= date('M j, Y · g:i A', strtotime($booking['arrived_at'])) ?></span>
            </div>
            <?php endif; ?>
            <div class="u-info-row">
                <span class="u-info-label"><i class="fa-solid fa-clock"></i> Booked At:</span>
                <span class="u-info-value"><?= date('M j, Y · g:i A', strtotime($booking['created_at'])) ?></span>
            </div>
        </div>

        <?php if ($booking['status'] === 'pending'): ?>
        <div class="u-bk-actions">
            <button class="u-btn u-btn-secondary" onclick="cancelBooking('<?= $id ?>')">Cancel Booking</button>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function cancelBooking(id) {
    if (confirm('Are you sure you want to cancel this booking?')) {
        // Implement cancel logic, perhaps AJAX to update status
        alert('Cancel functionality to be implemented');
    }
}
</script>

<?php
$content = ob_get_clean();
include '../layout/user_layout.php';
?>