<?php
require_once "../../autoload.php";

$title    = 'Schedules';
$page_css = '../../assets/css/schedules.css';
$page_js  = '../../assets/js/schedules-js.js';

ob_start();

$scheduleObj = new Schedules($conn);
$schedules   = $scheduleObj->GetAllSchedules();

$routeObj = new Routes($conn);
$routes   = $routeObj->GetAllRoutes();

$driverObj = new Drivers($conn);
$drivers   = $driverObj->GetAllDrivers();

$vanObj = new Vans($conn);
$vans   = $vanObj->GetAllVans();
?>

<div class="toolbar">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="schedule-search" placeholder="Search schedules...">
    </div>
    <button class="btn-add" id="open-add-modal">
        <i class="fas fa-plus"></i> Add Schedule
    </button>
</div>

<div class="schedules-wrapper">

    <!-- ── TABLE CARD ──────────────────────────────────────────────────────── -->
    <div class="schedules-card">
        <div class="schedules-card-header">
            <h2>
                <i class="fas fa-calendar-check" style="margin-right:7px;color:var(--color-accent)"></i>
                All Schedules
            </h2>
            <span id="schedule-count"></span>
        </div>
        <div class="schedules-table-wrap">
            <table class="schedules-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Route</th>
                        <th>Driver</th>
                        <th>Van</th>
                        <th>Departure</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="schedules-tbody">
                    <?php if (empty($schedules)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fas fa-calendar-check"></i>
                                    <p>No schedules yet. Add your first schedule.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($schedules as $i => $s):
                            $departure    = date('M d', strtotime($s['departure_date']))
                                          . ' at '
                                          . date('g:i A', strtotime($s['departure_time']));
                            $status       = htmlspecialchars($s['trip_status'],     ENT_QUOTES);
                            $routeDisplay = htmlspecialchars($s['route_display'] ?? 'N/A', ENT_QUOTES);
                            $driverName   = htmlspecialchars($s['driver_name']   ?? 'N/A', ENT_QUOTES);
                            $vanPlate     = htmlspecialchars($s['van_plate']     ?? 'N/A', ENT_QUOTES);
                            $vanCapacity  = (int) ($s['van_capacity'] ?? 0);
                            $arrivedAt    = !empty($s['arrived_at'])
                                            ? htmlspecialchars(date('M d, Y g:i A', strtotime($s['arrived_at'])), ENT_QUOTES)
                                            : '';
                        ?>
                            <tr class="schedule-row"
                                data-id="<?= (int) $s['schedule_id_pk'] ?>"
                                data-route="<?= (int) $s['route_id_fk'] ?>"
                                data-driver="<?= (int) $s['driver_id_fk'] ?>"
                                data-van="<?= (int) $s['van_id_fk'] ?>"
                                data-route-display="<?= $routeDisplay ?>"
                                data-driver-name="<?= $driverName ?>"
                                data-van-plate="<?= $vanPlate ?>"
                                data-van-capacity="<?= $vanCapacity ?>"
                                data-date="<?= htmlspecialchars($s['departure_date'], ENT_QUOTES) ?>"
                                data-time="<?= htmlspecialchars($s['departure_time'], ENT_QUOTES) ?>"
                                data-status="<?= $status ?>"
                                data-arrived-at="<?= $arrivedAt ?>">

                                <td class="text-muted-sm"><?= $i + 1 ?></td>
                                <td>
                                    <div class="route-display">
                                        <i class="fas fa-route" style="color:var(--color-accent);font-size:11px"></i>
                                        <span><?= $routeDisplay ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="driver-display">
                                        <i class="fas fa-user-tie" style="color:#9ca3af;font-size:11px"></i>
                                        <?= $driverName ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="plate-display">
                                        <i class="fas fa-van-shuttle" style="color:var(--color-accent);font-size:11px"></i>
                                        <span class="plate-number"><?= $vanPlate ?></span>
                                    </div>
                                </td>
                                <td><?= $departure ?></td>
                                <td>
                                    <span class="badge <?= $status ?>">
                                        <?= ucfirst(str_replace('_', ' ', $s['trip_status'])) ?>
                                    </span>
                                </td>
                                <td class="text-muted-sm">
                                    <?= date('M d, Y', strtotime($s['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <button class="icon-btn status" title="Update Status">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                        <button class="icon-btn edit" title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="icon-btn delete" title="Delete">
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

    <!-- ── PREVIEW CARD ────────────────────────────────────────────────────── -->
    <div class="schedule-card">
        <div class="schedule-card-header">
            <i class="fas fa-id-badge"></i>
            <p>Schedule Details</p>
            <span id="schedule-label">Select a schedule</span>
        </div>

        <div id="schedule-empty" class="schedule-empty">
            <i class="fas fa-calendar-check"></i>
            <p>Click a schedule to view details</p>
        </div>

        <div id="schedule-preview" style="display:none;">
            <div class="schedule-avatar">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="schedule-details">
                <h3 id="preview-route">—</h3>
                <div class="detail-row">
                    <span class="detail-label">Driver</span>
                    <span id="preview-driver" class="detail-value">—</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Van</span>
                    <span id="preview-van" class="detail-value">—</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Capacity</span>
                    <span id="preview-capacity" class="detail-value">—</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Departure</span>
                    <span id="preview-departure" class="detail-value">—</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span id="preview-status" class="detail-badge badge">—</span>
                </div>
                <!-- Shown only when status = arrived -->
                <div class="detail-row" id="preview-arrived-row" style="display:none;">
                    <span class="detail-label">Arrived At</span>
                    <span id="preview-arrived-at" class="detail-value">—</span>
                </div>
            </div>
        </div>
    </div>

</div><!-- /.schedules-wrapper -->

<?= csrf_field() ?>

<!-- ── ADD MODAL ───────────────────────────────────────────────────────────── -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rmodal">
            <div class="rmodal-header">
                <div class="rmodal-icon"><i class="fas fa-calendar-check"></i></div>
                <div>
                    <h6 class="rmodal-title">Add New Schedule</h6>
                    <p class="rmodal-sub">Create a new trip schedule</p>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="../../controllers/Schedules/AddSchedule.php">
                <div class="rmodal-body">
                    <?= csrf_field() ?>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-route"></i> Route</label>
                        <select name="route_id" class="ss" data-placeholder="Select a Route" required>
                            <option value="">Select a Route</option>
                            <?php foreach ($routes as $r): if ($r['is_active']): ?>
                                <option value="<?= $r['route_id_pk'] ?>">
                                    <?= htmlspecialchars($r['origin'] . ' → ' . $r['destination']) ?>
                                </option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-user-tie"></i> Driver</label>
                        <select name="driver_id" class="ss" data-placeholder="Select a Driver" required>
                            <option value="">Select a Driver</option>
                            <?php foreach ($drivers as $d): if ($d['status'] === 'active'): ?>
                                <option value="<?= $d['driver_id_pk'] ?>">
                                    <?= htmlspecialchars($d['full_name']) ?> (<?= $d['license_number'] ?>)
                                </option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-van-shuttle"></i> Van</label>
                        <select name="van_id" class="ss" data-placeholder="Select a Van" required>
                            <option value="">Select a Van</option>
                            <?php foreach ($vans as $v): if ($v['status'] === 'active'): ?>
                                <option value="<?= $v['van_id_pk'] ?>">
                                    <?= htmlspecialchars($v['plate_number']) ?> - <?= $v['model'] ?> (<?= $v['capacity'] ?> seats)
                                </option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>

                    <div class="rfield-inline">
                        <div class="rfield">
                            <label class="rfield-label"><i class="fas fa-calendar-day"></i> Date</label>
                            <input type="date" name="departure_date" class="rinput" required>
                        </div>
                        <div class="rfield">
                            <label class="rfield-label"><i class="fas fa-clock"></i> Time</label>
                            <input type="time" name="departure_time" class="rinput" required>
                        </div>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-info-circle"></i> Status</label>
                        <select name="trip_status" class="ss" data-placeholder="Select a Status">
                            <option value="boarding" selected>Boarding</option>
                            <option value="departed">Departed</option>
                            <option value="arrived">Arrived</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="rmodal-footer">
                    <button type="button" class="rbtn rbtn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="rbtn rbtn-primary">
                        <i class="fas fa-plus me-1"></i> Add Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── EDIT MODAL ──────────────────────────────────────────────────────────── -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rmodal">
            <div class="rmodal-header">
                <div class="rmodal-icon edit"><i class="fas fa-pen"></i></div>
                <div>
                    <h6 class="rmodal-title">Edit Schedule</h6>
                    <p class="rmodal-sub">Update schedule details</p>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="../../controllers/Schedules/EditSchedule.php">
                <div class="rmodal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="schedule_id" id="edit-id">

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-route"></i> Route</label>
                        <select name="route_id" id="edit-route" class="ss" data-placeholder="Select a Route" required>
                            <option value="">Select a Route</option>
                            <?php foreach ($routes as $r): if ($r['is_active']): ?>
                                <option value="<?= $r['route_id_pk'] ?>">
                                    <?= htmlspecialchars($r['origin'] . ' → ' . $r['destination']) ?>
                                </option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-user-tie"></i> Driver</label>
                        <select name="driver_id" id="edit-driver" class="ss" data-placeholder="Select a Driver" required>
                            <option value="">Select a Driver</option>
                            <?php foreach ($drivers as $d): if ($d['status'] === 'active'): ?>
                                <option value="<?= $d['driver_id_pk'] ?>">
                                    <?= htmlspecialchars($d['full_name']) ?> (<?= $d['license_number'] ?>)
                                </option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-van-shuttle"></i> Van</label>
                        <select name="van_id" id="edit-van" class="ss" data-placeholder="Select a Van" required>
                            <option value="">Select a Van</option>
                            <?php foreach ($vans as $v): if ($v['status'] === 'active'): ?>
                                <option value="<?= $v['van_id_pk'] ?>">
                                    <?= htmlspecialchars($v['plate_number']) ?> - <?= $v['model'] ?> (<?= $v['capacity'] ?> seats)
                                </option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>

                    <div class="rfield-inline">
                        <div class="rfield">
                            <label class="rfield-label"><i class="fas fa-calendar-day"></i> Date</label>
                            <input type="date" name="departure_date" id="edit-date" class="rinput" required>
                        </div>
                        <div class="rfield">
                            <label class="rfield-label"><i class="fas fa-clock"></i> Time</label>
                            <input type="time" name="departure_time" id="edit-time" class="rinput" required>
                        </div>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-info-circle"></i> Status</label>
                        <select name="trip_status" id="edit-status" class="ss" data-placeholder="Select a Status">
                            <option value="boarding">Boarding</option>
                            <option value="departed">Departed</option>
                            <option value="arrived">Arrived</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="rmodal-footer">
                    <button type="button" class="rbtn rbtn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="rbtn rbtn-primary">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layout/admin_layout.php';
?>