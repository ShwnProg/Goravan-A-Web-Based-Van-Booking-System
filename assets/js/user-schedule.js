/* user-schedule.js - Multi-step booking flow */

(function () {
    'use strict';

    if (!window.buildSearchableSelects) {
        installSearchableSelects();
    }

    var ROUTE_COORDS = window.ROUTE_COORDS || {
        "Maasin City": [10.1335, 124.8460],
        "Bontoc": [10.3559, 124.9693],
        "Sogod": [10.3856, 124.9806],
        "Malitbog": [10.1581, 125.0012],
        "Padre Burgos": [10.0296, 125.0170],
        "Limasawa": [9.9303, 125.0746],
        "Liloan": [10.1581, 125.1253],
        "Macrohon": [10.0766, 124.9401],
        "San Juan": [10.2641, 125.1735],
        "Silago": [10.5284, 125.1627],
        "Hinunangan": [10.3946, 125.1985],
        "Hinundayan": [10.3511, 125.2510],
        "St. Bernard": [10.2801, 125.1383],
        "San Ricardo": [9.9130, 125.2763],
        "Tomas Oppus": [10.2548, 124.9856],
        "San Francisco": [10.0575, 125.1576],
        "Libagon": [10.2968, 125.0505],
        "Anahawan": [10.2740, 125.2578],
        "Bato": [10.3279, 124.7919],
        "Pintuyan": [9.9446, 125.2492],
    };

    var DISCOUNTS = window.GV_DISCOUNTS || { student: 10, senior: 15, pwd: 20 };
    var VERIFIED_BONUS = parseFloat(window.GV_VERIFIED_BONUS || 0);
    var VERIFIED_TYPE = normalizePassengerType(window.GV_VERIFIED_TYPE || 'regular');
    var currentStep = 1;
    var map = null;
    var markers = [];
    var polyline = null;
    var modalEl = document.getElementById('bookingModal');
    var bookingModal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl, {
        backdrop: 'static',
        keyboard: false
    }) : null;

    var state = freshState();

    document.addEventListener('DOMContentLoaded', init);

    function init() {
        window.buildSearchableSelects(document);
        bindSearchValidation();
        bindBookButtons();
        bindStepButtons();
        bindPassengerInputs();
        bindPaymentInputs();

        if (modalEl) {
            modalEl.addEventListener('shown.bs.modal', function () {
                if (currentStep === 1) setTimeout(invalidateMap, 120);
            });
            modalEl.addEventListener('hidden.bs.modal', resetFlow);
        }
    }

    function freshState() {
        return {
            scheduleId: '',
            schedule: null,
            seats: [],
            selectedSeats: [],
            pricePerSeat: 0,
            passengerName: '',
            contactNumber: '',
            paymentMethod: '',
            paymentReference: '',
            verifiedSeatId: '',
            baseTotal: 0,
            discountAmount: 0,
            subtotal: 0,
            convenienceFee: 0,
            grandTotal: 0
        };
    }

    function bindSearchValidation() {
        var searchForm = document.querySelector('.u-srow');
        if (!searchForm) return;

        searchForm.addEventListener('submit', function (e) {
            var from = document.getElementById('from').value;
            var to = document.getElementById('to').value;

            if (from && to && from === to) {
                e.preventDefault();
                Swal.fire('Same location', 'Origin and destination cannot be the same.', 'info');
            }
        });
    }

    function bindBookButtons() {
        document.querySelectorAll('.u-book-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var scheduleId = btn.dataset.scheduleId || '';
                if (!scheduleId) return;
                openBooking(scheduleId);
            });
        });
    }

    function bindStepButtons() {
        document.querySelectorAll('[data-booking-next]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                goNext();
            });
        });

        document.querySelectorAll('[data-booking-back]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                showStep(Math.max(1, currentStep - 1));
            });
        });

        var confirmBtn = document.getElementById('confirmPayBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', submitBooking);
        }

        var anotherBtn = document.getElementById('bookAnotherBtn');
        if (anotherBtn) {
            anotherBtn.addEventListener('click', function () {
                resetFlow();
                showStep(1);
            });
        }

        var copyBtn = document.getElementById('copyRefBtn');
        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                var code = document.getElementById('receiptReference').textContent.trim();
                if (!code) return;
                navigator.clipboard && navigator.clipboard.writeText(code);
                copyBtn.classList.add('copied');
                setTimeout(function () { copyBtn.classList.remove('copied'); }, 900);
            });
        }
    }

    function bindPassengerInputs() {
        var name = document.getElementById('passengerName');
        var contact = document.getElementById('contactNumber');

        if (name) name.addEventListener('input', function () {
            state.passengerName = name.value.trim();
        });

        if (contact) contact.addEventListener('input', function () {
            contact.value = formatPhone(contact.value);
            state.contactNumber = contact.value.trim();
        });

        var passengerList = document.getElementById('passengerSeatList');
        if (passengerList) {
            passengerList.addEventListener('input', updatePassengerSeatFromInput);
            passengerList.addEventListener('change', updatePassengerSeatFromInput);
        }
    }

    function bindPaymentInputs() {
        document.querySelectorAll('.payment-card').forEach(function (card) {
            card.addEventListener('click', function () {
                selectPaymentMethod(card.dataset.method);
            });
        });

        ['paymentPhone', 'paymentPhonePaymaya', 'cardNumber', 'cardholderName', 'cardExpiry', 'cardCvv'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', function () {
                if (id === 'paymentPhone' || id === 'paymentPhonePaymaya') el.value = formatPhone(el.value);
                if (id === 'cardNumber') el.value = formatCardNumber(el.value);
                if (id === 'cardExpiry') el.value = formatExpiry(el.value);
                if (id === 'cardCvv') el.value = onlyDigits(el.value).slice(0, 4);
                updatePaymentReference();
            });
        });
    }

    function openBooking(scheduleId) {
        resetFlow();
        state.scheduleId = scheduleId;
        setLoading(true);

        fetch('../../controllers/users/GetSeats.php?schedule_id=' + encodeURIComponent(scheduleId), {
            headers: { 'Accept': 'application/json' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) {
                    throw new Error(data.message || 'Unable to load seats.');
                }

                state.schedule = data.schedule || {};
                state.seats = data.seats || [];
                state.pricePerSeat = parseFloat(state.schedule.fare || 0);
                hydrateRoutePreview();
                renderSeatLayout();
                updateFareSummary();
                showStep(1);
                bookingModal.show();
            })
            .catch(function (err) {
                Swal.fire('Could not open booking', err.message || 'Please try again.', 'error');
            })
            .finally(function () {
                setLoading(false);
            });
    }

    function goNext() {
        if (currentStep === 1) {
            showStep(2);
            return;
        }

        if (currentStep === 2) {
            if (!state.selectedSeats.length) {
                Swal.fire('Choose a seat', 'Please select at least one available seat.', 'info');
                return;
            }
            renderPassengerSeatList();
            showStep(3);
            return;
        }

        if (currentStep === 3) {
            if (!validatePassenger()) return;
            renderOrderSummary();
            showStep(4);
            return;
        }
    }

    function showStep(step) {
        currentStep = step;

        document.querySelectorAll('.booking-step').forEach(function (panel) {
            panel.classList.toggle('active', parseInt(panel.dataset.step, 10) === step);
        });

        document.querySelectorAll('.progress-step').forEach(function (item) {
            var itemStep = parseInt(item.dataset.step, 10);
            item.classList.toggle('active', itemStep === step);
            item.classList.toggle('completed', itemStep < step);
            var dot = item.querySelector('.progress-dot');
            if (dot) dot.innerHTML = itemStep < step ? '<i class="fa-solid fa-check"></i>' : itemStep;
        });

        var mobile = document.getElementById('mobileStepLabel');
        var activeLabel = document.querySelector('.progress-step[data-step="' + step + '"] .progress-label');
        if (mobile && activeLabel) {
            mobile.textContent = 'Step ' + step + ' of 5 - ' + activeLabel.textContent.trim();
        }

        if (step === 1) setTimeout(invalidateMap, 120);
        if (step === 4) renderOrderSummary();
    }

    function hydrateRoutePreview() {
        var schedule = state.schedule || {};
        setText('routeName', (schedule.origin || '') + ' → ' + (schedule.destination || ''));
        setText('routeDateTime', formatDateTime(schedule.departure_date, schedule.departure_time));
        setText('routeVan', [schedule.van_model, schedule.van_plate].filter(Boolean).join(' · '));
        setText('routeFare', peso(state.pricePerSeat));
        renderStopList(schedule);
        drawRouteMap(schedule);
    }

    function renderStopList(schedule) {
        var wrap = document.getElementById('bookingStopList');
        if (!wrap) return;

        var stops = Array.isArray(schedule.stops) ? schedule.stops : [];
        var points = [{ type: 'origin', label: 'Origin', name: schedule.origin }]
            .concat(stops.map(function (stop) {
                return { type: 'via', label: 'Via', name: stop };
            }))
            .concat([{ type: 'dest', label: 'Destination', name: schedule.destination }]);

        wrap.innerHTML = points.map(function (point, index) {
            var connector = index < points.length - 1
                ? '<div class="booking-stop-connector"><div class="stop-connector"></div></div>'
                : '';
            return '<div class="map-stop">' +
                '<div class="stop-dot ' + point.type + '"></div>' +
                '<div><div class="stop-label">' + esc(point.label) + '</div><span>' + esc(point.name || '-') + '</span></div>' +
                '</div>' + connector;
        }).join('');
    }

    function drawRouteMap(schedule) {
        if (!window.L) return;
        var el = document.getElementById('bookingRouteMap');
        if (!el) return;

        if (!map) {
            map = L.map(el, { attributionControl: false, zoomControl: true });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        }

        markers.forEach(function (marker) { map.removeLayer(marker); });
        markers = [];
        if (polyline) map.removeLayer(polyline);

        var stops = Array.isArray(schedule.stops) ? schedule.stops : [];
        var names = [schedule.origin].concat(stops).concat([schedule.destination]);
        var coords = names.map(function (name) { return ROUTE_COORDS[name]; }).filter(Boolean);

        if (!coords.length) {
            map.setView([10.2, 124.95], 10);
            return;
        }

        names.forEach(function (name, index) {
            var coord = ROUTE_COORDS[name];
            if (!coord) return;
            var color = index === 0 ? 'var(--u-success)' : index === names.length - 1 ? 'var(--u-danger)' : 'var(--u-info)';
            var fallback = index === 0 ? '#16a34a' : index === names.length - 1 ? '#ef4444' : '#3b82f6';
            var icon = L.divIcon({
                className: '',
                html: '<div class="booking-map-marker" style="background:' + fallback + '"></div>',
                iconAnchor: [7, 7]
            });
            markers.push(L.marker(coord, { icon: icon }).addTo(map).bindPopup(esc(name)));
        });

        polyline = L.polyline(coords, {
            color: '#F97316',
            weight: 4,
            opacity: 0.9,
            lineJoin: 'round'
        }).addTo(map);

        if (coords.length > 1) {
            map.fitBounds(polyline.getBounds(), { padding: [48, 48], maxZoom: 10 });
        } else {
            map.setView(coords[0], 10);
        }
    }

    function invalidateMap() {
        if (map) map.invalidateSize();
    }

    function renderSeatLayout() {
        var grid = document.getElementById('bookingSeatGrid');
        if (!grid) return;
        grid.innerHTML = '';

        var ordered = [{ driver: true }].concat(state.seats.slice().sort(function (a, b) {
            var rA = parseInt(a.seat_row, 10) || 0;
            var rB = parseInt(b.seat_row, 10) || 0;
            var cA = parseInt(a.seat_col, 10) || 0;
            var cB = parseInt(b.seat_col, 10) || 0;
            return rA !== rB ? rA - rB : cA - cB;
        }));

        var totalCells = Math.ceil(ordered.length / 3) * 3;
        for (var i = 0; i < totalCells; i++) {
            var seat = ordered[i] || null;
            var el = document.createElement('button');
            el.type = 'button';

            if (!seat) {
                el.className = 'vsv-seat vsv-empty-slot';
                el.tabIndex = -1;
            } else if (seat.driver) {
                el.className = 'vsv-seat driver';
                el.innerHTML = '<i class="fas fa-steering-wheel"></i><span>DRIVER</span>';
                el.disabled = true;
            } else {
                var booked = Boolean(seat.is_booked);
                el.className = 'vsv-seat ' + (booked ? 'occupied' : 'available selectable');
                el.dataset.seatId = seat.seat_id;
                el.dataset.seatNumber = seat.seat_number;
                el.setAttribute('aria-pressed', 'false');
                el.disabled = booked;
                el.innerHTML = '<i class="fas fa-chair"></i><span>' + esc(seat.seat_number) + '</span>';

                if (!booked) {
                    el.addEventListener('click', function () {
                        toggleSeat(this);
                    });
                }
            }

            grid.appendChild(el);
        }

        updateSeatCounter();
    }

    function toggleSeat(btn) {
        var seatId = btn.dataset.seatId;
        var seatNumber = btn.dataset.seatNumber;
        var idx = state.selectedSeats.findIndex(function (seat) {
            return String(seat.seat_id) === String(seatId);
        });

        if (idx >= 0) {
            state.selectedSeats.splice(idx, 1);
            btn.classList.remove('selected');
            btn.setAttribute('aria-pressed', 'false');
        } else {
            state.selectedSeats.push({
                seat_id: seatId,
                seat_number: seatNumber,
                name: defaultPassengerName(),
                type: 'regular'
            });
            btn.classList.add('selected');
            btn.setAttribute('aria-pressed', 'true');
        }

        assignVerifiedDiscountSeat();
        updateSeatCounter();
        renderPassengerSeatList();
        updateFareSummary();
    }

    function updateSeatCounter() {
        var selected = state.selectedSeats.length;
        var available = state.seats.filter(function (seat) { return !seat.is_booked; }).length;
        setText('seatCounter', selected + ' seat(s) selected');
        setText('seatAvailability', available + ' available');
        setText('seatFareLive', selected + ' seats x ' + peso(state.pricePerSeat) + ' = ' + peso(selected * state.pricePerSeat));

        var selectedList = document.getElementById('seatSelectedList');
        if (selectedList) {
            selectedList.innerHTML = state.selectedSeats.length
                ? state.selectedSeats.map(function (seat) {
                    return '<span class="seat-selected-chip">' + esc(seat.seat_number) + '</span>';
                }).join('')
                : 'No seats selected';
        }
    }

    function renderPassengerSeatList() {
        var wrap = document.getElementById('passengerSeatList');
        if (!wrap) return;

        if (!state.selectedSeats.length) {
            wrap.innerHTML = '<div class="passenger-seat-empty">Select seats first.</div>';
            return;
        }

        assignVerifiedDiscountSeat();

        wrap.innerHTML = state.selectedSeats.map(function (seat) {
            var type = seat.type || 'regular';
            var isVerifiedSeat = state.verifiedSeatId && String(state.verifiedSeatId) === String(seat.seat_id);
            return '<div class="passenger-seat-row" data-seat-id="' + esc(seat.seat_id) + '">' +
                '<div class="passenger-seat-label"><span>Seat ' + esc(seat.seat_number) + '</span><strong>' + esc(labelPassengerType(type)) + '</strong></div>' +
                '<select class="passenger-seat-type" data-field="type"' + (isVerifiedSeat ? ' disabled data-verified-seat="1"' : '') + '>' +
                passengerTypeOption('regular', type) +
                passengerTypeOption('student', type) +
                passengerTypeOption('senior', type) +
                passengerTypeOption('pwd', type) +
                '</select>' +
                '<span class="passenger-seat-discount' + (isVerifiedSeat ? ' verified' : '') + '">' +
                discountForType(type) + '% off' + (isVerifiedSeat ? ' · verified' : '') +
                '</span>' +
                '</div>';
        }).join('');
    }

    function passengerTypeOption(value, current) {
        return '<option value="' + value + '"' + (value === current ? ' selected' : '') + '>' + labelPassengerType(value) + '</option>';
    }

    function updatePassengerSeatFromInput(e) {
        var row = e.target.closest('.passenger-seat-row');
        if (!row) return;
        var seat = state.selectedSeats.find(function (item) {
            return String(item.seat_id) === String(row.dataset.seatId);
        });
        if (!seat) return;

        if (e.target.dataset.field === 'name') {
            seat.name = e.target.value.trim();
        }
        if (e.target.dataset.field === 'type') {
            if (e.target.dataset.verifiedSeat === '1') {
                e.target.value = VERIFIED_TYPE;
                return;
            }
            seat.type = e.target.value || 'regular';
            renderPassengerSeatList();
        }

        updateFareSummary();
    }

    function defaultPassengerName() {
        var input = document.getElementById('passengerName');
        return ((input && input.value) || state.passengerName || '').trim();
    }

    function summarizePassengerTypes() {
        if (!state.selectedSeats.length) return 'Regular';
        var counts = {};
        state.selectedSeats.forEach(function (seat) {
            counts[seat.type || 'regular'] = (counts[seat.type || 'regular'] || 0) + 1;
        });
        return Object.keys(counts).map(function (type) {
            return counts[type] + ' ' + labelPassengerType(type);
        }).join(', ');
    }

    function validatePassenger() {
        var name = document.getElementById('passengerName');
        var contact = document.getElementById('contactNumber');

        state.passengerName = (name ? name.value : '').trim();
        state.contactNumber = (contact ? contact.value : '').trim();

        if (state.passengerName.length < 2) {
            Swal.fire('Passenger name required', 'Please enter the passenger name.', 'info');
            return false;
        }

        if (!validPhone(state.contactNumber)) {
            Swal.fire('Invalid number', 'Use a Philippine mobile number like 0912 345 6789.', 'info');
            return false;
        }

        updateFareSummary();
        return true;
    }

    function updateFareSummary() {
        assignVerifiedDiscountSeat();
        var count = state.selectedSeats.length;
        var base = count * state.pricePerSeat;
        var discount = state.selectedSeats.reduce(function (sum, seat) {
            return sum + (state.pricePerSeat * (discountForType(seat.type) / 100));
        }, 0);
        state.baseTotal = base;
        state.discountAmount = discount;
        state.subtotal = Math.max(0, base - discount);
        state.convenienceFee = 0;
        state.grandTotal = state.subtotal + state.convenienceFee;

        setText('baseFareBreakdown', peso(base));
        setText('discountBreakdown', '-' + peso(discount));
        setText('totalBreakdown', peso(state.subtotal));
        setText('grandTotalDisplay', peso(state.grandTotal));
        renderOrderSummary();
    }

    function selectPaymentMethod(method) {
        state.paymentMethod = method;
        document.querySelectorAll('.payment-card').forEach(function (card) {
            card.classList.toggle('selected', card.dataset.method === method);
        });
        document.querySelectorAll('.payment-panel').forEach(function (panel) {
            panel.hidden = panel.dataset.panel !== method;
        });
        updatePaymentReference();
        updateFareSummary();
    }

    function updatePaymentReference() {
        if (state.paymentMethod === 'gcash' || state.paymentMethod === 'paymaya') {
            var fieldId = state.paymentMethod === 'paymaya' ? 'paymentPhonePaymaya' : 'paymentPhone';
            state.paymentReference = (document.getElementById(fieldId) || {}).value || '';
        } else if (state.paymentMethod === 'card') {
            var num = onlyDigits((document.getElementById('cardNumber') || {}).value || '');
            state.paymentReference = num.length >= 4 ? 'card-' + num.slice(-4) : '';
        }
    }

    function validatePayment() {
        updatePaymentReference();
        if (!state.paymentMethod) {
            Swal.fire('Payment method required', 'Please choose how you want to pay.', 'info');
            return false;
        }

        if ((state.paymentMethod === 'gcash' || state.paymentMethod === 'paymaya') && !validPhone(state.paymentReference)) {
            Swal.fire('Invalid wallet number', 'Use a Philippine mobile number like 0912 345 6789.', 'info');
            return false;
        }

        if (state.paymentMethod === 'card') {
            var number = onlyDigits((document.getElementById('cardNumber') || {}).value || '');
            var name = ((document.getElementById('cardholderName') || {}).value || '').trim();
            var expiry = ((document.getElementById('cardExpiry') || {}).value || '').trim();
            var cvv = onlyDigits((document.getElementById('cardCvv') || {}).value || '');
            if (number.length !== 16 || name.length < 2 || !validExpiry(expiry) || cvv.length < 3) {
                Swal.fire('Check card details', 'Please enter a valid card number, name, expiry, and CVV.', 'info');
                return false;
            }
        }

        return true;
    }

    function renderOrderSummary() {
        var schedule = state.schedule || {};
        setText('summaryRoute', (schedule.origin || '-') + ' → ' + (schedule.destination || '-'));
        setText('summaryDate', formatDateTime(schedule.departure_date, schedule.departure_time));
        setText('summarySeats', state.selectedSeats.map(function (seat) { return seat.seat_number; }).join(', ') || '-');
        setText('summaryPassengerType', summarizePassengerTypes());
        setText('summaryDiscount', '-' + peso(state.discountAmount));
        setText('summarySubtotal', peso(state.subtotal));
        setText('summaryFee', peso(state.convenienceFee));
        setText('summaryGrandTotal', peso(state.grandTotal));
    }

    function submitBooking() {
        if (!validatePassenger() || !validatePayment()) return;
        assignVerifiedDiscountSeat();

        var btn = document.getElementById('confirmPayBtn');
        if (btn) btn.disabled = true;

        var fd = new FormData();
        fd.append('schedule_id', state.scheduleId);
        state.selectedSeats.forEach(function (seat) { fd.append('seat_ids[]', seat.seat_id); });
        fd.append('passenger_name', state.passengerName);
        fd.append('contact_number', state.contactNumber);
        fd.append('passenger_type', state.selectedSeats[0] ? state.selectedSeats[0].type : 'regular');
        state.selectedSeats.forEach(function (seat) {
            fd.append('passenger_names[]', state.passengerName);
            fd.append('passenger_types[]', seat.type || 'regular');
        });
        fd.append('payment_method', state.paymentMethod);
        fd.append('payment_reference', state.paymentReference);
        fd.append('seats_count', state.selectedSeats.length);
        fd.append('total_amount', state.grandTotal.toFixed(2));
        fd.append('csrf_token', getCsrf());

        fetch('../../controllers/users/BookingController.php', {
            method: 'POST',
            body: fd,
            headers: { 'Accept': 'application/json' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) {
                    throw new Error(data.message || 'Booking failed.');
                }
                renderReceipt(data);
                showStep(5);
            })
            .catch(function (err) {
                Swal.fire('Booking failed', err.message || 'Please try again.', 'error');
            })
            .finally(function () {
                if (btn) btn.disabled = false;
            });
    }

    function renderReceipt(data) {
        var schedule = state.schedule || {};
        setText('receiptReference', data.reference_code || '');
        setText('receiptRoute', (schedule.origin || '-') + ' → ' + (schedule.destination || '-'));
        setText('receiptDate', formatDateTime(schedule.departure_date, schedule.departure_time));
        setText('receiptSeats', state.selectedSeats.map(function (seat) { return seat.seat_number; }).join(', '));
        setText('receiptPassenger', state.passengerName + ' · ' + summarizePassengerTypes());
        setText('receiptPaymentMethod', labelPaymentMethod(state.paymentMethod));
        setText('receiptAmount', peso(parseFloat(data.total_amount || state.grandTotal)));
    }

    function resetFlow() {
        state = freshState();
        currentStep = 1;
        document.querySelectorAll('#bookingModal input:not([name="csrf_token"]), #bookingModal select').forEach(function (el) {
            if (el.dataset.keepValue === '1') return;
            if (el.dataset.default !== undefined) {
                el.value = el.dataset.default;
                return;
            }
            if (el.tagName === 'SELECT') return;
            el.value = '';
        });

        document.querySelectorAll('.payment-card.selected, .vsv-seat.selected').forEach(function (el) {
            el.classList.remove('selected');
        });
        document.querySelectorAll('.payment-panel').forEach(function (panel) {
            panel.hidden = true;
        });

        renderPassengerSeatList();
        showStep(1);
    }

    function setLoading(isLoading) {
        document.querySelectorAll('.u-book-btn').forEach(function (btn) {
            btn.disabled = isLoading;
        });
    }

    function getCsrf() {
        var el = document.querySelector('#bookingForm input[name="csrf_token"], input[name="csrf_token"]');
        return el ? el.value : '';
    }

    function discountForType(type) {
        var base = 0;
        if (type === 'student') base = parseFloat(DISCOUNTS.student || 0);
        if (type === 'senior') base = parseFloat(DISCOUNTS.senior || 0);
        if (type === 'pwd') base = parseFloat(DISCOUNTS.pwd || 0);
        if (base > 0) return base + VERIFIED_BONUS;
        return 0;
    }

    function assignVerifiedDiscountSeat() {
        if (!hasVerifiedType()) {
            state.verifiedSeatId = '';
            return;
        }

        if (!state.selectedSeats.length) {
            state.verifiedSeatId = '';
            return;
        }

        var verifiedSeat = state.selectedSeats.find(function (seat) {
            return String(seat.seat_id) === String(state.verifiedSeatId);
        });

        if (!verifiedSeat) {
            verifiedSeat = state.selectedSeats[0];
            state.verifiedSeatId = verifiedSeat.seat_id;
        }

        verifiedSeat.type = VERIFIED_TYPE;
    }

    function hasVerifiedType() {
        return ['student', 'senior', 'pwd'].indexOf(VERIFIED_TYPE) !== -1;
    }

    function normalizePassengerType(type) {
        type = String(type || '').toLowerCase();
        return ['student', 'senior', 'pwd', 'regular'].indexOf(type) !== -1 ? type : 'regular';
    }

    function labelPassengerType(type) {
        return {
            regular: 'Regular',
            student: 'Student',
            senior: 'Senior Citizen',
            pwd: 'PWD'
        }[type] || 'Regular';
    }

    function labelPaymentMethod(method) {
        return {
            gcash: 'GCash',
            paymaya: 'PayMaya',
            card: 'Card'
        }[method] || '-';
    }

    function formatDateTime(date, time) {
        if (!date) return '-';
        var dt = new Date(date + 'T' + (time || '00:00:00'));
        if (Number.isNaN(dt.getTime())) return date + (time ? ' ' + time : '');
        return dt.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) +
            ' · ' + dt.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' });
    }

    function peso(value) {
        return '₱' + (parseFloat(value || 0)).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function onlyDigits(value) {
        return String(value || '').replace(/\D/g, '');
    }

    function formatPhone(value) {
        var digits = onlyDigits(value).slice(0, 11);
        if (digits.length <= 4) return digits;
        if (digits.length <= 7) return digits.slice(0, 4) + ' ' + digits.slice(4);
        return digits.slice(0, 4) + ' ' + digits.slice(4, 7) + ' ' + digits.slice(7);
    }

    function validPhone(value) {
        return /^09\d{9}$/.test(onlyDigits(value));
    }

    function formatCardNumber(value) {
        return onlyDigits(value).slice(0, 16).replace(/(.{4})/g, '$1 ').trim();
    }

    function formatExpiry(value) {
        var digits = onlyDigits(value).slice(0, 4);
        if (digits.length <= 2) return digits;
        return digits.slice(0, 2) + '/' + digits.slice(2);
    }

    function validExpiry(value) {
        if (!/^\d{2}\/\d{2}$/.test(value)) return false;
        var parts = value.split('/');
        var month = parseInt(parts[0], 10);
        var year = 2000 + parseInt(parts[1], 10);
        if (month < 1 || month > 12) return false;
        var expiry = new Date(year, month, 0, 23, 59, 59);
        return expiry >= new Date();
    }

    function installSearchableSelects() {
        if (window._userScheduleSSReady) return;
        window._userScheduleSSReady = true;

        function closeAll() {
            document.querySelectorAll('.ss-panel.is-open').forEach(function (p) { p.classList.remove('is-open'); });
            document.querySelectorAll('.ss-btn.is-open').forEach(function (b) { b.classList.remove('is-open'); });
        }

        function buildSS(select) {
            if (select._ssBuilt) return;
            select._ssBuilt = true;
            var ph = select.dataset.placeholder || 'Select';
            var wrap = document.createElement('div');
            wrap.className = 'ss-wrap';
            select.parentNode.insertBefore(wrap, select);
            wrap.appendChild(select);

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ss-btn';
            var txt = document.createElement('span');
            txt.className = 'ss-btn-txt';
            var current = select.options[select.selectedIndex];
            txt.textContent = current && current.value ? current.text : ph;
            if (!current || !current.value) btn.classList.add('is-placeholder');
            btn.appendChild(txt);
            var icon = document.createElement('i');
            icon.className = 'fas fa-chevron-down ss-btn-arr';
            btn.appendChild(icon);
            wrap.insertBefore(btn, select);

            var panel = document.createElement('div');
            panel.className = 'ss-panel';
            var search = document.createElement('input');
            search.type = 'text';
            search.className = 'ss-search';
            search.placeholder = 'Type to search...';
            panel.appendChild(search);
            var list = document.createElement('ul');
            list.className = 'ss-list';
            Array.from(select.options).forEach(function (opt) {
                var li = document.createElement('li');
                li.className = 'ss-item' + (!opt.value ? ' is-placeholder' : '') + (opt.selected && opt.value ? ' is-sel' : '');
                li.dataset.val = opt.value;
                li.dataset.text = opt.text;
                li.textContent = opt.text;
                list.appendChild(li);
            });
            panel.appendChild(list);
            wrap.insertBefore(panel, select);

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = panel.classList.contains('is-open');
                closeAll();
                if (!open) {
                    panel.classList.add('is-open');
                    btn.classList.add('is-open');
                    search.value = '';
                    filter('');
                    setTimeout(function () { search.focus(); }, 20);
                }
            });

            search.addEventListener('input', function () {
                filter(search.value.toLowerCase());
            });
            search.addEventListener('click', function (e) { e.stopPropagation(); });
            list.addEventListener('click', function (e) {
                var li = e.target.closest('.ss-item');
                if (!li) return;
                select.value = li.dataset.val;
                txt.textContent = li.dataset.val ? li.dataset.text : ph;
                btn.classList.toggle('is-placeholder', !li.dataset.val);
                list.querySelectorAll('.ss-item.is-sel').forEach(function (item) { item.classList.remove('is-sel'); });
                if (li.dataset.val) li.classList.add('is-sel');
                select.dispatchEvent(new Event('change', { bubbles: true }));
                closeAll();
            });

            function filter(q) {
                list.querySelectorAll('.ss-item').forEach(function (li) {
                    li.style.display = !q || li.dataset.text.toLowerCase().includes(q) ? '' : 'none';
                });
            }
        }

        document.addEventListener('click', closeAll);
        window.buildSearchableSelects = function (root) {
            (root || document).querySelectorAll('select.ss').forEach(buildSS);
        };
    }
})();
