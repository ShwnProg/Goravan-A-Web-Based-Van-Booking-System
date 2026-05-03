/**
 * Bookings Admin Module
 * Handles search, filter, and action operations for bookings
 */

document.addEventListener('DOMContentLoaded', () => {
    initBookingsModule();
});

function initBookingsModule() {
    const searchInput = document.getElementById('booking-search');
    const filterStatus = document.getElementById('booking-filter-status');
    const bookingsTbody = document.getElementById('bookings-tbody');
    const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));

    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            filterBookings();
            updateBookingCount();
        });
    }

    // Filter functionality
    if (filterStatus) {
        filterStatus.addEventListener('change', () => {
            filterBookings();
            updateBookingCount();
        });
    }

    // Row action handlers
    delegateRowActions(bookingsTbody, detailsModal);

    // Initial count
    updateBookingCount();
}

/**
 * Filter bookings based on search and status filter
 */
function filterBookings() {
    const searchInput = document.getElementById('booking-search');
    const filterStatus = document.getElementById('booking-filter-status');
    const searchTerm = searchInput?.value.toLowerCase() || '';
    const statusFilter = filterStatus?.value || '';

    const rows = document.querySelectorAll('.booking-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const refCode = row.dataset.refCode?.toLowerCase() || '';
        const userName = row.dataset.userName?.toLowerCase() || '';
        const status = row.dataset.status || '';

        // Match search term (ref code or passenger name)
        const matchesSearch =
            searchTerm === '' ||
            refCode.includes(searchTerm) ||
            userName.includes(searchTerm);

        // Match status filter
        const matchesStatus = statusFilter === '' || status === statusFilter;

        // Show/hide row
        if (matchesSearch && matchesStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Show empty state if no matches
    const tbody = document.getElementById('bookings-tbody');
    let emptyState = tbody?.querySelector('.empty-state')?.closest('tr');

    if (visibleCount === 0 && !emptyState) {
        tbody.innerHTML =
            `<tr>
            <td colspan="9">
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <p>No bookings match your search.</p>
                </div>
            </td>
        </tr>`;
    } else if (visibleCount > 0 && tbody?.querySelector('.empty-state')) {
        // Remove empty state if results found
        tbody.querySelector('.empty-state').closest('tr').remove();
    }
}

/**
 * Update visible booking count
 */
function updateBookingCount() {
    const visibleRows = document.querySelectorAll(
        '.booking-row:not([style*="display: none"])'
    ).length;
    const countSpan = document.getElementById('booking-count');
    if (countSpan) {
        countSpan.textContent =
            visibleRows === 1 ? '1 booking' : `${visibleRows} bookings`;
    }
}

/**
 * Delegate row action handlers
 */
function delegateRowActions(tbody, detailsModal) {
    if (!tbody) return;

    tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('[class*="icon-btn"]');
        if (!btn) return;

        const row = btn.closest('.booking-row');
        if (!row) return;

        if (btn.classList.contains('view')) {
            showBookingDetails(row, detailsModal);
        } else if (btn.classList.contains('approve')) {
            approveBooking(row);
        } else if (btn.classList.contains('reject')) {
            rejectBooking(row);
        } else if (btn.classList.contains('cancel')) {
            cancelBooking(row);
        }
    });
}

/**
 * Show booking details in modal
 */
function showBookingDetails(row, modal) {
    // Populate modal fields
    document.getElementById('detail-ref-code').textContent =
        row.dataset.refCode || '—';
    document.getElementById('detail-passenger-name').textContent =
        row.dataset.userName || '—';
    document.getElementById('detail-passenger-email').textContent =
        row.dataset.userEmail || '—';
    document.getElementById('detail-passenger-phone').textContent =
        row.dataset.userPhone || '—';
    document.getElementById('detail-route').textContent =
        row.dataset.route || '—';
    document.getElementById('detail-seat').textContent =
        row.dataset.seat || '—';
    document.getElementById('detail-departure').textContent =
        row.dataset.departure || '—';
    document.getElementById('detail-driver').textContent =
        row.dataset.driver || '—';
    document.getElementById('detail-van').textContent =
        row.dataset.van || '—';
    document.getElementById('detail-created').textContent =
        row.dataset.created ? new Date(row.dataset.created).toLocaleDateString() : '—';
    document.getElementById('detail-payment-due').textContent =
        row.dataset.paymentDeadline
            ? new Date(row.dataset.paymentDeadline).toLocaleString()
            : '—';

    // Status badge
    const statusBadge = document.getElementById('detail-status');
    statusBadge.textContent = row.dataset.status ? row.dataset.status.charAt(0).toUpperCase() + row.dataset.status.slice(1) : '—';
    statusBadge.className = `badge ${row.dataset.status || 'secondary'}`;

    if (row.dataset.isExpired === '1' && row.dataset.status === 'pending') {
        statusBadge.classList.add('expired');
    }

    // Show modal
    modal.show();
}

/**
 * Approve booking
 */
function approveBooking(row) {
    const bookingId = row.dataset.id;
    const refCode = row.dataset.refCode;

    Swal.fire({
        title: 'Approve Booking?',
        html: `Reference: <strong>${refCode}</strong>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Approve',
        confirmButtonColor: '#28a745',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            performBookingAction(bookingId, 'approved', 'Approve');
        }
    });
}

/**
 * Reject booking
 */
function rejectBooking(row) {
    const bookingId = row.dataset.id;
    const refCode = row.dataset.refCode;

    Swal.fire({
        title: 'Reject Booking?',
        html: `Reference: <strong>${refCode}</strong>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Reject',
        confirmButtonColor: '#dc3545',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            performBookingAction(bookingId, 'rejected', 'Reject');
        }
    });
}

/**
 * Cancel booking
 */
function cancelBooking(row) {
    const bookingId = row.dataset.id;
    const refCode = row.dataset.refCode;

    Swal.fire({
        title: 'Cancel Booking?',
        html: `Reference: <strong>${refCode}</strong>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Cancel Booking',
        confirmButtonColor: '#6c757d',
        cancelButtonText: 'Keep Booking'
    }).then((result) => {
        if (result.isConfirmed) {
            performBookingAction(bookingId, 'cancelled', 'Cancel');
        }
    });
}

/**
 * Perform booking status action (approve, reject, cancel)
 */
function performBookingAction(bookingId, newStatus, actionName) {
    const csrfToken = document.querySelector('#page-csrf-token, input[name="csrf_token"]')?.value;

    if (!csrfToken) {
        Swal.fire({
            title: 'Error',
            text: 'CSRF token not found',
            icon: 'error'
        });
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/GROAVAN/controllers/Bookings/UpdateStatus.php';

    const inputs = [
        { name: 'booking_id', value: bookingId },
        { name: 'status', value: newStatus },
        { name: 'csrf_token', value: csrfToken }
    ];

    inputs.forEach(({ name, value }) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}
