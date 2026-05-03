<?php
require_once "../../autoload.php";

$title    = 'Vans';
$page_css = '../../assets/css/vans.css';
$page_js  = '../../assets/js/vans-js.js';

ob_start();

$vanObj = new Vans($conn);
$vans   = $vanObj->GetAllVans();
?>

<div class="toolbar">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="van-search" placeholder="Search vans...">
    </div>
    <button class="btn-add" id="open-add-modal">
        <i class="fas fa-plus"></i> Add Van
    </button>
</div>

<input type="hidden" id="page-csrf-token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">

<div class="vans-wrapper">

    <!-- TABLE CARD -->
    <div class="vans-card">
        <div class="vans-card-header">
            <h2>
                <i class="fas fa-van-shuttle" style="margin-right:7px;color:var(--color-accent)"></i>
                All Vans
            </h2>
            <span id="van-count"></span>
        </div>
        <div class="vans-table-wrap">
            <table class="vans-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Plate Number</th>
                        <th>Model</th>
                        <th>Capacity</th>
                        <!-- <th>Seat Preview</th> -->
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="vans-tbody">
                    <?php if (empty($vans)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fas fa-van-shuttle"></i>
                                    <p>No vans yet. Add your first van.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vans as $i => $v):
                            $seatsJson = htmlspecialchars(
                                json_encode(array_map(fn($s) => [
                                    'seat_number' => $s['seat_number'],
                                    'seat_row'    => $s['seat_row'],
                                    'seat_col'    => $s['seat_col'],
                                ], $v['seats'])),
                                ENT_QUOTES, 'UTF-8'
                            );
                        ?>
                            <tr class="van-row"
                                data-id="<?= $v['van_id_pk'] ?>"
                                data-plate="<?= htmlspecialchars($v['plate_number'],  ENT_QUOTES) ?>"
                                data-model="<?= htmlspecialchars($v['model'],         ENT_QUOTES) ?>"
                                data-capacity="<?= (int) $v['capacity'] ?>"
                                data-status="<?= htmlspecialchars($v['status'],       ENT_QUOTES) ?>"
                                data-seats="<?= $seatsJson ?>">

                                <td class="text-muted-sm"><?= $i + 1 ?></td>

                                <td>
                                    <div class="plate-display">
                                        <i class="fas fa-id-card" style="color:var(--color-accent);font-size:11px"></i>
                                        <span class="plate-number"><?= htmlspecialchars($v['plate_number']) ?></span>
                                    </div>
                                </td>

                                <td>
                                    <div class="model-display">
                                        <i class="fas fa-car-side" style="color:#9ca3af;font-size:11px"></i>
                                        <?= htmlspecialchars($v['model']) ?>
                                    </div>
                                </td>

                                <td>
                                    <span class="capacity-badge">
                                        <i class="fas fa-chair" style="font-size:10px"></i>
                                        <?= (int) $v['capacity'] ?> seats
                                    </span>
                                </td>

                                <!-- <td>
                                    <div class="mini-seat-grid" data-seats="<?= $seatsJson ?>">
                                        <?php foreach ($v['seats'] as $s): ?>
                                            <div class="mini-seat" title="<?= htmlspecialchars($s['seat_number']) ?>">
                                                <?= htmlspecialchars($s['seat_number']) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td> -->

                                <td>
                                    <span class="badge <?= $v['status'] ?>">
                                        <?= ucfirst($v['status']) ?>
                                    </span>
                                </td>

                                <td class="text-muted-sm">
                                    <?= date('M d, Y', strtotime($v['created_at'])) ?>
                                </td>

                                <td>
                                    <div class="row-actions">
                                        <button class="icon-btn edit" title="Edit"
                                            data-id="<?= $v['van_id_pk'] ?>"
                                            data-plate="<?= htmlspecialchars($v['plate_number'],  ENT_QUOTES) ?>"
                                            data-model="<?= htmlspecialchars($v['model'],         ENT_QUOTES) ?>"
                                            data-capacity="<?= (int) $v['capacity'] ?>"
                                            data-status="<?= htmlspecialchars($v['status'],       ENT_QUOTES) ?>">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="icon-btn toggle" title="Toggle Status"
                                            data-id="<?= $v['van_id_pk'] ?>"
                                            data-status="<?= htmlspecialchars($v['status'], ENT_QUOTES) ?>">
                                            <i class="fas fa-<?= $v['status'] === 'active' ? 'toggle-on' : 'toggle-off' ?>"></i>
                                        </button>
                                        <button class="icon-btn delete" title="Delete"
                                            data-id="<?= $v['van_id_pk'] ?>"
                                            data-plate="<?= htmlspecialchars($v['plate_number'], ENT_QUOTES) ?>">
                                            <i class="fas fa-trash"></i>
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

    <!-- SEAT PREVIEW CARD -->
    <div class="seat-card">
        <div class="seat-card-header">
            <i class="fas fa-chair"></i>
            <p>Seat Layout</p>
            <span id="seat-label">Select a van</span>
        </div>

        <div id="seat-empty" class="seat-empty">
            <i class="fas fa-van-shuttle"></i>
            <p>Click a van to preview seats</p>
        </div>

        <div id="seat-preview" style="display:none;">
            <div class="van-body">
                <div class="van-front">
                    <div class="steering-wrap">
                        <!-- <i class="fas fa-steering-wheel"></i> -->
                        <span>VAN</span>
                    </div>
                </div>
                <div class="seat-grid" id="seat-grid"></div>
            </div>
            <div class="seat-legend">
                <div class="legend-item">
                    <div class="legend-dot available"></div>
                    <span>Available</span>
                </div>
            </div>
        </div>

        <div class="van-info-panel" id="van-info-panel">
            <div class="van-info-row">
                <div class="van-info-item">
                    <div class="van-info-label">Plate</div>
                    <span id="info-plate">—</span>
                </div>
                <div class="van-info-item">
                    <div class="van-info-label">Model</div>
                    <span id="info-model">—</span>
                </div>
                <div class="van-info-item">
                    <div class="van-info-label">Capacity</div>
                    <span id="info-capacity">—</span>
                </div>
                <div class="van-info-item">
                    <div class="van-info-label">Status</div>
                    <span id="info-status">—</span>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ADD MODAL -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rmodal">
            <div class="rmodal-header">
                <div class="rmodal-icon"><i class="fas fa-van-shuttle"></i></div>
                <div>
                    <h6 class="rmodal-title">Add New Van</h6>
                    <p class="rmodal-sub">Register a van and auto-generate its seats</p>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="../../controllers/vans/AddVan.php">
                <div class="rmodal-body">
                    <?= csrf_field() ?>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-id-card"></i> Plate Number</label>
                        <input type="text" name="plate_number" class="rinput"
                            placeholder="e.g. ABC-1234"
                            maxlength="20"
                            style="text-transform:uppercase"
                            required>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-car-side"></i> Model</label>
                        <input type="text" name="model" class="rinput"
                            placeholder="e.g. Toyota Hi-Ace"
                            maxlength="255"
                            required>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-chair"></i> Capacity</label>
                        <input type="number" name="capacity" class="rinput"
                            placeholder="e.g. 10"
                            min="1" max="14" step="1"
                            required>
                        <span class="rfield-hint">Seats will be auto-generated (A1, A2, B1, B2…)</span>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-toggle-on"></i> Status</label>
                        <select name="status" class="ss" data-placeholder="Select status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="rmodal-footer">
                    <button type="button" class="rbtn rbtn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="rbtn rbtn-primary"><i class="fas fa-plus me-1"></i> Add Van</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rmodal">
            <div class="rmodal-header">
                <div class="rmodal-icon edit"><i class="fas fa-pen"></i></div>
                <div>
                    <h6 class="rmodal-title">Edit Van</h6>
                    <p class="rmodal-sub">Update van details</p>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="../../controllers/vans/EditVan.php">
                <div class="rmodal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="van_id" id="edit-id">

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-id-card"></i> Plate Number</label>
                        <input type="text" name="plate_number" id="edit-plate" class="rinput"
                            placeholder="e.g. ABC-1234"
                            maxlength="20"
                            style="text-transform:uppercase"
                            required>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-car-side"></i> Model</label>
                        <input type="text" name="model" id="edit-model" class="rinput"
                            placeholder="e.g. Toyota Hi-Ace"
                            maxlength="255"
                            required>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-chair"></i> Capacity</label>
                        <input type="number" name="capacity" id="edit-capacity" class="rinput"
                            placeholder="e.g. 10"
                            min="1" max="14" step="1"
                            required>
                        <span class="rfield-hint">Changing capacity will regenerate all seats.</span>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-toggle-on"></i> Status</label>
                        <select name="status" id="edit-status" class="ss" data-placeholder="Select status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="rmodal-footer">
                    <button type="button" class="rbtn rbtn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="rbtn rbtn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layout/admin_layout.php';
?>