<?php
session_start();

// Database Configuration Parameters
$host     = "localhost";
$db_user  = "root";
$db_pass  = "";
$db_name  = "haven_hotel";
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Database Connection Error: " . $conn->connect_error);
}

// Redirect if the guest session profile is not established yet
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// LIVE NOTIFICATIONS ENGINE PIPELINE
$unread_notifications_count = 0;
if (isset($_SESSION['user_id'])) {
    $u_id_notif = $_SESSION['user_id'];
    $notif_stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
    $notif_stmt->bind_param("i", $u_id_notif);
    $notif_stmt->execute();
    $notif_res = $notif_stmt->get_result()->fetch_assoc();
    $unread_notifications_count = $notif_res['unread_count'] ?? 0;
    $notif_stmt->close();
}

// MASTER IMAGE POOL: 20 High-Quality Unique Room Images Shared Globally
$room_image_pool = [
    "https://images.unsplash.com/photo-1611892440504-42a792e24d32?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1590490360182-c33d57733427?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1566665797739-1674de7a421a?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1578683010236-d716f9a3f461?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1631049307264-da0ec9d70304?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1512918728675-ed5a9ecdebfd?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1445019980597-93fa8acb246c?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1522771739844-6a9f6d7f748c?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1595576508898-0ad5c879a061?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1590073844006-3337978788c6?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1598928506311-c55ded91a20c?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1618773928121-c37242e47177?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1629140727571-9b5c6f6267b4?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1579640212006-29d1e8284534?q=80&w=600&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1560661879-16a5d4828135?q=80&w=600&auto=format&fit=crop"
];

// --------------------------------------------------------
// DATA PIPELINE: ASSEMBLE ACTIVE REAL-TIME ROOM INVENTORY
// --------------------------------------------------------
$rooms = [];
$rooms_fetch_result = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");

if ($rooms_fetch_result && $rooms_fetch_result->num_rows > 0) {
    while ($r = $rooms_fetch_result->fetch_assoc()) {
        // AUTOMATED EXACT ALIGNMENT MATCH LOGIC VIA MODULO
        $image_index = (int)$r['room_id'] % count($room_image_pool);
        $assigned_image = $room_image_pool[$image_index];

        $rooms[$r['room_id']] = [
            "name"      => "Room " . $r['room_number'] . " (" . $r['room_type'] . ")",
            "price"     => (float)$r['price_per_night'],
            "status"    => $r['status'] ?? 'Available', // FIXED: Handles missing column warning safely
            "desc"      => $r['description'] ?? "Standard premium accommodations.",
            "image"     => $assigned_image
        ];
    }
}

// --------------------------------------------------------
// CONTROLLER: PROCESS MULTI-STEP RESERVATION STAGES
// --------------------------------------------------------
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        if (isset($_POST['room_id']) && array_key_exists($_POST['room_id'], $rooms)) {
            if ($rooms[$_POST['room_id']]['status'] === 'Not Available') {
                $error_msg = "Selected accommodation is currently undergoing maintenance. Please select another room.";
            } else {
                $_SESSION['booking_room_id'] = (int)$_POST['room_id'];
                header("Location: book.php?step=2");
                exit();
            }
        } else {
            $error_msg = "Please select a valid room from the dynamic terminal grid options to continue.";
        }
    } elseif ($step === 2) {
        $check_in  = trim($_POST['check_in_date'] ?? '');
        $check_out = trim($_POST['check_out_date'] ?? '');
        $requests  = strip_tags(trim($_POST['special_requests'] ?? ''));

        if (!empty($check_in) && !empty($check_out) && ($check_out > $check_in)) {
            if ($check_in >= date('Y-m-d')) {
                $_SESSION['booking_check_in']  = $check_in;
                $_SESSION['booking_check_out'] = $check_out;
                $_SESSION['booking_requests']  = $requests;
                
                header("Location: book.php?step=3");
                exit();
            } else {
                $error_msg = "Check-In dates cannot be historical days prior to the current date.";
            }
        } else {
            $error_msg = "Invalid range parameters. Check-out must follow your selected check-in arrival date.";
        }
    } elseif ($step === 3) {
        if (isset($_SESSION['booking_room_id'], $_SESSION['booking_check_in'], $_SESSION['booking_check_out'])) {
            
            $u_id      = $_SESSION['user_id'];
            $r_id      = $_SESSION['booking_room_id'];
            $c_in      = $_SESSION['booking_check_in'];
            $c_out     = $_SESSION['booking_check_out'];
            $requests  = $_SESSION['booking_requests'] ?? '';
            
            $days_span = (strtotime($c_out) - strtotime($c_in)) / 86400;
            $total_cost = $days_span * $rooms[$r_id]['price'];
            $ref_code   = "HVN-" . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
            $initial_status = "Pending";
            
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, room_id, room_type, check_in_date, check_out_date, total_price, booking_status, booking_reference, special_requests) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssdsss", $u_id, $r_id, $rooms[$r_id]['name'], $c_in, $c_out, $total_cost, $initial_status, $ref_code, $requests);
            
            if ($stmt->execute()) {
                // Generate Automated Notification Log for the secure transaction sequence
                $notif_msg = "Your booking request reference code " . $ref_code . " was compiled successfully and is pending approval.";
                $notif_insert = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                $notif_insert->bind_param("is", $u_id, $notif_msg);
                $notif_insert->execute();
                $notif_insert->close();

                unset($_SESSION['booking_room_id'], $_SESSION['booking_check_in'], $_SESSION['booking_check_out'], $_SESSION['booking_requests']);
                header("Location: book.php?step=4&ref=" . $ref_code);
                exit();
            } else {
                $error_msg = "Processing pipeline failure. Please retry verification review or call the front desk.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Accommodations - Haven Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="ui/book.css">
</head>
<body>

<nav class="nav-bar-frame">
    <div style="display: flex; align-items: center;">
        <a href="index.php" class="nav-brand" style="margin-right:30px;">Haven<span>Hotel</span></a>
    </div>
    
    <div style="display: flex; align-items: center;">
        <a href="dashboard.php" class="nav-exit-link"><i class="fa-solid fa-circle-arrow-left"></i> Return to Profile</a>
    </div>
</nav>

<div class="booking-flow-container">

    <div class="flow-stepper-track">
        <div class="step-node <?= $step === 1 ? 'active-node' : ($step > 1 ? 'completed-node' : '') ?>">
            <div class="step-num"><?= $step > 1 ? '<i class="fa-solid fa-check"></i>' : '1' ?></div> Choose Accommodation
        </div>
        <div class="step-node <?= $step === 2 ? 'active-node' : ($step > 2 ? 'completed-node' : '') ?>">
            <div class="step-num"><?= $step > 2 ? '<i class="fa-solid fa-check"></i>' : '2' ?></div> Reservation Timeline
        </div>
        <div class="step-node <?= $step === 3 ? 'active-node' : ($step > 3 ? 'completed-node' : '') ?>">
            <div class="step-num"><?= $step > 3 ? '<i class="fa-solid fa-check"></i>' : '3' ?></div> Review Details
        </div>
        <div class="step-node <?= $step === 4 ? 'active-node' : '' ?>">
            <div class="step-num">4</div> Confirmed Receipt
        </div>
    </div>

    <?php if (!empty($error_msg)): ?>
        <div class="alert-error-banner"><i class="fa-solid fa-circle-exclamation"></i> <span><?= htmlspecialchars($error_msg) ?></span></div>
    <?php endif; ?>

    <div class="view-content-card">

        <?php if ($step === 1): ?>
            <div class="view-title-group">
                <h2>Select Preferred Room</h2>
                <p>Browse through our collection of premium, modern hotel units tailored to fit your preferences.</p>
            </div>
            
            <form method="POST" id="step_one_form">
                <input type="hidden" name="room_id" id="selected_room_id_input" value="<?= $_SESSION['booking_room_id'] ?? '' ?>">
                
                <div class="room-grid-mesh">
                    <?php foreach ($rooms as $id => $rm): 
                        $isSel = (isset($_SESSION['booking_room_id']) && $_SESSION['booking_room_id'] == $id) ? 'card-selected' : '';
                        $isDisabled = ($rm['status'] === 'Not Available') ? 'disabled-inventory' : '';
                    ?>
                        <div class="room-unit-card <?= $isSel ?> <?= $isDisabled ?>" data-id="<?= $id ?>" onclick="selectRoomCard(this, '<?= $rm['status'] ?>')">
                            
                            <div class="room-card-image-box">
                                <img src="<?= htmlspecialchars($rm['image']) ?>" alt="<?= htmlspecialchars($rm['name']) ?>">
                            </div>

                            <div style="padding:20px; flex:1; display:flex; flex-direction:column; justify-content:space-between;">
                                <div>
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                                        <h3 style="font-size:15px; font-weight:700; color:#0f172a;"><?= htmlspecialchars($rm['name']) ?></h3>
                                        <span class="status-pill <?= strtolower(str_replace(' ', '_', $rm['status'])) ?>"><?= $rm['status'] ?></span>
                                    </div>
                                    <p style="font-size:12px; color:#64748b; margin-bottom:16px; min-height:36px; display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical; overflow:hidden;">
                                        <?= htmlspecialchars($rm['desc']) ?>
                                    </p>
                                </div>
                                <div style="font-size:18px; font-weight:700; color:#c69c4f; border-top:1px solid #f1f5f9; padding-top:10px; margin-top:4px;">
                                    ₱<?= number_format($rm['price'], 2) ?> <span style="font-size:11px; font-weight:400; color:#94a3b8;">/ night</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="control-action-row" style="justify-content: flex-end;">
                    <button type="submit" class="btn-action btn-gold">Continue To Timeline <i class="fa-solid fa-arrow-right"></i></button>
                </div>
            </form>

            <script>
                function selectRoomCard(element, status) {
                    if (status === 'Not Available') return;
                    document.querySelectorAll('.room-unit-card').forEach(c => c.classList.remove('card-selected'));
                    element.classList.add('card-selected');
                    document.getElementById('selected_room_id_input').value = element.getAttribute('data-id');
                }
            </script>

        <?php elseif ($step === 2): ?>
            <div class="view-title-group">
                <h2>Reservation Schedule</h2>
                <p>Define your stay boundaries. Check-in entries operate from 2:00 PM onwards daily.</p>
            </div>

            <form method="POST">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div class="form-input-block">
                        <label><i class="fa-solid fa-calendar-import"></i> Check-In Date Target</label>
                        <input type="date" name="check_in_date" required min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_SESSION['booking_check_in'] ?? '') ?>">
                    </div>
                    <div class="form-input-block">
                        <label><i class="fa-solid fa-calendar-export"></i> Check-Out Date Target</label>
                        <input type="date" name="check_out_date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= htmlspecialchars($_SESSION['booking_check_out'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-input-block" style="margin-top:10px;">
                    <label>Special Arrangements / Requirements Requests</label>
                    <textarea name="special_requests" rows="4" placeholder="Let us know if you need airport shuttle services, extra bedding, or specific room accessibility configurations..."><?= htmlspecialchars($_SESSION['booking_requests'] ?? '') ?></textarea>
                </div>

                <div class="control-action-row">
                    <a href="book.php?step=1" class="btn-action btn-muted"><i class="fa-solid fa-arrow-left"></i> Change Room</a>
                    <button type="submit" class="btn-action btn-gold">Generate Invoice Review <i class="fa-solid fa-arrow-right"></i></button>
                </div>
            </form>

        <?php elseif ($step === 3): 
            $target_id = $_SESSION['booking_room_id'];
            $days = (strtotime($_SESSION['booking_check_out']) - strtotime($_SESSION['booking_check_in'])) / 86400;
            $gross_total = $days * $rooms[$target_id]['price'];
        ?>
            <div class="view-title-group">
                <h2>Verify Transaction Invoice</h2>
                <p>Review the calculated metrics allocation parameters thoroughly before finalizing your booking.</p>
            </div>

            <div class="review-manifest-box">
                <div class="manifest-line"><span>Selected Accommodation Suite Unit</span><strong><?= htmlspecialchars($rooms[$target_id]['name']) ?></strong></div>
                <div class="manifest-line"><span>Base Inventory Operating Night Cost</span><strong>₱<?= number_format($rooms[$target_id]['price'], 2) ?></strong></div>
                <div class="manifest-line"><span>Allocated Stay Span Duration</span>strong><?= $days ?> Night(s)</strong></div>
                <div class="manifest-line"><span>Check-In Arrival Timeline Target</span><strong><?= date('F j, Y', strtotime($_SESSION['booking_check_in'])) ?></strong></div>
                <div class="manifest-line"><span>Check-Out Clearance Timeline Target</span><strong><?= date('F j, Y', strtotime($_SESSION['booking_check_out'])) ?></strong></div>
                <?php if(!empty($_SESSION['booking_requests'])): ?>
                    <div class="manifest-line"><span style="display:block; margin-bottom:4px;">Special Request Notes Logged</span><em style="font-size:12px; color:#64748b;">"<?= htmlspecialchars($_SESSION['booking_requests']) ?>"</em></div>
                <?php endif; ?>
                <div class="manifest-line"><span>Total Balance Due</span><span style="color:#c69c4f; font-size:20px;">₱<?= number_format($gross_total, 2) ?></span></div>
            </div>

            <form method="POST">
                <div class="control-action-row">
                    <a href="book.php?step=2" class="btn-action btn-muted"><i class="fa-solid fa-arrow-left"></i> Adjust Parameters</a>
                    <button type="submit" class="btn-action btn-gold"><i class="fa-solid fa-credit-card"></i> Finalize Secure Booking</button>
                </div>
            </form>

        <?php elseif ($step === 4): ?>
            <div style="text-align:center; padding:20px 0;">
                <div style="width:70px; height:70px; background:#dcfce7; color:#10b981; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:32px; margin:0 auto 24px auto;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h2 style="font-size:26px; font-weight:700; color:#0f172a; margin-bottom:8px;">Reservation Logged Successfully!</h2>
                <p style="color:#64748b; font-size:15px; max-width:480px; margin:0 auto 30px auto;">
                    Your booking reference key sequence is <strong style="color:#0f172a; font-family:monospace; background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:16px;"><?= htmlspecialchars($_GET['ref'] ?? 'N/A') ?></strong>. The desk management team will review your timeline parameters immediately.
                </p>
                <div style="display:flex; justify-content:center; gap:15px;">
                    <a href="dashboard.php" class="btn-action btn-muted"><i class="fa-solid fa-house"></i> Go to Dashboard</a>
                    <a href="book.php?step=1" class="btn-action btn-gold"><i class="fa-solid fa-calendar-plus"></i> Book Another Stay</a>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
<?php $conn->close(); ?>
