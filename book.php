<?php
session_start();

// Strict Access Control Layer: User identity must be active in global application space
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. DATABASE CONFIGURATION
$host     = "localhost";
$db_user  = "root";
$db_pass  = "";
$db_name  = "haven_hotel";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Direct Database Connection to phpMyAdmin Failed: " . $conn->connect_error);
}

// Pull authenticated account parameters dynamically out of active session state context
$user_id      = $_SESSION['user_id'];
$user_name    = $_SESSION['user_name'];
$is_logged_in = true; 

// Handle back navigation step decrement logic routing
if (isset($_GET['back_to'])) {
    $back_step = (int)$_GET['back_to'];
    if ($back_step >= 1 && $back_step <= 4) {
        header("Location: book.php?step=" . $back_step);
        exit();
    }
}

$step = isset($_GET['step']) ? $_GET['step'] : '1';

// ROOM DATA SOURCE MODEL
$rooms = [
    1 => [
        "name"     => "Standard Room",
        "price"    => 149,
        "size"     => "25m²",
        "capacity" => "2",
        "status"   => "Available",
        "image"    => "https://images.unsplash.com/photo-1631049307264-da0ec9d70304?q=80&w=1200&auto=format&fit=crop"
    ],
    2 => [
        "name"     => "Deluxe Room",
        "price"    => 229,
        "size"     => "35m²",
        "capacity" => "2",
        "status"   => "Available",
        "image"    => "https://images.unsplash.com/photo-1590490360182-c33d57733427?q=80&w=1200&auto=format&fit=crop"
    ],
    3 => [
        "name"     => "Executive Room",
        "price"    => 319,
        "size"     => "42m²",
        "capacity" => "2",
        "status"   => "Limited",
        "image"    => "https://images.unsplash.com/photo-1566665797739-1674de7a421a?q=80&w=1200&auto=format&fit=crop"
    ],
    4 => [
        "name"     => "Junior Suite",
        "price"    => 459,
        "size"     => "55m²",
        "capacity" => "3",
        "status"   => "Available",
        "image"    => "https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=1200&auto=format&fit=crop"
    ],
    5 => [
        "name"     => "Presidential Suite",
        "price"    => 899,
        "size"     => "120m²",
        "capacity" => "4",
        "status"   => "Limited",
        "image"    => "https://images.unsplash.com/photo-1578683010236-d716f9a3f461?q=80&w=1200&auto=format&fit=crop"
    ]
];

// POST ROUTER ACTIONS MATRIX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_room'])) {
        $_SESSION['room_id'] = (int)$_POST['room_id'];
        header("Location: book.php?step=2");
        exit();
    }
    
    if (isset($_POST['save_dates'])) {
        $_SESSION['checkin']  = strip_tags($_POST['checkin']);
        $_SESSION['checkout'] = strip_tags($_POST['checkout']);
        $_SESSION['guests']   = (int)$_POST['guests'];
        header("Location: book.php?step=3");
        exit();
    }
    
    if (isset($_POST['save_details'])) {
        $_SESSION['firstname']        = strip_tags($_POST['firstname']);
        $_SESSION['lastname']         = strip_tags($_POST['lastname']);
        $_SESSION['email']            = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $_SESSION['phone']            = strip_tags($_POST['phone']);
        $_SESSION['special_requests'] = strip_tags($_POST['special_requests']);
        header("Location: book.php?step=4");
        exit();
    }
    
    if (isset($_POST['confirm_booking'])) {
        $room     = $rooms[$_SESSION['room_id']];
        $checkin  = new DateTime($_SESSION['checkin']);
        $checkout = new DateTime($_SESSION['checkout']);
        $nights   = $checkin->diff($checkout)->days;
        
        if ($nights <= 0) $nights = 1;
        $total = $nights * $room['price'];
        
        $booking_reference = "BKG-" . rand(10000000, 99999999);
        $room_name         = $room['name'];
        $requests          = $_SESSION['special_requests'];
        $guests            = (int)$_SESSION['guests'];
        
        $insert_stmt = $conn->prepare("INSERT INTO bookings (user_id, booking_reference, room_type, check_in, check_out, guests, special_requests, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed')");
        $insert_stmt->bind_param("issssssd", $user_id, $booking_reference, $room_name, $_SESSION['checkin'], $_SESSION['checkout'], $guests, $requests, $total);
        
        if ($insert_stmt->execute()) {
            $_SESSION['completed_receipt'] = [
                'reference' => $booking_reference,
                'room_type' => $room_name,
                'check_in'  => $_SESSION['checkin'],
                'check_out' => $_SESSION['checkout'],
                'total'     => $total
            ];
            
            unset($_SESSION['room_id'], $_SESSION['checkin'], $_SESSION['checkout'], $_SESSION['guests'], $_SESSION['firstname'], $_SESSION['lastname'], $_SESSION['email'], $_SESSION['phone'], $_SESSION['special_requests']);
            header("Location: book.php?step=success");
            exit();
        } else {
            die("Critical Write Database Exception Error: " . $insert_stmt->error);
        }
    }
}

$selected_room_data = null;
if (isset($_SESSION['room_id']) && isset($rooms[$_SESSION['room_id']])) {
    $selected_room_data = $rooms[$_SESSION['room_id']];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxury Booking Workspace</title>
    <link href="https://fonts.googleapis.com/css?family=Playfair+Display:ital,wght@0,400..700;1,400..700|Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800|Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="ui/book.css?v=<?= time(); ?>">
</head>
<body>
 <header class="navbar">
    <div class="logo">Haven<span>Hotel</span></div>
    <nav>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="index.php#rooms">Accommodations</a></li>
            <li><a href="index.php#booking">Booking</a></li>
            <li><a href="index.php#location">Location</a></li>
            <li><a href="index.php#contact">Contact</a></li>
        </ul>
    </nav>
    <div class="nav-actions-group">
        <a href="book.php" class="nav-btn active">Book Now</a>
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
                    <a href="dashboard.php" class="dropdown-item"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                    <a href="login.php" class="dropdown-item logout-action"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="guest-login-link"><i class="fa-solid fa-arrow-right-to-bracket"></i> Login</a>
        <?php endif; ?>
    </div>
 </header>
 
 <section class="booking-hero">
    <div class="overlay"></div>
    <div class="hero-content">
        <span>RESERVE YOUR STAY</span>
        <h1>Book Your Haven</h1>
    </div>
 </section>
 
 <?php if ($step !== 'success'): ?>
 <div class="wizard-timeline">
    <div class="timeline-node <?= $step >= 1 ? 'node-active' : '' ?> <?= $step > 1 ? 'node-complete' : '' ?>">
        <div class="node-circle"><?= $step > 1 ? '<i class="fa-solid fa-check"></i>' : '1' ?></div>
        <p>Room</p>
    </div>
    <div class="node-connector <?= $step >= 2 ? 'connector-active' : '' ?>"></div>
    <div class="timeline-node <?= $step >= 2 ? 'node-active' : '' ?> <?= $step > 2 ? 'node-complete' : '' ?>">
        <div class="node-circle"><?= $step > 2 ? '<i class="fa-solid fa-check"></i>' : '2' ?></div>
        <p>Dates</p>
    </div>
    <div class="node-connector <?= $step >= 3 ? 'connector-active' : '' ?>"></div>
    <div class="timeline-node <?= $step >= 3 ? 'node-active' : '' ?> <?= $step > 3 ? 'node-complete' : '' ?>">
        <div class="node-circle"><?= $step > 3 ? '<i class="fa-solid fa-check"></i>' : '3' ?></div>
        <p>Details</p>
    </div>
    <div class="node-connector <?= $step >= 4 ? 'connector-active' : '' ?>"></div>
    <div class="timeline-node <?= $step >= 4 ? 'node-active' : '' ?>">
        <div class="node-circle">4</div>
        <p>Confirm</p>
    </div>
 </div>
 <?php endif; ?>
 
 <div class="workspace-card">
    <?php if ($step == 1) { ?>
        <div class="workspace-title-head">
            <h1>Choose Your Room</h1>
            <p>Select the room type you'd like to book.</p>
        </div>
        <form method="POST" id="roomMeshSelectionForm">
            <input type="hidden" name="room_id" id="active_selected_room_id" value="<?= isset($_SESSION['room_id']) ? $_SESSION['room_id'] : '' ?>">
            <div class="room-grid-mesh">
                <?php foreach ($rooms as $id => $room) { 
                    $isSelected = (isset($_SESSION['room_id']) && $_SESSION['room_id'] == $id) ? 'card-selected' : ''; ?>
                    <div class="room-unit-card <?= $isSelected ?>" data-room-id="<?= $id ?>">
                        <div class="unit-img-frame">
                            <img src="<?= $room['image']; ?>" alt="<?= $room['name']; ?>">
                            <div class="unit-center-overlay-badge"><i class="fa-solid fa-check"></i></div>
                            <div class="unit-floating-price">$<?= $room['price']; ?>/night</div>
                        </div>
                        <div class="unit-content-box">
                            <div class="unit-header-line">
                                <h3><?= $room['name']; ?></h3>
                                <span class="unit-status-pill <?= strtolower($room['status']); ?>"><?= $room['status']; ?></span>
                            </div>
                            <div class="unit-spec-items">
                                <span><i class="fa-regular fa-square-minus" style="transform: rotate(90deg);"></i> <?= $room['size']; ?></span>
                                <span><i class="fa-regular fa-user"></i> Up to <?= $room['capacity']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <div class="workflow-action-footer">
                <button type="submit" name="save_room" id="step1ForwardBtn" class="workflow-forward-submit-btn" <?= isset($_SESSION['room_id']) ? '' : 'disabled' ?>>
                    Continue to Dates <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </form>
    <?php } ?>
 
    <?php if ($step == 2) { ?>
        <div class="workspace-title-head">
            <h1>Select Your Dates</h1>
            <p>Choose your check-in and check-out dates and number of guests.</p>
        </div>
        <?php if ($selected_room_data): ?>
            <div class="selected-room-mini-banner">
                <div class="mini-banner-thumb"><img src="<?= $selected_room_data['image'] ?>" alt="Preview"></div>
                <div class="mini-banner-details">
                    <h4><?= $selected_room_data['name'] ?></h4>
                    <p>$<?= $selected_room_data['price'] ?>/night</p>
                </div>
            </div>
        <?php endif; ?>
        <form method="POST" id="datesCalculationForm">
            <div class="fields-structural-grid">
                <div class="field-input-wrapper">
                    <label>Check-In Date <span>*</span></label>
                    <input type="date" name="checkin" id="checkin_date_picker" value="<?= isset($_SESSION['checkin']) ? $_SESSION['checkin'] : '' ?>" required>
                </div>
                <div class="field-input-wrapper">
                    <label>Check-Out Date <span>*</span></label>
                    <input type="date" name="checkout" id="checkout_date_picker" value="<?= isset($_SESSION['checkout']) ? $_SESSION['checkout'] : '' ?>" required>
                </div>
            </div>
            <div class="field-input-wrapper" style="margin-bottom: 10px;">
                <label>Number of Guests <span>*</span></label>
                <div style="display: flex; align-items: center;">
                    <div class="guest-stepper-control">
                        <button type="button" class="stepper-action-btn" id="stepper_decrement_trigger"><i class="fa-solid fa-minus"></i></button>
                        <div class="stepper-value-monitor" id="stepper_value_viewer"><?= isset($_SESSION['guests']) ? $_SESSION['guests'] : '1' ?></div>
                        <button type="button" class="stepper-action-btn" id="stepper_increment_trigger"><i class="fa-solid fa-plus"></i></button>
                    </div>
                    <input type="hidden" name="guests" id="structural_guest_hidden_input" value="<?= isset($_SESSION['guests']) ? $_SESSION['guests'] : '1' ?>">
                    <span class="stepper-hint-text">Max. <?= $selected_room_data ? $selected_room_data['capacity'] : '2' ?> guests for this room</span>
                </div>
            </div>
            <div class="live-receipt-summary-block" id="live_receipt_summary_block" style="display: none;">
                <div class="receipt-calculation-row">
                    <span id="receipt_multiplication_formula">$0 × 0 nights</span>
                    <span id="receipt_multiplication_product">$0</span>
                </div>
                <div class="receipt-grand-total-row">
                    <span>Estimated Total</span>
                    <div class="receipt-total-value" id="receipt_grand_total_display">$0</div>
                </div>
            </div>
            <div class="workflow-action-footer">
                <a href="book.php?back_to=1" class="workflow-back-link-btn"><i class="fa-solid fa-arrow-left"></i> Back</a>
                <button type="submit" name="save_dates" class="workflow-forward-submit-btn">
                    Continue to Details <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </form>
    <?php } ?>
 
    <?php if ($step == 3) { 
        $name_segments = explode(' ', $user_name);
        $extracted_first = $name_segments[0] ?? '';
        $extracted_last  = $name_segments[1] ?? '';
    ?>
        <div class="workspace-title-head">
            <h1>Your Details</h1>
            <p>Tell us a bit about you so we can prepare for your arrival.</p>
        </div>
        <form method="POST">
            <div class="fields-structural-grid">
                <div class="field-input-wrapper">
                    <label>First Name <span>*</span></label>
                    <input type="text" value="<?= htmlspecialchars($extracted_first) ?>" disabled style="background:#fafafa; color:#777; cursor:not-allowed;">
                    <input type="hidden" name="firstname" value="<?= htmlspecialchars($extracted_first) ?>">
                </div>
                <div class="field-input-wrapper">
                    <label>Last Name <span>*</span></label>
                    <input type="text" value="<?= htmlspecialchars($extracted_last) ?>" disabled style="background:#fafafa; color:#777; cursor:not-allowed;">
                    <input type="hidden" name="lastname" value="<?= htmlspecialchars($extracted_last) ?>">
                </div>
            </div>
            <div class="fields-structural-grid">
                <div class="field-input-wrapper">
                    <label>Email Address <span>*</span></label>
                    <input type="email" value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>" disabled style="background:#fafafa; color:#777; cursor:not-allowed;">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>">
                </div>
                <div class="field-input-wrapper">
                    <label>Phone Number <span>*</span></label>
                    <input type="text" name="phone" placeholder="Type your phone number..." value="<?= isset($_SESSION['phone']) ? htmlspecialchars($_SESSION['phone']) : '' ?>" required>
                </div>
            </div>
            <div class="field-input-wrapper">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label>Special Requests <span style="color:#a1a1a1; font-weight:normal;">(optional)</span></label>
                    <span style="font-size: 10px; color: #a1a1a1;" id="textarea_char_counter">0/500</span>
                </div>
                <textarea name="special_requests" id="special_requests_input" rows="4" maxlength="500" placeholder="Type instructions..."><?= isset($_SESSION['special_requests']) ? htmlspecialchars($_SESSION['special_requests']) : '' ?></textarea>
            </div>
            <div class="policy-disclaimer-callout" style="margin-top:24px; margin-bottom:10px; display:flex; gap:12px; align-items:center;">
                <i class="fa-solid fa-circle-info" style="color: #c69c4f; font-size:14px;"></i>
                <span>A confirmation email will be sent to your address. Our concierge team will contact you prior to arrival if needed.</span>
            </div>
            <div class="workflow-action-footer">
                <a href="book.php?back_to=2" class="workflow-back-link-btn"><i class="fa-solid fa-arrow-left"></i> Back</a>
                <button type="submit" name="save_details" class="workflow-forward-submit-btn">
                    Review Booking <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </form>
    <?php } ?>
 
    <?php if ($step == 4) {
        $room = $rooms[$_SESSION['room_id']];
        $checkin = new DateTime($_SESSION['checkin']);
        $checkout = new DateTime($_SESSION['checkout']);
        $nights = $checkin->diff($checkout)->days;
        if ($nights <= 0) $nights = 1;
        $total = $nights * $room['price'];
    ?>
        <div class="workspace-title-head">
            <h1>Review Your Booking</h1>
            <p>Please review your reservation details before confirming.</p>
        </div>
        <div class="review-quad-deck">
            <div class="review-quad-panel">
                <h3>Your Room</h3>
                <div class="quad-room-capsule">
                    <div class="quad-room-thumb"><img src="<?= $room['image'] ?>" alt="Room"></div>
                    <div class="quad-room-info">
                        <h4><?= $room['name'] ?></h4>
                        <p><i class="fa-regular fa-square-minus" style="transform: rotate(90deg);"></i> <?= $room['size'] ?> &nbsp;•&nbsp; <i class="fa-regular fa-user"></i> <?= $_SESSION['guests'] ?> guests</p>
                        <div class="quad-room-amenities">
                            <span>Free Wi-Fi</span>
                            <span>Flat-screen TV</span>
                            <span>Air Conditioning</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="review-quad-panel">
                <h3>Stay Dates</h3>
                <div class="quad-data-line-item" style="margin-top: 4px;">
                    <span class="data-label">Check-In</span>
                    <span class="data-value"><?= date('D, M d, Y', strtotime($_SESSION['checkin'])) ?></span>
                </div>
                <div class="quad-data-line-item">
                    <span class="data-label">Check-Out</span>
                    <span class="data-value"><?= date('D, M d, Y', strtotime($_SESSION['checkout'])) ?></span>
                </div>
                <div class="quad-data-line-item" style="border-top:1px dashed #f2eade; padding-top:10px; margin-top:10px;">
                    <span class="data-label">Duration</span>
                    <span class="data-value"><?= $nights ?> nights</span>
                </div>
            </div>
            <div class="review-quad-panel">
                <h3>Guest Information</h3>
                <div class="quad-data-line-item">
                    <span class="data-label">Name</span>
                    <span class="data-value"><?= htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']) ?></span>
                </div>
                <div class="quad-data-line-item">
                    <span class="data-label">Email</span>
                    <span class="data-value"><?= htmlspecialchars($_SESSION['email']) ?></span>
                </div>
                <div class="quad-data-line-item">
                    <span class="data-label">Phone</span>
                    <span class="data-value"><?= htmlspecialchars($_SESSION['phone']) ?></span>
                </div>
                <?php if (!empty($_SESSION['special_requests'])): ?>
                    <div class="quad-data-line-item" style="flex-direction:column; gap:4px; border-top:1px dashed #f2eade; padding-top:8px; margin-top:8px;">
                        <span class="data-label">Special Requests</span>
                        <span class="data-value" style="font-weight:normal; font-size:13px; color:#555;"><?= htmlspecialchars($_SESSION['special_requests']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="review-quad-panel" style="background-color: #141414; color:#fff; border:none;">
                <h3 style="color:#9c9c9c;">Price Summary</h3>
                <div class="quad-data-line-item">
                    <span class="data-label" style="color:#9c9c9c;">$<?= $room['price'] ?> × <?= $nights ?> nights</span>
                    <span class="data-value" style="color:#fff;">$<?= number_format($total) ?></span>
                </div>
                <div class="quad-data-line-item">
                    <span class="data-label" style="color:#9c9c9c;">Taxes & fees</span>
                    <span class="data-value" style="color:#fff; font-weight:normal; font-size:13px;">Included</span>
                </div>
                <div class="quad-data-line-item" style="border-top:1px solid #292929; padding-top:14px; margin-top:14px; align-items:center;">
                    <span class="data-label" style="color:#fff; font-weight:600;">Total</span>
                    <span class="data-value" style="color:#c69c4f; font-size:22px; font-weight:700;">$<?= number_format($total) ?></span>
                </div>
            </div>
        </div>
        <div class="policy-disclaimer-callout">
            <strong>Cancellation Policy:</strong> Free cancellation up to 48 hours before check-in. Late cancellations are subject to a one-night penalty. No-shows will be charged the full amount.
        </div>
        <form method="POST">
            <div class="workflow-action-footer">
                <a href="book.php?back_to=3" class="workflow-back-link-btn"><i class="fa-solid fa-arrow-left"></i> Back</a>
                <button type="submit" name="confirm_booking" class="workflow-forward-submit-btn" style="background-color:#c69c4f;">
                    <i class="fa-solid fa-check" style="font-size:12px; margin-right:2px;"></i> Confirm Reservation
                </button>
            </div>
        </form>
    <?php } ?>
 
    <?php if ($step === 'success' && isset($_SESSION['completed_receipt'])) { 
        $receipt = $_SESSION['completed_receipt'];
        $first_name_salutation = explode(' ', $user_name)[0]; 
    ?>
        <div style="text-align: center; padding: 20px 0;">
            <div class="success-badge-icon">
                <div class="success-badge-icon-check">✓</div>
            </div>
            <h1 style="font-family: 'Playfair Display', serif; font-size: 32px; font-weight:600; margin-bottom: 12px;">Reservation Confirmed!</h1>
            <p style="color: #666; font-size:13px; margin-bottom: 35px; max-width:460px; margin-left:auto; margin-right:auto; line-height:1.6;">
                Wonderful, <strong><?= htmlspecialchars(strtoupper($first_name_salutation)) ?></strong>! Your stay at Haven Hotel has been successfully reserved. A confirmation has been sent to your email.
            </p>
            <div class="ticket-box">
                <div class="ticket-header">
                    <div>Booking Reference</div>
                    <div class="ticket-ref-value"><?= htmlspecialchars($receipt['reference']) ?></div>
                </div>
                <div class="ticket-body">
                    <div class="ticket-row">
                        <span class="ticket-label">Room</span>
                        <span class="ticket-value"><?= htmlspecialchars($receipt['room_type']) ?></span>
                    </div>
                    <div class="ticket-row">
                        <span class="ticket-label">Check-In</span>
                        <span class="ticket-value"><?= date('D, M d, Y', strtotime($receipt['check_in'])) ?></span>
                    </div>
                    <div class="ticket-row">
                        <span class="ticket-label">Check-Out</span>
                        <span class="ticket-value"><?= date('D, M d, Y', strtotime($receipt['check_out'])) ?></span>
                    </div>
                    <div class="ticket-row" style="border: none; padding: 10px 0 0 0; align-items: center;">
                        <span class="ticket-label" style="font-weight: 700; color: #111;">Total Charged</span>
                        <span class="ticket-total-charged">$<?= number_format($receipt['total']) ?></span>
                    </div>
                </div>
            </div>
            <div class="success-footer-actions">
                <a href="dashboard.php" class="workflow-forward-submit-btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:8px; width:fit-content; float:none; background-color:#c69c4f;">
                    <i class="fa-regular fa-user" style="font-size: 13px;"></i> View My Bookings
                </a>
                <a href="index.php" class="workflow-back-link-btn" style="border: 1px solid #e6ded2; padding:12px 24px; border-radius:25px; text-decoration:none; color:#666; font-size:13px; font-weight:500;">Back to Home</a>
            </div>
        </div>
    <?php 
        unset($_SESSION['completed_receipt']);
    } ?>
 </div>
 
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

 document.addEventListener("DOMContentLoaded", () => {
    const roomCards = document.querySelectorAll(".room-unit-card");
    const activeRoomInput = document.getElementById("active_selected_room_id");
    const step1ForwardBtn = document.getElementById("step1ForwardBtn");
    
    roomCards.forEach(card => {
        card.addEventListener("click", () => {
            roomCards.forEach(c => c.classList.remove("card-selected"));
            card.classList.add("card-selected");
            if (activeRoomInput) activeRoomInput.value = card.dataset.roomId;
            if (step1ForwardBtn) step1ForwardBtn.disabled = false;
        });
    });

    const checkinInput = document.getElementById("checkin_date_picker");
    const checkoutInput = document.getElementById("checkout_date_picker");
    const liveReceiptBlock = document.getElementById("live_receipt_summary_block");
    const roomBaseNightPrice = <?= $selected_room_data ? $selected_room_data['price'] : 0 ?>;
    
    function evaluateBookingNightsReceipt() {
        if (!checkinInput || !checkoutInput || !liveReceiptBlock) return;
        const date1 = new Date(checkinInput.value);
        const date2 = new Date(checkoutInput.value);
        if (checkinInput.value && checkoutInput.value && date2 > date1) {
            const timeDifference = date2.getTime() - date1.getTime();
            const computationalNights = Math.ceil(timeDifference / (1000 * 3600 * 24));
            if (computationalNights > 0) {
                const totalCalculatedCost = computationalNights * roomBaseNightPrice;
                document.getElementById("receipt_multiplication_formula").innerText = `$${roomBaseNightPrice} × ${computationalNights} night${computationalNights > 1 ? 's' : ''}`;
                document.getElementById("receipt_multiplication_product").innerText = `$${totalCalculatedCost.toLocaleString()}`;
                document.getElementById("receipt_grand_total_display").innerText = `$${totalCalculatedCost.toLocaleString()}`;
                liveReceiptBlock.style.display = "block";
            } else {
                liveReceiptBlock.style.display = "none";
            }
        } else {
            liveReceiptBlock.style.display = "none";
        }
    }

    if (checkinInput && checkoutInput) {
        const structuralTodayString = new Date().toISOString().split("T")[0];
        if (!checkinInput.value) checkinInput.min = structuralTodayString;
        checkinInput.addEventListener("change", () => {
            checkoutInput.min = checkinInput.value;
            if (checkoutInput.value && checkoutInput.value < checkinInput.value) {
                checkoutInput.value = checkinInput.value;
            }
            evaluateBookingNightsReceipt();
        });
        checkoutInput.addEventListener("change", evaluateBookingNightsReceipt);
        evaluateBookingNightsReceipt();
    }

    const decBtn = document.getElementById("stepper_decrement_trigger");
    const incBtn = document.getElementById("stepper_increment_trigger");
    const stepperViewer = document.getElementById("stepper_value_viewer");
    const structuralHiddenInput = document.getElementById("structural_guest_hidden_input");
    const maxCapacityLimit = <?= $selected_room_data ? $selected_room_data['capacity'] : 4 ?>;
    
    if (decBtn && incBtn && stepperViewer && structuralHiddenInput) {
        decBtn.addEventListener("click", () => {
            let currentVal = parseInt(stepperViewer.innerText);
            if (currentVal > 1) {
                currentVal--;
                stepperViewer.innerText = currentVal;
                structuralHiddenInput.value = currentVal;
            }
        });
        incBtn.addEventListener("click", () => {
            let currentVal = parseInt(stepperViewer.innerText);
            if (currentVal < maxCapacityLimit) {
                currentVal++;
                stepperViewer.innerText = currentVal;
                structuralHiddenInput.value = currentVal;
            }
        });
    }

    const textRequestArea = document.getElementById("special_requests_input");
    const counterDisplayLabel = document.getElementById("textarea_char_counter");
    if (textRequestArea && counterDisplayLabel) {
        const processCounterUpdate = () => {
            counterDisplayLabel.innerText = `${textRequestArea.value.length}/500`;
        };
        textRequestArea.addEventListener("input", processCounterUpdate);
        processCounterUpdate();
    }
 });
 </script>
</body>
</html>
<?php $conn->close(); ?>