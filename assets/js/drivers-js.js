if (!window._ssReady) {
    window._ssReady = true;

    (function () {
        // Searchable select code (same as vans-js.js)
        function closeAll() {
            document.querySelectorAll('.ss-panel.is-open').forEach(p => p.classList.remove('is-open'));
            document.querySelectorAll('.ss-btn.is-open').forEach(b => b.classList.remove('is-open'));
        }

        function buildSS(select) {
            if (select._ssBuilt) return;
            select._ssBuilt = true;

            const ph = select.dataset.placeholder || '— Select —';
            const wrap = document.createElement('div');
            wrap.className = 'ss-wrap';
            select.parentNode.insertBefore(wrap, select);
            wrap.appendChild(select);

            const btn = document.createElement('button');
            btn.type = 'button';
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
                li.dataset.val = opt.value;
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
            const ul = wrap.querySelector('.ss-list');
            const ph = selectEl.dataset.placeholder || '— Select —';
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

function showDriverPreview(row) {
    const driverEmpty = document.getElementById('driver-empty');
    const driverPreview = document.getElementById('driver-preview');
    if (!driverEmpty || !driverPreview) return;

    const fullname = row.dataset.fullname || '—';
    const license = row.dataset.license || '—';
    const contact = row.dataset.contact || '—';
    const status = row.dataset.status || '—';

    // Update details
    document.getElementById('preview-name').textContent = fullname;
    document.getElementById('preview-license').textContent = license;
    document.getElementById('preview-contact').textContent = contact;
    document.getElementById('preview-status').textContent = status.charAt(0).toUpperCase() + status.slice(1);
    document.getElementById('preview-status').className = 'detail-badge ' + status;

    document.getElementById('driver-label').textContent = fullname;

    driverEmpty.style.display = 'none';
    driverPreview.style.display = 'block';
}

function handleDriverActionClick(e) {
    // Edit
    const editBtn = e.target.closest('.icon-btn.edit');
    if (editBtn) {
        e.stopPropagation();

        document.getElementById('edit-id').value = editBtn.dataset.id;
        document.getElementById('edit-fullname').value = editBtn.dataset.fullname;
        document.getElementById('edit-license').value = editBtn.dataset.license;
        document.getElementById('edit-contact').value = editBtn.dataset.contact;
        window.syncSS(document.getElementById('edit-status'), editBtn.dataset.status);

        bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal')).show();
        return;
    }

}

function getCsrf() {
    return document.getElementById('csrf_token')?.value;
}

/* =========================================================
   SINGLE EVENT DELEGATION (EDIT / DELETE / TOGGLE)
========================================================= */
document.getElementById('drivers-tbody')?.addEventListener('click', function (e) {

    /* ===================== EDIT ===================== */
    const editBtn = e.target.closest('.icon-btn.edit');
    if (editBtn) {

        document.getElementById('edit-id').value = editBtn.dataset.id;
        document.getElementById('edit-fullname').value = editBtn.dataset.fullname;
        document.getElementById('edit-license').value = editBtn.dataset.license;
        document.getElementById('edit-contact').value = editBtn.dataset.contact;

        window.syncSS(
            document.getElementById('edit-status'),
            editBtn.dataset.status
        );

        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('editModal')
        ).show();

        return;
    }

    /* ===================== DELETE ===================== */
    const delBtn = e.target.closest('.icon-btn.delete');
    if (delBtn) {

        Swal.fire({
            title: 'Delete Driver?',
            text: `Driver "${delBtn.dataset.fullname}" will be permanently removed.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, delete'
        }).then(result => {
            if (!result.isConfirmed) return;

            const formData = new FormData();
            formData.append('driver_id', delBtn.dataset.id);
            formData.append('csrf_token', getCsrf());

            fetch('../../controllers/Drivers/DeleteDriver.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(res => {

                    if (res.success) {
                        Swal.fire('Deleted!', res.message, 'success')
                            .then(() => location.reload());
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }

                })
                .catch(() => {
                    Swal.fire('Error', 'Network error', 'error');
                });
        });

        return;
    }

    /* ===================== TOGGLE ===================== */
    const toggleBtn = e.target.closest('.icon-btn.toggle');
    if (toggleBtn) {

        const nextStatus =
            toggleBtn.dataset.status === 'active'
                ? 'inactive'
                : 'active';

        Swal.fire({
            title: 'Toggle Status?',
            text: 'Set this driver to ' + nextStatus + '?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, toggle',
            confirmButtonColor: '#2e3a4d',
        }).then(result => {
            if (!result.isConfirmed) return;

            const formData = new FormData();
            formData.append('driver_id', toggleBtn.dataset.id);
            formData.append('status', nextStatus);
            formData.append('csrf_token', getCsrf());

            fetch('../../controllers/Drivers/ToggleDriver.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(res => {

                    if (res.success) {
                        Swal.fire('Updated!', res.message, 'success')
                            .then(() => location.reload());
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }

                })
                .catch(() => {
                    Swal.fire('Error', 'Network error', 'error');
                });
        });

        return;
    }
});
document.getElementById('editDriverForm')?.addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('csrf_token', getCsrf());

    fetch('../../controllers/Drivers/EditDriver.php', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(res => {

            if (res.no_changes) {
                Swal.fire({
                    icon: 'info',
                    title: 'No Changes',
                    text: res.message
                });
                return;
            }

            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: res.message
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: res.message
                });
            }

        })
        .catch(err => {
            console.log(err);
            Swal.fire('Error', 'Network error', 'error');
        });
});
document.addEventListener('DOMContentLoaded', () => {
    window.buildSearchableSelects(document);

    // Driver count badge
    const rows = document.querySelectorAll('.driver-row');
    const countEl = document.getElementById('driver-count');
    if (countEl) countEl.textContent = `${rows.length} driver${rows.length !== 1 ? 's' : ''}`;

    // Search filter — name, license, contact
    document.getElementById('driver-search')?.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        rows.forEach(row => {
            const match =
                row.dataset.fullname.toLowerCase().includes(q) ||
                row.dataset.license.toLowerCase().includes(q) ||
                row.dataset.contact.toLowerCase().includes(q);
            row.style.display = match ? '' : 'none';
        });
    });

    // Row click → driver preview
    rows.forEach(row => {
        row.addEventListener('click', function (e) {
            if (e.target.closest('.row-actions')) return;
            rows.forEach(r => r.classList.remove('selected'));
            this.classList.add('selected');
            showDriverPreview(this);
        });
    });

    // Action buttons via event delegation
    document.getElementById('page-content')?.addEventListener('click', handleDriverActionClick);

    // Add modal open
    document.getElementById('open-add-modal')?.addEventListener('click', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addModal')).show();
    });

    // Auto-uppercase license inputs
    document.querySelectorAll('input[name="license_number"]').forEach(input => {
        input.addEventListener('input', function () {
            const pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);
        });
    });
});

