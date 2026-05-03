<?php
require_once "../../autoload.php";

$title    = 'Bookings';
$page_css = '../../assets/css/bookings.css';
$page_js  = '../../assets/js/bookings-js.js';

ob_start();

$bookingObj = new Bookings($conn);
$bookings   = $bookingObj->GetAllBookings();

// Count by status
$statusCounts = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0];
foreach ($bookings as $b) {
    $statusCounts[$b['status']]++;
}
?>

<div class="toolbar">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="booking-search" placeholder="Search by reference code or passenger...">
    </div>
    <div class="filter-group">
        <select id="booking-filter-status" class="filter-select">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>
</div>

<div class="bookings-wrapper">

    <!-- ── STATS CARDS ──────────────────────────────────────────────────── -->
    <div class="stats-row">
        <div class="stat-card pending">
            <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-content">
                <span class="stat-label">Pending</span>
                <span class="stat-number"><?= $statusCounts['pending'] ?></span>
            </div>
        </div>
        <div class="stat-card approved">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content">
                <span class="stat-label">Approved</span>
                <span class="stat-number"><?= $statusCounts['approved'] ?></span>
            </div>
        </div>
        <div class="stat-card rejected">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-content">
                <span class="stat-label">Rejected</span>
                <span class="stat-number"><?= $statusCounts['rejected'] ?></span>
            </div>
        </div>
        <div class="stat-card cancelled">
            <div class="stat-icon"><i class="fas fa-ban"></i></div>
            <div class="stat-content">
                <span class="stat-label">Cancelled</span>
                <span class="stat-number"><?= $statusCounts['cancelled'] ?></span>
            </div>
        </div>
    </div>

    <!-- ── TABLE CARD ──────────────────────────────────────────────────── -->
    <div class="bookings-card">
        <div class="bookings-card-header">
            <h2>
                <i class="fas fa-ticket-alt" style="margin-right:7px;color:var(--color-accent)"></i>
                All Bookings
            </h2>
            <span id="booking-count"></span>
        </div>
        <div class="bookings-table-wrap">
            <table class="bookings-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Reference Code</th>
                        <th>Passenger</th>
                        <th>Route</th>
                        <th>Seat</th>
                        <th>Status</th>
                        <th>Payment Due</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="bookings-tbody">
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <i class="fas fa-ticket-alt"></i>
                                    <p>No bookings yet.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $i => $b):
                            $isExpired = Bookings::IsPaymentExpired($b['payment_deadline']);
                            $paymentDue = date('M d, Y g:i A', strtotime($b['payment_deadline']));
                        ?>
                            <tr class="booking-row"
                                data-id="<?= (int) $b['book_id_pk'] ?>"
                                data-ref-code="<?= htmlspecialchars($b['reference_code'], ENT_QUOTES) ?>"
                                data-user-name="<?= htmlspecialchars($b['user_name'] ?? 'N/A', ENT_QUOTES) ?>"
                                data-user-email="<?= htmlspecialchars($b['user_email'] ?? 'N/A', ENT_QUOTES) ?>"
                                data-user-phone="<?= htmlspecialchars($b['user_phone'] ?? 'N/A', ENT_QUOTES) ?>"
                                data-route="<?= htmlspecialchars($b['route_display'] ?? 'N/A', ENT_QUOTES) ?>"
                                data-seat="<?= htmlspecialchars($b['seat_number'] ?? 'N/A', ENT_QUOTES) ?>"
                                data-status="<?= htmlspecialchars($b['status'], ENT_QUOTES) ?>"
                                data-payment-deadline="<?= htmlspecialchars($b['payment_deadline'], ENT_QUOTES) ?>"
                                data-driver="<?= htmlspecialchars($b['driver_name'] ?? 'N/A', ENT_QUOTES) ?>"
                                data-van="<?= htmlspecialchars($b['van_plate'] ?? 'N/A', ENT_QUOTES) ?>"
                                data-departure="<?= date('M d g:i A', strtotime($b['departure_date'] . ' ' . $b['departure_time'])) ?>"
                                data-created="<?= htmlspecialchars($b['created_at'], ENT_QUOTES) ?>"
                                data-is-expired="<?= $isExpired ? '1' : '0' ?>">

                                <td class="text-muted-sm"><?= $i + 1 ?></td>
                                <td>
                                    <span class="ref-code"><?= htmlspecialchars($b['reference_code']) ?></span>
                                </td>
                                <td>
                                    <div class="passenger-info">
                                        <span class="name"><?= htmlspecialchars($b['user_name'] ?? 'Unknown') ?></span>
                                        <span class="email text-muted-sm"><?= htmlspecialchars($b['user_email'] ?? '') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="route-info">
                                        <i class="fas fa-route" style="color:var(--color-accent);font-size:11px"></i>
                                        <span><?= htmlspecialchars($b['route_display'] ?? 'N/A') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="seat-badge">
                                        <i class="fas fa-chair" style="font-size:10px"></i>
                                        <?= htmlspecialchars($b['seat_number'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $b['status'] ?> <?= $isExpired ? 'expired' : '' ?>">
                                        <?= ucfirst($b['status']) ?>
                                        <?php if ($isExpired && $b['status'] === 'pending'): ?>
                                            <i class="fas fa-exclamation-triangle" style="font-size:9px;margin-left:3px"></i>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td class="<?= $isExpired ? 'text-danger' : '' ?>">
                                    <small><?= $paymentDue ?></small>
                                    <?php if ($isExpired): ?>
                                        <div class="expired-label">EXPIRED</div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted-sm">
                                    <small><?= date('M d, Y', strtotime($b['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <button class="icon-btn view" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <button class="icon-btn approve" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="icon-btn reject" title="Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (in_array($b['status'], ['pending', 'approved'])): ?>
                                            <button class="icon-btn cancel" title="Cancel">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /.bookings-wrapper -->

<?= csrf_field() ?>

<!-- ── DETAILS MODAL ────────────────────────────────────────────────────── -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rmodal">
            <div class="rmodal-header">
                <div class="rmodal-icon"><i class="fas fa-file-invoice"></i></div>
                <div>
                    <h6 class="rmodal-title">Booking Details</h6>
                    <p class="rmodal-sub">View complete booking information</p>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="rmodal-body">
                <div class="details-grid">
                    <!-- Left column -->
                    <div class="details-col">
                        <div class="detail-section">
                            <h4 class="section-title">Booking Info</h4>
                            <div class="detail-row">
                                <span class="detail-label">Reference Code</span>
                                <span id="detail-ref-code" class="detail-value">—</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Status</span>
                                <span id="detail-status" class="detail-value badge">—</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Created</span>
                                <span id="detail-created" class="detail-value">—</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Payment Due</span>
                                <span id="detail-payment-due" class="detail-value">—</span>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h4 class="section-title">Passenger Info</h4>
                            <div class="detail-row">
                                <span class="detail-label">Name</span>
                                <span id="detail-passenger-name" class="detail-value">—</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Email</span>
                                <span id="detail-passenger-email" class="detail-value">—</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Phone</span>
                                <span id="detail-passenger-phone" class="detail-value">—</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right column -->
                    <div class="details-col">
                        <div class="detail-section">
                            <h4 class="section-title">Trip Info</h4>
                            <div class="detail-row">
                                <span class="detail-label">Route</span>
                                <span id="detail-route" class="detail-value">—</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Departure</span>
                                <span id="detail-departure" class="detail-value">—</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Driver</span>
                                <span id="detail-driver" class="detail-value">—</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Van</span>
                                <span id="detail-van" class="detail-value">—</span>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h4 class="section-title">Seat Assignment</h4>
                            <div class="detail-row">
                                <span class="detail-label">Seat Number</span>
                                <span id="detail-seat" class="detail-value badge seat-badge">—</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="rmodal-footer">
                <button type="button" class="rbtn rbtn-ghost" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layout/admin_layout.php';
?>