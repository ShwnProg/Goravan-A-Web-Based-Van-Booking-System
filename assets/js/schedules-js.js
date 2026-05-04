/**
 * schedules-js.js
 *
 * Fixed version — all logic inside window.initSchedulesPage()
 * so nav.js can call it after AJAX page swaps.
 *
 * Searchable-select (SS) widget is registered ONCE globally
 * (guarded by window._ssWidgetReady) then rebuilt per-page
 * inside initSchedulesPage.
 */

/* ══════════════════════════════════════════════════════════════════════════
   SEARCHABLE-SELECT WIDGET
   Registered once globally. buildSearchableSelects(root) can be called
   multiple times safely — it skips already-built selects.
══════════════════════════════════════════════════════════════════════════ */
if (!window._ssWidgetReady) {
    window._ssWidgetReady = true;

    (function () {

        function closeAll() {
            document.querySelectorAll('.ss-panel.is-open').forEach(function (p) {
                p.classList.remove('is-open');
            });
            document.querySelectorAll('.ss-btn.is-open').forEach(function (b) {
                b.classList.remove('is-open');
            });
        }

        function buildSS(select) {
            if (select._ssBuilt) return; // already built — skip
            select._ssBuilt = true;

            var ph   = select.dataset.placeholder || '— Select —';
            var wrap = document.createElement('div');
            wrap.className = 'ss-wrap';
            select.parentNode.insertBefore(wrap, select);
            wrap.appendChild(select);

            /* Button */
            var btn = document.createElement('button');
            btn.type      = 'button';
            btn.className = 'ss-btn';

            var txt = document.createElement('span');
            txt.className = 'ss-btn-txt';

            var cur = select.options[select.selectedIndex];
            if (cur && cur.value) {
                txt.textContent = cur.text;
            } else {
                txt.textContent = ph;
                btn.classList.add('is-placeholder');
            }
            btn.appendChild(txt);

            var arr = document.createElement('i');
            arr.className = 'fas fa-chevron-down ss-btn-arr';
            btn.appendChild(arr);
            wrap.insertBefore(btn, select);

            /* Panel */
            var panel = document.createElement('div');
            panel.className = 'ss-panel';

            var search = document.createElement('input');
            search.type        = 'text';
            search.placeholder = 'Search...';
            search.className   = 'ss-search';
            panel.appendChild(search);

            var ul = document.createElement('ul');
            ul.className = 'ss-list';
            panel.appendChild(ul);
            wrap.appendChild(panel);

            /* Options */
            Array.from(select.options).forEach(function (opt) {
                if (opt.value === '') return; // skip placeholder option
                var li         = document.createElement('li');
                li.className   = 'ss-item';
                li.dataset.val = opt.value;
                li.textContent = opt.text;
                if (select.value && select.value === opt.value) {
                    li.classList.add('is-sel');
                }
                li.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    select.value = opt.value;
                    syncSS(select, opt.value);
                    closeAll();
                });
                ul.appendChild(li);
            });

            /* Search filter */
            search.addEventListener('input', function () {
                var q = search.value.toLowerCase();
                ul.querySelectorAll('.ss-item').forEach(function (li) {
                    li.style.display = li.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
            });

            /* Open / close */
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var wasOpen = panel.classList.contains('is-open');
                closeAll();
                if (!wasOpen) {
                    btn.classList.add('is-open');
                    panel.classList.add('is-open');
                    search.value = '';
                    ul.querySelectorAll('.ss-item').forEach(function (li) {
                        li.style.display = '';
                    });
                    setTimeout(function () { search.focus(); }, 50);
                }
            });
        }

        /* Global helpers */
        document.addEventListener('click', closeAll);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeAll();
        });

        /**
         * Build SS widgets inside a root element.
         * Safe to call multiple times — skips already-built selects.
         */
        window.buildSearchableSelects = function (root) {
            (root || document).querySelectorAll('select.ss').forEach(buildSS);
        };

        /**
         * Sync the SS button text & highlighted item to a new value.
         * Call this whenever you programmatically change select.value.
         */
        window.syncSS = function (selectEl, value) {
            if (!selectEl || !selectEl._ssBuilt) return;
            selectEl.value = value;
            var wrap = selectEl.closest('.ss-wrap');
            if (!wrap) return;
            var btn  = wrap.querySelector('.ss-btn');
            var txt  = wrap.querySelector('.ss-btn-txt');
            var ul   = wrap.querySelector('.ss-list');
            var ph   = selectEl.dataset.placeholder || '— Select —';
            var opt  = selectEl.options[selectEl.selectedIndex];
            if (opt && opt.value) {
                txt.textContent = opt.text;
                btn.classList.remove('is-placeholder');
            } else {
                txt.textContent = ph;
                btn.classList.add('is-placeholder');
            }
            if (ul) {
                ul.querySelectorAll('.ss-item').forEach(function (li) {
                    li.classList.toggle('is-sel', li.dataset.val === String(value) && value !== '');
                });
            }
        };

        /* Expose close helper */
        window.closeAllSS = closeAll;

    })();
}

/* ══════════════════════════════════════════════════════════════════════════
   CONSTANTS
══════════════════════════════════════════════════════════════════════════ */

var STATUS_META = {
    boarding : { label: 'Boarding',  color: '#f97316' },
    departed : { label: 'Departed',  color: '#3b82f6' },
    arrived  : { label: 'Arrived',   color: '#22c55e' },
    cancelled: { label: 'Cancelled', color: '#ef4444' },
};

var STATUS_TRANSITIONS = {
    boarding : ['departed', 'cancelled'],
    departed : ['arrived',  'cancelled'],
    arrived  : [],
    cancelled: [],
};

/* ══════════════════════════════════════════════════════════════════════════
   PAGE INIT  —  called by nav.js after every page swap
══════════════════════════════════════════════════════════════════════════ */
window.initSchedulesPage = function () {

    /* Guard — only run on the schedules page */
    var tbody = document.getElementById('schedules-tbody');
    if (!tbody) return;

    /* ── Element refs ────────────────────────────────────────────────── */
    var searchInput = document.getElementById('schedule-search');
    var countBadge  = document.getElementById('schedule-count');
    var openAddBtn  = document.getElementById('open-add-modal');
    var editForm    = document.getElementById('editForm');
    var addModalEl  = document.getElementById('addModal');
    var editModalEl = document.getElementById('editModal');

    /* ── Build SS widgets for this page ──────────────────────────────── */
    window.buildSearchableSelects(document);

    /* ── Bootstrap modals ────────────────────────────────────────────── */
    var addModal  = addModalEl  ? bootstrap.Modal.getOrCreateInstance(addModalEl)  : null;
    var editModal = editModalEl ? bootstrap.Modal.getOrCreateInstance(editModalEl) : null;

    /* ── Open add-modal button ───────────────────────────────────────── */
    if (openAddBtn && addModal) {
        openAddBtn.addEventListener('click', function () {
            addModal.show();
        });
    }

    /* ── Status filter ref (declared here so applyFilters can close over it) ── */
    var statusFilter = document.getElementById('schedule-status-filter');

    /* ══════════════════════════════════════════════════════════════════
       applyFilters()  —  ONE function, handles search + status together.
       Called by both the search input and the status dropdown.
    ══════════════════════════════════════════════════════════════════ */
    function applyFilters() {
        var q      = searchInput  ? searchInput.value.toLowerCase().trim() : '';
        var status = statusFilter ? statusFilter.value : '';

        tbody.querySelectorAll('tr.schedule-row').forEach(function (row) {
            /* Build searchable text from every meaningful data attribute */
            var text = (
                (row.dataset.routeDisplay || '') + ' ' +
                (row.dataset.driverName   || '') + ' ' +
                (row.dataset.vanPlate     || '') + ' ' +
                (row.dataset.status       || '')
            ).toLowerCase();

            var matchSearch = !q      || text.includes(q);
            var matchStatus = !status || row.dataset.status === status;

            row.style.display = (matchSearch && matchStatus) ? '' : 'none';
        });

        updateCount();
    }

    /* ── Count badge ─────────────────────────────────────────────────── */
    function updateCount() {
        if (!countBadge) return;
        /* :not([style*="display: none"]) misses rows hidden with display:'' reset,
           so check offsetParent instead — but simplest reliable way is to recount */
        var visible = 0;
        tbody.querySelectorAll('tr.schedule-row').forEach(function (row) {
            if (row.style.display !== 'none') visible++;
        });
        countBadge.textContent = visible + ' schedule' + (visible !== 1 ? 's' : '');
    }

    /* ── Wire events — both call the same applyFilters() ─────────────── */
    if (searchInput)  searchInput.addEventListener('input',  applyFilters);
    if (statusFilter) statusFilter.addEventListener('change', applyFilters);

    /* Run once on init so count is correct immediately */
    applyFilters();

    /* ── Row click → highlight + preview ────────────────────────────── */
    tbody.addEventListener('click', function (e) {
        if (e.target.closest('.row-actions')) return;
        var row = e.target.closest('tr.schedule-row');
        if (!row) return;
        tbody.querySelectorAll('tr.schedule-row.selected').forEach(function (r) {
            r.classList.remove('selected');
        });
        row.classList.add('selected');
        showPreview(row);
    });

    /* ── Action buttons (delegated) ──────────────────────────────────── */
    tbody.addEventListener('click', function (e) {

        /* EDIT */
        var editBtn = e.target.closest('.icon-btn.edit');
        if (editBtn) {
            e.stopPropagation();
            var row = editBtn.closest('tr.schedule-row');
            if (!row || !editModal) return;
            populateEditModal(row);
            editModal.show();
            return;
        }

        /* DELETE */
        var delBtn = e.target.closest('.icon-btn.delete');
        if (delBtn) {
            e.stopPropagation();
            var row = delBtn.closest('tr.schedule-row');
            if (!row) return;
            deleteSchedule(row.dataset.id, row.dataset.routeDisplay || 'this schedule');
            return;
        }

        /* STATUS */
        var statusBtn = e.target.closest('.icon-btn.status');
        if (statusBtn) {
            e.stopPropagation();
            var row = statusBtn.closest('tr.schedule-row');
            if (!row) return;
            showStatusDropdown(statusBtn, row);
            return;
        }
    });

    /* ── Edit form submit (AJAX) ─────────────────────────────────────── */
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var id = document.getElementById('edit-id').value;
            if (id) submitEdit(id);
        });
    }

    /* ── Close status dropdown on outside click ──────────────────────── */
    document.addEventListener('click', closeStatusDropdown);

    /* ══════════════════════════════════════════════════════════════════
       PREVIEW PANEL
    ══════════════════════════════════════════════════════════════════ */
    function showPreview(row) {
        var emptyEl   = document.getElementById('schedule-empty');
        var previewEl = document.getElementById('schedule-preview');
        var labelEl   = document.getElementById('schedule-label');
        if (!emptyEl || !previewEl) return;

        var route    = row.dataset.routeDisplay || 'N/A';
        var driver   = row.dataset.driverName   || 'N/A';
        var plate    = row.dataset.vanPlate     || 'N/A';
        var capacity = row.dataset.vanCapacity  || '—';
        var date     = row.dataset.date         || '';
        var time     = row.dataset.time         || '';
        var status   = row.dataset.status       || 'boarding';
        var meta     = STATUS_META[status] || { label: status, color: '#6b7280' };

        var departure = '—';
        if (date && time) {
            try {
                departure = new Date(date + 'T' + time).toLocaleString('en-US', {
                    month: 'short', day: 'numeric',
                    hour: 'numeric', minute: '2-digit'
                });
            } catch (_) { departure = date + ' ' + time; }
        }

        if (labelEl) labelEl.textContent = route;

        setText('preview-route',    route);
        setText('preview-driver',   driver);
        setText('preview-van',      plate);
        setText('preview-capacity', capacity + ' seats');
        setText('preview-departure', departure);

        var statusEl = document.getElementById('preview-status');
        if (statusEl) {
            /* Reset all status classes before setting new one */
            statusEl.className = 'detail-badge badge ' + status;
            statusEl.textContent = meta.label;
        }

        /* Arrived-at row */
        var arrivedRow = document.getElementById('preview-arrived-row');
        var arrivedAt  = document.getElementById('preview-arrived-at');
        if (arrivedRow && arrivedAt) {
            if (status === 'arrived' && row.dataset.arrivedAt) {
                arrivedAt.textContent   = row.dataset.arrivedAt;
                arrivedRow.style.display = 'flex';
            } else {
                arrivedRow.style.display = 'none';
            }
        }

        emptyEl.style.display   = 'none';
        previewEl.style.display = 'block';
    }

    function setText(id, text) {
        var el = document.getElementById(id);
        if (el) el.textContent = text;
    }

    /* ══════════════════════════════════════════════════════════════════
       EDIT MODAL — populate + sync SS
    ══════════════════════════════════════════════════════════════════ */
    function populateEditModal(row) {
        document.getElementById('edit-id').value   = row.dataset.id    || '';
        document.getElementById('edit-date').value = row.dataset.date  || '';
        document.getElementById('edit-time').value = row.dataset.time  || '';

        /* Sync each searchable select */
        syncField('edit-route',  row.dataset.route);
        syncField('edit-driver', row.dataset.driver);
        syncField('edit-van',    row.dataset.van);
        syncField('edit-status', row.dataset.status);
    }

    function syncField(id, value) {
        var el = document.getElementById(id);
        if (!el) return;
        /* If SS not built yet on this element, build it first */
        if (!el._ssBuilt) window.buildSearchableSelects(editModalEl);
        window.syncSS(el, value || '');
    }

    /* ══════════════════════════════════════════════════════════════════
       AJAX — EDIT
    ══════════════════════════════════════════════════════════════════ */
    function submitEdit(scheduleId) {
        /* Build payload manually from each field.
           Avoids FormData issues with hidden SS selects
           and guarantees we only send what we intend. */
        var payload = {
            schedule_id   : scheduleId,
            route_id      : getVal('edit-route'),
            driver_id     : getVal('edit-driver'),
            van_id        : getVal('edit-van'),
            departure_date: getVal('edit-date'),
            departure_time: getVal('edit-time'),
            trip_status   : getVal('edit-status'),
            csrf_token    : getCsrf(),
        };

        /* Basic client-side validation */
        if (!payload.route_id || !payload.driver_id || !payload.van_id ||
            !payload.departure_date || !payload.departure_time) {
            Swal.fire('Validation', 'Please fill in all required fields.', 'warning');
            return;
        }

        fetchPost('../../controllers/Schedules/EditSchedule.php', payload)
            .then(function (data) {
                if (data.no_changes) {
                    Swal.fire({ icon: 'info', title: 'No Changes', text: data.message });
                    return;
                }
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Saved!', text: data.message })
                        .then(function () { location.reload(); });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Save failed.' });
                }
            })
            .catch(function () {
                Swal.fire('Error', 'Network error. Please try again.', 'error');
            });
    }

    /* ══════════════════════════════════════════════════════════════════
       AJAX — DELETE
    ══════════════════════════════════════════════════════════════════ */
    function deleteSchedule(scheduleId, routeLabel) {
        Swal.fire({
            title            : 'Delete Schedule?',
            text             : 'Delete "' + routeLabel + '"? This cannot be undone.',
            icon             : 'warning',
            showCancelButton : true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor : '#6b7280',
            confirmButtonText : 'Yes, delete it!',
        }).then(function (result) {
            if (!result.isConfirmed) return;

            fetchPost('../../controllers/Schedules/DeleteSchedule.php', {
                schedule_id: scheduleId,
                csrf_token : getCsrf(),
            }).then(function (data) {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Deleted!', text: data.message })
                        .then(function () { location.reload(); });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Delete failed.' });
                }
            }).catch(function () {
                Swal.fire('Error', 'Network error. Please try again.', 'error');
            });
        });
    }

    /* ══════════════════════════════════════════════════════════════════
       AJAX — STATUS TOGGLE
    ══════════════════════════════════════════════════════════════════ */
    var _openDropdown = null;

    function closeStatusDropdown() {
        if (_openDropdown) {
            _openDropdown.remove();
            _openDropdown = null;
        }
    }

    function showStatusDropdown(anchorBtn, row) {
        closeStatusDropdown();

        var current     = row.dataset.status || 'boarding';
        var scheduleId  = row.dataset.id;
        var transitions = STATUS_TRANSITIONS[current] || [];

        var dropdown       = document.createElement('div');
        dropdown.className = 'sched-status-dropdown';

        /* Stop clicks inside dropdown from bubbling to the
           document listener that would immediately close it */
        dropdown.addEventListener('click', function (e) { e.stopPropagation(); });

        if (transitions.length === 0) {
            var msg       = document.createElement('div');
            msg.className = 'sched-status-item sched-status-disabled';
            msg.textContent = 'No further transitions';
            dropdown.appendChild(msg);
        } else {
            transitions.forEach(function (newStatus) {
                var meta = STATUS_META[newStatus] || { label: newStatus, color: '#6b7280' };
                var item       = document.createElement('div');
                item.className = 'sched-status-item';
                item.innerHTML =
                    '<span class="badge ' + newStatus + '">' + meta.label + '</span>' +
                    '<span>Mark as ' + meta.label + '</span>';

                item.addEventListener('click', function () {
                    closeStatusDropdown();
                    Swal.fire({
                        title            : 'Change status?',
                        text             : '"' + formatStatus(current) + '" → "' + meta.label + '"',
                        icon             : 'question',
                        showCancelButton : true,
                        confirmButtonColor: meta.color,
                        cancelButtonColor : '#6b7280',
                        confirmButtonText : 'Yes, update!',
                    }).then(function (result) {
                        if (result.isConfirmed) updateStatus(scheduleId, newStatus, row);
                    });
                });

                dropdown.appendChild(item);
            });
        }

        /* Position below the anchor button */
        var rect = anchorBtn.getBoundingClientRect();
        dropdown.style.cssText =
            'position:fixed;' +
            'top:'  + (rect.bottom + 6) + 'px;' +
            'left:' + rect.left         + 'px;' +
            'z-index:9999;';

        document.body.appendChild(dropdown);
        _openDropdown = dropdown;
    }

    function updateStatus(scheduleId, newStatus, row) {
        fetchPost('../../controllers/Schedules/ToggleSchedule.php', {
            schedule_id: scheduleId,
            status     : newStatus,
            csrf_token : getCsrf(),
        }).then(function (data) {
            if (data.success) {
                /* Update row dataset */
                row.dataset.status = newStatus;

                /* Update status badge inside row */
                var badge = row.querySelector('td .badge');
                if (badge) {
                    badge.className   = 'badge ' + newStatus;
                    badge.textContent = formatStatus(newStatus);
                }

                /* If this row is currently selected, refresh preview */
                if (row.classList.contains('selected')) {
                    showPreview(row);
                }

                Swal.fire({ icon: 'success', title: 'Updated!', text: 'Status changed to ' + formatStatus(newStatus) + '.', timer: 1800, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to update status.' });
            }
        }).catch(function () {
            Swal.fire('Error', 'Network error. Please try again.', 'error');
        });
    }

    /* ══════════════════════════════════════════════════════════════════
       HELPERS
    ══════════════════════════════════════════════════════════════════ */

    /** Get value from a form field by ID */
    function getVal(id) {
        var el = document.getElementById(id);
        return el ? el.value : '';
    }

    /**
     * Get CSRF token.
     * Reads from the standalone hidden input rendered OUTSIDE any modal
     * by the bare <?= csrf_field() ?> at the bottom of the schedules wrapper.
     */
    function getCsrf() {
        /* The bare csrf_field() outside modals is the reliable one */
        var inputs = document.querySelectorAll('input[name="csrf_token"]');
        /* Last one is least likely to be inside a closed/hidden modal */
        if (inputs.length) return inputs[inputs.length - 1].value;
        return '';
    }

    /** POST with application/x-www-form-urlencoded and return parsed JSON */
    function fetchPost(url, data) {
        var body = Object.keys(data).map(function (k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(data[k] || '');
        }).join('&');
        return fetch(url, {
            method : 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body   : body,
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        });
    }

    /** Nicely format raw status key → display label */
    function formatStatus(status) {
        var meta = STATUS_META[status];
        if (meta) return meta.label;
        return status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ');
    }

}; // end initSchedulesPage

/* Auto-init for direct (non-AJAX) page loads */
document.addEventListener('DOMContentLoaded', window.initSchedulesPage);