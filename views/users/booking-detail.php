<?php
require_once '../../autoload.php';
if (!isset($_SESSION['is_login'])) { header('Location: ../auth/login.php'); exit; }

ob_start();
$title       = 'Booking Details';
$active_page = 'bookings';
$page_css    = '../../assets/css/user-bookings.css';
$page_js     = '../../assets/js/user-bookings.js';

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: my-bookings.php'); exit; }

$userId = (int) decrypt($_SESSION['id']);
$bookingId = (int) decrypt($id);
if (!$bookingId) { header('Location: my-bookings.php'); exit; }

$bk = new Bookings($conn);
$booking = $bk->GetUserBookingGroupByID($bookingId, $userId);

if (!$booking) { header('Location: my-bookings.php'); exit; }
?>

<div class="u-body">
    <div class="u-back-link">
        <a href="my-bookings.php" class="u-back-btn">
            <i class="fa-solid fa-arrow-left"></i> Back to My Bookings
        </a>
    </div>

    <div class="u-bk-detail">
        <div class="u-bk-header">
            <div>
                <div class="u-bk-ref"><?= htmlspecialchars($booking['reference_code']) ?></div>
                <div class="u-bk-route"><?= htmlspecialchars($booking['route_display']) ?></div>
            </div>
            <span class="u-badge <?= htmlspecialchars($booking['status']) ?>">
                <?= ucfirst($booking['status']) ?>
            </span>
        </div>

        <div class="u-bk-info">
            <div class="u-info-row">
                <span class="u-info-label"><i class="fa-solid fa-calendar-days"></i> Departure Date & Time:</span>
                <span class="u-info-value">
                    <?= date('M j, Y', strtotime($booking['departure_date'])) ?> &middot; <?= date('g:i A', strtotime($booking['departure_time'])) ?>
                </span>
            </div>

            <div class="u-info-row">
                <span class="u-info-label"><i class="fa-solid fa-chair"></i> Seats:</span>
                <span class="u-info-value">
                    <?= (int) $booking['seats_count'] ?> seat<?= (int) $booking['seats_count'] === 1 ? '' : 's' ?>
                    <?php if (!empty($booking['seat_numbers'])): ?>
                        <span class="u-detail-seat-row">
                            <?php foreach (explode(',', $booking['seat_numbers']) as $seatNumber): ?>
                                <span class="u-seat-chip"><?= htmlspecialchars(trim($seatNumber)) ?></span>
                            <?php endforeach; ?>
                        </span>
                    <?php endif; ?>
                </span>
            </div>

            <div class="u-info-row">
                <span class="u-info-label"><i class="fa-solid fa-peso-sign"></i> Fare:</span>
                <span class="u-info-value">&#8369;<?= number_format((float) $booking['route_fare'], 2) ?> per seat</span>
            </div>

            <?php if (!empty($booking['payment_amount'])): ?>
            <div class="u-info-row">
                <span class="u-info-label"><i class="fa-solid fa-wallet"></i> Payment:</span>
                <span class="u-info-value">
                    <?= ucfirst($booking['payment_method']) ?> &middot; &#8369;<?= number_format((float) $booking['payment_amount'], 2) ?>
                    (<?= ucfirst($booking['payment_status']) ?>)
                </span>
            </div>
            <?php endif; ?>

            <?php if (!empty($booking['passenger_name'])): ?>
            <div class="u-info-row">
                <span class="u-info-label"><i class="fa-solid fa-user"></i> Passenger:</span>
                <span class="u-info-value"><?= htmlspecialchars($booking['passenger_name']) ?></span>
            </div>
            <?php endif; ?>

            <div class="u-info-row">
                <span class="u-info-label"><i class="fa-solid fa-user-tie"></i> Driver:</span>
                <span class="u-info-value"><?= htmlspecialchars($booking['driver_name'] ?? 'Unassigned') ?></span>
            </div>

            <div class="u-info-row">
                <span class="u-info-label"><i class="fa-solid fa-bus"></i> Van:</span>
                <span class="u-info-value"><?= htmlspecialchars($booking['van_model']) ?> (<?= htmlspecialchars($booking['van_plate']) ?>)</span>
            </div>

            <div class="u-info-row">
                <span class="u-info-label"><i class="fa-solid fa-route"></i> Trip Status:</span>
                <span class="u-info-value"><?= ucfirst($booking['schedule_status']) ?></span>
            </div>

            <?php if (!empty($booking['arrived_at'])): ?>
            <div class="u-info-row">
                <span class="u-info-label"><i class="fa-solid fa-flag-checkered"></i> Arrived At:</span>
                <span class="u-info-value"><?= date('M j, Y', strtotime($booking['arrived_at'])) ?> &middot; <?= date('g:i A', strtotime($booking['arrived_at'])) ?></span>
            </div>
            <?php endif; ?>

            <div class="u-info-row">
                <span class="u-info-label"><i class="fa-solid fa-clock"></i> Booked At:</span>
                <span class="u-info-value"><?= date('M j, Y', strtotime($booking['created_at'])) ?> &middot; <?= date('g:i A', strtotime($booking['created_at'])) ?></span>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layout/user_layout.php';
?>
