<?php
require_once "../../autoload.php";

if (empty($_SESSION['is_login'])) {
    header("Location: ../auth/login.php");
    exit;
}

$title   = 'Payments';
$page_js = '../../assets/js/payments-js.js';

ob_start();

$payObj   = new Payments($conn);
$payments = $payObj->GetAllPayments();
?>

<div class="toolbar">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="payment-search" placeholder="Search payments…">
    </div>
    <div class="filter-group">
        <select class="filter-select" id="payment-status-filter">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="failed">Failed</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>
</div>

<input type="hidden" id="page-csrf-token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">

<div class="payments-card">
    <div class="payments-card-header">
        <h2>
            <i class="fas fa-credit-card" style="margin-right:7px;color:var(--color-accent)"></i>
            All Payments
        </h2>
        <span id="payment-count"></span>
    </div>
    <div class="payments-table-wrap">
        <table class="payments-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Booking Ref</th>
                    <th>Passenger</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Status</th>
                    <th>Paid At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="payments-tbody">
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="fas fa-credit-card"></i>
                                <p>No payments yet.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $i => $p):
                        $status    = $p['status'] ?? 'pending';
                        $encId     = encrypt((string) $p['payment_id_pk']);
                    ?>
                        <tr class="payment-row"
                            data-id="<?= htmlspecialchars($encId, ENT_QUOTES) ?>"
                            data-booking-ref="<?= htmlspecialchars($p['booking_ref'] ?? 'N/A', ENT_QUOTES) ?>"
                            data-user-name="<?= htmlspecialchars($p['user_name'] ?? 'N/A', ENT_QUOTES) ?>"
                            data-user-email="<?= htmlspecialchars($p['user_email'] ?? '', ENT_QUOTES) ?>"
                            data-user-phone="<?= htmlspecialchars($p['user_phone'] ?? '', ENT_QUOTES) ?>"
                            data-amount="<?= number_format((float) $p['amount'], 2, '.', '') ?>"
                            data-method="<?= htmlspecialchars($p['payment_method'] ?? 'N/A', ENT_QUOTES) ?>"
                            data-ref="<?= htmlspecialchars($p['payment_reference'] ?? 'N/A', ENT_QUOTES) ?>"
                            data-status="<?= htmlspecialchars($status, ENT_QUOTES) ?>"
                            data-paid-at="<?= htmlspecialchars($p['paid_at'] ?? '', ENT_QUOTES) ?>"
                            data-created="<?= htmlspecialchars($p['created_at'] ?? '', ENT_QUOTES) ?>"
                            data-notes="<?= htmlspecialchars($p['notes'] ?? '', ENT_QUOTES) ?>"
                            data-route="<?= htmlspecialchars($p['route_display'] ?? 'N/A', ENT_QUOTES) ?>">

                            <td class="text-muted-sm"><?= $i + 1 ?></td>

                            <td>
                                <div class="booking-ref-display">
                                    <i class="fas fa-ticket-alt" style="color:#9ca3af;font-size:11px"></i>
                                    <span class="ref-code"><?= htmlspecialchars($p['booking_ref'] ?? 'N/A') ?></span>
                                </div>
                            </td>

                            <td>
                                <div class="user-info">
                                    <span class="name"><?= htmlspecialchars($p['user_name'] ?? 'Unknown') ?></span>
                                    <span class="email text-muted-sm"><?= htmlspecialchars($p['user_email'] ?? '') ?></span>
                                </div>
                            </td>

                            <td>
                                <span class="amount-display">
                                    <i class="fas fa-peso-sign" style="font-size:11px;color:var(--color-accent)"></i>
                                    <?= number_format((float) $p['amount'], 2) ?>
                                </span>
                            </td>

                            <td>
                                <span class="payment-method-badge">
                                    <?= ucfirst(htmlspecialchars($p['payment_method'] ?? 'N/A')) ?>
                                </span>
                            </td>

                            <td>
                                <span class="text-muted-sm" title="<?= htmlspecialchars($p['payment_reference'] ?? '') ?>">
                                    <?= htmlspecialchars(mb_substr($p['payment_reference'] ?? 'N/A', 0, 15)) ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge <?= htmlspecialchars($status) ?>">
                                    <?= ucfirst(htmlspecialchars($status)) ?>
                                </span>
                            </td>

                            <td class="text-muted-sm">
                                <?= $p['paid_at'] ? date('M d, Y g:i A', strtotime($p['paid_at'])) : '—' ?>
                            </td>

                            <td>
                                <div class="row-actions">
                                    <button class="icon-btn view" title="View Details"
                                        data-id="<?= htmlspecialchars($encId, ENT_QUOTES) ?>"
                                        data-booking-ref="<?= htmlspecialchars($p['booking_ref'] ?? 'N/A', ENT_QUOTES) ?>"
                                        data-user-name="<?= htmlspecialchars($p['user_name'] ?? 'N/A', ENT_QUOTES) ?>"
                                        data-user-email="<?= htmlspecialchars($p['user_email'] ?? '', ENT_QUOTES) ?>"
                                        data-user-phone="<?= htmlspecialchars($p['user_phone'] ?? '', ENT_QUOTES) ?>"
                                        data-amount="<?= number_format((float) $p['amount'], 2, '.', '') ?>"
                                        data-method="<?= htmlspecialchars($p['payment_method'] ?? 'N/A', ENT_QUOTES) ?>"
                                        data-ref="<?= htmlspecialchars($p['payment_reference'] ?? 'N/A', ENT_QUOTES) ?>"
                                        data-status="<?= htmlspecialchars($status, ENT_QUOTES) ?>"
                                        data-paid-at="<?= htmlspecialchars($p['paid_at'] ?? '', ENT_QUOTES) ?>"
                                        data-created="<?= htmlspecialchars($p['created_at'] ?? '', ENT_QUOTES) ?>"
                                        data-notes="<?= htmlspecialchars($p['notes'] ?? '', ENT_QUOTES) ?>"
                                        data-route="<?= htmlspecialchars($p['route_display'] ?? 'N/A', ENT_QUOTES) ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- VIEW PAYMENT DETAILS MODAL (read-only) -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content rmodal">
            <div class="rmodal-header">
                <div class="rmodal-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <div>
                    <h6 class="rmodal-title">Payment Details</h6>
                    <p class="rmodal-sub">Transaction information</p>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="rmodal-body">
                <div class="payment-details-viewer">

                    <div class="pdv-section">
                        <h6 class="pdv-section-title">Booking Information</h6>
                        <div class="pdv-info-grid">
                            <div class="pdv-info-item">
                                <span class="pdv-label">Booking Reference</span>
                                <span class="pdv-value" id="view-booking-ref">—</span>
                            </div>
                            <div class="pdv-info-item">
                                <span class="pdv-label">Route</span>
                                <span class="pdv-value" id="view-route">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="pdv-section">
                        <h6 class="pdv-section-title">Passenger Information</h6>
                        <div class="pdv-info-grid">
                            <div class="pdv-info-item">
                                <span class="pdv-label">Name</span>
                                <span class="pdv-value" id="view-user-name">—</span>
                            </div>
                            <div class="pdv-info-item">
                                <span class="pdv-label">Email</span>
                                <span class="pdv-value" id="view-user-email">—</span>
                            </div>
                            <div class="pdv-info-item">
                                <span class="pdv-label">Phone</span>
                                <span class="pdv-value" id="view-user-phone">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="pdv-section">
                        <h6 class="pdv-section-title">Payment Information</h6>
                        <div class="pdv-info-grid">
                            <div class="pdv-info-item">
                                <span class="pdv-label">Amount</span>
                                <span class="pdv-value" id="view-amount">—</span>
                            </div>
                            <div class="pdv-info-item">
                                <span class="pdv-label">Method</span>
                                <span class="pdv-value" id="view-method">—</span>
                            </div>
                            <div class="pdv-info-item">
                                <span class="pdv-label">Payment Reference</span>
                                <span class="pdv-value" id="view-payment-ref" style="word-break:break-all">—</span>
                            </div>
                            <div class="pdv-info-item">
                                <span class="pdv-label">Status</span>
                                <span class="pdv-value" id="view-status-badge">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="pdv-section">
                        <h6 class="pdv-section-title">Transaction History</h6>
                        <div class="pdv-info-grid">
                            <div class="pdv-info-item">
                                <span class="pdv-label">Created At</span>
                                <span class="pdv-value" id="view-created">—</span>
                            </div>
                            <div class="pdv-info-item">
                                <span class="pdv-label">Paid At</span>
                                <span class="pdv-value" id="view-paid-at">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="pdv-section">
                        <h6 class="pdv-section-title">Notes</h6>
                        <div class="pdv-notes" id="view-notes">No notes</div>
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