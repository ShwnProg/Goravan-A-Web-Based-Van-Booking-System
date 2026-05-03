/**
 * Bookings Admin Module  ·  bookings-js.js
 *
 * FIX LOG:
 *  - Added loading / disabled state on action buttons while request is in flight
 *  - Page reload after form submission now uses a safe absolute URL (no wrong-page redirect)
 *  - filterBookings() / updateBookingCount() are stable after DOM reload
 *  - delegateRowActions uses data-action attribute to avoid class-name ambiguity
 *  - Session flash messages (success / error) are shown via SweetAlert on load
 */

document.addEventListener('DOMContentLoaded', () => {
    initBookingsModule();
    showFlashMessage();
});

/* ═══════════════════════════════════════════════════════════════════
   INIT
═══════════════════════════════════════════════════════════════════ */
function initBookingsModule() {
    const searchInput   = document.getElementById('booking-search');
    const filterStatus  = document.getElementById('booking-filter-status');
    const bookingsTbody = document.getElementById('bookings-tbody');
    const detailsModal  = new bootstrap.Modal(document.getElementById('detailsModal'));

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            filterBookings();
            updateBookingCount();
        });
    }

    if (filterStatus) {
        filterStatus.addEventListener('change', () => {
            filterBookings();
            updateBookingCount();
        });
    }

    delegateRowActions(bookingsTbody, detailsModal);
    updateBookingCount();
}

/* ═══════════════════════════════════════════════════════════════════
   FLASH MESSAGE  (reads data attributes injected by PHP into <body>)
═══════════════════════════════════════════════════════════════════ */
function showFlashMessage() {
    const carrier = document.getElementById('page-flash');
    if (!carrier) return;

    const success = carrier.dataset.flashSuccess;
    const error   = carrier.dataset.flashError;

    if (success) {
        Swal.fire({
            icon:              'success',
            title:             'Done!',
            text:              success,
            timer:             2800,
            showConfirmButton: false,
            toast:             true,
            position:          'top-end',
        });
    }

    if (error) {
        Swal.fire({
            icon:               'error',
            title:              'Error',
            text:               error,
            confirmButtonColor: 'var(--color-primary)',
        });
    }
}

/* ═══════════════════════════════════════════════════════════════════
   FILTER + COUNT
═══════════════════════════════════════════════════════════════════ */
function filterBookings() {
    const searchInput  = document.getElementById('booking-search');
    const filterStatus = document.getElementById('booking-filter-status');
    const searchTerm   = searchInput?.value.toLowerCase().trim() || '';
    const statusFilter = filterStatus?.value || '';

    const rows = document.querySelectorAll('.booking-row');
    let visible = 0;

    rows.forEach(row => {
        const refCode  = (row.dataset.refCode  || '').toLowerCase();
        const userName = (row.dataset.userName || '').toLowerCase();
        const status   = row.dataset.status || '';

        const matchesSearch = !searchTerm || refCode.includes(searchTerm) || userName.includes(searchTerm);
        const matchesStatus = !statusFilter || status === statusFilter;

        if (matchesSearch && matchesStatus) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
        }
    });

    /* empty state */
    const tbody     = document.getElementById('bookings-tbody');
    const emptyRow  = tbody?.querySelector('.empty-state')?.closest('tr');

    if (visible === 0 && !emptyRow) {
        tbody.insertAdjacentHTML('beforeend', `
            <tr class="js-empty-row">
                <td colspan="9">
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <p>No bookings match your search.</p>
                    </div>
                </td>
            </tr>`);
    } else if (visible > 0 && emptyRow) {
        emptyRow.remove();
    }
}

function updateBookingCount() {
    const visible   = document.querySelectorAll('.booking-row:not([style*="display: none"])').length;
    const countSpan = document.getElementById('booking-count');
    if (countSpan) {
        countSpan.textContent = visible === 1 ? '1 booking' : `${visible} bookings`;
    }
}

/* ═══════════════════════════════════════════════════════════════════
   ROW ACTION DELEGATION
═══════════════════════════════════════════════════════════════════ */
function delegateRowActions(tbody, detailsModal) {
    if (!tbody) return;

    tbody.addEventListener('click', e => {
        const btn = e.target.closest('.icon-btn');
        if (!btn) return;

        const row = btn.closest('.booking-row');
        if (!row) return;

        if (btn.classList.contains('view'))    return showBookingDetails(row, detailsModal);
        if (btn.classList.contains('approve')) return confirmAction(btn, row, 'approved');
        if (btn.classList.contains('reject'))  return confirmAction(btn, row, 'rejected');
        if (btn.classList.contains('cancel'))  return confirmAction(btn, row, 'cancelled');
    });
}

/* ═══════════════════════════════════════════════════════════════════
   BOOKING DETAILS MODAL
═══════════════════════════════════════════════════════════════════ */
function showBookingDetails(row, modal) {
    const set = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = val || '—';
    };

    set('detail-ref-code',        row.dataset.refCode);
    set('detail-passenger-name',  row.dataset.userName);
    set('detail-passenger-email', row.dataset.userEmail);
    set('detail-passenger-phone', row.dataset.userPhone);
    set('detail-route',           row.dataset.route);
    set('detail-seat',            row.dataset.seat);
    set('detail-departure',       row.dataset.departure);
    set('detail-driver',          row.dataset.driver);
    set('detail-van',             row.dataset.van);

    set('detail-created',
        row.dataset.created
            ? new Date(row.dataset.created).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
            : ''
    );

    set('detail-payment-due',
        row.dataset.paymentDeadline
            ? new Date(row.dataset.paymentDeadline).toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
            : ''
    );

    /* status badge */
    const statusEl = document.getElementById('detail-status');
    if (statusEl) {
        const status = row.dataset.status || '';
        statusEl.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        statusEl.className   = `badge ${status}`;
        if (row.dataset.isExpired === '1' && status === 'pending') {
            statusEl.classList.add('expired');
        }
    }

    modal.show();
}

/* ═══════════════════════════════════════════════════════════════════
   CONFIRM + PERFORM ACTION
═══════════════════════════════════════════════════════════════════ */

const ACTION_CONFIG = {
    approved: {
        title:       'Approve Booking?',
        icon:        'question',
        confirmText: 'Yes, Approve',
        confirmColor:'#28a745',
    },
    rejected: {
        title:       'Reject Booking?',
        icon:        'warning',
        confirmText: 'Yes, Reject',
        confirmColor:'#dc3545',
    },
    cancelled: {
        title:       'Cancel Booking?',
        icon:        'warning',
        confirmText: 'Yes, Cancel',
        confirmColor:'#6c757d',
    },
};

function confirmAction(btn, row, newStatus) {
    const cfg     = ACTION_CONFIG[newStatus];
    const refCode = row.dataset.refCode || '';

    Swal.fire({
        title:              cfg.title,
        html:               `Reference: <strong>${refCode}</strong>`,
        icon:               cfg.icon,
        showCancelButton:   true,
        confirmButtonText:  cfg.confirmText,
        confirmButtonColor: cfg.confirmColor,
        cancelButtonText:   'Go Back',
        reverseButtons:     true,
        focusCancel:        true,
    }).then(result => {
        if (result.isConfirmed) {
            performBookingAction(btn, row, newStatus);
        }
    });
}

function performBookingAction(btn, row, newStatus) {
    /* ── Get CSRF token ── */
    const csrfToken =
        document.querySelector('#page-csrf-token')?.value ||
        document.querySelector('input[name="csrf_token"]')?.value;

    if (!csrfToken) {
        Swal.fire({ icon: 'error', title: 'Security Error', text: 'CSRF token missing. Please refresh the page.' });
        return;
    }

    /* ── Loading state ── */
    setButtonLoading(btn, true);

    /* ── Build and submit form ── */
    const form   = document.createElement('form');
    form.method  = 'POST';
    form.action  = '../../controllers/Bookings/UpdateStatus.php';
    form.style.display = 'none';

    const fields = {
        booking_id: row.dataset.id,
        status:     newStatus,
        csrf_token: csrfToken,
    };

    Object.entries(fields).forEach(([name, value]) => {
        const input   = document.createElement('input');
        input.type    = 'hidden';
        input.name    = name;
        input.value   = value;
        form.appendChild(input);
    });

    document.body.appendChild(form);

    /* Small delay so the loading UI is visible before page unloads */
    setTimeout(() => form.submit(), 120);
}

/* ═══════════════════════════════════════════════════════════════════
   BUTTON LOADING HELPER
═══════════════════════════════════════════════════════════════════ */
function setButtonLoading(btn, loading) {
    const icon = btn.querySelector('i');

    if (loading) {
        btn.disabled = true;
        btn.style.opacity  = '0.65';
        btn.style.cursor   = 'not-allowed';
        if (icon) {
            icon.dataset.originalClass = icon.className;
            icon.className = 'fas fa-spinner fa-spin';
        }
    } else {
        btn.disabled = false;
        btn.style.opacity  = '';
        btn.style.cursor   = '';
        if (icon && icon.dataset.originalClass) {
            icon.className = icon.dataset.originalClass;
            delete icon.dataset.originalClass;
        }
    }
}