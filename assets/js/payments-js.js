window.initPaymentsPage = function () {

    var tbody        = document.getElementById('payments-tbody');
    var countBadge   = document.getElementById('payment-count');
    var searchInput  = document.getElementById('payment-search');
    var statusFilter = document.getElementById('payment-status-filter');

    if (!tbody) return;

    var viewModal = _modal('viewModal');

    /* ── Count badge ─────────────────────────── */
    function updateCount() {
        var visible = tbody.querySelectorAll('tr.payment-row:not([style*="display: none"])').length;
        if (countBadge) {
            countBadge.textContent = visible + ' payment' + (visible !== 1 ? 's' : '');
        }
    }
    updateCount();

    /* ── Search + status filter ──────────────── */
    function applyFilters() {
        var q      = searchInput  ? searchInput.value.toLowerCase().trim() : '';
        var status = statusFilter ? statusFilter.value : '';

        tbody.querySelectorAll('tr.payment-row').forEach(function (row) {
            var matchQ = !q
                || (row.dataset.bookingRef || '').toLowerCase().includes(q)
                || (row.dataset.userName   || '').toLowerCase().includes(q)
                || (row.dataset.userEmail  || '').toLowerCase().includes(q)
                || (row.dataset.ref        || '').toLowerCase().includes(q);
            var matchS = !status || (row.dataset.status || '') === status;
            row.style.display = matchQ && matchS ? '' : 'none';
        });
        updateCount();
    }

    if (searchInput)  searchInput.addEventListener('input',  applyFilters);
    if (statusFilter) statusFilter.addEventListener('change', applyFilters);

    /* ── Row highlight ───────────────────────── */
    tbody.addEventListener('click', function (e) {
        if (e.target.closest('.row-actions')) return;
        var row = e.target.closest('tr.payment-row');
        if (!row) return;
        tbody.querySelectorAll('tr.payment-row.selected').forEach(function (r) {
            r.classList.remove('selected');
        });
        row.classList.add('selected');
    });

    /* ── VIEW: open read-only modal ──────────── */
    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('.icon-btn.view');
        if (!btn || !viewModal) return;
        e.stopPropagation();

        var status = btn.dataset.status || 'pending';

        document.getElementById('view-booking-ref').textContent  = btn.dataset.bookingRef || '—';
        document.getElementById('view-route').textContent        = btn.dataset.route       || '—';
        document.getElementById('view-user-name').textContent    = btn.dataset.userName   || '—';
        document.getElementById('view-user-email').textContent   = btn.dataset.userEmail  || '—';
        document.getElementById('view-user-phone').textContent   = btn.dataset.userPhone  || 'N/A';
        document.getElementById('view-amount').textContent       = '₱ ' + parseFloat(btn.dataset.amount || 0).toFixed(2);
        document.getElementById('view-method').textContent       = _ucFirst(btn.dataset.method || '—');
        document.getElementById('view-payment-ref').textContent  = btn.dataset.ref        || '—';
        document.getElementById('view-notes').textContent        = btn.dataset.notes      || 'No notes';

        var statusEl = document.getElementById('view-status-badge');
        statusEl.textContent = _ucFirst(status);
        statusEl.className   = 'badge ' + status;

        document.getElementById('view-created').textContent  = _formatDate(btn.dataset.created);
        document.getElementById('view-paid-at').textContent  = btn.dataset.paidAt ? _formatDate(btn.dataset.paidAt) : '—';

        viewModal.show();
    });

    /* ── Helpers ─────────────────────────────── */
    function _modal(id) {
        var el = document.getElementById(id);
        return el ? bootstrap.Modal.getOrCreateInstance(el) : null;
    }

    function _ucFirst(str) {
        return String(str).charAt(0).toUpperCase() + String(str).slice(1);
    }

    function _formatDate(str) {
        if (!str) return '—';
        return new Date(str).toLocaleDateString('en-US', {
            year:   'numeric',
            month:  'short',
            day:    'numeric',
            hour:   '2-digit',
            minute: '2-digit'
        });
    }
};

document.addEventListener('DOMContentLoaded', window.initPaymentsPage);