/* user-home.js — Home page specific JavaScript */

(function () {
    // Initialize searchable selects
    if (window.buildSearchableSelects) {
        window.buildSearchableSelects(document);
    }

    // Search form validation
    var searchForm = document.querySelector('.u-toolbar-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            var from = document.getElementById('from').value;
            var to = document.getElementById('to').value;
            var date = document.getElementById('date').value;

            if (!from || !to || !date) {
                e.preventDefault();
                alert('Please fill in all search fields');
                return false;
            }

            if (from === to) {
                e.preventDefault();
                alert('Origin and destination cannot be the same');
                return false;
            }
        });
    }

    // Quick action cards
    var qaCards = document.querySelectorAll('.u-qa-card');
    qaCards.forEach(function (card) {
        card.addEventListener('click', function (e) {
            // Add ripple effect or visual feedback
            this.style.transform = 'scale(0.95)';
            setTimeout(function () {
                card.style.transform = '';
            }, 150);
        });
    });

    // Booking items click handling
    var bookingItems = document.querySelectorAll('.u-bk-item');
    bookingItems.forEach(function (item) {
        item.addEventListener('click', function (e) {
            // Navigation is handled by the href, but we can add analytics or tracking here
            console.log('Booking clicked:', this.querySelector('.u-bk-ref').textContent);
        });
    });
})();