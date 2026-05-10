<?php
require_once "../../autoload.php";

if (empty($_SESSION['is_login'])) {
    header("Location: ../auth/login.php");
    exit;
}

$title   = 'Users';
$page_js = '../../assets/js/users-js.js';

ob_start();

$userObj = new UserManagement($conn);
$users   = $userObj->GetAllUsers();

/**
 * Returns badge HTML based on verification_status.
 * 'no_submission' → Gray → "No Submission"
 * 'pending' → Yellow → "Pending"
 * 'approved' → Green → "Verified"
 * 'rejected' → Red → "Rejected"
 */
function verificationBadge(string $status): string
{
    $map = [
        'no_submission' => ['class' => 'no-submission', 'label' => 'No Submission'],
        'pending'       => ['class' => 'pending', 'label' => 'Pending'],
        'approved'      => ['class' => 'approved', 'label' => 'Verified'],
        'rejected'      => ['class' => 'rejected', 'label' => 'Rejected'],
    ];
    $config = $map[$status] ?? $map['no_submission'];
    return '<span class="badge ' . $config['class'] . '">' . $config['label'] . '</span>';
}
?>

<div class="toolbar">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="user-search" placeholder="Search users…">
    </div>
    <div class="filter-group">
        <select class="filter-select" id="user-verify-filter">
            <option value="">All Status</option>
            <option value="approved">Verified</option>
            <option value="pending">Pending</option>
            <option value="rejected">Rejected</option>
            <option value="no_submission">No Submission</option>
        </select>
    </div>
</div>

<input type="hidden" id="page-csrf-token"
    value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">

<div class="users-card">
    <div class="users-card-header">
        <h2>
            <i class="fas fa-users" style="margin-right:7px;color:var(--color-accent)"></i>
            All Users
        </h2>
        <span id="user-count"></span>
    </div>
    <div class="users-table-wrap">
        <table class="users-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Birthdate</th>
                    <th>Verification</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="users-tbody">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <p>No users yet.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $i => $u):
                        $encId  = encrypt((string) $u['user_id_pk']);
                        $vStatus = $u['verification_status'];
                    ?>
                        <tr class="user-row"
                            data-id="<?= htmlspecialchars($encId, ENT_QUOTES) ?>"
                            data-fullname="<?= htmlspecialchars($u['fullname'], ENT_QUOTES) ?>"
                            data-email="<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>"
                            data-contact="<?= htmlspecialchars($u['contact_number'] ?? '', ENT_QUOTES) ?>"
                            data-birthdate="<?= htmlspecialchars($u['birthdate'] ?? '', ENT_QUOTES) ?>"
                            data-verify-status="<?= htmlspecialchars($vStatus, ENT_QUOTES) ?>"
                            data-doc-count="<?= (int) $u['document_count'] ?>">

                            <td class="text-muted-sm"><?= $i + 1 ?></td>

                            <td>
                                <div class="user-name-display">
                                    <i class="fas fa-user-circle" style="color:#9ca3af;font-size:14px"></i>
                                    <?= htmlspecialchars($u['fullname']) ?>
                                </div>
                            </td>

                            <td>
                                <div class="user-email-display">
                                    <i class="fas fa-envelope"
                                        style="color:var(--color-accent);font-size:11px"></i>
                                    <span class="email-text"><?= htmlspecialchars($u['email']) ?></span>
                                </div>
                            </td>

                            <td class="text-muted-sm">
                                <?= htmlspecialchars($u['contact_number'] ?? 'N/A') ?>
                            </td>

                            <td class="text-muted-sm">
                                <?= $u['birthdate']
                                    ? date('M d, Y', strtotime($u['birthdate']))
                                    : 'N/A' ?>
                            </td>

                            <td><?= verificationBadge($vStatus) ?></td>

                            <td class="text-muted-sm">
                                <?= date('M d, Y', strtotime($u['created_at'])) ?>
                            </td>

                            <td>
                                <div class="row-actions">
                                    <button class="icon-btn view" title="View & Verify"
                                        data-id="<?= htmlspecialchars($encId, ENT_QUOTES) ?>"
                                        data-fullname="<?= htmlspecialchars($u['fullname'], ENT_QUOTES) ?>"
                                        data-email="<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>"
                                        data-contact="<?= htmlspecialchars($u['contact_number'] ?? '', ENT_QUOTES) ?>"
                                        data-birthdate="<?= htmlspecialchars($u['birthdate'] ?? '', ENT_QUOTES) ?>">
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

<!-- VIEW & VERIFY MODAL -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content rmodal">
            <div class="rmodal-header">
                <div class="rmodal-icon"><i class="fas fa-file-circle-check"></i></div>
                <div>
                    <h6 class="rmodal-title">User Details</h6>
                    <p class="rmodal-sub">Verification documents</p>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="rmodal-body">
                <div class="user-details-viewer">

                    <div class="udv-info-section">
                        <h6 class="udv-section-title">User Information</h6>
                        <div class="udv-info-grid">
                            <div class="udv-info-item">
                                <span class="udv-label">Name</span>
                                <span class="udv-value" id="view-fullname">—</span>
                            </div>
                            <div class="udv-info-item">
                                <span class="udv-label">Email</span>
                                <span class="udv-value" id="view-email">—</span>
                            </div>
                            <div class="udv-info-item">
                                <span class="udv-label">Contact</span>
                                <span class="udv-value" id="view-contact">—</span>
                            </div>
                            <div class="udv-info-item">
                                <span class="udv-label">Birthdate</span>
                                <span class="udv-value" id="view-birthdate">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="udv-docs-section">
                        <h6 class="udv-section-title">Verification Documents</h6>
                        <div id="udv-docs-container">
                            <p class="text-muted-sm">Loading documents…</p>
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
