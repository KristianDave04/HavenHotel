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

// --------------------------------------------------------
// CONTROLLER: PROCESSING ADMINISTRATIVE WRITE ACTIONS (CRUD)
// --------------------------------------------------------

// Action: Update Booking Reservation Status
if (isset($_POST['update_status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $new_status = $_POST['status_value'];
    if (in_array($new_status, ['Confirmed', 'Cancelled', 'Pending'])) {
        $stmt = $conn->prepare("UPDATE bookings SET booking_status = ? WHERE booking_id = ?");
        $stmt->bind_param("si", $new_status, $booking_id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_dashboard.php?tab=preservation&success=1");
        exit();
    }
}

// Action: Delete Testimonial Entry
if (isset($_POST['delete_testimonial'])) {
    $testimonial_id = (int)$_POST['testimonial_id'];
    $stmt = $conn->prepare("DELETE FROM testimonials WHERE id = ?");
    $stmt->bind_param("i", $testimonial_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php?tab=review&deleted=1");
    exit();
}

// Action: Insert New Room Unit Configuration into Schema & Push Event Notice
if (isset($_POST['add_new_room'])) {
    $room_number = strip_tags(trim($_POST['room_number']));
    $room_type   = strip_tags(trim($_POST['room_type']));
    $price       = (float)$_POST['price_per_night'];
    $status      = $_POST['room_status'];
    $description = strip_tags(trim($_POST['description']));

    $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_type, price_per_night, status, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdss", $room_number, $room_type, $price, $status, $description);
    
    if ($stmt->execute()) {
        // Construct notification log alert entry for user system monitoring
        $msg = "New Room Deployed: Room " . $room_number . " (" . $room_type . ") is now available for reservation at ₱" . number_format($price, 2) . "!";
        $notif = $conn->prepare("INSERT INTO notifications (message) VALUES (?)");
        $notif->bind_param("s", $msg);
        $notif->execute();
        $notif->close();
    }
    $stmt->close();
    header("Location: admin_dashboard.php?tab=rooms&added=1");
    exit();
}

// Action: Save Modifications to Existing Room Unit Properties & Push Event Notice
if (isset($_POST['edit_existing_room'])) {
    $room_id     = (int)$_POST['room_id'];
    $room_number = strip_tags(trim($_POST['room_number']));
    $room_type   = strip_tags(trim($_POST['room_type']));
    $price       = (float)$_POST['price_per_night'];
    $status      = $_POST['room_status'];
    $description = strip_tags(trim($_POST['description']));

    $stmt = $conn->prepare("UPDATE rooms SET room_number=?, room_type=?, price_per_night=?, status=?, description=? WHERE room_id=?");
    $stmt->bind_param("ssdssi", $room_number, $room_type, $price, $status, $description, $room_id);
    
    if ($stmt->execute()) {
        // Construct detailed parameter modification message summary trace
        $msg = "Room Configuration Altered: Desk modified properties for Room " . $room_number . " (Price: ₱" . number_format($price) . " | Status: " . $status . ").";
        $notif = $conn->prepare("INSERT INTO notifications (message) VALUES (?)");
        $notif->bind_param("s", $msg);
        $notif->execute();
        $notif->close();
    }
    $stmt->close();
    header("Location: admin_dashboard.php?tab=rooms&updated=1");
    exit();
}

// --------------------------------------------------------
// DATA AGGREGATION & ANALYTICS PIPELINES
// --------------------------------------------------------
$user_roles_query = $conn->query("SELECT COUNT(CASE WHEN role = 'user' THEN 1 END) as users_count, COUNT(CASE WHEN role = 'admin' THEN 1 END) as admins_count FROM users");
$roles_metrics = $user_roles_query->fetch_assoc();

$bookings_metrics_query = $conn->query("SELECT COUNT(*) as total_bookings, COUNT(CASE WHEN booking_status = 'Pending' THEN 1 END) as pending_bookings, COUNT(CASE WHEN booking_status = 'Confirmed' THEN 1 END) as confirmed_bookings, COUNT(CASE WHEN booking_status = 'Cancelled' THEN 1 END) as cancelled_bookings FROM bookings");
$bookings_metrics = $bookings_metrics_query->fetch_assoc();

$global_ledger = [];
$ledger_query = $conn->query("SELECT b.*, CONCAT(u.first_name, ' ', u.last_name) AS guest_name, u.user_email AS guest_email, u.phone AS guest_phone, b.special_requests FROM bookings b LEFT JOIN users u ON b.user_id = u.id ORDER BY b.created_at DESC");
while ($row = $ledger_query->fetch_assoc()) { $global_ledger[] = $row; }

$admin_reviews_list = [];
$admin_reviews_query = $conn->query("SELECT t.*, CONCAT(u.first_name, ' ', u.last_name) AS guest_name, u.user_email AS guest_email FROM testimonials t LEFT JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC");
while ($r = $admin_reviews_query->fetch_assoc()) { $admin_reviews_list[] = $r; }

// Core structural link mapping targeting custom configuration schemas
$system_rooms_list = [];
$rooms_query = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");
while ($rm = $rooms_query->fetch_assoc()) { $system_rooms_list[] = $rm; }

$active_tab = $_GET['tab'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Control Dashboard - Haven Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f4f6f9; color: #1e293b; display: flex; min-height: 100vh; }
        
        aside.admin-sidebar { width: 260px; background: #0f172a; color: white; display: flex; flex-direction: column; padding: 30px 0; position: fixed; height: 100vh; z-index: 100; }
        .sidebar-brand { padding: 0 24px 30px 24px; font-size: 20px; font-weight: 700; border-bottom: 1px solid #1e293b; letter-spacing: 0.5px; }
        .sidebar-brand span { color: #c69c4f; }
        .sidebar-menu { list-style: none; margin-top: 24px; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 14px; padding: 14px 24px; color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: 500; border-left: 4px solid transparent; transition: all 0.2s; cursor: pointer; }
        .sidebar-menu li a:hover, .sidebar-menu li.active-tab a { color: white; background: #1e293b; border-left-color: #c69c4f; }
        
        main.admin-stage { margin-left: 260px; flex: 1; padding: 40px; }
        header.stage-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }
        header.stage-header h1 { font-size: 26px; font-weight: 700; color: #0f172a; }
        
        .tab-panel-view { display: none; }
        .tab-panel-view.active-view { display: block; }

        .analytics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 35px; }
        .analytic-card { background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 16px; }
        .card-icon-frame { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .icon-blue { background: #eff6ff; color: #2563eb; }
        .icon-gold { background: #fef9c3; color: #ca8a04; }
        .icon-green { background: #f0fdf4; color: #16a34a; }
        .icon-red { background: #fef2f2; color: #dc2626; }
        .icon-purple { background: #faf5ff; color: #9333ea; }
        .card-meta-box h3 { font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 4px; }
        .card-meta-box .num-val { font-size: 22px; font-weight: 700; color: #0f172a; }

        .charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-top: 30px; }
        .chart-box-container { background: white; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .mock-bar-graph { display: flex; align-items: flex-end; justify-content: space-between; height: 200px; border-bottom: 2px solid #e2e8f0; }
        .graph-pillar { flex: 1; margin: 0 16px; background: #e2e8f0; border-radius: 4px 4px 0 0; position: relative; min-height: 10px; }
        .pillar-fill-pending { background: #ca8a04; }
        .pillar-fill-confirmed { background: #16a34a; }
        .pillar-fill-cancelled { background: #dc2626; }
        .graph-pillar .pillar-tooltip { position: absolute; top: -30px; left: 50%; transform: translateX(-50%); background: #0f172a; color: white; font-size: 11px; padding: 4px 8px; border-radius: 4px; }

        .table-card-wrapper { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
        table.data-ledger-table { width: 100%; border-collapse: collapse; }
        table.data-ledger-table th, table.data-ledger-table td { padding: 14px 20px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        table.data-ledger-table th { background: #f8fafc; font-size: 11px; text-transform: uppercase; font-weight: 600; color: #475569; }
        
        .status-pill { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .pill-pending, .pill-limited { background: #fef3c7; color: #d97706; }
        .pill-confirmed, .pill-available { background: #dcfce7; color: #15803d; }
        .pill-cancelled, .pill-not_available { background: #fee2e2; color: #b91c1c; }

        .control-btn { padding: 8px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-info { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
        .btn-success { background: #10b981; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-primary { background: #c69c4f; color: white; }

        .modal-overlay-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 1000; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.2s ease; }
        .modal-overlay-backdrop.active-modal { opacity: 1; pointer-events: auto; }
        .modal-box-frame { background: white; width: 100%; max-width: 500px; border-radius: 12px; overflow: hidden; padding: 24px; }
        
        .form-input-node { margin-bottom: 14px; }
        .form-input-node label { display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px; }
        .form-input-node input, .form-input-node select, .form-input-node textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 14px; }

        .reviews-panel-masonry { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .admin-review-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; }
    </style>
</head>
<body>

<aside class="admin-sidebar">
    <div class="sidebar-brand">Haven<span>Hotel</span></div>
    <ul class="sidebar-menu">
        <li class="<?= $active_tab === 'home' ? 'active-tab' : '' ?>" data-target="panel_home"><a onclick="switchTab('home')"><i class="fa-solid fa-chart-pie"></i> Dashboard Hub</a></li>
        <li class="<?= $active_tab === 'preservation' ? 'active-tab' : '' ?>" data-target="panel_preservation"><a onclick="switchTab('preservation')"><i class="fa-solid fa-bed"></i> Preservation</a></li>
        <li class="<?= $active_tab === 'rooms' ? 'active-tab' : '' ?>" data-target="panel_rooms"><a onclick="switchTab('rooms')"><i class="fa-solid fa-door-open"></i> Control Panel</a></li>
        <li class="<?= $active_tab === 'review' ? 'active-tab' : '' ?>" data-target="panel_review"><a onclick="switchTab('review')"><i class="fa-solid fa-star"></i> Guest Reviews</a></li>
        <li style="margin-top: 40px; border-top: 1px solid #1e293b; padding-top: 10px;"><a href="index.php" style="color: #ef4444;"><i class="fa-solid fa-right-from-bracket"></i> Exit System</a></li>
    </ul>
</aside>

<main class="admin-stage">
    
    <div id="panel_home" class="tab-panel-view <?= $active_tab === 'home' ? 'active-view' : '' ?>">
        <header class="stage-header"><h1>System Metrics Hub</h1></header>
        <div class="analytics-grid">
            <div class="analytic-card">
                <div class="card-icon-frame icon-blue"><i class="fa-solid fa-users"></i></div>
                <div class="card-meta-box"><h3>Active Users</h3><div class="num-val"><?= $roles_metrics['users_count'] ?></div></div>
            </div>
            <div class="analytic-card">
                <div class="card-icon-frame icon-purple"><i class="fa-solid fa-user-shield"></i></div>
                <div class="card-meta-box"><h3>Admin Staff</h3><div class="num-val"><?= $roles_metrics['admins_count'] ?></div></div>
            </div>
            <div class="analytic-card">
                <div class="card-icon-frame icon-green"><i class="fa-solid fa-book"></i></div>
                <div class="card-meta-box"><h3>Bookings</h3><div class="num-val"><?= $bookings_metrics['total_bookings'] ?></div></div>
            </div>
            <div class="analytic-card">
                <div class="card-icon-frame icon-gold"><i class="fa-solid fa-clock"></i></div>
                <div class="card-meta-box"><h3>Pendings</h3><div class="num-val"><?= $bookings_metrics['pending_bookings'] ?></div></div>
            </div>
            <div class="analytic-card">
                <div class="card-icon-frame icon-red"><i class="fa-solid fa-circle-xmark"></i></div>
                <div class="card-meta-box"><h3>Canceled</h3><div class="num-val"><?= $bookings_metrics['cancelled_bookings'] ?></div></div>
            </div>
        </div>

        <div class="charts-row">
            <div class="chart-box-container">
                <h3>Reservation Matrix Ratios</h3>
                <div class="mock-bar-graph">
                    <?php $max = max(1, $bookings_metrics['pending_bookings'], $bookings_metrics['confirmed_bookings'], $bookings_metrics['cancelled_bookings']); ?>
                    <div class="graph-pillar pillar-fill-pending" style="height: <?= ($bookings_metrics['pending_bookings']/$max)*100 ?>%;"><div class="pillar-tooltip"><?= $bookings_metrics['pending_bookings'] ?></div></div>
                    <div class="graph-pillar pillar-fill-confirmed" style="height: <?= ($bookings_metrics['confirmed_bookings']/$max)*100 ?>%;"><div class="pillar-tooltip"><?= $bookings_metrics['confirmed_bookings'] ?></div></div>
                    <div class="graph-pillar pillar-fill-cancelled" style="height: <?= ($bookings_metrics['cancelled_bookings']/$max)*100 ?>%;"><div class="pillar-tooltip"><?= $bookings_metrics['cancelled_bookings'] ?></div></div>
                </div>
                <div style="display:flex; justify-content:space-between; margin-top:10px;">
                    <span>Pending Checkouts</span><span>Confirmed Arrivals</span><span>Cancelled Slates</span>
                </div>
            </div>
            <div class="chart-box-container" style="text-align:center; display:flex; flex-direction:column; justify-content:center;">
                <h3>Success Ratio</h3>
                <div style="font-size:36px; font-weight:700; color:#16a34a;">
                    <?= $bookings_metrics['total_bookings'] > 0 ? round(($bookings_metrics['confirmed_bookings'] / $bookings_metrics['total_bookings']) * 100, 1) : 0 ?>%
                </div>
            </div>
        </div>
    </div>

    <div id="panel_preservation" class="tab-panel-view <?= $active_tab === 'preservation' ? 'active-view' : '' ?>">
        <header class="stage-header"><h1>Reservations Ledger Panel</h1></header>
        <div class="table-card-wrapper">
            <table class="data-ledger-table">
                <thead>
                    <tr>
                        <th>Ref Number</th>
                        <th>Guest Name</th>
                        <th>Accommodation Target</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($global_ledger as $b): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($b['booking_reference']) ?></strong></td>
                            <td><?= htmlspecialchars($b['guest_name']) ?></td>
                            <td><?= htmlspecialchars($b['room_type']) ?></td>
                            <td><span class="status-pill pill-<?= strtolower($b['booking_status']) ?>"><?= $b['booking_status'] ?></span></td>
                            <td style="text-align:right;">
                                <button class="control-btn btn-info" onclick='displayBookingDetails(<?= json_encode($b) ?>)'>Details</button>
                                <?php if ($b['booking_status'] === 'Pending'): ?>
                                    <form method="POST" style="display:inline;"><input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>"><input type="hidden" name="status_value" value="Confirmed"><button type="submit" name="update_status" class="control-btn btn-success">Approve</button></form>
                                    <form method="POST" style="display:inline;"><input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>"><input type="hidden" name="status_value" value="Cancelled"><button type="submit" name="update_status" class="control-btn btn-danger">Cancel</button></form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="panel_rooms" class="tab-panel-view <?= $active_tab === 'rooms' ? 'active-view' : '' ?>">
        <header class="stage-header">
            <h1>Hotel Inventory Manager</h1>
            <button class="control-btn btn-primary" onclick="openRoomModal()"><i class="fa-solid fa-plus"></i> Initialize Room Type</button>
        </header>
        
        <div class="table-card-wrapper">
            <table class="data-ledger-table">
                <thead>
                    <tr>
                        <th>Room Number</th>
                        <th>Room Type Label</th>
                        <th>Features Description</th>
                        <th>Price Per Night</th>
                        <th>Status</th>
                        <th style="text-align:right;">Modification Controls</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($system_rooms_list as $room): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($room['room_number']) ?></strong></td>
                            <td><?= htmlspecialchars($room['room_type']) ?></td>
                            <td><small style="color:#64748b;"><?= htmlspecialchars($room['description'] ?? 'No descriptions registered.') ?></small></td>
                            <td><strong>₱<?= number_format($room['price_per_night'], 2) ?></strong></td>
                            <td><span class="status-pill pill-<?= strtolower(str_replace(' ', '_', $room['status'])) ?>"><?= $room['status'] ?></span></td>
                            <td style="text-align:right;">
                                <button class="control-btn btn-info" onclick='openRoomModal(<?= json_encode($room) ?>)'><i class="fa-solid fa-pen-to-square"></i> Edit Properties</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="panel_review" class="tab-panel-view <?= $active_tab === 'review' ? 'active-view' : '' ?>">
        <header class="stage-header"><h1>Guest Feedback Matrix</h1></header>
        <div class="reviews-panel-masonry">
            <?php foreach ($admin_reviews_list as $rev): ?>
                <div class="admin-review-card">
                    <div style="color:#eab308; margin-bottom:8px;">
                        <?php for($i=1; $i<=5; $i++) echo ($i <= $rev['rating']) ? '★' : '☆'; ?>
                    </div>
                    <p style="font-style:italic; font-size:14px; margin-bottom:12px;">"<?= htmlspecialchars($rev['review_text']) ?>"</p>
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:12px; color:#64748b;">
                        <div><strong><?= htmlspecialchars($rev['guest_name']) ?></strong></div>
                        <form method="POST" onsubmit="return confirm('Permanently drop feedback item?');" style="margin-left:auto;">
                            <input type="hidden" name="testimonial_id" value="<?= $rev['id'] ?>">
                            <button type="submit" name="delete_testimonial" class="control-btn btn-danger" style="padding:4px 8px; font-size:11px;">Purge</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<div class="modal-overlay-backdrop" id="global_booking_details_modal">
    <div class="modal-box-frame">
        <h2 id="m_ref_id" style="margin-bottom:16px;">BKG-CODE</h2>
        <div style="font-size:14px; display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px;">
            <div><label style="color:#64748b; font-size:11px; display:block;">GUEST</label><span id="m_name"></span></div>
            <div><label style="color:#64748b; font-size:11px; display:block;">EMAIL</label><span id="m_email"></span></div>
            <div><label style="color:#64748b; font-size:11px; display:block;">PHONE</label><span id="m_phone"></span></div>
            <div><label style="color:#64748b; font-size:11px; display:block;">RATE CHARGED</label><span id="m_total" style="color:#16a34a; font-weight:700;"></span></div>
        </div>
        <button class="control-btn btn-danger" style="width:100%; justify-content:center;" onclick="closeModal('global_booking_details_modal')">Dismiss View</button>
    </div>
</div>

<div class="modal-overlay-backdrop" id="room_editor_modal">
    <div class="modal-box-frame" style="max-width:440px;">
        <h2 id="room_modal_title" style="margin-bottom:20px;">Initialize Room Unit</h2>
        <form method="POST" action="admin_dashboard.php">
            <input type="hidden" name="room_id" id="form_room_id">
            <div class="form-input-node"><label>Room Number *</label><input type="text" name="room_number" id="form_room_number" required placeholder="e.g. 101"></div>
            <div class="form-input-node"><label>Room Type Class Label *</label><input type="text" name="room_type" id="form_room_type" placeholder="e.g. Deluxe Suite" required></div>
            <div class="form-input-node"><label>Base Operational Cost Per Night (₱) *</label><input type="number" step="0.01" name="price_per_night" id="form_price_per_night" required></div>
            <div class="form-input-node">
                <label>Inventory Operational Status *</label>
                <select name="room_status" id="form_room_status">
                    <option value="Available">Available</option>
                    <option value="Limited">Limited</option>
                    <option value="Not Available">Not Available</option>
                </select>
            </div>
            <div class="form-input-node"><label>Room Features / Description Specification</label><textarea name="description" id="form_description" rows="3"></textarea></div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="button" class="control-btn btn-info" style="flex:1; justify-content:center;" onclick="closeModal('room_editor_modal')">Cancel</button>
                <button type="submit" id="room_submit_action_btn" name="add_new_room" class="control-btn btn-success" style="flex:1; justify-content:center;">Save Unit Layout</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(t) {
    document.querySelectorAll('.tab-panel-view').forEach(p => p.classList.remove('active-view'));
    document.querySelectorAll('.sidebar-menu li').forEach(l => l.classList.remove('active-tab'));
    document.getElementById('panel_' + t).classList.add('active-view');
    const match = document.querySelector(`.sidebar-menu li[data-target="panel_${t}"]`);
    if(match) match.classList.add('active-tab');
    window.history.replaceState(null, null, 'admin_dashboard.php?tab=' + t);
}

function displayBookingDetails(data) {
    document.getElementById('m_ref_id').innerText = data.booking_reference;
    document.getElementById('m_name').innerText = data.guest_name;
    document.getElementById('m_email').innerText = data.guest_email ?? 'N/A';
    document.getElementById('m_phone').innerText = data.guest_phone ?? 'N/A';
    document.getElementById('m_total').innerText = "₱" + parseFloat(data.total_price).toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById('global_booking_details_modal').classList.add('active-modal');
}

function openRoomModal(room = null) {
    const title = document.getElementById('room_modal_title');
    const btn = document.getElementById('room_submit_action_btn');
    
    if(room) {
        title.innerText = "Modify Room Parameters";
        btn.name = "edit_existing_room";
        btn.innerText = "Commit Modifications";
        document.getElementById('form_room_id').value = room.room_id;
        document.getElementById('form_room_number').value = room.room_number;
        document.getElementById('form_room_type').value = room.room_type;
        document.getElementById('form_price_per_night').value = room.price_per_night;
        document.getElementById('form_room_status').value = room.status;
        document.getElementById('form_description').value = room.description ?? "";
    } else {
        title.innerText = "Initialize New Room Unit";
        btn.name = "add_new_room";
        btn.innerText = "Deploy Inventory";
        document.getElementById('form_room_id').value = "";
        document.getElementById('form_room_number').value = "";
        document.getElementById('form_room_type').value = "";
        document.getElementById('form_price_per_night').value = "";
        document.getElementById('form_room_status').value = "Available";
        document.getElementById('form_description').value = "";
    }
    document.getElementById('room_editor_modal').classList.add('active-modal');
}

function closeModal(id) { document.getElementById(id).classList.remove('active-modal'); }
</script>
</body>
</html>
<?php $conn->close(); ?>
