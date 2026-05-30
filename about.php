<?php
// Initialize session tracking context safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Track authenticated identity configurations dynamically
$is_logged_in = isset($_SESSION['user_id']);
$user_name    = $is_logged_in ? ($_SESSION['user_name'] ?? 'Guest') : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Haven Hotel</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="ui/about.css">
    
    <style>
        /* =========================================================================
           PROFILE ACTION MATRIX NAVIGATION DROPDOWN CSS (CLICK INITIALIZED)
        ========================================================================= */
        .nav-actions-group {
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
        }

        .profile-dropdown-wrapper {
            position: relative;
            display: inline-block;
        }

        .profile-avatar-trigger {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: white;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            outline: none;
        }

        /* Active styling applied when menu is open */
        .profile-avatar-trigger:focus,
        .profile-avatar-trigger.active {
            border-color: #d4af37;
            color: #d4af37;
            background: rgba(198, 156, 79, 0.05);
        }

        .profile-dropdown-menu {
            position: absolute;
            top: 130%;
            right: 0;
            width: 200px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border: 1px solid #f2eade;
            padding: 12px 0;
            
            /* Hidden state defaults */
            display: none; 
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.2s ease, transform 0.2s ease;
            z-index: 1100;
            text-align: left;
        }

        /* The JavaScript toggles this class to show the menu */
        .profile-dropdown-menu.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown-user-meta {
            padding: 8px 20px 12px 20px;
        }

        .dropdown-user-meta .user-greeting {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .dropdown-user-meta .user-profile-name {
            font-size: 15px;
            font-weight: 600;
            color: #111;
            margin-top: 2px;
            font-family: 'Poppins', sans-serif;
        }

        .dropdown-divider {
            border: 0;
            height: 1px;
            background: #f2eade;
            margin: 4px 0 8px 0;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            color: #555;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .dropdown-item i {
            font-size: 14px;
            color: #888;
            width: 16px;
            text-align: center;
        }

        .dropdown-item:hover {
            background: #FAF8F5;
            color: #c69c4f;
        }

        .dropdown-item:hover i {
            color: #c69c4f;
        }

        .dropdown-item.logout-action:hover {
            background: #fff5f5;
            color: #ef4444;
        }

        .dropdown-item.logout-action:hover i {
            color: #ef4444;
        }

        .guest-login-link {
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .guest-login-link:hover {
            color: #c69c4f;
        }
    </style>
</head>
<body>

<header class="navbar">

    <div class="logo">
        Haven<span>Hotel</span>
    </div>

    <nav>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="about.php" class="active">About</a></li>
            <li><a href="index.php #rooms">Accommodations</a></li>
            <li><a href="index.php #booking">Booking</a></li>
            <li><a href="index.php #location">Location</a></li>
            <li><a href="index.php #contact">Contact</a></li>
        </ul>
    </nav>

    <div class="nav-actions-group">
        <a href="book.php" class="nav-btn">Book Now</a>

        <?php if ($is_logged_in): ?>
            <div class="profile-dropdown-wrapper">
                <button class="profile-avatar-trigger" id="profileDropdownBtn" aria-label="User Account Menu">
                    <i class="fa-regular fa-user"></i>
                </button>
                <div class="profile-dropdown-menu" id="profileDropdownMenu">
                    <div class="dropdown-user-meta">
                        <p class="user-greeting">Welcome,</p>
                        <p class="user-profile-name"><?= htmlspecialchars(explode(' ', $user_name)[0]); ?></p>
                    </div>
                    <hr class="dropdown-divider">
                    <a href="dashboard.php" class="dropdown-item">
                        <i class="fa-solid fa-gauge-high"></i> Dashboard
                    </a>
                    <a href="login.php" class="dropdown-item logout-action">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="guest-login-link"><i class="fa-solid fa-arrow-right-to-bracket"></i> Login</a>
        <?php endif; ?>
    </div>

</header>

<section class="about-hero">

    <div class="overlay"></div>

    <div class="hero-content">

        <span><br><br>WELCOME TO HAVEN HOTEL</span>

        <h1>Luxury, Comfort & Hospitality</h1>

        <p>
            Discover elegance, relaxation, and unforgettable experiences
            designed to make every guest feel at home.
        </p>

    </div>

</section>

<section class="story-section">

    <div class="story-image">

        <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=1600&auto=format&fit=crop" alt="Haven Hotel Interior">

    </div>

    <div class="story-content">

        <span>OUR STORY</span>

        <h2>Where Luxury Meets Serenity</h2>

        <p>
            Haven Hotel was established with one vision:
            to create a world-class luxury destination where guests can relax,
            recharge, and create unforgettable memories.
        </p>

        <p>
            Inspired by modern elegance and timeless hospitality,
            Haven Hotel combines premium accommodations,
            exceptional service, and breathtaking spaces
            to provide a truly luxurious experience.
        </p>

    </div>

</section>

<section class="mission-vision">

    <div class="mv-card">

        <i class="fa-solid fa-bullseye"></i>

        <h3>Our Mission</h3>

        <p>
            To deliver exceptional hospitality experiences
            through luxurious accommodations, premium services,
            and heartfelt customer care.
        </p>

    </div>

    <div class="mv-card">

        <i class="fa-solid fa-eye"></i>

        <h3>Our Vision</h3>

        <p>
            To become the leading luxury hotel destination
            known for elegance, comfort, and world-class hospitality.
        </p>

    </div>

    <div class="mv-card">

        <i class="fa-solid fa-heart"></i>

        <h3>Our Values</h3>

        <p>
            Excellent, Integrity, Comfort, Innovation,
            and Genuine Hospitality are at the heart of everything we do.
        </p>

    </div>

</section>

<section class="services">

    <div class="section-title">

        <span>WHAT WE OFFER</span>

        <h2>Luxury Services & Amenities</h2>

    </div>

    <div class="service-grid">

        <div class="service-card">
            <i class="fa-solid fa-bed"></i>
            <h3>Luxury Suites</h3>
            <p>Elegant rooms designed for comfort and relaxation.</p>
        </div>

        <div class="service-card">
            <i class="fa-solid fa-spa"></i>
            <h3>Spa & Wellness</h3>
            <p>Premium spa experiences for complete rejuvenation.</p>
        </div>

        <div class="service-card">
            <i class="fa-solid fa-utensils"></i>
            <h3>Fine Dining</h3>
            <p>World-class culinary experiences from expert chefs.</p>
        </div>

        <div class="service-card">
            <i class="fa-solid fa-water-ladder"></i>
            <h3>Infinity Pool</h3>
            <p>Relax beside our luxurious infinity pool area.</p>
        </div>

        <div class="service-card">
            <i class="fa-solid fa-dumbbell"></i>
            <h3>Fitness Center</h3>
            <p>Modern gym facilities for health and wellness.</p>
        </div>

        <div class="service-card">
            <i class="fa-solid fa-wifi"></i>
            <h3>High-Speed WiFi</h3>
            <p>Fast and reliable internet throughout the hotel.</p>
        </div>

    </div>

</section>

<section class="team-section">

    <div class="section-title">

        <span>OUR TEAM</span>

        <h2>Meet Our Hospitality Experts</h2>

    </div>

    <div class="team-grid">

        <div class="team-card">
            <img src="https://via.placeholder.com/400x450.png?text=Member+1" alt="John Anderson">
            <div class="team-content">
                <h3>John Anderson</h3>
                <p>General Manager</p>
            </div>
        </div>

        <div class="team-card">
            <img src="https://via.placeholder.com/400x450.png?text=Member+2" alt="Maria Santos">
            <div class="team-content">
                <h3>Maria Santos</h3>
                <p>Guest Relations Manager</p>
            </div>
        </div>

        <div class="team-card">
            <img src="https://via.placeholder.com/400x450.png?text=Member+3" alt="David Lee">
            <div class="team-content">
                <h3>David Lee</h3>
                <p>Executive Chef</p>
            </div>
        </div>

        <div class="team-card">
            <img src="https://via.placeholder.com/400x450.png?text=Member+4" alt="Angela Cruz">
            <div class="team-content">
                <h3>Angela Cruz</h3>
                <p>Operations Supervisor</p>
            </div>
        </div>

        <div class="team-card">
            <img src="https://via.placeholder.com/400x450.png?text=Member+5" alt="Michael Reyes">
            <div class="team-content">
                <h3>Michael Reyes</h3>
                <p>Luxury Concierge</p>
            </div>
        </div>

    </div>

</section>

<footer class="footer">

    <div class="footer-grid">

        <div>
            <h3>Haven Hotel</h3>
            <p>
                Luxury and comfort designed to make every stay unforgettable.
            </p>
        </div>

        <div>
            <h3>Quick Links</h3>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="book.php">Booking</a></li>
                <li><a href="#">Contact</a></li>
            </ul>
        </div>

        <div>
            <h3>Contact</h3>
            <p>Email: havenhotel@gmail.com</p>
            <p>Phone: +63 912 345 6789</p>
            <p>Manila, Philippines</p>
        </div>

        <div>
            <h3>Follow Us</h3>
            <div class="socials">
                <i class="fa-brands fa-facebook-f"></i>
                <i class="fa-brands fa-instagram"></i>
                <i class="fa-brands fa-twitter"></i>
                <i class="fa-brands fa-youtube"></i>
            </div>
        </div>

    </div>

    <div class="footer-bottom">
        &copy; 2026 Haven Hotel. All Rights Reserved.
    </div>

</footer>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const dropdownBtn = document.getElementById("profileDropdownBtn");
        const dropdownMenu = document.getElementById("profileDropdownMenu");

        // Only handle clicks if user identity metrics are verified and elements exist
        if (dropdownBtn && dropdownMenu) {
            dropdownBtn.addEventListener("click", function(event) {
                // Prevent event bubbling up to the window bounds
                event.stopPropagation(); 
                
                dropdownMenu.classList.toggle("show");
                dropdownBtn.classList.toggle("active");
            });

            // Dismiss menu instantly if click actions fall anywhere outside the boundary box
            window.addEventListener("click", function(event) {
                if (!dropdownBtn.contains(event.target) && !dropdownMenu.contains(event.target)) {
                    dropdownMenu.classList.remove("show");
                    dropdownBtn.classList.remove("active");
                }
            });
        }
    });
</script>

</body>
</html>
