<?php
require_once "../../autoload.php";

$title  = 'Schedules';
/* FIX 1: Remove $page_css — schedules.css is already loaded globally
   by admin_layout head.php. Loading it again caused duplicate styles. */
$page_js = '../../assets/js/schedules-js.js';

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
    <!-- FIX 2: Added filter-group for status filter (consistent with other pages) -->
    <div class="filter-group">
        <select class="filter-select" id="schedule-status-filter">
            <option value="">All Status</option>
            <option value="boarding">Boarding</option>
            <option value="departed">Departed</option>
            <option value="arrived">Arrived</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>
    <div class="admin-date-filters" data-filter-scope="schedules">
        <label><span>From</span><input type="date" id="schedule-date-from"></label>
        <label><span>To</span><input type="date" id="schedule-date-to"></label>
        <button type="button" class="filter-btn ghost" id="schedule-date-clear">Clear</button>
    </div>
    <button class="btn-add" id="open-add-modal">
        <i class="fas fa-plus"></i> Add Schedule
    </button>
</div>

<!-- FIX 3: ONE csrf_field() here, outside all modals.
     JS reads the LAST input[name="csrf_token"] on page,
     which will always be this one.
     The modals below also keep their own csrf_field() for
     the native form POST fallback — that is correct. -->
<input type="hidden" name="csrf_token" id="page-csrf-token"
       value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">

<div class="schedules-wrapper">

    <!-- TABLE CARD -->
    <div class="schedules-card">
        <div class="schedules-card-header">
            <h2>
                <i class="fas fa-calendar-check" style="margin-right:7px;color:var(--color-accent)"></i>
                <span id="schedule-view-title">All Schedules</span>
            </h2>
            <!-- FIX 4: Removed data-label attribute — JS now handles pluralisation itself -->
            <span id="schedule-count"></span>
        </div>
        <div class="schedules-table-wrap">
            <table class="schedules-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Actions</th>
                        <th>Route</th>
                        <th>Driver</th>
                        <th>Van</th>
                        <th>Departure</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="schedules-tbody">
                    <?php if (empty($schedules)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-calendar-check"></i>
                                    <p>No schedules yet. Add your first schedule.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php
                        $scheduleStatusGroups = [
                            'boarding' => ['label' => 'Boarding Schedules', 'icon' => 'fas fa-door-open', 'hint' => 'Accepting passengers'],
                            'departed' => ['label' => 'Departed Schedules', 'icon' => 'fas fa-route', 'hint' => 'On the road'],
                            'arrived' => ['label' => 'Arrived Schedules', 'icon' => 'fas fa-flag-checkered', 'hint' => 'Reached destination'],
                            'cancelled' => ['label' => 'Cancelled Schedules', 'icon' => 'fas fa-ban', 'hint' => 'Not running'],
                        ];
                        $currentGroup = '';
                        foreach ($schedules as $i => $s):
                            $rawStatus = (string) ($s['trip_status'] ?? '');
                            $group = $scheduleStatusGroups[$rawStatus] ?? ['label' => ucwords($rawStatus) . ' Schedules', 'icon' => 'fas fa-calendar-check', 'hint' => 'Other schedules'];
                            if ($currentGroup !== $rawStatus):
                                $currentGroup = $rawStatus;
                        ?>
                            <tr class="admin-status-group-row" data-group-key="<?= htmlspecialchars($currentGroup, ENT_QUOTES) ?>">
                                <td colspan="7">
                                    <div class="admin-status-group-label">
                                        <i class="<?= htmlspecialchars($group['icon'], ENT_QUOTES) ?>"></i>
                                        <span><?= htmlspecialchars($group['label']) ?></span>
                                        <small><?= htmlspecialchars($group['hint']) ?></small>
                                    </div>
                                </td>
                            </tr>
                        <?php
                            endif;
                            $departure    = date('M d', strtotime($s['departure_date']))
                                          . ' at '
                                          . date('g:i A', strtotime($s['departure_time']));
                            $status       = htmlspecialchars($s['trip_status'],     ENT_QUOTES);
                            $stops        = array_values(array_filter($s['stops'] ?? []));
                            $viaText      = !empty($stops) ? 'via ' . implode(', ', $stops) : '';
                            $routeDisplay = htmlspecialchars($s['route_display'] ?? 'N/A', ENT_QUOTES);
                            $routeVia     = htmlspecialchars($viaText, ENT_QUOTES);
                            $driverName   = htmlspecialchars($s['driver_name']   ?? 'N/A', ENT_QUOTES);
                            $vanPlate     = htmlspecialchars($s['van_plate']     ?? 'N/A', ENT_QUOTES);
                            $vanCapacity  = (int) ($s['van_capacity'] ?? 0);
                            $arrivedAt    = !empty($s['arrived_at'])
                                            ? htmlspecialchars(date('M d, Y g:i A', strtotime($s['arrived_at'])), ENT_QUOTES)
                                            : '';
                            $etaValue     = !empty($s['estimated_arrival_at'])
                                            ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($s['estimated_arrival_at'])), ENT_QUOTES)
                                            : '';
                            $etaDisplay   = !empty($s['estimated_arrival_at'])
                                            ? htmlspecialchars(date('M d', strtotime($s['estimated_arrival_at'])) . ' at ' . date('g:i A', strtotime($s['estimated_arrival_at'])), ENT_QUOTES)
                                            : '';
                            $pendingBookings = (int) ($s['pending_bookings_count'] ?? 0);
                        ?>
                            <tr class="schedule-row status-<?= $status ?>"
                                data-id="<?= (int) $s['schedule_id_pk'] ?>"
                                data-route="<?= (int) $s['route_id_fk'] ?>"
                                data-driver="<?= (int) $s['driver_id_fk'] ?>"
                                data-van="<?= (int) $s['van_id_fk'] ?>"
                                data-route-display="<?= $routeDisplay ?>"
                                data-route-via="<?= $routeVia ?>"
                                data-driver-name="<?= $driverName ?>"
                                data-van-plate="<?= $vanPlate ?>"
                                data-van-capacity="<?= $vanCapacity ?>"
                                data-date="<?= htmlspecialchars($s['departure_date'], ENT_QUOTES) ?>"
                                data-time="<?= htmlspecialchars($s['departure_time'], ENT_QUOTES) ?>"
                                data-eta="<?= $etaValue ?>"
                                data-eta-display="<?= $etaDisplay ?>"
                                data-status="<?= $status ?>"
                                data-pending-bookings="<?= $pendingBookings ?>"
                                data-arrived-at="<?= $arrivedAt ?>">

                                <td class="text-muted-sm"><?= $i + 1 ?></td>
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
                                <td>
                                    <div class="route-display">
                                        <i class="fas fa-route" style="color:var(--color-accent);font-size:11px"></i>
                                        <div class="route-stack">
                                            <span><?= $routeDisplay ?></span>
                                            <?php if ($viaText): ?>
                                                <small><?= htmlspecialchars($viaText) ?></small>
                                            <?php endif; ?>
                                        </div>
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
                                <td class="text-muted-sm"><?= $departure ?></td>
                                <td>
                                    <!-- FIX 5: ucfirst + str_replace for display,
                                         but data-status keeps the raw value for JS logic -->
                                    <span class="badge <?= $status ?>">
                                        <?= ucfirst(str_replace('_', ' ', $s['trip_status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PREVIEW CARD — unchanged, kept as-is -->
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
                    <span class="detail-label">ETA</span>
                    <span id="preview-eta" class="detail-value">—</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span id="preview-status" class="detail-badge badge" style="justify-content: center;">—</span>
                </div>
                <div class="detail-row" id="preview-arrived-row" style="display:none;">
                    <span class="detail-label">Arrived At</span>
                    <span id="preview-arrived-at" class="detail-value">—</span>
                </div>
            </div>
        </div>
    </div>

</div><!-- /.schedules-wrapper -->

<!-- FIX 7: Removed the bare <?= csrf_field() ?> that was here.
     We replaced it with the explicit #page-csrf-token input above
     the wrapper, which JS targets reliably. -->

<!-- ADD MODAL — csrf_field() inside is for native POST fallback, correct -->
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
                                <?php
                                    $routeStops = array_column($r['stops'] ?? [], 'stop_name');
                                    $routeLabel = $r['origin'] . ' → ' . $r['destination'];
                                    if (!empty($routeStops)) {
                                        $routeLabel .= ' · via ' . implode(', ', $routeStops);
                                    }
                                ?>
                                <option value="<?= $r['route_id_pk'] ?>">
                                    <?= htmlspecialchars($routeLabel) ?>
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
                        <label class="rfield-label"><i class="fas fa-flag-checkered"></i> Estimated Time of Arrival</label>
                        <div class="rfield-inline">
                            <input type="date" name="eta_date" class="rinput" required>
                            <input type="time" name="eta_time" class="rinput" required>
                        </div>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-info-circle"></i> Initial Status</label>
                        <select name="trip_status" class="ss" data-placeholder="Select Status">
                            <option value="boarding" selected>Boarding</option>
                            <option value="departed">Departed</option>
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

<!-- EDIT MODAL -->
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
            <!-- FIX 8: action removed (was javascript:void(0) which is fine but
                 confusing). JS intercepts submit via e.preventDefault(). -->
            <form id="editForm">
                <div class="rmodal-body">
                    <!-- FIX 9: No csrf_field() here — JS sends csrf_token via
                         fetchPost() from the #page-csrf-token input. Adding it
                         here as well caused duplicate tokens and "invalid CSRF"
                         errors when PHP read the wrong one. -->
                    <input type="hidden" name="schedule_id" id="edit-id">

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-route"></i> Route</label>
                        <select name="route_id" id="edit-route" class="ss" data-placeholder="Select a Route" required>
                            <option value="">Select a Route</option>
                            <?php foreach ($routes as $r): if ($r['is_active']): ?>
                                <?php
                                    $routeStops = array_column($r['stops'] ?? [], 'stop_name');
                                    $routeLabel = $r['origin'] . ' → ' . $r['destination'];
                                    if (!empty($routeStops)) {
                                        $routeLabel .= ' · via ' . implode(', ', $routeStops);
                                    }
                                ?>
                                <option value="<?= $r['route_id_pk'] ?>">
                                    <?= htmlspecialchars($routeLabel) ?>
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
                        <label class="rfield-label"><i class="fas fa-flag-checkered"></i> Estimated Time of Arrival</label>
                        <div class="rfield-inline">
                            <input type="date" name="eta_date" id="edit-eta-date" class="rinput" required>
                            <input type="time" name="eta_time" id="edit-eta-time" class="rinput" required>
                        </div>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-info-circle"></i> Status</label>
                        <select name="trip_status" id="edit-status" class="ss" data-placeholder="Select Status">
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
