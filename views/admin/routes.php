<?php
require_once "../../autoload.php";
ob_start();
$title = 'Routes';
$route = new Routes($conn);
$routes = $route->GetAllRoutes();
?>

<div class="page-content">

    <div class="toolbar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="route-search" placeholder="Search routes...">
        </div>
        <button class="btn-add" id="open-add-modal">
            <i class="fas fa-plus"></i> Add Route
        </button>
    </div>

    <div class="routes-wrapper">

        <!-- TABLE -->
        <div class="routes-card">
            <div class="routes-card-header">
                <h2>
                    <i class="fas fa-road" style="margin-right:7px;color:var(--color-accent)"></i>
                    All Routes
                </h2>
                <span id="route-count"></span>
            </div>
            <div class="routes-table-wrap">
                <table class="routes-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Route</th>
                            <th>Via</th>
                            <th>Fare</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($routes)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-road"></i>
                                        <p>No routes yet. Add your first route.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($routes as $i => $r):
                                $stops = $r['stops'] ?? [];
                                $stopNames = array_column($stops, 'stop_name');
                                $stopsJson = htmlspecialchars(json_encode($stopNames), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr class="route-row" data-id="<?= $r['route_id_pk'] ?>"
                                    data-origin="<?= htmlspecialchars($r['origin'], ENT_QUOTES) ?>"
                                    data-destination="<?= htmlspecialchars($r['destination'], ENT_QUOTES) ?>"
                                    data-fare="<?= htmlspecialchars($r['fare'], ENT_QUOTES) ?>"
                                    data-active="<?= (int) $r['is_active'] ?>" data-stops="<?= $stopsJson ?>">

                                    <td class="text-muted-sm"><?= $i + 1 ?></td>

                                    <td>
                                        <div class="route-display">
                                            <div class="route-point">
                                                <span class="label">From</span>
                                                <span class="value"><?= htmlspecialchars($r['origin']) ?></span>
                                            </div>
                                            <div class="route-arrow">
                                                <i class="fas fa-arrow-right"></i>
                                            </div>
                                            <div class="route-point">
                                                <span class="label">To</span>
                                                <span class="value"><?= htmlspecialchars($r['destination']) ?></span>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if (!empty($stopNames)): ?>
                                            <div class="stops-pills">
                                                <?php foreach ($stopNames as $sn): ?>
                                                    <span class="stop-pill">
                                                        <i class="fas fa-circle" style="font-size:5px;color:var(--color-accent)"></i>
                                                        <?= htmlspecialchars($sn) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted-sm">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fare">
                                            ₱<?= number_format($r["fare"], 2) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $r['is_active'] ? 'active' : 'inactive' ?>">
                                            <?= $r['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>

                                    <td class="text-muted-sm">
                                        <?= date('M d, Y', strtotime($r['created_at'])) ?>
                                    </td>

                                    <td>
                                        <div class="row-actions">
                                            <button class="icon-btn edit" title="Edit" data-id="<?= $r['route_id_pk'] ?>"
                                                data-origin="<?= htmlspecialchars($r['origin'], ENT_QUOTES) ?>"
                                                data-destination="<?= htmlspecialchars($r['destination'], ENT_QUOTES) ?>"
                                                data-active="<?= (int) $r['is_active'] ?>" data-stops="<?= $stopsJson ?>"
                                                data-fare="<?= htmlspecialchars($r['fare'], ENT_QUOTES) ?>">

                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button class="icon-btn toggle" title="Toggle Status"
                                                data-id="<?= $r['route_id_pk'] ?>" data-active="<?= (int) $r['is_active'] ?>">
                                                <i class="fas fa-<?= $r['is_active'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                                            </button>
                                            <button class="icon-btn delete" title="Delete" data-id="<?= $r['route_id_pk'] ?>"
                                                data-route="<?= htmlspecialchars($r['origin'] . ' → ' . $r['destination'], ENT_QUOTES) ?>">
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

        <!-- MAP CARD -->
        <div class="map-card">
            <div class="map-card-header">
                <i class="fas fa-map-marked-alt"></i>
                <p>Route Preview</p>
                <span id="map-label">Select a route</span>
            </div>
            <div id="map-empty" class="map-empty">
                <i class="fas fa-map"></i>
                <p>Click a route to preview on map</p>
            </div>
            <div id="route-map" style="display:none;height:300px;width:100%"></div>
            <div class="map-route-info" id="map-route-info">
                <div class="map-route-stops">
                    <div class="map-stop">
                        <div class="stop-dot origin"></div>
                        <div>
                            <div class="stop-label">Origin</div>
                            <span id="map-origin-label">—</span>
                        </div>
                    </div>
                    <div style="margin-left:4px">
                        <div class="stop-connector"></div>
                    </div>
                    <div id="map-stops-list"></div>
                    <div class="map-stop">
                        <div class="stop-dot dest"></div>
                        <div>
                            <div class="stop-label">Destination</div>
                            <span id="map-dest-label">—</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!--  ADD MODAL  -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rmodal">
            <div class="rmodal-header">
                <div class="rmodal-icon"><i class="fas fa-road"></i></div>
                <div>
                    <h6 class="rmodal-title">Add New Route</h6>
                    <p class="rmodal-sub">Define origin, stops, and destination</p>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="../../controllers/routes/AddRoute.php">
                <div class="rmodal-body">

                    <?= csrf_field() ?>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-map-marker-alt"></i> Origin</label>
                        <select name="origin" class="ss" data-placeholder="Select origin city" required>
                            <option value="">Select origin</option>
                            <?php foreach (LOCATIONS as $name => $coords): ?>
                                <option value="<?= $name ?>"><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label">
                            <i class="fas fa-map-signs"></i> Via
                            <span class="rfield-opt">(optional)</span>
                            <span class="stops-counter" id="add-stops-counter">0 / 10</span>
                        </label>
                        <div class="stops-list" id="stops-container"></div>
                        <button type="button" class="btn-add-stop" id="add-stop-btn">
                            <i class="fas fa-plus"></i> Add Stop
                        </button>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-map-pin"></i> Destination</label>
                        <select name="destination" class="ss" data-placeholder="Select destination city" required>
                            <option value="">Select destination</option>
                            <?php foreach (LOCATIONS as $name => $coords): ?>
                                <option value="<?= $name ?>"><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label">
                            <i class="fas fa-peso-sign"></i> Fare
                        </label>
                        <input type="number" name="fare" class="rinput" placeholder="Enter route fare" min="0" step="1"
                            required>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-toggle-on"></i> Status</label>
                        <select name="status" class="ss" data-placeholder="Select status">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="rmodal-footer">
                    <button type="button" class="rbtn rbtn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="rbtn rbtn-primary"><i class="fas fa-plus me-1"></i> Add Route</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!--  EDIT MODAL  -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rmodal">
            <div class="rmodal-header">
                <div class="rmodal-icon edit"><i class="fas fa-pen"></i></div>
                <div>
                    <h6 class="rmodal-title">Edit Route</h6>
                    <p class="rmodal-sub">Update route details</p>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="../../controllers/routes/EditRoute.php">
                <div class="rmodal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="route_id" id="edit-id">

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-map-marker-alt"></i> Origin</label>
                        <select name="origin" id="edit-origin" class="ss" data-placeholder="Select origin city"
                            required>
                            <option value="">Select origin…</option>
                            <?php foreach (LOCATIONS as $name => $coords): ?>
                                <option value="<?= $name ?>"><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label">
                            <i class="fas fa-map-signs"></i> Via
                            <span class="rfield-opt">(optional)</span>
                            <span class="stops-counter" id="edit-stops-counter">0 / 10</span>
                        </label>
                        <div class="stops-list" id="edit-stops-container"></div>
                        <button type="button" class="btn-add-stop" id="edit-add-stop-btn">
                            <i class="fas fa-plus"></i> Add Stop
                        </button>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-map-pin"></i> Destination</label>
                        <select name="destination" id="edit-destination" class="ss"
                            data-placeholder="Select destination city" required>
                            <option value="">Select destination…</option>
                            <?php foreach (LOCATIONS as $name => $coords): ?>
                                <option value="<?= $name ?>"><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="rfield">
                        <label class="rfield-label">
                            <i class="fas fa-peso-sign"></i> Fare
                        </label>
                        <input type="number" name="fare" id="edit-fare" class="rinput" placeholder="Enter route fare"
                            min="0" step="1" required>
                    </div>


                    <div class="rfield">
                        <label class="rfield-label"><i class="fas fa-toggle-on"></i> Status</label>
                        <select name="is_active" id="edit-status" class="ss" data-placeholder="Select status">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="rmodal-footer">
                    <button type="button" class="rbtn rbtn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="rbtn rbtn-primary"><i class="fas fa-save me-1"></i> Save
                        Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!--  DELETE MODAL  -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rmodal delete-modal">
            <div class="delete-icon-wrap">
                <div class="delete-icon-circle">
                    <i class="fas fa-trash-alt"></i>
                </div>
            </div>
            <div class="delete-body">
                <h6 class="delete-title">Delete Route?</h6>
                <p class="delete-msg" id="delete-label">This action cannot be undone.</p>
            </div>
            <div class="rmodal-footer justify-content-center">
                <button type="button" class="rbtn rbtn-ghost" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <form method="POST" action="../../controllers/routes/DeleteRoute.php" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="route_id" id="delete-id">
                    <button type="submit" class="rbtn rbtn-danger">
                        <i class="fas fa-trash-alt me-1"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="../../assets/js/routes.js"></script>

<?php
$content = ob_get_clean();
include '../layout/admin_layout.php';
?>