<?php
require_once "../../autoload.php";

$title    = 'Bookings';
$page_css = '../../assets/css/bookings.css';
$page_js  = '../../assets/js/bookings-js.js';

ob_start();

$bookingObj = new Bookings($conn);
$bookings   = $bookingObj->GetAllBookings();
?>

<div class="toolbar">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="booking-search" placeholder="Search bookings...">
    </div>
    <div class="filter-group">
        <select id="booking-filter-status" class="filter-select">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="completed">Completed</option>
            <option value="rejected">Rejected</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>
</div>

<?= csrf_field() ?>

<div class="bookings-wrapper">

    <!-- ── TABLE CARD ──────────────────────────────────────────────── -->
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
                        <th>Booking</th>
                        <th>Passenger</th>
                        <th>Trip</th>
                        <th>Seats</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="bookings-tbody">
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-ticket-alt"></i>
                                    <p>No bookings yet.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $i => $b):
                            $seatNumbers = array_filter(array_map('trim', explode(',', $b['seat_numbers'] ?? '')));
                            $paymentStatus = strtolower($b['payment_status'] ?? 'paid');
                            $paymentLabel = 'Paid';
                            $paymentClass = 'paid';
                            $paymentAmount = isset($b['payment_amount']) ? (float) $b['payment_amount'] : 0;
                            $paymentMethod = $b['payment_method'] ? ucfirst($b['payment_method']) : 'No payment';
                            $notes = !empty($b['payment_notes']) ? json_decode($b['payment_notes'], true) : [];
                            $notes = is_array($notes) ? $notes : [];
                            $passengers = is_array($notes['passengers'] ?? null) ? $notes['passengers'] : [];
                            $passengerSummary = $passengers
                                ? implode(', ', array_map(fn($p) => ($p['seat_number'] ?? '-') . ': ' . ucfirst($p['type'] ?? 'regular'), $passengers))
                                : ucfirst($notes['passenger_type'] ?? 'regular');
                        ?>
                            <tr class="booking-row"
                                data-id="<?= (int) $b['book_id_pk'] ?>"
                                data-ref-code="<?= htmlspecialchars($b['reference_code'], ENT_QUOTES) ?>"
                                data-user-name="<?= htmlspecialchars($b['user_name'] ?? 'N/A', ENT_QUOTES) ?>"
                                data-user-email="<?= htmlspecialchars($b['user_email'] ?? 'N/A', ENT_QUOTES) ?>"
                                data-user-phone="<?= htmlspecialchars($b['user_phone'] ?? 'N/A', ENT_QUOTES) ?>"
                                data-route="<?= htmlspecialchars($b['route_display'] ?? 'N/A', ENT_QUOTES) ?>"
                                data-seat="<?= htmlspecialchars($b['seat_numbers'] ?? 'N/A', ENT_QUOTES) ?>"
                                data-seat-count="<?= (int) ($b['seats_count'] ?? 0) ?>"
                                data-status="<?= htmlspecialchars($b['status'], ENT_QUOTES) ?>"
                                data-payment="<?= htmlspecialchars($paymentLabel, ENT_QUOTES) ?>"
                                data-payment-method="<?= htmlspecialchars($paymentMethod, ENT_QUOTES) ?>"
                                data-payment-amount="<?= htmlspecialchars(number_format($paymentAmount, 2), ENT_QUOTES) ?>"
                                data-passenger-types="<?= htmlspecialchars($passengerSummary, ENT_QUOTES) ?>"
                                data-driver="<?= htmlspecialchars($b['driver_name'] ?? 'N/A', ENT_QUOTES) ?>"
                                data-van="<?= htmlspecialchars(($b['van_model'] ?? 'Van') . ' (' . ($b['van_plate'] ?? 'N/A') . ')', ENT_QUOTES) ?>"
                                data-departure="<?= date('M d, Y g:i A', strtotime($b['departure_date'] . ' ' . $b['departure_time'])) ?>"
                                data-created="<?= htmlspecialchars($b['created_at'], ENT_QUOTES) ?>">

                                <td class="text-muted-sm"><?= $i + 1 ?></td>
                                <td>
                                    <div class="booking-ref-stack">
                                        <span class="ref-code"><?= htmlspecialchars($b['reference_code']) ?></span>
                                        <small><?= htmlspecialchars($paymentLabel) ?> &middot; <?= date('M d, Y', strtotime($b['created_at'])) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="passenger-info">
                                        <span class="name"><?= htmlspecialchars($b['user_name'] ?? 'Unknown') ?></span>
                                        <span class="email text-muted-sm"><?= htmlspecialchars($passengerSummary) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="route-info trip-stack">
                                        <i class="fas fa-route" style="color:var(--color-accent);font-size:11px"></i>
                                        <span><?= htmlspecialchars($b['route_display'] ?? 'N/A') ?></span>
                                        <small><?= date('M d, g:i A', strtotime($b['departure_date'] . ' ' . $b['departure_time'])) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="seat-chip-list">
                                        <?php if (empty($seatNumbers)): ?>
                                            <span class="seat-badge">N/A</span>
                                        <?php else: ?>
                                            <?php foreach (array_slice($seatNumbers, 0, 3) as $seatNumber): ?>
                                                <span class="seat-badge"><i class="fas fa-chair" style="font-size:10px"></i><?= htmlspecialchars($seatNumber) ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($seatNumbers) > 3): ?>
                                                <span class="seat-badge more">+<?= count($seatNumbers) - 3 ?></span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><span class="badge <?= htmlspecialchars($b['status']) ?>"><?= ucfirst($b['status']) ?></span></td>
                                <td>
                                    <div class="row-actions">
                                        <button class="icon-btn view" title="View Details"><i class="fas fa-eye"></i></button>
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <button class="icon-btn approve" title="Approve"><i class="fas fa-check"></i></button>
                                            <button class="icon-btn reject" title="Reject"><i class="fas fa-times"></i></button>
                                        <?php endif; ?>
                                        <?php if ($b['status'] === 'approved'): ?>
                                            <button class="icon-btn cancel" title="Cancel"><i class="fas fa-ban"></i></button>
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

<!-- ── DETAILS MODAL ────────────────────────────────────────────────── -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">
                    <i class="fas fa-file-invoice" style="margin-right:8px;color:var(--color-accent)"></i>
                    Booking Details
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
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
                                <span id="detail-status" class="detail-value">—</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Created</span>
                                <span id="detail-created" class="detail-value">—</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Payment</span>
                                <span id="detail-payment" class="detail-value">-</span>
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
                            <h4 class="section-title">Seat Assignments</h4>
                            <div class="detail-row">
                                <span class="detail-label">Seats</span>
                                <span id="detail-seat" class="detail-value">—</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layout/admin_layout.php';
?>
