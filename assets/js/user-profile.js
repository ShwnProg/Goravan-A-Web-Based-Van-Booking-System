/* user-profile.js — Profile page specific JavaScript */

(function () {
    // Profile form handling
    var profileForm = document.querySelector('form[action*="ProfileController.php"]');
    if (profileForm) {
        profileForm.addEventListener('submit', function (e) {
            var action = this.querySelector('input[name="action"]').value;

            if (action === 'update_profile') {
                // Validate profile update
                var firstName = document.getElementById('firstName').value;
                var lastName = document.getElementById('lastName').value;
                var email = document.getElementById('email').value;
                var phone = document.getElementById('phone').value;

                if (!firstName || !lastName || !email) {
                    e.preventDefault();
                    alert('Please fill in all required fields');
                    return false;
                }

                // Email validation
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    alert('Please enter a valid email address');
                    return false;
                }

                // Phone validation (if provided)
                if (phone && phone.length < 10) {
                    e.preventDefault();
                    alert('Please enter a valid phone number');
                    return false;
                }
            }

            if (action === 'change_password') {
                // Validate password change
                var currentPassword = document.getElementById('currentPassword').value;
                var newPassword = document.getElementById('newPassword').value;
                var confirmPassword = document.getElementById('confirmPassword').value;

                if (!currentPassword || !newPassword || !confirmPassword) {
                    e.preventDefault();
                    alert('Please fill in all password fields');
                    return false;
                }

                if (newPassword.length < 8) {
                    e.preventDefault();
                    alert('New password must be at least 8 characters long');
                    return false;
                }

                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('New passwords do not match');
                    return false;
                }

                if (currentPassword === newPassword) {
                    e.preventDefault();
                    alert('New password must be different from current password');
                    return false;
                }
            }
        });
    }

    // Menu item interactions
    var menuItems = document.querySelectorAll('.u-menu-item');
    menuItems.forEach(function (item) {
        item.addEventListener('click', function (e) {
            // Add visual feedback
            this.style.transform = 'scale(0.98)';
            setTimeout(function () {
                item.style.transform = '';
            }, 150);

            // Handle danger items (sign out)
            if (this.classList.contains('danger')) {
                var confirmSignOut = confirm('Are you sure you want to sign out?');
                if (!confirmSignOut) {
                    e.preventDefault();
                }
            }
        });
    });

    // Input field focus effects
    var inputs = document.querySelectorAll('.u-form-group input');
    inputs.forEach(function (input) {
        input.addEventListener('focus', function () {
            this.parentElement.classList.add('focused');
        });
        input.addEventListener('blur', function () {
            this.parentElement.classList.remove('focused');
        });
    });

    // Password strength indicator (optional enhancement)
    var newPasswordInput = document.getElementById('newPassword');
    var confirmPasswordInput = document.getElementById('confirmPassword');

    if (newPasswordInput && confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function () {
            if (this.value !== newPasswordInput.value) {
                this.style.borderColor = 'var(--u-danger)';
            } else {
                this.style.borderColor = 'var(--u-success)';
            }
        });
    }

    // Phone number formatting
    var phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function (e) {
            // Remove non-numeric characters
            var value = this.value.replace(/\D/g, '');
            // Format as 09XX XXX XXXX
            if (value.length > 0) {
                if (value.length <= 4) {
                    this.value = value;
                } else if (value.length <= 7) {
                    this.value = value.slice(0, 4) + ' ' + value.slice(4);
                } else {
                    this.value = value.slice(0, 4) + ' ' + value.slice(4, 7) + ' ' + value.slice(7, 11);
                }
            }
        });
    }
})();