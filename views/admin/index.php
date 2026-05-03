<?php

require_once "../../autoload.php";
ob_start();
$title    = 'Dashboard';
$page_css = '../../assets/css/dashboard.css';
$page_js  = '../../assets/js/dashboard-js.js';

// FETCH  
$dash = new Dashboard($conn);

$bookingSummary = $dash->GetBookingSummary();   // total, pending, approved, rejected, cancelled
$totalUsers     = $dash->GetTotalUsers();
$schedSummary   = $dash->GetScheduleSummary();  // active_schedules
$recentBookings = $dash->GetRecentBookings();   // last 5
$dailyBookings  = $dash->GetDailyBookings();    // last 7 days


// $bookingsByStatus = $dash->GetBookingsByStatus();
// $seatsBooked      = $dash->GetSeatsBooked();

// Chart data 
$chartDailyLabels = array_column($dailyBookings, 'date');
$chartDailyData   = array_map('intval', array_column($dailyBookings, 'total'));

// Breakdown percentages
$totalBookings = max(1, (int)$bookingSummary['total_bookings']);
$breakdown = [
    ['label' => 'Approved',  'count' => (int)$bookingSummary['approved'],  'color' => '#16a34a'],
    ['label' => 'Cancelled', 'count' => (int)$bookingSummary['cancelled'], 'color' => '#9ca3af'],
    ['label' => 'Rejected',  'count' => (int)$bookingSummary['rejected'],  'color' => '#ef4444'],
    ['label' => 'Pending',   'count' => (int)$bookingSummary['pending'],   'color' => '#F97316'],
];
?>

<!-- ── KPI CARDS ─────────────────────────────────────────────────────────────── -->
<div class="db-top-row">

    <div class="db-stat db-stat--accent">
        <span class="db-stat__label">Total bookings</span>
        <span class="db-stat__val"><?= number_format($bookingSummary['total_bookings']) ?></span>
        <span class="db-stat__sub">All time</span>
    </div>

    <div class="db-stat">
        <span class="db-stat__label">Pending</span>
        <span class="db-stat__val"><?= number_format($bookingSummary['pending']) ?></span>
        <span class="db-stat__sub"><strong>Needs</strong> your action</span>
    </div>

    <div class="db-stat">
        <span class="db-stat__label">Total users</span>
        <span class="db-stat__val"><?= number_format($totalUsers) ?></span>
        <span class="db-stat__sub">Registered passengers</span>
    </div>

    <div class="db-stat">
        <span class="db-stat__label">Active schedules</span>
        <span class="db-stat__val"><?= number_format($schedSummary['active_schedules']) ?></span>
        <span class="db-stat__sub">Not cancelled</span>
    </div>

</div>


<!-- ── MID ROW: BREAKDOWN + BAR CHART ───────────────────────────────────────── -->
<div class="db-mid-row">

    <!-- Booking breakdown -->
    <div class="db-card">
        <div class="db-card__head">
            <span class="db-card__title">Booking breakdown</span>
            <span class="db-pill">All time</span>
        </div>
        <div class="db-seg-bars">
            <?php foreach ($breakdown as $seg):
                $pct = round($seg['count'] / $totalBookings * 100);
            ?>
                <div class="db-seg">
                    <div class="db-seg__meta">
                        <span class="db-seg__name"><?= $seg['label'] ?></span>
                        <span class="db-seg__count">
                            <?= number_format($seg['count']) ?>
                            <span class="db-seg__sep">·</span>
                            <?= $pct ?>%
                        </span>
                    </div>
                    <div class="db-seg__track">
                        <div class="db-seg__fill"
                             style="width:<?= $pct ?>%;background:<?= $seg['color'] ?>">
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Daily bookings bar chart -->
    <div class="db-card">
        <div class="db-card__head">
            <span class="db-card__title">Bookings this week</span>
            <span class="db-pill">Last 7 days</span>
        </div>
        <div class="db-chart-wrap">
            <canvas id="chartDaily"></canvas>
        </div>
    </div>

</div>


<!-- ── BOTTOM ROW: TABLE + ACTIVITY ─────────────────────────────────────────── -->
<div class="db-bottom-row">

    <!-- Recent bookings -->
    <div class="db-tbl-card">
        <div class="db-tbl-head">
            <span class="db-card__title">Recent bookings</span>
            <span class="db-pill">Last 5</span>
        </div>
        <div class="db-tbl-wrap">
            <table class="db-table">
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Passenger</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentBookings)): ?>
                        <tr class="db-empty-row">
                            <td colspan="4">
                                <i class="fa-regular fa-folder-open"></i>
                                No bookings yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentBookings as $b): ?>
                            <tr>
                                <td>
                                    <span class="db-ref">
                                        <?= htmlspecialchars($b['reference_code']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($b['passenger'] ?? '—') ?></td>
                                <td>
                                    <span class="db-badge db-badge--<?= htmlspecialchars($b['status']) ?>">
                                        <?= ucfirst($b['status']) ?>
                                    </span>
                                </td>
                                <td class="db-muted">
                                    <?= date('M d', strtotime($b['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Activity feed -->
    <div class="db-card">
        <div class="db-card__head">
            <span class="db-card__title">Recent activity</span>
            <span class="db-pill db-pill--live">Live</span>
        </div>
        <div class="db-activity" id="db-activity">
            <!-- Populated by dashboard-js.js via GetRecentActivity() or static fallback -->
        </div>
    </div>

</div>


<!-- ── CHART DATA (PHP → JS) ─────────────────────────────────────────────────── -->
<script>
const dailyLabels = <?= json_encode($chartDailyLabels) ?>;
const dailyData   = <?= json_encode($chartDailyData)   ?>;
</script>


<?php
$content = ob_get_clean();
include '../layout/admin_layout.php';
?>