/**
 * schedules-js.js
 *
 * Matches schedules.php exactly:
 *  - Preview uses #preview-route, #preview-driver, #preview-van,
 *    #preview-capacity, #preview-departure, #preview-status spans
 *  - All data-* read from <tr class="schedule-row"> only
 *  - CSRF token read once via getCsrfToken() helper
 *  - Delete → postForm() → DeleteSchedule.php  (no login redirect)
 *  - Status → dropdown with state machine → UpdateStatus.php
 *  - Edit   → populate modal selects via syncSS()
 */

/* ── Searchable-select widget ─────────────────────────────────────────────── */
if (!window._ssReady) {
    window._ssReady = true;

    (function () {
        function closeAll() {
            document.querySelectorAll('.ss-panel.is-open').forEach(p => p.classList.remove('is-open'));
            document.querySelectorAll('.ss-btn.is-open').forEach(b => b.classList.remove('is-open'));
        }

        function buildSS(select) {
            if (select._ssBuilt) return;
            select._ssBuilt = true;

            const ph   = select.dataset.placeholder || '— Select —';
            const wrap = document.createElement('div');
            wrap.className = 'ss-wrap';
            select.parentNode.insertBefore(wrap, select);
            wrap.appendChild(select);

            const btn = document.createElement('button');
            btn.type      = 'button';
            btn.className = 'ss-btn';

            const txt = document.createElement('span');
            txt.className = 'ss-btn-txt';
            const current = select.options[select.selectedIndex];
            if (current?.value) {
                txt.textContent = current.text;
            } else {
                txt.textContent = ph;
                btn.classList.add('is-placeholder');
            }
            btn.appendChild(txt);
            btn.appendChild(Object.assign(document.createElement('i'), {
                className: 'fas fa-chevron-down ss-btn-arr'
            }));
            wrap.insertBefore(btn, select);

            const panel = document.createElement('div');
            panel.className = 'ss-panel';

            const ul = document.createElement('ul');
            ul.className = 'ss-list';

            const noResults = Object.assign(document.createElement('li'), {
                className: 'ss-no-results', textContent: 'No results found'
            });
            noResults.style.display = 'none';
            ul.appendChild(noResults);

            Array.from(select.options).forEach(opt => {
                const li = Object.assign(document.createElement('li'), {
                    className: 'ss-item'
                        + (!opt.value ? ' is-placeholder' : '')
                        + (opt.selected && opt.value ? ' is-sel' : ''),
                    textContent: opt.text
                });
                li.dataset.val  = opt.value;
                li.dataset.text = opt.text;
                ul.appendChild(li);
            });

            panel.appendChild(ul);
            wrap.insertBefore(panel, select);

            btn.addEventListener('click', e => {
                e.stopPropagation();
                const wasOpen = panel.classList.contains('is-open');
                closeAll();
                if (!wasOpen) {
                    panel.classList.add('is-open');
                    btn.classList.add('is-open');
                }
            });

            ul.addEventListener('click', e => {
                const li = e.target.closest('.ss-item');
                if (!li) return;
                select.value = li.dataset.val;
                txt.textContent = li.dataset.val ? li.dataset.text : ph;
                btn.classList.toggle('is-placeholder', !li.dataset.val);
                ul.querySelectorAll('.ss-item.is-sel').forEach(x => x.classList.remove('is-sel'));
                if (li.dataset.val) li.classList.add('is-sel');
                closeAll();
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }

        document.addEventListener('click', closeAll);
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAll(); });

        window.buildSearchableSelects = function (root) {
            (root || document).querySelectorAll('select.ss').forEach(buildSS);
        };

        window.syncSS = function (selectEl, value) {
            if (!selectEl?._ssBuilt) return;
            selectEl.value = value;
            const wrap = selectEl.closest('.ss-wrap');
            if (!wrap) return;
            const btn = wrap.querySelector('.ss-btn');
            const txt = wrap.querySelector('.ss-btn-txt');
            const ul  = wrap.querySelector('.ss-list');
            const ph  = selectEl.dataset.placeholder || '— Select —';
            const opt = selectEl.options[selectEl.selectedIndex];
            if (opt?.value) {
                txt.textContent = opt.text;
                btn.classList.remove('is-placeholder');
            } else {
                txt.textContent = ph;
                btn.classList.add('is-placeholder');
            }
            ul?.querySelectorAll('.ss-item').forEach(li => {
                li.classList.toggle('is-sel', li.dataset.val === value && value !== '');
            });
        };
    })();
}

/* ── Helpers ──────────────────────────────────────────────────────────────── */

function getCsrfToken() {
    const el = document.querySelector('input[name="csrf_token"]');
    if (!el) {
        console.error('[schedules] CSRF token input not found.');
        return null;
    }
    return el.value;
}

function postForm(action, fields) {
    const csrf = getCsrfToken();
    if (!csrf) {
        Swal.fire('Error', 'Security token missing. Please refresh the page.', 'error');
        return;
    }
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action;

    const addHidden = (name, value) => {
        const input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = name;
        input.value = value;
        form.appendChild(input);
    };

    addHidden('csrf_token', csrf);
    Object.entries(fields).forEach(([name, value]) => addHidden(name, value));
    document.body.appendChild(form);
    form.submit();
}

/* ── State machine ────────────────────────────────────────────────────────── */

const STATUS_META = {
    boarding:  { label: 'Boarding',  colour: '#f97316' },
    departed:  { label: 'Departed',  colour: '#3b82f6' },
    arrived:   { label: 'Arrived',   colour: '#22c55e' },
    cancelled: { label: 'Cancelled', colour: '#ef4444' },
};

function validTransitions(current) {
    const map = {
        boarding:  ['departed', 'cancelled'],
        departed:  ['arrived',  'cancelled'],
        arrived:   [],
        cancelled: [],
    };
    return map[current] ?? [];
}

/* ── Preview panel ────────────────────────────────────────────────────────── */

/**
 * Populates the named #preview-* spans in schedules.php.
 * Never overwrites innerHTML of the whole panel — avatar stays intact.
 */
function showSchedulePreview(row) {
    const empty   = document.getElementById('schedule-empty');
    const preview = document.getElementById('schedule-preview');
    const label   = document.getElementById('schedule-label');

    if (!empty || !preview || !label) return;

    const routeDisplay = row.dataset.routeDisplay || 'N/A';
    const driverName   = row.dataset.driverName   || 'N/A';
    const vanPlate     = row.dataset.vanPlate      || 'N/A';
    const vanCapacity  = row.dataset.vanCapacity   || 'N/A';
    const date         = row.dataset.date          || '';
    const time         = row.dataset.time          || '';
    const status       = row.dataset.status        || 'boarding';
    const meta         = STATUS_META[status] || { label: status, colour: '#6b7280' };

    const departure = (date && time)
        ? new Date(date + 'T' + time).toLocaleString('en-US', {
              month: 'short', day: 'numeric',
              hour: 'numeric', minute: '2-digit'
          })
        : 'N/A';

    label.textContent = routeDisplay;

    document.getElementById('preview-route').textContent     = routeDisplay;
    document.getElementById('preview-driver').textContent    = driverName;
    document.getElementById('preview-van').textContent       = vanPlate;
    document.getElementById('preview-capacity').textContent  = vanCapacity + ' seats';
    document.getElementById('preview-departure').textContent = departure;

    const statusEl = document.getElementById('preview-status');
    statusEl.textContent = meta.label;
    statusEl.className = 'detail-badge badge ' + status;

    // Arrived At — only show when status is arrived
    const arrivedRow = document.getElementById('preview-arrived-row');
    const arrivedAt  = document.getElementById('preview-arrived-at');
    if (status === 'arrived' && row.dataset.arrivedAt) {
        arrivedAt.textContent    = row.dataset.arrivedAt;
        arrivedRow.style.display = 'flex';
    } else {
        arrivedRow.style.display = 'none';
    }

    empty.style.display   = 'none';
    preview.style.display = 'block';
}

/* ── Status dropdown ──────────────────────────────────────────────────────── */

let _openDropdown = null;

function closeStatusDropdown() {
    if (_openDropdown) { _openDropdown.remove(); _openDropdown = null; }
}

function showStatusDropdown(anchorBtn, row) {
    closeStatusDropdown();

    const current    = row.dataset.status || 'boarding';
    const scheduleId = row.dataset.id;
    const next       = validTransitions(current);

    const dropdown = document.createElement('div');
    dropdown.className = 'status-dropdown';

    if (next.length === 0) {
        const item = document.createElement('div');
        item.className = 'status-item invalid';
        item.style.cssText = 'font-style:italic;color:#9ca3af;';
        item.textContent = 'No further transitions';
        dropdown.appendChild(item);
    } else {
        next.forEach(newStatus => {
            const meta = STATUS_META[newStatus] || { label: newStatus, colour: '#6b7280' };
            const item = document.createElement('div');
            item.className = 'status-item valid';
            item.innerHTML = `
                <span class="status-badge-small badge ${newStatus}">${meta.label}</span>
                <span>Mark as ${meta.label}</span>`;
            item.addEventListener('click', e => {
                e.stopPropagation();
                closeStatusDropdown();
                Swal.fire({
                    title: `Change to ${meta.label}?`,
                    text: `Update status: "${current}" → "${newStatus}"`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: meta.colour,
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, update!'
                }).then(result => {
                    if (result.isConfirmed) {
                        postForm('../../controllers/Schedules/UpdateStatus.php', {
                            schedule_id: scheduleId,
                            status: newStatus
                        });
                    }
                });
            });
            dropdown.appendChild(item);
        });
    }

    const rect = anchorBtn.getBoundingClientRect();
    dropdown.style.cssText = `position:fixed;top:${rect.bottom + 6}px;left:${rect.left}px;z-index:9999;`;
    document.body.appendChild(dropdown);
    _openDropdown = dropdown;
}

/* ── Action delegation ────────────────────────────────────────────────────── */

function handleScheduleActionClick(e) {

    // Edit
    const editBtn = e.target.closest('.icon-btn.edit');
    if (editBtn) {
        e.stopPropagation();
        const row = editBtn.closest('.schedule-row');
        if (!row) return;

        document.getElementById('edit-id').value = row.dataset.id;

        document.getElementById('edit-route').value = row.dataset.route;
        window.syncSS(document.getElementById('edit-route'), row.dataset.route);

        document.getElementById('edit-driver').value = row.dataset.driver;
        window.syncSS(document.getElementById('edit-driver'), row.dataset.driver);

        document.getElementById('edit-van').value = row.dataset.van;
        window.syncSS(document.getElementById('edit-van'), row.dataset.van);

        document.getElementById('edit-date').value   = row.dataset.date;
        document.getElementById('edit-time').value   = row.dataset.time;
        document.getElementById('edit-status').value = row.dataset.status;
        window.syncSS(document.getElementById('edit-status'), row.dataset.status);

        bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal')).show();
        return;
    }

    // Delete
    const delBtn = e.target.closest('.icon-btn.delete');
    if (delBtn) {
        e.stopPropagation();
        const row        = delBtn.closest('.schedule-row');
        const routeLabel = row?.dataset.routeDisplay || 'this schedule';

        Swal.fire({
            title: 'Delete Schedule?',
            text: `Are you sure you want to delete "${routeLabel}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (result.isConfirmed) {
                postForm('../../controllers/Schedules/DeleteSchedule.php', {
                    schedule_id: row.dataset.id
                });
            }
        });
        return;
    }

    // Status
    const statusBtn = e.target.closest('.icon-btn.status');
    if (statusBtn) {
        e.stopPropagation();
        const row = statusBtn.closest('.schedule-row');
        if (!row) return;
        showStatusDropdown(statusBtn, row);
        return;
    }
}

/* ── DOMContentLoaded ─────────────────────────────────────────────────────── */

document.addEventListener('DOMContentLoaded', () => {
    window.buildSearchableSelects(document);

    const rows    = document.querySelectorAll('.schedule-row');
    const countEl = document.getElementById('schedule-count');
    if (countEl) {
        const label = countEl.dataset.label || 'schedule';
        countEl.textContent = `${rows.length} ${label}${rows.length !== 1 ? 's' : ''}`;
    }

    // Search
    const searchInput = document.getElementById('schedule-search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            rows.forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    // Row click → preview
    rows.forEach(row => {
        row.addEventListener('click', function (e) {
            if (e.target.closest('.row-actions')) return;
            rows.forEach(r => r.classList.remove('selected'));
            this.classList.add('selected');
            showSchedulePreview(this);
        });
    });

    // Add modal
    const addBtn = document.getElementById('open-add-modal');
    if (addBtn) {
        addBtn.addEventListener('click', () => {
            const modal = document.getElementById('addModal');
            if (modal) {
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        });
    }

    // Delegated actions
    const pageContent = document.getElementById('page-content');
    if (pageContent) {
        pageContent.addEventListener('click', handleScheduleActionClick);
    }

    // Close status dropdown on outside click
    document.addEventListener('click', e => {
        if (_openDropdown && !_openDropdown.contains(e.target)) {
            closeStatusDropdown();
        }
    });
});