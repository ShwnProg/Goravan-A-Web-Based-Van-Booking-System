<?php
require_once '../../autoload.php';
if (!isset($_SESSION['is_login'])) { header('Location: ../auth/login.php'); exit; }

ob_start();
$title       = 'My Bookings';
$active_page = 'bookings';
$page_css    = '../../assets/css/user-bookings.css';
$page_js     = '../../assets/js/user-bookings.js';

// Fetch data

$status = $_GET['status'] ?? 'all';
$bk     = new Bookings($conn);
$bk->id = decrypt($_SESSION['id']);
$bk->status = $status;
$stats  = $bk->GetUserStats();
$bookings = $bk->GetBookingsByUserFiltered();
?>

<!-- PAGE BODY -->
<div class="u-body">
    <!-- Stats Strip -->
    <div class="u-stats">
        <div class="u-stat primary">
            <div class="u-stat-lbl">Total</div>
            <div class="u-stat-val"><?= $stats['total'] ?? 0 ?></div>
            <div class="u-stat-sub">All bookings</div>
        </div>
        <div class="u-stat">
            <div class="u-stat-lbl">Pending</div>
            <div class="u-stat-val"><?= $stats['pending'] ?? 0 ?></div>
            <div class="u-stat-sub">Awaiting approval</div>
        </div>
        <div class="u-stat">
            <div class="u-stat-lbl">Completed</div>
            <div class="u-stat-val"><?= $stats['completed'] ?? 0 ?></div>
            <div class="u-stat-sub">Past trips</div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="u-filtabs">
        <a href="?status=all" class="u-ftab <?= $status === 'all' ? 'active' : '' ?>">All</a>
        <a href="?status=upcoming" class="u-ftab <?= $status === 'upcoming' ? 'active' : '' ?>">Upcoming</a>
        <a href="?status=completed" class="u-ftab <?= $status === 'completed' ? 'active' : '' ?>">Completed</a>
        <a href="?status=cancelled" class="u-ftab <?= $status === 'cancelled' ? 'active' : '' ?>">Cancelled</a>
    </div>

    <!-- Booking List -->
    <div class="u-bk-list">
        <?php if ($bookings && count($bookings) > 0): ?>
            <?php foreach ($bookings as $booking): ?>
            <a href="booking-detail.php?id=<?= $booking['id'] ?>" class="u-bk-item">
                <div>
                    <div class="u-bk-ref"><?= htmlspecialchars($booking['reference_code']) ?></div>
                    <div class="u-bk-route"><?= htmlspecialchars($booking['origin']) ?> → <?= htmlspecialchars($booking['destination']) ?></div>
                    <div class="u-bk-meta">
                        <?= date('M j, Y · g:i A', strtotime($booking['departure_date'] . ' ' . $booking['departure_time'])) ?>
                        · <?= $booking['seats'] ?> seat(s)
                    </div>
                </div>
                <span class="u-badge <?= $booking['status'] ?>">
                    <?= ucfirst($booking['status']) ?>
                </span>
            </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="u-empty-state">
                <span class="u-empty-icon"><i class="fa-solid fa-inbox"></i></span>
                <p>No bookings found</p>
                <p>Try changing your filter or <a href="schedule.php">book a new trip</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layout/user_layout.php';
?>