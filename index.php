<?php
session_start();

// Include separated database configuration logic layout
include 'config/db.php';

$message = "";

// Check if a secure user tracking session profile matrix is active
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['user_name'] : "Guest";

if(isset($_POST['book_now'])){

    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $room_type = mysqli_real_escape_string($conn, $_POST['room_type']);
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];
    $guests = $_POST['guests'];

    // If logged in, grab user id to prevent structural isolation anomalies
    $user_id_val = $is_logged_in ? (int)$_SESSION['user_id'] : "NULL";

    // Standard structural schema format map query layout context sequence
    $insert = "INSERT INTO bookings (
        user_id,
        booking_reference,
        room_type,
        check_in,
        check_out,
        guests,
        total_price,
        status
    ) VALUES (
        " . ($is_logged_in ? $user_id_val : "0") . ",
        'BKG-" . rand(10000000, 99999999) . "',
        '$room_type',
        '$checkin',
        '$checkout',
        '$guests',
        5000.00,
        'Pending'
    )";

    if(mysqli_query($conn, $insert)){
        $message = "Reservation Successfully Submitted!";
    }else{
        $message = "Booking Failed: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Haven Hotel | Luxury & Comfort</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css"/>

    <link rel="stylesheet" href="ui/style.css">
    
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
            border-color: #c69c4f;
            color: #c69c4f;
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
            <li><a href="about.php">About</a></li>
            <li><a href="#rooms">Accommodations</a></li>
            <li><a href="#booking">Booking</a></li>
            <li><a href="#location">Location</a></li>
            <li><a href="#contact">Contact</a></li>
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

<section class="hero" id="home">

    <div class="hero-overlay"></div>

    <div class="hero-content" data-aos="fade-up">

        <h1>Luxury Redefined at Haven Hotel</h1>

        <p>
            Experience world-class hospitality, elegant suites,
            and unforgettable comfort in the heart of paradise.
        </p>

        <div class="hero-buttons">
            <a href="book.php" class="btn-primary">Book Your Stay</a>
            <a href="#rooms" class="btn-secondary">Explore Rooms</a>
        </div>

    </div>

</section>

<section class="features">

    <div class="feature-card" data-aos="fade-up">
        <i class="fa-solid fa-wifi"></i>
        <h3>Free WiFi</h3>
        <p>Fast and reliable internet access throughout the hotel.</p>
    </div>

    <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
        <i class="fa-solid fa-spa"></i>
        <h3>Spa & Wellness</h3>
        <p>Relax and rejuvenate with premium spa treatments.</p>
    </div>

    <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
        <i class="fa-solid fa-utensils"></i>
        <h3>Fine Dining</h3>
        <p>Enjoy gourmet dishes crafted by world-class chefs.</p>
    </div>

    <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
        <i class="fa-solid fa-water-ladder"></i>
        <h3>Infinity Pool</h3>
        <p>Luxury poolside relaxation with breathtaking views.</p>
    </div>

</section>

<section class="about" id="about">

    <div class="about-image" data-aos="fade-right">
        <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=1600&auto=format&fit=crop" alt="About Haven Hotel Image Grid View">
    </div>

    <div class="about-content" data-aos="fade-left">

        <span>ABOUT HAVEN HOTEL</span>

        <h2>Experience Luxury Like Never Before</h2>

        <p>
            Haven Hotel is a modern luxury destination designed to provide
            comfort, elegance, and unforgettable hospitality. From premium
            suites to world-class services, every detail is crafted for your perfect stay.
        </p>

        <div class="about-stats">

            <div>
                <h3>15+</h3>
                <p>Years Experience</p>
            </div>

            <div>
                <h3>5★</h3>
                <p>Luxury Rating</p>
            </div>

            <div>
                <h3>500+</h3>
                <p>Happy Guests</p>
            </div>

        </div>

    </div>

</section>

<section class="rooms" id="rooms">

    <div class="section-title" data-aos="fade-up">
        <span>OUR ACCOMMODATIONS</span>
        <h2>Luxury Rooms & Suites</h2>
    </div>

    <div class="room-grid">

        <div class="room-card" data-aos="zoom-in">

            <img src="https://images.unsplash.com/photo-1631049307264-da0ec9d70304?q=80&w=1600&auto=format&fit=crop" alt="Standard Room Thumbnail">

            <div class="room-content">

                <h3>Standard Room</h3>

                <p>Comfortable and elegant room perfect for relaxing stays.</p>

                <div class="room-info">
                    <span><i class="fa-solid fa-user-group"></i> 2 Guests</span>
                    <span><i class="fa-solid fa-expand"></i> 25 sqm</span>
                </div>

                <div class="room-bottom">
                    <h4>₱3,500 <span>/night</span></h4>
                    <a href="book.php" class="nav-btn">Book Now</a>
                </div>

            </div>

        </div>

        <div class="room-card" data-aos="zoom-in" data-aos-delay="100">

            <img src="https://images.unsplash.com/photo-1590490360182-c33d57733427?q=80&w=1600&auto=format&fit=crop" alt="Deluxe Room Thumbnail">

            <div class="room-content">

                <h3>Deluxe Room</h3>

                <p>Premium comfort with balcony and modern amenities.</p>

                <div class="room-info">
                    <span><i class="fa-solid fa-user-group"></i> 3 Guests</span>
                    <span><i class="fa-solid fa-expand"></i> 35 sqm</span>
                </div>

                <div class="room-bottom">
                    <h4>₱5,500 <span>/night</span></h4>
                    <a href="book.php" class="nav-btn">Book Now</a>
                </div>

            </div>

        </div>

        <div class="room-card" data-aos="zoom-in" data-aos-delay="200">

            <img src="https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=1600&auto=format&fit=crop" alt="Presidential Suite Thumbnail">

            <div class="room-content">

                <h3>Presidential Suite</h3>

                <p>Ultimate luxury suite with premium services and ocean view.</p>

                <div class="room-info">
                    <span><i class="fa-solid fa-user-group"></i> 6 Guests</span>
                    <span><i class="fa-solid fa-expand"></i> 120 sqm</span>
                </div>

                <div class="room-bottom">
                    <h4>₱25,000 <span>/night</span></h4>
                    <a href="book.php" class="nav-btn">Book Now</a>
                </div>

            </div>

        </div>

    </div>

</section>

<section class="booking" id="booking">

    <div class="booking-container">

        <div class="booking-text">

            <span>BOOK YOUR STAY</span>

            <h2>Reserve Your Luxury Experience</h2>

            <p>
                Book your stay today and enjoy luxury,
                comfort, and unforgettable hospitality.
            </p>

        </div>

        <form class="booking-form" method="POST">

            <?php
            if($message != ""){
                echo "<div class='success-msg'>$message</div>";
            }
            ?>

            <div class="input-group">
                <label>Full Name</label>
                <input type="text" name="fullname" value="<?= $is_logged_in ? htmlspecialchars($user_name) : ''; ?>" required>
            </div>

            <div class="input-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= $is_logged_in ? htmlspecialchars($_SESSION['user_email'] ?? '') : ''; ?>" required>
            </div>

            <div class="input-group">
                <label>Room Type</label>

                <select name="room_type">
                    <option>Standard Room</option>
                    <option>Deluxe Room</option>
                    <option>Executive Room</option>
                    <option>Junior Suite</option>
                    <option>Presidential Suite</option>
                </select>
            </div>

            <div class="input-group">
                <label>Check-In</label>
                <input type="date" name="checkin" required>
            </div>

            <div class="input-group">
                <label>Check-Out</label>
                <input type="date" name="checkout" required>
            </div>

            <div class="input-group">
                <label>Guests</label>
                <input type="number" name="guests" min="1" value="1" required>
            </div>

            <button type="submit" name="book_now" class="booking-btn">
                Confirm Reservation
            </button>

        </form>

    </div>

</section>

<section class="testimonials">

    <div class="section-title" data-aos="fade-up">
        <span>TESTIMONIALS</span>
        <h2>What Our Guests Say</h2>
    </div>

    <div class="testimonial-grid">

        <div class="testimonial-card" data-aos="fade-up">
            <img src="https://preview.redd.it/which-actor-could-play-jeff-if-a-movie-was-made-on-crab-game-v0-vmjxr6usjpk81.jpg?width=640&crop=smart&auto=webp&s=7223e0cc63b7291b249905591c08a08eb3494e15" alt="Jeff Profile Picture Picture Card">
            <h3>Jeff</h3>
            <div class="stars">★★★★★</div>
            <p>“Wow, this hotel is amazing!”</p>
        </div>

        <div class="testimonial-card" data-aos="fade-up" data-aos-delay="100">
            <img src="https://preview.redd.it/which-actor-could-play-jeff-if-a-movie-was-made-on-crab-game-v0-vmjxr6usjpk81.jpg?width=640&crop=smart&auto=webp&s=7223e0cc63b7291b249905591c08a08eb3494e15" alt="Jeff Secondary Card Profile">
            <h3>Also Jeff</h3>
            <div class="stars">★★★★★</div>
            <p>“Wow, Phenomenoul experience!”</p>
        </div>

        <div class="testimonial-card" data-aos="fade-up" data-aos-delay="200">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT6Y3x8m8NAhOSh5PYWEQqETRITBRzdTnc00w&s" alt="Guest Testimonial Card Frame Image">
            <h3>Donald Trump</h3>
            <div class="stars">★★★★★</div>
            <p>“Beautiful rooms, amazing food, and wonderful hospitality.”</p>
        </div>

    </div>

</section>

<section class="location" id="location">

    <div class="section-title" data-aos="fade-up">
        <span>LOCATION</span>
        <h2>Find Us</h2>
    </div>

    <div class="map-container" data-aos="zoom-in">
        <iframe 
        src="https://www.google.com/maps/embed?pb=!1m18..."
        allowfullscreen=""
        loading="lazy"
        title="Haven Hotel Location Map Blueprint Node Panel">
        </iframe>
    </div>

</section>

<footer class="footer" id="contact">

    <div class="footer-grid">

        <div>
            <h3>Haven Hotel</h3>
            <p>Luxury and comfort designed to make every stay unforgettable.</p>
        </div>

        <div>
            <h3>Quick Links</h3>
            <ul>
                <li><a href="#">Home</a></li>
                <li><a href="#">Rooms</a></li>
                <li><a href="#">Booking</a></li>
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
        © 2026 Haven Hotel. All Rights Reserved.
    </div>

</footer>

<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const dropdownBtn = document.getElementById("profileDropdownBtn");
        const dropdownMenu = document.getElementById("profileDropdownMenu");

        // Only initialize if the user is logged in and elements exist
        if (dropdownBtn && dropdownMenu) {
            dropdownBtn.addEventListener("click", function(event) {
                // Prevent event bubbling to avoid instant closing via the window listener
                event.stopPropagation(); 
                
                dropdownMenu.classList.toggle("show");
                dropdownBtn.classList.toggle("active");
            });

            // Close the menu automatically if the user clicks anywhere else outside of it
            window.addEventListener("click", function(event) {
                if (!dropdownBtn.contains(event.target) && !dropdownMenu.contains(event.target)) {
                    dropdownMenu.classList.remove("show");
                    dropdownBtn.classList.remove("active");
                }
            });
        }
    });

    AOS.init({
        duration:1000,
        once:true
    });
</script>

</body>
</html>