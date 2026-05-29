<?php
session_start();

// Redirect to login if user tries to bypass the authentication barrier manually
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// =========================================================================
// 1. DATABASE CONNECTION SYNTAX (Direct Tracking Engine connection to phpMyAdmin)
// =========================================================================
$host     = "localhost";
$db_user  = "root";
$db_pass  = "";
$db_name  = "haven_hotel"; 

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Direct Database Connection to phpMyAdmin Failed: " . $conn->connect_error);
}

// Pull validated user credential identity fields out of global session tracking context
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name']; // Extracted for dropdown compatibility

// Since we are inside the authenticated dashboard, this state variable defaults to true
$is_logged_in = true; 

// =========================================================================
// 2. BACKEND CONTROLLER: TRANSACTION INTERCEPTOR (Cancellation Modal Handler)
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'cancel_reservation') {
    $booking_id_to_cancel = intval($_POST['booking_id']);
    
    // Safely update booking status to Cancelled inside phpMyAdmin 
    $stmt_cancel = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ? AND user_id = ?");
    $stmt_cancel->bind_param("ii", $booking_id_to_cancel, $user_id);
    $stmt_cancel->execute();
    $stmt_cancel->close();
    
    header("Location: dashboard.php");
    exit();
}

// =========================================================================
// 3. LIVE MONITOR ANALYTICS (Computes metric summary values dynamically)
// =========================================================================
$query_stats = "SELECT 
                    COUNT(*) as total_stays,
                    COUNT(CASE WHEN status = 'Confirmed' THEN 1 END) as confirmed,
                    COUNT(CASE WHEN status = 'Cancelled' THEN 1 END) as cancelled,
                    COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending,
                    SUM(CASE WHEN status != 'Cancelled' THEN total_price ELSE 0 END) as total_spent
                FROM bookings WHERE user_id = ?";
$stmt_stats = $conn->prepare($query_stats);
$stmt_stats->bind_param("i", $user_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();

// Fetch booking records history stack specific to the logged-in user profile
$query_bookings = "SELECT id, booking_reference, room_type, check_in, check_out, guests, special_requests, total_price, status 
                   FROM bookings WHERE user_id = ? ORDER BY id DESC";
$stmt_bookings = $conn->prepare($query_bookings);
$stmt_bookings->bind_param("i", $user_id);
$stmt_bookings->execute();
$bookings_result = $stmt_bookings->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Haven Hotel - My Reservations</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            background: #FAF8F5; 
            color: #333; 
        }

        /* =========================================================================
           YOUR GLASSMORPHIC NAVBAR STYLES
        ========================================================================= */
        .navbar{
            position:fixed;
            top:0;
            width:100%;
            padding:20px 8%;
            display:flex;
            justify-content:space-between;
            align-items:center;
            z-index:1000;
            background:rgba(0,0,0,0.75); /* Deepened transparency profile slightly for enhanced dashboard link readability over light items */
            backdrop-filter:blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .logo{
            color:white;
            font-size:30px;
            font-weight:700;
            font-family:'Playfair Display',serif;
        }

        .logo span{
            color:#d4af37;
        }

        .nav-links{
            display:flex;
            gap:35px;
            list-style:none;
        }

        .nav-links a{
            color:white;
            text-decoration:none;
            transition:.3s;
            font-size: 14px;
            font-weight: 500;
        }

        .nav-links a:hover,
        .nav-links .active{
            color:#d4af37;
        }

        .nav-btn{
            background:#d4af37;
            color:white;
            padding:12px 24px;
            border-radius:30px;
            text-decoration:none;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .nav-btn:hover {
            background: #b8932e;
        }

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

        /* =========================================================================
            DASHBOARD & RESERVATION AREA STYLES
        ========================================================================= */
        .header-profile { 
            background: #111111; 
            color: white; 
            padding: 120px 8%; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }

        .membership-badge {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #d4af37;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: 600;
            margin-left: 10px;
            color: #fff;
        }

        .metrics-bar { 
            background: #1C1C1C; 
            display: flex; 
            padding: 20px 8%; 
            border-top: 1px solid #2a2a2a; 
            justify-content: space-around; 
            text-align: center; 
            flex-wrap: wrap;
            gap: 15px;
        }

        .metric-box { 
            color: #aaaaaa; 
            font-size: 12px; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-box strong { 
            display: block; 
            font-size: 24px; 
            color: white; 
            margin-top: 4px; 
            font-family: 'Playfair Display', serif;
        }
        
        .main-content { 
            max-width: 1200px; 
            margin: 40px auto; 
            padding: 0 8%; 
        }

        .main-content h3 {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            color: #111;
            margin-bottom: 25px;
        }

        .reservation-card { 
            background: white; 
            border-radius: 8px; 
            padding: 24px; 
            margin-bottom: 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.02); 
            border-left: 5px solid #d4af37; 
            transition: transform 0.2s ease;
        }

        .reservation-card:hover {
            transform: translateY(-2px);
        }

        .reservation-card.status-cancelled { 
            border-left-color: #e74c3c; 
            opacity: 0.75; 
        }
        
        .badge { 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 11px; 
            font-weight: 600; 
            text-transform: uppercase;
            display: inline-block;
            margin-left: 8px;
        }

        .badge.confirmed { background: #E8F8F5; color: #1E8449; }
        .badge.cancelled { background: #FDEDEC; color: #CB4335; }
        .badge.pending { background: #FEF9E7; color: #D4AC0D; }
        
        .btn-cancel { 
            background: transparent; 
            border: 1px solid #E74C3C; 
            color: #E74C3C; 
            padding: 8px 18px; 
            border-radius: 20px; 
            cursor: pointer; 
            font-size: 13px;
            font-weight: 500;
            transition: 0.2s; 
        }

        .btn-cancel:hover { 
            background: #E74C3C; 
            color: white; 
        }

        .btn-new-booking {
            background: #d4af37; 
            border: none; 
            padding: 12px 24px; 
            color: white; 
            border-radius: 25px; 
            font-weight: 600; 
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            transition: background 0.2s ease;
        }

        .btn-new-booking:hover {
            background: #b8932e;
        }
        
        /* Interactive Confirmation Alert Modal Styling */
        .modal-overlay { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.5); 
            justify-content: center; 
            align-items: center; 
            z-index: 2000;
        }

        .modal-box { 
            background: white; 
            padding: 35px; 
            border-radius: 12px; 
            max-width: 420px; 
            text-align: center; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .modal-actions { 
            display: flex; 
            justify-content: center; 
            gap: 15px;
            margin-top: 25px; 
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

    <div class="header-profile">
        <div>
            <h2 style="margin:0; font-weight:normal; font-family: 'Playfair Display', serif; font-size: 28px;">
                Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> 
                <span class="membership-badge"><?php echo htmlspecialchars($_SESSION['membership_tier'] ?? 'Regular'); ?> Member</span>
            </h2>
            <p style="margin:6px 0 0 0; color:#999; font-size:14px;"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
        </div>
        <a href="book.php" class="btn-new-booking">+ New Booking</a>
    </div>

    <div class="metrics-bar">
        <div class="metric-box">Total Stays <strong><?php echo $stats['total_stays'] ?? 0; ?></strong></div>
        <div class="metric-box">Confirmed <strong><?php echo $stats['confirmed'] ?? 0; ?></strong></div>
        <div class="metric-box">Cancelled <strong><?php echo $stats['cancelled'] ?? 0; ?></strong></div>
        <div class="metric-box">Pending <strong><?php echo $stats['pending'] ?? 0; ?></strong></div>
        <div class="metric-box">Total Spent <strong>$<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></strong></div>
    </div>

    <div class="main-content">
        <h3>My Reservations</h3>
        
        <?php if ($bookings_result->num_rows > 0): ?>
            <?php while($row = $bookings_result->fetch_assoc()): ?>
                <div class="reservation-card <?php echo ($row['status'] == 'Cancelled') ? 'status-cancelled' : ''; ?>">
                    <div>
                        <h4 style="margin:0 0 8px 0; color:#2C3E50; font-size: 18px;">
                            <?php echo htmlspecialchars($row['room_type']); ?> 
                            <span class="badge <?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span>
                        </h4>
                        <p style="margin:4px 0; font-size:13px; color:#555;">Booking Reference: <strong style="color: #111;"><?php echo $row['booking_reference']; ?></strong></p>
                        <p style="margin:4px 0; font-size:13px; color:#7F8C8D;">
                            Duration: <strong><?php echo date('M d, Y', strtotime($row['check_in'])); ?></strong> to <strong><?php echo date('M d, Y', strtotime($row['check_out'])); ?></strong>
                        </p>
                        <p style="margin:4px 0; font-size:13px; color:#7F8C8D;">Party Size: <?php echo $row['guests']; ?> Guests</p>
                        
                        <?php if(!empty($row['special_requests'])): ?>
                            <p style="margin:12px 0 0 0; font-size:12px; background:#Fdfbfa; border: 1px dashed #f2eade; padding:8px; font-style:italic; border-radius:6px; color: #555;">
                                "<?php echo htmlspecialchars($row['special_requests']); ?>"
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div style="text-align: right; min-width: 160px;">
                        <span style="display:block; font-size:22px; font-weight:600; color:#d4af37; margin-bottom:12px; font-family: 'Playfair Display', serif;">
                            $<?php echo number_format($row['total_price'], 2); ?>
                        </span>
                        <?php if($row['status'] == 'Confirmed' || $row['status'] == 'Pending'): ?>
                            <button type="button" class="btn-cancel" onclick="triggerCancellationModal(<?php echo $row['id']; ?>)">Cancel Booking</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align:center; padding:60px 20px; background: white; border-radius: 8px; border: 1px dashed #e6ded2;">
                <p style="color:#888; font-size:15px; margin-bottom: 20px;">No reservation history discovered for your profile account entry.</p>
                <a href="book.php" class="btn-new-booking" style="display: inline-block;">Make Your First Booking</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal-overlay" id="cancelModal">
        <div class="modal-box">
            <h3 style="margin-top:0; color:#2C3E50; font-family: 'Playfair Display', serif; font-size: 22px;">Cancel Reservation?</h3>
            <p style="color:#7F8C8D; font-size:14px; margin-top: 10px; line-height: 1.5;">Are you sure you want to cancel this reservation? This action updates your metrics profile parameters inside phpMyAdmin.</p>
            <form action="dashboard.php" method="POST">
                <input type="hidden" name="action" value="cancel_reservation">
                <input type="hidden" name="booking_id" id="modalBookingId" value="">
                <div class="modal-actions">
                    <button type="button" style="padding:10px 22px; border:1px solid #ccc; background:white; border-radius:20px; cursor:pointer; font-size:13px; font-weight:500;" onclick="closeCancellationModal()">Keep Booking</button>
                    <button type="submit" style="padding:10px 22px; background:#E74C3C; border:none; color:white; border-radius:20px; cursor:pointer; font-weight:600; font-size:13px;">Confirm Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('cancelModal');
        const modalIdInput = document.getElementById('modalBookingId');

        function triggerCancellationModal(id) {
            modalIdInput.value = id;
            modal.style.display = 'flex';
        }

        function closeCancellationModal() {
            modal.style.display = 'none';
        }

        // Close modal clicking anywhere outside box container
        window.onclick = function(event) {
            if (event.target == modal) {
                closeCancellationModal();
            }
        }

        // Profile Click Dropdown Management Trigger
        document.addEventListener("DOMContentLoaded", function() {
            const dropdownBtn = document.getElementById("profileDropdownBtn");
            const dropdownMenu = document.getElementById("profileDropdownMenu");

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
    </script>
</body>
</html>
<?php 
$bookings_result->close();
$stmt_bookings->close();
$conn->close(); 
?>