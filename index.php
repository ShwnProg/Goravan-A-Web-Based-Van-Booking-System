<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoraVan</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <nav class="navbar">
        <div class="logo">
            <img src="/images/logo.png" alt="goravan logo">
            <span class="navbar-name">Gora<span>Van</span></span>
        </div>
        <div class="navbar-nav">
            <a href="#home" class="nav-link">Home</a>
            <a href="#features" class="nav-link">Features</a>
            <a href="#how" class="nav-link">How It Works</a>
            <a href="#routes" class="nav-link">Routes</a>
            <div class="btn-group">
                <a href="/views/login.php" class="btn login-btn">Log In</a>
                <a href="/views/register.php" class="btn register-btn">Get Started</a>
            </div>
        </div>
        <button class="hamburger" id="hamburger" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
    </nav>

    <div class="mobile-menu" id="mobileMenu">
        <a href="#home" class="nav-link">Home</a>
        <a href="#features" class="nav-link">Features</a>
        <a href="#how" class="nav-link">How It Works</a>
        <a href="#routes" class="nav-link">Routes</a>
        <div class="mobile-divider"></div>
        <div class="btn-group">
            <a href="/views/login.php" class="btn login-btn">Log In</a>
            <a href="/views/register.php" class="btn register-btn">Get Started</a>
        </div>
    </div>

    <!-- HOME -->
    <section class="hero" id="home">
        <div class="hero-content">
            <div class="hero-badge">Southern Leyte Transportation</div>
            <h1>Book Your <span>Van Ride</span><br>Online, Anytime</h1>
            <p>GoraVan makes commuting between Southern Leyte destinations easier. Reserve your seat, view schedules,
                and confirm your booking — all without going to the terminal.</p>
            <div class="hero-actions">
                <a href="/views/register.php" class="cta-btn">Book a Ride</a>
                <a href="/views/login.php" class="cta-outline">Log In</a>
            </div>
        </div>

        <div class="hero-visual">
            <div class="hero-card">
                <div class="hero-card-header">
                    <span class="card-label">Available Routes Today</span>
                    <span class="live-badge"><i class="fa-solid fa-circle"></i> Live</span>
                </div>
                <div class="route-list">
                    <div class="route-item">
                        <span class="route-name">Sogod → Maasin</span>
                        <span class="route-time">6:00 AM</span>
                    </div>
                    <div class="route-item">
                        <span class="route-name">Maasin → Sogod</span>
                        <span class="route-time">8:30 AM</span>
                    </div>
                    <div class="route-item">
                        <span class="route-name">Sogod → Liloan</span>
                        <span class="route-time">10:00 AM</span>
                    </div>
                </div>
            </div>

            <div class="hero-card">
                <div class="hero-card-header">
                    <span class="card-label">Seat Availability — Sogod → Maasin</span>
                </div>
                <div class="seat-grid">
                    <div class="seat available">1A</div>
                    <div class="seat available">1B</div>
                    <div class="seat taken">1C</div>
                    <div class="seat selected">2A</div>
                    <div class="seat available">2B</div>
                    <div class="seat taken">2C</div>
                    <div class="seat available">3A</div>
                    <div class="seat available">3B</div>
                    <div class="seat taken">3C</div>
                    <div class="seat available">4A</div>
                </div>
                <div class="seat-legend">
                    <span><span class="legend-box available-box"></span> Available</span>
                    <span><span class="legend-box selected-box"></span> Selected</span>
                    <span><span class="legend-box taken-box"></span> Taken</span>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section class="features" id="features">
        <div class="features-header">
            <div class="badge-dark">What We Offer</div>
            <h2>A smarter way <span>to travel</span></h2>
            <p>
                GoraVan replaces the hassle of lining up at the terminal with a simple, organized online booking
                experience.
            </p>
        </div>

        <div class="features-grid">

            <!-- Seat Selection -->
            <div class="feature-card">
                <div class="icon-wrap">
                    <i class="fa-solid fa-couch"></i>
                </div>
                <h3>Seat Selection</h3>
                <p>
                    Choose your exact seat from a visual van layout before you confirm your booking. No surprises on the
                    day of your trip.
                </p>
            </div>

            <!-- Route-Based Booking -->
            <div class="feature-card">
                <div class="icon-wrap">
                    <i class="fa-solid fa-route"></i>
                </div>
                <h3>Route-Based Booking</h3>
                <p>
                    Browse trips by route — origin, destination, and via points. Find the right schedule that fits your
                    travel plan.
                </p>
            </div>

            <!-- Secure Payment Upload -->
            <div class="feature-card">
                <div class="icon-wrap">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <h3>Secure Payment Upload</h3>
                <p>
                    Pay through your preferred method and simply upload a photo of your receipt. Our admin verifies it
                    before confirming your booking.
                </p>
            </div>

            <!-- Trip Status Tracking -->
            <div class="feature-card">
                <div class="icon-wrap">
                    <i class="fa-solid fa-location-crosshairs"></i>
                </div>
                <h3>Trip Status Tracking</h3>
                <p>
                    Know where your van is in its journey — Scheduled, Boarding, Departed, or Arrived — updated in real
                    time by our operators.
                </p>
            </div>

            <!-- Booking Confirmation -->
            <div class="feature-card">
                <div class="icon-wrap">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h3>Booking Confirmation</h3>
                <p>
                    Receive a unique reference code upon approval. Present it at the terminal for fast, organized
                    boarding.
                </p>
            </div>

            <!-- Priority Passenger Support -->
            <div class="feature-card">
                <div class="icon-wrap">
                    <i class="fa-solid fa-users"></i>
                </div>
                <h3>Priority Passenger Support</h3>
                <p>
                    Senior citizens, PWD, students, and pregnant passengers may upload verification documents to avail
                    of
                    applicable discounts.
                </p>
            </div>

        </div>
    </section>

    <!-- HOW IT WORKS SECTION -->
    <section class="how" id="how">
        <div class="how-header">
            <div class="badge-dark">How It Works</div>
            <h2>Book in <span>Four Simple Steps</span></h2>
            <p>
                From registration to boarding, the GoraVan process is designed to be fast, straightforward, and
                user-friendly.
            </p>
        </div>

        <div class="how-grid">

            <div class="how-card">
                <div class="how-step">1</div>
                <h3>Register or Log In</h3>
                <p>Create a free account or sign in to access all booking features.</p>
            </div>

            <div class="how-card">
                <div class="how-step">2</div>
                <h3>Select Route & Seat</h3>
                <p>Choose your destination, pick a schedule, and reserve your preferred seat.</p>
            </div>

            <div class="how-card">
                <div class="how-step">3</div>
                <h3>Upload Payment</h3>
                <p>Pay through any channel and upload your proof of payment for admin review.</p>
            </div>

            <div class="how-card">
                <div class="how-step">4</div>
                <h3>Board & Travel</h3>
                <p>Receive your reference code and present it at the terminal. That's it!</p>
            </div>

        </div>
    </section>

    <!-- ROUTES SECTION -->
    <section class="routes" id="routes">
        <div class="routes-header">
            <div class="badge-light">Service Area</div>
            <h2>Covering <span>Southern Leyte</span></h2>
            <p>
                We operate scheduled van trips across major destinations in Southern Leyte province.
            </p>
        </div>

        <div class="routes-grid">

            <div class="route-card">Sogod → Maasin</div>
            <div class="route-card">Maasin → Sogod</div>
            <div class="route-card">Sogod → Liloan</div>
            <div class="route-card">Maasin → Bato</div>
            <div class="route-card">Bato → Sogod</div>
            <div class="route-card">Sogod → Malitbog</div>
            <div class="route-card">Maasin → Pintuyan</div>
            <div class="route-card">Sogod → San Juan</div>

        </div>
    </section>

    <!-- CTA SECTION -->
    <section class="cta-section">
        <div class="cta-card">
            <h2>Ready to book your next trip?</h2>
            <p>
                Join hundreds of Southern Leyte commuters who use GoraVan to travel smarter.
            </p>

            <div class="cta-buttons">
                <a href="register.php" class="cta-btn">Create an Account</a>
                <a href="login.php" class="cta-outline">Log In</a>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">

            <!-- Brand -->
            <div class="footer-brand">
                <h2>GoraVan</h2>
                <p>
                    A web-based van booking system for Southern Leyte commuters.
                    Organized, reliable, and easy to use.
                </p>
            </div>

            <!-- System -->
            <div class="footer-box">
                <h3>System</h3>
                <p>Home</p>
                <p>Login</p>
                <p>Register</p>
                <p>Dashboard</p>
            </div>

            <!-- Routes -->
            <div class="footer-box">
                <h3>Routes</h3>
                <p>Sogod — Maasin</p>
                <p>Maasin — Sogod</p>
                <p>Sogod — Liloan</p>
                <p>All Routes</p>
            </div>

        </div>

        <!-- Bottom -->
        <div class="footer-bottom">
            <p>2026 GoraVan. All rights reserved. Southern Leyte, Philippines.</p>
        </div>
    </footer>
</body>

<script>
    const hamburger = document.getElementById('hamburger');
    const mobileMenu = document.getElementById('mobileMenu');
    const body = document.body;

    function toggleMenu() {
        hamburger.classList.toggle('open');
        mobileMenu.classList.toggle('open');
        body.classList.toggle('menu-open');
    }

    hamburger.addEventListener('click', toggleMenu);
    document.querySelectorAll('.mobile-menu a').forEach(link => {
        link.addEventListener('click', () => {
            hamburger.classList.remove('open');
            mobileMenu.classList.remove('open');
            body.classList.remove('menu-open');
        });
    });
</script>

</html>