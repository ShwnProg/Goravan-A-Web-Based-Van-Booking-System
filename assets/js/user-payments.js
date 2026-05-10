/* user-payments.js - Payment list filtering and receipt modal */

(function () {
    'use strict';

    var payments = [];
    var filtered = [];
    var modalEl = document.getElementById('paymentDetailModal');
    var detailModal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

    document.addEventListener('DOMContentLoaded', function () {
        bindFilters();
        bindPrint();
        loadPayments();
    });

    function bindFilters() {
        ['paymentSearch', 'paymentStatusFilter', 'paymentDateFrom', 'paymentDateTo'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener(id === 'paymentSearch' ? 'input' : 'change', applyFilters);
        });

        var list = document.getElementById('paymentList');
        if (list) {
            list.addEventListener('click', function (e) {
                var card = e.target.closest('.payment-card-item');
                if (!card) return;
                var payment = payments.find(function (item) {
                    return String(item.payment_id) === String(card.dataset.paymentId);
                });
                if (payment) openDetail(payment);
            });
        }
    }

    function bindPrint() {
        var btn = document.getElementById('downloadReceiptBtn');
        if (btn) {
            btn.addEventListener('click', function () {
                window.print();
            });
        }
    }

    function loadPayments() {
        var userId = (document.getElementById('paymentsUserId') || {}).value || '';
        fetch('../../controllers/users/PaymentController.php?action=list&user_id=' + encodeURIComponent(userId), {
            headers: { 'Accept': 'application/json' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) throw new Error(data.message || 'Unable to load payments.');
                payments = data.data || [];
                applyFilters();
            })
            .catch(function (err) {
                renderEmpty(err.message || 'Unable to load payments.');
            });
    }

    function applyFilters() {
        var q = ((document.getElementById('paymentSearch') || {}).value || '').toLowerCase().trim();
        var status = ((document.getElementById('paymentStatusFilter') || {}).value || '').toLowerCase();
        var from = ((document.getElementById('paymentDateFrom') || {}).value || '');
        var to = ((document.getElementById('paymentDateTo') || {}).value || '');

        filtered = payments.filter(function (p) {
            var haystack = [
                p.reference_code,
                p.route_display,
                p.payment_method,
                p.payment_reference,
                p.passenger_name
            ].join(' ').toLowerCase();

            var paidDate = (p.paid_at || p.created_at || '').slice(0, 10);
            var matchQ = !q || haystack.includes(q);
            var matchStatus = !status || String(p.status).toLowerCase() === status;
            var matchFrom = !from || paidDate >= from;
            var matchTo = !to || paidDate <= to;

            return matchQ && matchStatus && matchFrom && matchTo;
        });

        renderStats();
        renderList();
    }

    function renderStats() {
        var paid = payments.filter(function (p) {
            return p.status === 'paid';
        });
        var totalSpent = paid.reduce(function (sum, p) {
            return sum + parseFloat(p.amount || 0);
        }, 0);
        var sorted = paid.slice().sort(function (a, b) {
            return new Date(b.paid_at || b.created_at || 0) - new Date(a.paid_at || a.created_at || 0);
        });

        setText('totalSpent', peso(totalSpent));
        setText('totalTrips', paid.length);
        setText('lastPayment', sorted[0] ? formatDate(sorted[0].paid_at || sorted[0].created_at) : '-');
    }

    function renderList() {
        var list = document.getElementById('paymentList');
        if (!list) return;

        if (!filtered.length) {
            renderEmpty('No payments match your filters.');
            return;
        }

        list.innerHTML = filtered.map(function (p) {
            var method = methodMeta(p.payment_method);
            return '<article class="payment-card-item" data-payment-id="' + esc(p.payment_id) + '">' +
                '<div class="payment-main">' +
                    '<div class="payment-ref">' + esc(p.reference_code) + '</div>' +
                    '<div class="payment-route">' + esc(p.route_display) + '</div>' +
                    '<div class="payment-meta">' + esc(formatDateTime(p.departure_date, p.departure_time)) + ' · ' + esc(p.seats_count || 0) + ' seat(s)</div>' +
                '</div>' +
                '<div>' +
                    '<div class="payment-method">' +
                        '<i class="' + method.icon + '"></i><span>' + esc(method.label) + '</span>' +
                    '</div>' +
                    '<div style="margin-top:8px"><span class="pay-badge passenger">' + esc(passengerTypeSummary(p)) + '</span></div>' +
                '</div>' +
                '<div class="payment-side">' +
                    '<div class="payment-amount">' + esc(peso(p.amount)) + '</div>' +
                    '<span class="pay-badge ' + esc(p.status) + '">' + esc(p.status) + '</span>' +
                '</div>' +
                '<div class="payment-chevron"><i class="fa-solid fa-chevron-right"></i></div>' +
            '</article>';
        }).join('');
    }

    function renderEmpty(message) {
        var list = document.getElementById('paymentList');
        if (!list) return;
        list.innerHTML = '<div class="payment-empty">' +
            '<i class="fa-regular fa-credit-card"></i>' +
            '<p>' + esc(message) + '</p>' +
        '</div>';
    }

    function openDetail(payment) {
        var method = methodMeta(payment.payment_method);
        setText('detailReference', payment.reference_code || '-');
        setText('detailRoute', payment.route_display || '-');
        setText('detailDate', formatDateTime(payment.departure_date, payment.departure_time));
        setHTML('detailSeats', seatSummary(payment.seat_numbers));
        setHTML('detailPassenger', passengerDetails(payment));
        setText('detailPassengerType', passengerTypeSummary(payment));
        setText('detailMethod', method.label);
        setText('detailPaymentRef', payment.payment_reference || '-');
        setText('detailStatus', capitalize(payment.status || '-'));
        setText('detailAmount', peso(payment.amount));

        if (detailModal) detailModal.show();
    }

    function methodMeta(method) {
        return {
            gcash: { icon: 'fa-regular fa-credit-card', label: 'GCash' },
            paymaya: { icon: 'fa-regular fa-credit-card', label: 'PayMaya' },
            card: { icon: 'fa-regular fa-credit-card', label: 'Card' },
            cash: { icon: 'fa-solid fa-money-bill-1', label: 'Cash' }
        }[method] || { icon: 'fa-regular fa-credit-card', label: capitalize(method || 'Payment') };
    }

    function labelPassengerType(type) {
        return {
            regular: 'Regular',
            student: 'Student',
            senior: 'Senior Citizen',
            pwd: 'PWD'
        }[type] || 'Regular';
    }

    function passengerTypeSummary(payment) {
        var passengers = Array.isArray(payment.passengers) ? payment.passengers : [];
        if (!passengers.length) return labelPassengerType(payment.passenger_type);
        var counts = {};
        passengers.forEach(function (p) {
            var type = p.type || 'regular';
            counts[type] = (counts[type] || 0) + 1;
        });
        return Object.keys(counts).map(function (type) {
            return counts[type] + ' ' + labelPassengerType(type);
        }).join(', ');
    }

    function passengerNameSummary(payment) {
        var passengers = Array.isArray(payment.passengers) ? payment.passengers : [];
        return passengers.map(function (p) {
            return (p.seat_number || '-') + ': ' + (p.name || '-') + ' (' + labelPassengerType(p.type) + ')';
        }).join(', ');
    }

    function passengerDetails(payment) {
        var passengers = Array.isArray(payment.passengers) ? payment.passengers : [];
        if (!passengers.length) {
            return '<span class="receipt-value-text">' + esc(payment.passenger_name || '-') + '</span>';
        }

        var names = [];
        passengers.forEach(function (p) {
            var name = String(p.name || '').trim();
            if (name && names.indexOf(name) === -1) names.push(name);
        });

        return '<span class="receipt-value-text">' + esc(names.join(', ') || payment.passenger_name || '-') + '</span>';
    }

    function seatSummary(value) {
        var seats = String(value || '').split(',').map(function (seat) {
            return seat.trim();
        }).filter(Boolean);

        if (!seats.length) return '<span class="receipt-value-text">-</span>';

        return '<span class="receipt-seat-list">' + seats.map(function (seat) {
            return '<span class="receipt-seat">' + esc(seat) + '</span>';
        }).join('') + '</span>';
    }

    function formatDateTime(date, time) {
        if (!date) return '-';
        var dt = new Date(date + 'T' + (time || '00:00:00'));
        if (Number.isNaN(dt.getTime())) return date + (time ? ' ' + time : '');
        return dt.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) +
            ' · ' + dt.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' });
    }

    function formatDate(value) {
        if (!value) return '-';
        var dt = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(dt.getTime())) return value;
        return dt.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function peso(value) {
        return '₱' + (parseFloat(value || 0)).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function capitalize(value) {
        value = String(value || '');
        return value.charAt(0).toUpperCase() + value.slice(1);
    }

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function setHTML(id, value) {
        var el = document.getElementById(id);
        if (el) el.innerHTML = value;
    }

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
})();
