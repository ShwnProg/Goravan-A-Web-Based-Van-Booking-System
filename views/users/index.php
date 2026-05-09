<?php
require_once '../../autoload.php';
if (!isset($_SESSION['is_login'])) { header('Location: ../auth/login.php'); exit; }

ob_start();
$title       = 'Home';
$active_page = 'home';
$page_css    = '../../assets/css/user-home.css';
$page_js     = '../../assets/js/user-home.js';

// Fetch data
$um     = new Users($conn);
$um->id = decrypt($_SESSION['id']);
$user   = $um->GetUserById();

$bk             = new Bookings($conn);
$bk->id = decrypt($_SESSION['id']);
$upcomingTrip   = $bk->GetUpcomingTripByUser();   // nearest approved future booking
$recentBookings = $bk->GetRecentBookingsByUser(); // last 3
$stats          = $bk->GetUserStats();             // total, upcoming, completed
$routes         = (new Routes($conn))->GetActiveRoutes();  // for search dropdowns
$locations      = LOCATIONS; // Use LOCATIONS constant from autoload.php
?>

<!-- COMPACT DASHBOARD HEADER -->
<section class="u-dashboard-header">
    <div class="u-header-content">
        <div class="u-header-text">
            <h1 class="u-header-title">Welcome Back, <span><?= htmlspecialchars(ucfirst($user['firstname'] ?? '')) ?>!</span></h1>
            <p class="u-header-subtitle">Manage your bookings and schedules</p>
        </div>
        <div class="u-header-date">
            <span class="u-date-label">Today</span>
            <span class="u-date-value"><?= date('M j, Y') ?></span>
        </div>
    </div>
</section>

<!-- SEARCH TOOLBAR -->
<section class="u-search-toolbar">
    <div class="u-toolbar-content">
        <h2 class="u-toolbar-title">Find a Trip</h2>
        <form class="u-toolbar-form" action="schedule.php" method="GET">
            <div class="u-tf">
                <label for="from">From</label>
                <select id="from" name="from" class="ss" data-placeholder="Select origin" required>
                    <option value="">Select origin</option>
                    <?php foreach ($locations as $name => $coords): ?>
                    <option value="<?= $name ?>"><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="u-t-sep"><i class = 'fa-solid fa-arrow-right'></i></div>
            <div class="u-tf">
                <label for="to">To</label>
                <select id="to" name="to" class="ss" data-placeholder="Select destination" required>
                    <option value="">Select destination</option>
                    <?php foreach ($locations as $name => $coords): ?>
                    <option value="<?= $name ?>"><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="u-tf">
                <label for="date">Date</label>
                <input type="date" id="date" name="date" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
            </div>
            <button type="submit" class="u-tbtn">
                <i class="fa-solid fa-magnifying-glass"></i> Search
            </button>
        </form>
    </div>
</section>

<!-- PAGE BODY -->
<div class="u-body">
    <!-- Stats Overview -->
    <div class="u-sec">
        <div class="u-sec-head">
            <h2 class="u-sec-title">Your Stats</h2>
        </div>
        <div class="u-stats">
            <div class="u-stat primary">
                <div class="u-stat-icon">
                    <i class="fa-solid fa-route"></i>
                </div>
                <div class="u-stat-content">
                    <div class="u-stat-lbl">Total trips</div>
                    <div class="u-stat-val"><?= $stats['total'] ?? 0 ?></div>
                    <div class="u-stat-trend">+12% vs last month</div>
                </div>
            </div>
            <div class="u-stat">
                <div class="u-stat-icon">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div class="u-stat-content">
                    <div class="u-stat-lbl">Upcoming</div>
                    <div class="u-stat-val"><?= $stats['upcoming'] ?? 0 ?></div>
                    <div class="u-stat-trend">Next trip soon</div>
                </div>
            </div>
            <div class="u-stat">
                <div class="u-stat-icon">
                    <i class="fa-solid fa-check-circle"></i>
                </div>
                <div class="u-stat-content">
                    <div class="u-stat-lbl">Completed</div>
                    <div class="u-stat-val"><?= $stats['completed'] ?? 0 ?></div>
                    <div class="u-stat-trend">All successful</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="u-sec">
        <div class="u-sec-head">
            <h2 class="u-sec-title">Quick Actions</h2>
        </div>
        <div class="u-qa-grid">
            <a href="schedule.php" class="u-qa-card">
                <div class="u-qa-icon">
                    <i class="fa-solid fa-van-shuttle"></i>
                </div>
                <span class="u-qa-label">Book Van</span>
            </a>
            <a href="my-bookings.php" class="u-qa-card">
                <div class="u-qa-icon">
                    <i class="fa-solid fa-list-ul"></i>
                </div>
                <span class="u-qa-label">My Bookings</span>
            </a>
            <a href="#" class="u-qa-card">
                <div class="u-qa-icon">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <span class="u-qa-label">Receipts</span>
            </a>
            <a href="#" class="u-qa-card">
                <div class="u-qa-icon">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <span class="u-qa-label">Support</span>
            </a>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="u-sec">
        <div class="u-sec-head">
            <h2 class="u-sec-title">Recent Bookings</h2>
            <a href="my-bookings.php" class="u-sec-link">View all <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="u-bookings-table">
            <?php if ($recentBookings && count($recentBookings) > 0): ?>
                <div class="u-table-header">
                    <div class="u-col-ref">Ref Code</div>
                    <div class="u-col-route">Route</div>
                    <div class="u-col-date">Date</div>
                    <div class="u-col-status">Status</div>
                    <div class="u-col-action"></div>
                </div>
                <?php foreach ($recentBookings as $booking): ?>
                <div class="u-table-row">
                    <div class="u-col-ref">
                        <span class="u-ref-code"><?= htmlspecialchars($booking['reference_code']) ?></span>
                    </div>
                    <div class="u-col-route">
                        <span class="u-route-text"><?= htmlspecialchars($booking['origin']) ?> → <?= htmlspecialchars($booking['destination']) ?></span>
                    </div>
                    <div class="u-col-date">
                        <div class="u-date-main"><?= date('M j, Y', strtotime($booking['departure_date'])) ?></div>
                        <div class="u-date-time"><?= date('g:i A', strtotime($booking['departure_time'])) ?></div>
                    </div>
                    <div class="u-col-status">
                        <span class="u-badge <?= $booking['status'] ?>">
                            <?= ucfirst($booking['status']) ?>
                        </span>
                    </div>
                    <div class="u-col-action">
                        <a href="booking-detail.php?id=<?= $booking['id'] ?>" class="u-action-link">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="u-table-empty">
                    <div class="u-empty-icon">
                        <i class="fa-solid fa-calendar-xmark"></i>
                    </div>
                    <div class="u-empty-text">
                        <p>No recent bookings</p>
                        <a href="schedule.php" class="u-empty-link">Book your first trip</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layout/user_layout.php';
?>