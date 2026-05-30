<?php
session_start();

// Include separated database configuration logic layout
include 'config/db.php';

$message = "";

// Check if a secure user tracking session profile matrix is active
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['user_name'] : "Guest";

$host     = "localhost";
$db_user  = "root";
$db_pass  = "";
$db_name  = "haven_hotel";
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

// Handle Asynchronous AJAX Review Submission
if (isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Please log in to submit a review.']);
        exit();
    }
    
    $user_id = (int)$_SESSION['user_id'];
    $rating = (int)$_POST['rating'];
    $review_text = strip_tags(trim($_POST['review_text']));
    
    // Read the access key input from the form, but don't throw an error if it's empty/ignored for now
    $master_key = isset($_POST['master_key']) ? trim($_POST['master_key']) : ''; 
    
    if (empty($review_text)) {
        echo json_encode(['status' => 'error', 'message' => 'Review content cannot be left empty.']);
        exit();
    }
    
    // Standard insert without master key constraint
    $stmt = $conn->prepare("INSERT INTO testimonials (user_id, rating, review_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $rating, $review_text);
    
    if ($stmt->execute()) {
        $fullName = $_SESSION['user_name'] ?? 'Anonymous Guest';
        echo json_encode([
            'status' => 'success',
            'user_name' => htmlspecialchars($fullName),
            'rating' => $rating,
            'review_text' => htmlspecialchars($review_text),
            'date' => date('M d, Y')
        ]);
    } else {
        // Send back the direct SQL error message to your console log instead of masking it
        echo json_encode(['status' => 'error', 'message' => 'SQL Error: ' . $stmt->error]);
    }
    exit();
}

// --------------------------------------------------------
// DATA PIPELINE: ASSEMBLE ACTIVE REAL-TIME ROOM INVENTORY
// --------------------------------------------------------
$display_rooms = [];
$rooms_query = $conn->query("SELECT * FROM rooms ORDER BY price_per_night ASC");

if ($rooms_query && $rooms_query->num_rows > 0) {
    while ($r = $rooms_query->fetch_assoc()) {
        
        // Match the clean room image mapper configuration set up in book.php
        $assigned_image = "https://images.unsplash.com/photo-1611892440504-42a792e24d32?q=80&w=600&auto=format&fit=crop"; // Default fallback
        $room_label = strtolower($r['room_type']);
        
        if (strpos($room_label, 'standard') !== false) {
            $assigned_image = "https://images.unsplash.com/photo-1611892440504-42a792e24d32?q=80&w=600&auto=format&fit=crop";
        } elseif (strpos($room_label, 'deluxe') !== false) {
            $assigned_image = "https://images.unsplash.com/photo-1590490360182-c33d57733427?q=80&w=600&auto=format&fit=crop";
        } elseif (strpos($room_label, 'executive') !== false) {
            $assigned_image = "https://images.unsplash.com/photo-1566665797739-1674de7a421a?q=80&w=600&auto=format&fit=crop";
        } elseif (strpos($room_label, 'junior') !== false || strpos($room_label, 'suite') !== false) {
            $assigned_image = "https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=600&auto=format&fit=crop";
        } elseif (strpos($room_label, 'presidential') !== false) {
            $assigned_image = "https://images.unsplash.com/photo-1578683010236-d716f9a3f461?q=80&w=600&auto=format&fit=crop";
        }

        $display_rooms[] = [
            "number" => $r['room_number'],
            "type"   => $r['room_type'],
            "price"  => (float)$r['price_per_night'],
            "status" => $r['status'],
            "desc"   => $r['description'] ?? "Experience baseline premium living with standard amenities.",
            "image"  => $assigned_image
        ];
    }
}

// Fetch all approved testimonials from database
$reviews_query = "SELECT t.*, CONCAT(u.first_name, ' ', u.last_name) AS guest_name FROM testimonials t LEFT JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC";
$reviews_result = $conn->query($reviews_query);

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
        check_in_date,
        check_out_date,
        guests,
        total_price,
        booking_status
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
            display: none; 
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.2s ease, transform 0.2s ease;
            z-index: 1100;
            text-align: left;
        }

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

        /* Status Badge for Room Cards */
        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        .status-badge.available { background: #dcfce7; color: #15803d; }
        .status-badge.limited { background: #fef3c7; color: #d97706; }
        .status-badge.not_available { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>

<header class="navbar">
    <div class="logo">Haven<span>Hotel</span></div>
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
        <p>Experience world-class hospitality, elegant suites, and unforgettable comfort in the heart of paradise.</p>
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
        <p>Haven Hotel is a modern luxury destination designed to provide comfort, elegance, and unforgettable hospitality. From premium suites to world-class services, every detail is crafted for your perfect stay.</p>
        </div>
    </div>
</section>

<section class="rooms" id="rooms">
    <div class="section-title" data-aos="fade-up">
        <span>OUR ACCOMMODATIONS</span>
        <h2>Luxury Rooms & Suites</h2>
    </div>

    <div class="room-grid">
        <?php if (!empty($display_rooms)): ?>
            <?php foreach ($display_rooms as $room): ?>
                <div class="room-card" data-aos="zoom-in" style="position: relative;">
                    
                    <span class="status-badge <?= strtolower(str_replace(' ', '_', $room['status'])) ?>"><?= $room['status'] ?></span>
                    
                    <img src="<?= htmlspecialchars($room['image']) ?>" alt="<?= htmlspecialchars($room['type']) ?> Room Thumbnail">

                    <div class="room-content">
                        <div style="display: flex; justify-content: space-between; align-items: baseline;">
                            <h3><?= htmlspecialchars($room['type']) ?></h3>
                            <span style="font-size:11px; font-weight:600; color:#888; background:#f4f4f4; padding:2px 6px; border-radius:4px;">No. <?= htmlspecialchars($room['number']) ?></span>
                        </div>

                        <p><?= htmlspecialchars($room['desc']) ?></p>

                        <div class="room-info">
                            <span><i class="fa-solid fa-user-group"></i> Room Capacity Matched</span>
                            <span><i class="fa-solid fa-expand"></i> Executive Layout</span>
                        </div>

                        <div class="room-bottom">
                            <h4>₱<?= number_format($room['price'], 2) ?> <span>/night</span></h4>
                            <a href="book.php?step=1" class="nav-btn">
                                <?= ($room['status'] === 'Not Available') ? 'Unavailable' : 'Book Now' ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column: 1/-1; text-align: center; color: #888; font-style: italic; padding: 40px 0;">No room listings found in the active inventory pipeline matrix.</p>
        <?php endif; ?>
    </div>
</section>

<section class="booking" id="booking">
    <div class="booking-container">
        <div class="booking-text">
            <span>BOOK YOUR STAY</span>
            <h2>Reserve Your Luxury Experience</h2>
            <p>Book your stay today and enjoy luxury, comfort, and unforgettable hospitality.</p>
        </div>

        <form class="booking-form" method="POST">
            <?php if($message != ""): ?>
                <div class='success-msg'><?= $message ?></div>
            <?php endif; ?>

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

            <button type="submit" name="book_now" class="booking-btn">Confirm Reservation</button>
        </form>
    </div>
</section>

<section id="testimonials" class="testimonials-section" style="padding: 80px 20px; background: #fafafa; font-family: 'Plus Jakarta Sans', sans-serif;">
    <div style="max-width: 1100px; margin: 0 auto; text-align: center;">
        <span style="color: #c69c4f; font-weight: 600; text-transform: uppercase; letter-spacing: 2px; font-size: 13px;">GUEST EXPERIENCES</span>
        <h2 style="font-family: 'Playfair Display', serif; font-size: 36px; margin: 10px 0 40px 0; color: #111;">What Our Guests Say</h2>
        
        <div id="reviews_feed_grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 50px;">
            <?php if ($reviews_result && $reviews_result->num_rows > 0): ?>
                <?php while ($rev = $reviews_result->fetch_assoc()): ?>
                    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid #f0e6d6; text-align: left; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="color: #c69c4f; margin-bottom: 15px;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?= $i <= $rev['rating'] ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <p style="color: #555; font-size: 14px; line-height: 1.6; font-style: italic;">"<?= htmlspecialchars($rev['review_text']) ?>"</p>
                        </div>
                        <div style="margin-top: 20px; border-top: 1px solid #f2f2f2; padding-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                            <strong style="color: #222; font-size: 15px;"><?= htmlspecialchars($rev['guest_name'] ?? 'Anonymous Guest') ?></strong>
                            <span style="font-size: 12px; color: #999;"><?= date('M d, Y', strtotime($rev['created_at'])) ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p id="no_reviews_msg" style="grid-column: 1/-1; color: #888; font-style: italic;">Be the very first guest to leave an accommodation review trace below.</p>
            <?php endif; ?>
        </div>

        <div style="max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.04); border: 1px solid #eadecc; text-align: left;">
            <h3 style="font-family: 'Playfair Display', serif; font-size: 22px; margin-bottom: 20px; color: #222;">Share Your Experience</h3>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <form id="ajaxReviewSubmissionForm">
                    <input type="hidden" name="action" value="submit_review">
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; color: #555;">Your Rating</label>
                        <div style="display: flex; gap: 8px; color: #cbd5e1; font-size: 22px; cursor: pointer;" id="star_rating_selector">
                            <i class="fa-solid fa-star selector-star" data-score="1" style="color: #c69c4f;"></i>
                            <i class="fa-solid fa-star selector-star" data-score="2" style="color: #c69c4f;"></i>
                            <i class="fa-solid fa-star selector-star" data-score="3" style="color: #c69c4f;"></i>
                            <i class="fa-solid fa-star selector-star" data-score="4" style="color: #c69c4f;"></i>
                            <i class="fa-solid fa-star selector-star" data-score="5" style="color: #c69c4f;"></i>
                        </div>
                        <input type="hidden" name="rating" id="hidden_rating_score" value="5">
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; color: #555;">Review Details</label>
                        <textarea name="review_text" rows="4" placeholder="How was your stay at Haven Hotel? Describe your experience..." style="width: 100%; box-sizing: border-box; padding: 14px; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-family: inherit; font-size: 14px; resize: vertical;" required></textarea>
                    </div>

                    <button type="submit" style="background: #c69c4f; color: white; border: none; padding: 14px 28px; font-size: 14px; font-weight: 600; border-radius: 8px; cursor: pointer; transition: background 0.2s; width: 100%;">Post Review</button>
                </form>
            <?php else: ?>
                <div style="text-align: center; padding: 20px 0;">
                    <p style="color: #666; font-size: 15px; margin-bottom: 20px;">You must be signed into your reservation account to write a testimonial ledger entry.</p>
                    <a href="login.php" style="display: inline-block; background: #111; color: white; text-decoration: none; padding: 12px 24px; font-size: 14px; font-weight: 600; border-radius: 6px;">Log In to Account</a>
                </div>
            <?php endif; ?>
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
                <li><a href="#home">Home</a></li>
                <li><a href="#rooms">Rooms</a></li>
                <li><a href="#booking">Booking</a></li>
                <li><a href="#contact">Contact</a></li>
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

<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const dropdownBtn = document.getElementById("profileDropdownBtn");
        const dropdownMenu = document.getElementById("profileDropdownMenu");

        if (dropdownBtn && dropdownMenu) {
            dropdownBtn.addEventListener("click", function(event) {
                event.stopPropagation(); 
                dropdownMenu.classList.toggle("show");
                dropdownBtn.classList.toggle("active");
            });

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

    document.addEventListener("DOMContentLoaded", function() {
    const stars = document.querySelectorAll(".selector-star");
    const scoreInput = document.getElementById("hidden_rating_score");
    
    stars.forEach(star => {
        star.addEventListener("click", function() {
            const score = parseInt(this.dataset.score);
            scoreInput.value = score;
            stars.forEach((s, idx) => {
                s.style.color = (idx < score) ? "#c69c4f" : "#cbd5e1";
            });
        });
    });

    const form = document.getElementById("ajaxReviewSubmissionForm");
    if (form) {
        form.addEventListener("submit", function(e) {
            e.preventDefault();
            const payload = new FormData(this);
            
            fetch("index.php", { method: "POST", body: payload })
            .then(res => res.json())
            .then(data => {
                if (data.status === "success") {
                    const noRevMsg = document.getElementById("no_reviews_msg");
                    if (noRevMsg) noRevMsg.remove();
                    
                    let starMarkup = "";
                    for (let i = 1; i <= 5; i++) {
                        starMarkup += `<i class="${i <= data.rating ? 'fa-solid' : 'fa-regular'} fa-star"></i>`;
                    }
                    
                    const nextCard = document.createElement("div");
                    nextCard.style = "background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid #f0e6d6; text-align: left; display: flex; flex-direction: column; justify-content: space-between; animation: fadeIn 0.5s ease-in-out;";
                    nextCard.innerHTML = `
                        <div>
                            <div style="color: #c69c4f; margin-bottom: 15px;">${starMarkup}</div>
                            <p style="color: #555; font-size: 14px; line-height: 1.6; font-style: italic;">"${data.review_text}"</p>
                        </div>
                        <div style="margin-top: 20px; border-top: 1px solid #f2f2f2; padding-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                            <strong style="color: #222; font-size: 15px;">${data.user_name}</strong>
                            <span style="font-size: 12px; color: #999;">${data.date}</span>
                        </div>`;
                    
                    const feed = document.getElementById("reviews_feed_grid");
                    feed.insertBefore(nextCard, feed.firstChild);
                    form.reset();
                    scoreInput.value = 5;
                    stars.forEach(s => s.style.color = "#c69c4f");
                    alert("Thank you! Your testimonial has been saved successfully.");
                } else {
                    alert(data.message);
                }
            }).catch(() => alert("An error occurred during submission."));
        });
    }
});
</script>

</body>
</html>
<?php $conn->close(); ?>
