/* user-schedule.js — Schedule page specific JavaScript */

(function () {
    var modal = document.getElementById('bookingModal');
    var modalOverlay = document.getElementById('modalOverlay');
    var modalClose = document.getElementById('modalClose');
    var cancelBtn = document.getElementById('cancelBtn');
    var bookingForm = document.getElementById('bookingForm');
    var seatsCount = document.getElementById('seatsCount');
    var totalPriceDisplay = document.getElementById('totalPriceDisplay');
    var currentPrice = 0;

    // Book Now button handlers
    var bookButtons = document.querySelectorAll('.u-book-btn');
    bookButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var scheduleId = this.getAttribute('data-schedule-id');
            var price = parseFloat(this.getAttribute('data-price'));

            // Set form values
            document.getElementById('scheduleId').value = scheduleId;
            currentPrice = price;
            updateTotalPrice();

            // Show modal
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });
    });

    // Close modal handlers
    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        bookingForm.reset();
        seatsCount.value = 1;
        updateTotalPrice();
    }

    modalClose && modalClose.addEventListener('click', closeModal);
    cancelBtn && cancelBtn.addEventListener('click', closeModal);
    modalOverlay && modalOverlay.addEventListener('click', closeModal);

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            closeModal();
        }
    });

    // Update total price when seats count changes
    seatsCount && seatsCount.addEventListener('input', function () {
        var seats = parseInt(this.value) || 0;
        if (seats < 1) this.value = 1;
        if (seats > 10) this.value = 10;
        updateTotalPrice();
    });

    function updateTotalPrice() {
        var seats = parseInt(seatsCount.value) || 1;
        var total = currentPrice * seats;
        totalPriceDisplay.textContent = '₱' + total.toFixed(2);
    }

    // Form submission
    bookingForm && bookingForm.addEventListener('submit', function (e) {
        var seats = parseInt(seatsCount.value) || 1;
        var contact = document.getElementById('contactNumber').value;

        // Basic validation
        if (!contact || contact.length < 10) {
            e.preventDefault();
            alert('Please enter a valid contact number');
            return;
        }

        if (seats < 1 || seats > 10) {
            e.preventDefault();
            alert('Number of seats must be between 1 and 10');
            return;
        }

        // Form will submit normally to the server
        console.log('Submitting booking for', seats, 'seats');
    });

    // Search form handling
    var searchForm = document.querySelector('.u-srow');
    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            var from = document.getElementById('from').value;
            var to = document.getElementById('to').value;
            var date = document.getElementById('date').value;

            if (!from || !to) {
                e.preventDefault();
                alert('Please select both origin and destination');
                return false;
            }

            if (from === to) {
                e.preventDefault();
                alert('Origin and destination cannot be the same');
                return false;
            }
        });
    }

    // Schedule card hover effects
    var scheduleCards = document.querySelectorAll('.u-schedule-card');
    scheduleCards.forEach(function (card) {
        card.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-2px)';
        });
        card.addEventListener('mouseleave', function () {
            this.style.transform = '';
        });
    });
})();