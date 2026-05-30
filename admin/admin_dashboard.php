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

// MASTER IMAGE POOL: 20 High-Quality Room Visuals
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

// CONTROLLER: PROCESSING ADMINISTRATIVE WRITE ACTIONS (CRUD)

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

// Action: Delete Room Unit
if (isset($_POST['delete_room'])) {
    $room_id = (int)$_POST['room_id'];
    
    // Fetch room number for notification ledger before deletion
    $stmt_info = $conn->prepare("SELECT room_number FROM rooms WHERE room_id = ?");
    $stmt_info->bind_param("i", $room_id);
    $stmt_info->execute();
    $res = $stmt_info->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $r_num = $row['room_number'];
        $msg = "Room Purged: Room " . $r_num . " has been permanently removed from inventory.";
        
        $notif = $conn->prepare("INSERT INTO notifications (message) VALUES (?)");
        $notif->bind_param("s", $msg);
        $notif->execute();
        $notif->close();
    }
    $stmt_info->close();
    
    // Execute standard wipe query
    $stmt = $conn->prepare("DELETE FROM rooms WHERE room_id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php?tab=rooms&deleted=1");
    exit();
}

// Action: Insert New Room Unit Configuration with RANDOM IMAGE GENERATION & Push Notice
if (isset($_POST['add_new_room'])) {
    $room_number = strip_tags(trim($_POST['room_number']));
    $room_type   = strip_tags(trim($_POST['room_type']));
    $price       = (float)$_POST['price_per_night'];
    $status      = $_POST['room_status'];
    $description = strip_tags(trim($_POST['description']));
    
    // Pick a random image from the pool
    $random_image = $room_image_pool[array_rand($room_image_pool)];
    
    $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_type, price_per_night, status, description, image_url) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsss", $room_number, $room_type, $price, $status, $description, $random_image);
    
    if ($stmt->execute()) {
        $msg = "New Room Deployed: Room " . $room_number . " (" . $room_type . ") is now available at ₱" . number_format($price, 2) . "!";
        
        $notif = $conn->prepare("INSERT INTO notifications (message) VALUES (?)");
        $notif->bind_param("s", $msg);
        $notif->execute();
        $notif->close();
        
        $stmt->close();
        header("Location: admin_dashboard.php?tab=rooms&added=1");
        exit();
    }
}

// Action: Save Modifications to Existing Room Unit Properties & Push Notice
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
        $msg = "Room Altered: Desk modified properties for Room " . $room_number . " (Price: ₱" . number_format($price, 2) . " | Status: " . $status . ").";
        
        $notif = $conn->prepare("INSERT INTO notifications (message) VALUES (?)");
        $notif->bind_param("s", $msg);
        $notif->execute();
        $notif->close();
    }
    $stmt->close();
    header("Location: admin_dashboard.php?tab=rooms&updated=1");
    exit();
}

// DATA AGGREGATION & ANALYTICS PIPELINES
$user_roles_query = $conn->query("SELECT COUNT(CASE WHEN role = 'user' THEN 1 END) as users_count, COUNT(CASE WHEN role = 'admin' THEN 1 END) as admins_count FROM users");
$roles_metrics = $user_roles_query->fetch_assoc();

$bookings_metrics_query = $conn->query("SELECT COUNT(*) as total_bookings, COUNT(CASE WHEN booking_status = 'Pending' THEN 1 END) as pending_bookings, COUNT(CASE WHEN booking_status = 'Confirmed' THEN 1 END) as confirmed_bookings, COUNT(CASE WHEN booking_status = 'Cancelled' THEN 1 END) as cancelled_bookings FROM bookings");
$bookings_metrics = $bookings_metrics_query->fetch_assoc();

$global_ledger = [];
$ledger_query = $conn->query("SELECT b.*, CONCAT(u.first_name, ' ', u.last_name) AS guest_name, u.user_email AS guest_email, u.phone AS guest_phone, b.special_requests FROM bookings b LEFT JOIN users u ON b.user_id = u.id ORDER BY b.created_at DESC");
while ($row = $ledger_query->fetch_assoc()) { 
    $global_ledger[] = $row; 
}

$admin_reviews_list = [];
$admin_reviews_query = $conn->query("SELECT t.*, CONCAT(u.first_name, ' ', u.last_name) AS guest_name, u.user_email AS guest_email FROM testimonials t LEFT JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC");
while ($r = $admin_reviews_query->fetch_assoc()) { 
    $admin_reviews_list[] = $r; 
}

$system_rooms_list = [];
$rooms_query = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");
while ($rm = $rooms_query->fetch_assoc()) { 
    $system_rooms_list[] = $rm; 
}

$all_users_list = [];
$u_lookup_query = $conn->query("SELECT first_name, last_name, user_email, role, created_at FROM users ORDER BY created_at DESC");
if ($u_lookup_query) {
    while ($usr = $u_lookup_query->fetch_assoc()) { 
        $all_users_list[] = $usr; 
    }
}

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
    <link rel="stylesheet" href="./ui.admin/a.dashboard.css">
</head>
<body>
 
 <aside class="admin-sidebar">
     <div class="sidebar-brand">Haven<span>Hotel</span></div>
     <ul class="sidebar-menu">
         <li class="<?= $active_tab === 'home' ? 'active-tab' : '' ?>" data-target="panel_home">
             <a onclick="switchTab('home')"><i class="fa-solid fa-chart-pie"></i> Dashboard Hub</a>
         </li>
         <li class="<?= $active_tab === 'preservation' ? 'active-tab' : '' ?>" data-target="panel_preservation">
             <a onclick="switchTab('preservation')"><i class="fa-solid fa-bed"></i> Bookings Ledger</a>
         </li>
         <li class="<?= $active_tab === 'rooms' ? 'active-tab' : '' ?>" data-target="panel_rooms">
             <a onclick="switchTab('rooms')"><i class="fa-solid fa-door-open"></i> Control Panel</a>
         </li>
         <li class="<?= $active_tab === 'review' ? 'active-tab' : '' ?>" data-target="panel_review">
             <a onclick="switchTab('review')"><i class="fa-solid fa-star"></i> Guest Reviews</a>
         </li>
         <li style="margin-top: 40px; border-top: 1px solid #1e293b; padding-top: 10px;">
             <a href="admin_login.php" style="color: #ef4444;"><i class="fa-solid fa-right-from-bracket"></i> Exit System</a>
         </li>
     </ul>
 </aside>
 
 <main class="admin-stage">
     
     <div id="panel_home" class="tab-panel-view <?= $active_tab === 'home' ? 'active-view' : '' ?>">
         <header class="stage-header"><h1>System Metrics Hub</h1></header>
         
         <div class="analytics-grid">
             <div class="analytic-card" onclick="openSystemInspectionView('active_users')">
                 <div class="card-icon-frame icon-blue"><i class="fa-solid fa-users"></i></div>
                 <div class="card-meta-box"><h3>Active Users</h3><div class="num-val"><?= $roles_metrics['users_count'] ?></div></div>
             </div>
             <div class="analytic-card" onclick="openSystemInspectionView('admin_staff')">
                 <div class="card-icon-frame icon-purple"><i class="fa-solid fa-user-shield"></i></div>
                 <div class="card-meta-box"><h3>Admin Staff</h3><div class="num-val"><?= $roles_metrics['admins_count'] ?></div></div>
             </div>
             <div class="analytic-card" onclick="openSystemInspectionView('bookings')">
                 <div class="card-icon-frame icon-green"><i class="fa-solid fa-book"></i></div>
                 <div class="card-meta-box"><h3>Bookings</h3><div class="num-val"><?= $bookings_metrics['total_bookings'] ?></div></div>
             </div>
             <div class="analytic-card" onclick="openSystemInspectionView('pendings')">
                 <div class="card-icon-frame icon-gold"><i class="fa-solid fa-clock"></i></div>
                 <div class="card-meta-box"><h3>Pendings</h3><div class="num-val"><?= $bookings_metrics['pending_bookings'] ?></div></div>
             </div>
             <div class="analytic-card" onclick="openSystemInspectionView('canceled')">
                 <div class="card-icon-frame icon-red"><i class="fa-solid fa-circle-xmark"></i></div>
                 <div class="card-meta-box"><h3>Canceled</h3><div class="num-val"><?= $bookings_metrics['cancelled_bookings'] ?></div></div>
             </div>
         </div>
         
         <div class="charts-row">
             <div class="chart-box-container" style="display: flex; flex-direction: column; justify-content: space-between;">
                 <h3 style="margin-bottom: 5px;">Reservation & Account Metric System</h3>
                 <div class="mock-bar-graph">
                     <?php
                     $max_val = max(1, $roles_metrics['users_count'], $roles_metrics['admins_count'], $bookings_metrics['pending_bookings'], $bookings_metrics['confirmed_bookings'], $bookings_metrics['cancelled_bookings']);
                     ?>
                     <div class="graph-column-group">
                         <div class="graph-pillar pillar-fill-user" style="height: <?= ($roles_metrics['users_count']/$max_val)*100 ?>%;">
                             <div class="pillar-tooltip">Users: <?= $roles_metrics['users_count'] ?></div>
                         </div>
                         <div class="graph-pillar pillar-fill-admin" style="height: <?= ($roles_metrics['admins_count']/$max_val)*100 ?>%;">
                             <div class="pillar-tooltip">Admins: <?= $roles_metrics['admins_count'] ?></div>
                         </div>
                     </div>
                     <div class="graph-column-group">
                         <div class="graph-pillar pillar-fill-pending" style="height: <?= ($bookings_metrics['pending_bookings']/$max_val)*100 ?>%;">
                             <div class="pillar-tooltip">Pending: <?= $bookings_metrics['pending_bookings'] ?></div>
                         </div>
                         <div class="graph-pillar pillar-fill-confirmed" style="height: <?= ($bookings_metrics['confirmed_bookings']/$max_val)*100 ?>%;">
                             <div class="pillar-tooltip">Confirmed: <?= $bookings_metrics['confirmed_bookings'] ?></div>
                         </div>
                         <div class="graph-pillar pillar-fill-cancelled" style="height: <?= ($bookings_metrics['cancelled_bookings']/$max_val)*100 ?>%;">
                             <div class="pillar-tooltip">Cancelled: <?= $bookings_metrics['cancelled_bookings'] ?></div>
                         </div>
                     </div>
                 </div>
                 
                 <div style="display: flex; justify-content: space-around; margin-top: 15px; font-size: 11px; font-weight: 600; color: #64748b; text-align: center;">
                     <div style="flex: 1; max-width: 140px; display: flex; flex-direction: column; gap: 4px;">
                         <span style="color: #0f172a;">Account Deployments</span>
                         <small style="font-weight: 400; font-size: 10px;">(Joined Users vs Staff)</small>
                     </div>
                     <div style="flex: 1; max-width: 140px; display: flex; flex-direction: column; gap: 4px;">
                         <span style="color: #0f172a;">Operational Invoices</span>
                         <small style="font-weight: 400; font-size: 10px;">(Pending/Arrived/Voided)</small>
                     </div>
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
                                     <form method="POST" style="display:inline;">
                                         <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
                                         <input type="hidden" name="status_value" value="Confirmed">
                                         <button type="submit" name="update_status" class="control-btn btn-success">Approve</button>
                                     </form>
                                     <form method="POST" style="display:inline;">
                                         <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
                                         <input type="hidden" name="status_value" value="Cancelled">
                                         <button type="submit" name="update_status" class="control-btn btn-danger">Cancel</button>
                                     </form>
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
                         <th>Visual</th>
                         <th>Room Number</th>
                         <th>Room Type Label</th>
                         <th>Price Per Night</th>
                         <th>Status</th>
                         <th style="text-align:right;">Modification Controls</th>
                     </tr>
                 </thead>
                 <tbody>
                     <?php foreach ($system_rooms_list as $room):
                         $img_src = !empty($room['image_url']) ? $room['image_url'] : 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?q=80&w=200';
                     ?>
                         <tr>
                             <td><img src="<?= htmlspecialchars($img_src) ?>" style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></td>
                             <td><strong><?= htmlspecialchars($room['room_number']) ?></strong></td>
                             <td>
                                 <?= htmlspecialchars($room['room_type']) ?><br>
                                 <small style="color:#64748b; font-size:11px;"><?= htmlspecialchars(substr($room['description'] ?? 'No descriptions registered.', 0, 40)) ?>...</small>
                             </td>
                             <td><strong>₱<?= number_format($room['price_per_night'], 2) ?></strong></td>
                             <td><span class="status-pill pill-<?= strtolower(str_replace(' ', '_', $room['status'])) ?>"><?= $room['status'] ?></span></td>
                             <td style="text-align:right;">
                                 <button class="control-btn btn-info" onclick='openRoomModal(<?= json_encode($room) ?>)'><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                                 <form method="POST" style="display:inline;" onsubmit="return confirm('WARNING: Are you sure you want to permanently delete Room <?= htmlspecialchars($room['room_number']) ?>? This action cannot be undone.');">
                                     <input type="hidden" name="room_id" value="<?= $room['room_id'] ?>">
                                     <button type="submit" name="delete_room" class="control-btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
                                 </form>
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
 
 <div class="modal-overlay-backdrop" id="system_analytics_inspection_modal">
     <div class="modal-box-frame" style="max-width: 520px;">
         <h2 id="inspect_modal_title" style="margin-bottom: 6px; font-size: 18px; color: #0f172a;">Content Inventory Ledger</h2>
         <p id="inspect_modal_desc" style="font-size: 13px; color: #64748b; margin-bottom: 16px;">System data collection subset snapshot.</p>
         <div class="inspect-modal-list-box" id="inspect_modal_dynamic_content_target"></div>
         <div style="display: flex; gap: 10px;">
             <button id="inspect_modal_route_btn" class="control-btn btn-primary" style="flex: 1; justify-content: center;">Go to Master Ledger</button>
             <button class="control-btn btn-info" style="flex: 1; justify-content: center;" onclick="closeModal('system_analytics_inspection_modal')">Close View</button>
         </div>
     </div>
 </div>
 
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
             <div class="form-input-node">
                 <label>Room Number *</label>
                 <input type="text" name="room_number" id="form_room_number" required placeholder="e.g. 101">
             </div>
             <div class="form-input-node">
                 <label>Room Type Class Label *</label>
                 <input type="text" name="room_type" id="form_room_type" placeholder="e.g. Deluxe Suite" required>
             </div>
             <div class="form-input-node">
                 <label>Base Operational Cost Per Night (₱) *</label>
                 <input type="number" step="0.01" name="price_per_night" id="form_price_per_night" required>
             </div>
             <div class="form-input-node">
                 <label>Inventory Operational Status *</label>
                 <select name="room_status" id="form_room_status">
                     <option value="Available">Available</option>
                     <option value="Limited">Limited</option>
                     <option value="Not Available">Not Available</option>
                 </select>
             </div>
             <div class="form-input-node">
                 <label>Room Features / Description Specification</label>
                 <textarea name="description" id="form_description" rows="3"></textarea>
             </div>
             <div style="display:flex; gap:10px; margin-top:20px;">
                 <button type="button" class="control-btn btn-info" style="flex:1; justify-content:center;" onclick="closeModal('room_editor_modal')">Cancel</button>
                 <button type="submit" id="room_submit_action_btn" name="add_new_room" class="control-btn btn-success" style="flex:1; justify-content:center;">Save Unit Layout</button>
             </div>
         </form>
     </div>
 </div>
 
 <script>
 // JSON Encoding internal script datasets for localized modal parsing engine queries
 const globalUsersDataset = <?= json_encode($all_users_list); ?>;
 const globalBookingsDataset = <?= json_encode($global_ledger); ?>;
 
 function switchTab(t) {
     document.querySelectorAll('.tab-panel-view').forEach(p => p.classList.remove('active-view'));
     document.querySelectorAll('.sidebar-menu li').forEach(l => l.classList.remove('active-tab'));
     
     document.getElementById('panel_' + t).classList.add('active-view');
     const match = document.querySelector(`.sidebar-menu li[data-target="panel_${t}"]`);
     if(match) match.classList.add('active-tab');
     
     window.history.replaceState(null, null, 'admin_dashboard.php?tab=' + t);
 }
 
 function openSystemInspectionView(metricKey) {
     const titleNode = document.getElementById('inspect_modal_title');
     const descNode = document.getElementById('inspect_modal_desc');
     const targetNode = document.getElementById('inspect_modal_dynamic_content_target');
     const routeBtn = document.getElementById('inspect_modal_route_btn');
     
     targetNode.innerHTML = "";
     let collectionHTML = "";
     
     if (metricKey === 'active_users' || metricKey === 'admin_staff') {
         const targetRole = (metricKey === 'active_users') ? 'user' : 'admin';
         titleNode.innerText = (metricKey === 'active_users') ? "Active Registered Guest Accounts" : "System Administrative Staff Profiles";
         descNode.innerText = "Viewing registered entities sorted by registration entry date.";
         
         const filteredUsers = globalUsersDataset.filter(u => u.role === targetRole);
         if(filteredUsers.length === 0) {
             collectionHTML = `<div class='inspect-list-item' style='color:#64748b;'>No verified accounts tracked in this role directory.</div>`;
         } else {
             filteredUsers.forEach(u => {
                 collectionHTML += `
                 <div class='inspect-list-item'>
                     <div>
                         <strong>${u.first_name} ${u.last_name}</strong><br>
                         <small style='color:#64748b;'>${u.user_email}</small>
                     </div>
                     <div style='font-size:11px; color:#94a3b8;'>Joined: ${u.created_at ? u.created_at.substring(0,10) : 'N/A'}</div>
                 </div>`;
             });
         }
         routeBtn.style.display = "none";
     } else {
         // Bookings Ledger Filtering Matrix
         routeBtn.style.display = "inline-flex";
         routeBtn.onclick = function() {
             closeModal('system_analytics_inspection_modal');
             switchTab('preservation');
         };
         
         let filteredInvoices = globalBookingsDataset;
         
         if(metricKey === 'bookings') {
             titleNode.innerText = "Complete Operational Reservation Manifest";
             descNode.innerText = "Overview logs of all system-wide reservation entries.";
         } else if(metricKey === 'pendings') {
             titleNode.innerText = "Awaiting Verification: Pending Vouchers";
             descNode.innerText = "Reservations flagged as Pending waiting management response.";
             filteredInvoices = globalBookingsDataset.filter(b => b.booking_status === 'Pending');
         } else if(metricKey === 'canceled') {
             titleNode.innerText = "Voided Logs: Cancelled Reservations";
             descNode.innerText = "Reservations processed as Cancelled or inactive slates.";
             filteredInvoices = globalBookingsDataset.filter(b => b.booking_status === 'Cancelled');
         }
         
         if(filteredInvoices.length === 0) {
             collectionHTML = `<div class='inspect-list-item' style='color:#64748b;'>No localized matched bookings records detected.</div>`;
         } else {
             filteredInvoices.forEach(b => {
                 collectionHTML += `
                 <div class='inspect-list-item'>
                     <div>
                         <strong>${b.booking_reference}</strong> - <small>${b.guest_name}</small><br>
                         <small style='color:#c69c4f; font-weight:600;'>${b.room_type}</small>
                     </div>
                     <div style='text-align:right;'>
                         <span class='status-pill pill-${b.booking_status.toLowerCase()}'>${b.booking_status}</span>
                     </div>
                 </div>`;
             });
         }
     }
     
     targetNode.innerHTML = collectionHTML;
     document.getElementById('system_analytics_inspection_modal').classList.add('active-modal');
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
 
 function closeModal(id) {
     document.getElementById(id).classList.remove('active-modal');
 }
 </script>
</body>
</html>
<?php $conn->close(); ?>
