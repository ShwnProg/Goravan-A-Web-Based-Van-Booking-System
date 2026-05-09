<?php
require_once '../../autoload.php';
if (!isset($_SESSION['is_login'])) {
    header('Location: ../auth/login.php');
    exit;
}

ob_start();
$title = 'Schedule';
$active_page = 'schedule';
$page_css = '../../assets/css/user-schedule.css';
$page_js = '../../assets/js/user-schedule.js';

// Fetch data
$userId = decrypt($_SESSION['id']);
$um = new Users($conn);
$um->id = $userId;
$user = $um->GetUserById();

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');
$sched = new Schedules($conn);
$routes = (new Routes($conn))->GetActiveRoutes();
$results = $sched->GetAvailableSchedules();
$locations = LOCATIONS; // Use LOCATIONS constant from autoload.php
?>

<!-- PAGE BODY -->
<div class="u-body mobile-view">
    <!-- Search Bar -->
    <div class="u-sec">
        <div class="u-search-card" style="border-radius: 12px; border-bottom: 1px solid var(--u-border);">
            <form class="u-srow" action="" method="GET">
                <div class="u-sf">
                    <label for="from">From</label>
                    <select id="from" name="from" class="ss" data-placeholder="Select origin">
                        <option value="">Select origin</option>
                        <?php foreach ($locations as $name => $coords): ?>
                            <option value="<?= $name ?>" <?= $from === $name ? 'selected' : '' ?>>
                                <?= $name ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="u-s-sep"><i class = 'fa-solid fa-arrow-right'></i></div>
                <div class="u-sf">
                    <label for="to">To</label>
                    <select id="to" name="to" class="ss" data-placeholder="Select destination">
                        <option value="">Select destination</option>
                        <?php foreach ($locations as $name => $coords): ?>
                            <option value="<?= $name ?>" <?= $to === $name ? 'selected' : '' ?>>
                                <?= $name ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="u-sf">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" value="<?= htmlspecialchars($date) ?>"
                        min="<?= date('Y-m-d') ?>">
                </div>
                <button type="submit" class="u-sbtn">
                    <i class="fa-solid fa-magnifying-glass"></i> Search
                </button>
            </form>
        </div>
    </div>

    <!-- Results Grid -->
    <div class="u-sec">
        <div class="u-sec-head">
            <h2 class="u-sec-title">Available Schedules</h2>
            <?php if ($results && count($results) > 0): ?>
                <span style="font-size: 12px; color: var(--u-muted);"><?= count($results) ?> result(s)</span>
            <?php endif; ?>
        </div>

        <?php if ($results && count($results) > 0): ?>
            <div class="u-schedule-grid">
                <?php foreach ($results as $schedule): ?>
                    <div class="u-schedule-card">
                        <div class="u-schedule-header">
                            <div class="u-schedule-time">
                                <div class="u-schedule-dep"><?= date('g:i A', strtotime($schedule['departure_time'])) ?></div>
                                <div class="u-schedule-arr">Est. <?= date('g:i A', strtotime($schedule['arrived_at'])) ?></div>
                            </div>
                            <div class="u-schedule-route">
                                <div class="u-schedule-origin"><?= htmlspecialchars($schedule['origin']) ?></div>
                                <div class="u-schedule-arrow"><i class="fa-solid fa-arrow-right"></i></div>
                                <div class="u-schedule-dest"><?= htmlspecialchars($schedule['destination']) ?></div>
                            </div>
                        </div>
                        <div class="u-schedule-meta">
                            <div class="u-schedule-info">
                                <i class="fa-solid fa-van"></i>
                                <span><?= htmlspecialchars($schedule['model'] ?? 'Standard Van') ?></span>
                            </div>
                            <div class="u-schedule-info">
                                <i class="fa-solid fa-chair"></i>
                                <span><?= $schedule['capacity'] ?> seats available</span>
                            </div>
                        </div>
                        <div class="u-schedule-footer">
                            <div class="u-schedule-price">
                                <span class="u-price-label">per seat</span>
                                <span class="u-price-value">₱<?= number_format($schedule['route_fare'], 2) ?></span>
                            </div>
                            <button class="u-sbtn u-book-btn" data-schedule-id="<?php echo $schedule['schedule_id_pk'] ?? ''?>"
                                data-price="<?php echo $schedule['route_fare'] ?>">
                                Book Now
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: var(--u-muted);">
                <i class="fa-solid fa-calendar-xmark" style="font-size: 36px; margin-bottom: 12px;"></i>
                <p style="font-size: 14px; margin-bottom: 8px;">No schedules available</p>
                <p style="font-size: 12px;">Try adjusting your search criteria or date</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Booking Confirmation Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content booking-modal">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="bookingModalLabel">Confirm Booking</h5>
                    <span class="modal-subtitle">Review passenger details and total fare before confirming.</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bookingForm" action="../../controllers/users/BookingController.php" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="schedule_id" id="scheduleId">
                    <input type="hidden" name="action" value="create">

                    <div class="u-form-group">
                        <label for="passengerName">Passenger Name</label>
                        <input type="text" class="form-control" id="passengerName" name="passenger_name"
                            value="<?= htmlspecialchars($user['firstname'] ?? '') . ' ' . htmlspecialchars($user['lastname'] ?? '') ?>"
                            required>
                    </div>

                    <div class="u-form-group">
                        <label for="contactNumber">Contact Number</label>
                        <input type="tel" class="form-control" id="contactNumber" name="contact_number"
                            value="<?= htmlspecialchars($user['contact_number'] ?? '') ?>"
                            placeholder="09XX XXX XXXX"
                            required>
                    </div>

                    <div class="u-form-group">
                        <label for="seatsCount">Number of Seats</label>
                        <input type="number" class="form-control" id="seatsCount" name="seats_count" min="1" max="10" value="1" required>
                    </div>

                    <div class="u-total-price">
                        <span class="u-total-label">Total Fare</span>
                        <span class="u-total-value" id="totalPriceDisplay">₱0.00</span>
                    </div>

                    <div class="u-modal-actions">
                        <button type="button" class="u-btn u-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="u-btn u-btn-primary">Confirm Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layout/user_layout.php';
?>