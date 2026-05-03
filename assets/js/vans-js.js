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

            const txt     = document.createElement('span');
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

/**
 * Renders the seat grid in the side card.
 * seats = array of { seat_number, seat_row, seat_col } from PHP json_encode.
 * Strict layout: 3 columns, front row with driver and 2 seats, back rows 3x3 grid.
 */
function showSeatPreview(row) {
    const seatEmpty   = document.getElementById('seat-empty');
    const seatPreview = document.getElementById('seat-preview');
    const seatGrid    = document.getElementById('seat-grid');
    const vanPanel    = document.getElementById('van-info-panel');
    const seatLabel   = document.getElementById('seat-label');
    if (!seatEmpty || !seatPreview || !seatGrid) return;

    const plate    = row.dataset.plate    || '—';
    const model    = row.dataset.model    || '—';
    const capacity = row.dataset.capacity || '—';
    const status   = row.dataset.status   || '—';
    const seats    = row.dataset.seats ? JSON.parse(row.dataset.seats) : [];

    // Header
    if (seatLabel) seatLabel.textContent = plate;

    // Info panel
    document.getElementById('info-plate').textContent    = plate;
    document.getElementById('info-model').textContent    = model;
    document.getElementById('info-capacity').textContent = capacity + ' seats';
    document.getElementById('info-status').textContent   = status.charAt(0).toUpperCase() + status.slice(1);

    // Build grid
    seatGrid.innerHTML = '';

    if (seats.length === 0) {
        seatGrid.innerHTML = '<p style="font-size:12px;color:#9ca3af;padding:12px;text-align:center;">No seats found.</p>';
    } else {
        // Always 3 columns
        seatGrid.style.gridTemplateColumns = 'repeat(3, 1fr)';

        // Add driver seat
        const driver = document.createElement('div');
        driver.className = 'seat-box available';
        driver.style.gridRow = 1;
        driver.style.gridColumn = 1;
        driver.innerHTML = `<i class="fas fa-steering-wheel seat-icon"></i><span class="seat-code">DR</span>`;
        seatGrid.appendChild(driver);

        // Add passenger seats in strict order
        seats.forEach((s, i) => {
            let row, col;
            if (i === 0) {
                // Front passenger 1
                row = 1;
                col = 2;
            } else if (i === 1) {
                // Front passenger 2
                row = 1;
                col = 3;
            } else {
                // Back seats: start from row 2, 3 per row
                const backIndex = i - 2;
                row = Math.floor(backIndex / 3) + 2;
                col = (backIndex % 3) + 1;
            }
            const seat = document.createElement('div');
            seat.className = 'seat-box available';
            seat.style.gridRow = row;
            seat.style.gridColumn = col;
            seat.innerHTML = `<i class="fas fa-chair seat-icon"></i><span class="seat-code">${s.seat_number}</span>`;
            seatGrid.appendChild(seat);
        });
    }

    seatEmpty.style.display   = 'none';
    seatPreview.style.display = 'block';
    vanPanel?.classList.add('visible');
}

function getCsrfToken() {
    const tokenInput = document.getElementById('page-csrf-token') || document.querySelector('input[name="csrf_token"]');
    return tokenInput?.value?.trim() || '';
}

function buildHiddenForm(action, fields) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action;
    form.style.display = 'none';

    Object.entries(fields).forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    return form;
}

function handleVanActionClick(e) {

    // Edit
    const editBtn = e.target.closest('.icon-btn.edit');
    if (editBtn) {
        e.stopPropagation();

        document.getElementById('edit-id').value       = editBtn.dataset.id;
        document.getElementById('edit-plate').value    = editBtn.dataset.plate;
        document.getElementById('edit-model').value    = editBtn.dataset.model;
        document.getElementById('edit-capacity').value = editBtn.dataset.capacity;
        window.syncSS(document.getElementById('edit-status'), editBtn.dataset.status);

        bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal')).show();
        return;
    }

    // Delete
    const delBtn = e.target.closest('.icon-btn.delete');
    if (delBtn) {
        Swal.fire({
            title: 'Delete Van?',
            text: `Van "${delBtn.dataset.plate}" and all its seats will be permanently removed.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then(result => {
            if (!result.isConfirmed) return;
            const csrfToken = getCsrfToken();
            if (!csrfToken) {
                Swal.fire({
                    icon: 'error',
                    title: 'Missing security token',
                    text: 'Please refresh the page and try again.'
                });
                return;
            }

            const form = buildHiddenForm('../../controllers/vans/DeleteVan.php', {
                csrf_token: csrfToken,
                van_id: delBtn.dataset.id
            });
            form.submit();
        });
        return;
    }

    // Toggle
    const toggleBtn = e.target.closest('.icon-btn.toggle');
    if (toggleBtn) {
        e.stopPropagation();
        const csrfToken = getCsrfToken();
        if (!csrfToken) {
            Swal.fire({
                icon: 'error',
                title: 'Missing security token',
                text: 'Please refresh the page and try again.'
            });
            return;
        }

        const newStatus = toggleBtn.dataset.status === 'active' ? 'inactive' : 'active';
        const form = buildHiddenForm('../../controllers/vans/ToggleVan.php', {
            csrf_token: csrfToken,
            van_id: toggleBtn.dataset.id,
            status: newStatus
        });
        form.submit();
        return;
    }
}
document.addEventListener('DOMContentLoaded', () => {

    window.buildSearchableSelects(document);

    // Van count badge
    const rows    = document.querySelectorAll('.van-row');
    const countEl = document.getElementById('van-count');
    if (countEl) countEl.textContent = `${rows.length} van${rows.length !== 1 ? 's' : ''}`;

    // Search filter — plate, model
    document.getElementById('van-search')?.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        rows.forEach(row => {
            const match =
                row.dataset.plate.toLowerCase().includes(q) ||
                row.dataset.model.toLowerCase().includes(q);
            row.style.display = match ? '' : 'none';
        });
    });

    // Row click → seat preview
    rows.forEach(row => {
        row.addEventListener('click', function (e) {
            if (e.target.closest('.row-actions')) return;
            rows.forEach(r => r.classList.remove('selected'));
            this.classList.add('selected');
            showSeatPreview(this);
        });
    });

    // Action buttons via event delegation
    document.getElementById('page-content')?.addEventListener('click', handleVanActionClick);

    // Add modal open
    document.getElementById('open-add-modal')?.addEventListener('click', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addModal')).show();
    });

    // Auto-uppercase plate inputs
    document.querySelectorAll('input[name="plate_number"]').forEach(input => {
        input.addEventListener('input', function () {
            const pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);
        });
    });
});