<?php
session_start();

$host     = "localhost";
$db_user  = "root";
$db_pass  = "";
$db_name  = "haven_hotel";
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection Error: " . $conn->connect_error);
}

// --------------------------------------------------------
// PROCESS CONTROLLER ACTIONS
// --------------------------------------------------------
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

if (isset($_POST['delete_testimonial'])) {
    $testimonial_id = (int)$_POST['testimonial_id'];
    $stmt = $conn->prepare("DELETE FROM testimonials WHERE id = ?");
    $stmt->bind_param("i", $testimonial_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php?tab=review&deleted=1");
    exit();
}

// --------------------------------------------------------
// AGGREGATE CORE METRICS (HOME TAB DATA)
// --------------------------------------------------------
// 1. Roles counts
$user_roles_query = $conn->query("SELECT COUNT(CASE WHEN role = 'user' THEN 1 END) as users_count, COUNT(CASE WHEN role = 'admin' THEN 1 END) as admins_count FROM users");
$roles_metrics = $user_roles_query->fetch_assoc();

// 2. Bookings segment breakdowns
$bookings_metrics_query = $conn->query("
    SELECT 
        COUNT(*) as total_bookings,
        COUNT(CASE WHEN booking_status = 'Pending' THEN 1 END) as pending_bookings,
        COUNT(CASE WHEN booking_status = 'Confirmed' THEN 1 END) as confirmed_bookings,
        COUNT(CASE WHEN booking_status = 'Cancelled' THEN 1 END) as cancelled_bookings
    FROM bookings
");
$bookings_metrics = $bookings_metrics_query->fetch_assoc();

// --------------------------------------------------------
// FETCH RESERVATIONS LEDGER (PRESERVATION DATA)
// --------------------------------------------------------
$global_ledger = [];
$ledger_query = $conn->query("
    SELECT b.*, CONCAT(u.first_name, ' ', u.last_name) AS guest_name, u.user_email AS guest_email, u.phone AS guest_phone, b.special_requests
    FROM bookings b LEFT JOIN users u ON b.user_id = u.id ORDER BY b.created_at DESC
");
while ($row = $ledger_query->fetch_assoc()) { $global_ledger[] = $row; }

// --------------------------------------------------------
// FETCH TESTIMONIALS DATA (REVIEW TAB DATA)
// --------------------------------------------------------
$admin_reviews_list = [];
$admin_reviews_query = $conn->query("
    SELECT t.*, CONCAT(u.first_name, ' ', u.last_name) AS guest_name, u.user_email AS guest_email 
    FROM testimonials t LEFT JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC
");
while ($r = $admin_reviews_query->fetch_assoc()) { $admin_reviews_list[] = $r; }

// Determine active view tab state on render redirect
$active_tab = $_GET['tab'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Control Dashboard - Haven Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f4f6f9; color: #1e293b; display: flex; min-height: 100vh; }
        
        /* Navigation Sidebar Styling */
        aside.admin-sidebar { width: 260px; background: #0f172a; color: white; display: flex; flex-direction: column; padding: 30px 0; position: fixed; height: 100vh; z-index: 100; }
        .sidebar-brand { padding: 0 24px 30px 24px; font-size: 20px; font-weight: 700; border-bottom: 1px solid #1e293b; letter-spacing: 0.5px; }
        .sidebar-brand span { color: #c69c4f; }
        .sidebar-menu { list-style: none; margin-top: 24px; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 14px; padding: 14px 24px; color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: 500; border-left: 4px solid transparent; transition: all 0.2s; cursor: pointer; }
        .sidebar-menu li a:hover, .sidebar-menu li.active-tab a { color: white; background: #1e293b; border-left-color: #c69c4f; }
        .sidebar-menu li a i { font-size: 16px; width: 20px; text-align: center; }

        /* Main Screen Layout Container */
        main.admin-stage { margin-left: 260px; flex: 1; padding: 40px; }
        header.stage-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }
        header.stage-header h1 { font-size: 26px; font-weight: 700; color: #0f172a; }
        
        /* Dynamic Multi-Tab Content Engine Panels */
        .tab-panel-view { display: none; }
        .tab-panel-view.active-view { display: block; }

        /* Dashboard Analytical Metrics Cards Grid Layout */
        .analytics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 35px; }
        .analytic-card { background: white; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01); }
        .card-icon-frame { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .icon-blue { background: #eff6ff; color: #2563eb; }
        .icon-gold { background: #fef9c3; color: #ca8a04; }
        .icon-green { background: #f0fdf4; color: #16a34a; }
        .icon-red { background: #fef2f2; color: #dc2626; }
        .icon-purple { background: #faf5ff; color: #9333ea; }
        .card-meta-box h3 { font-size: 12px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 4px; }
        .card-meta-box .num-val { font-size: 24px; font-weight: 700; color: #0f172a; }

        /* Visual Analytics Content Layer Rows */
        .charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-top: 30px; }
        .chart-box-container { background: white; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .chart-box-container h3 { font-size: 16px; font-weight: 600; margin-bottom: 20px; color: #0f172a; }
        .mock-bar-graph { display: flex; align-items: flex-end; justify-content: space-between; height: 200px; padding-top: 20px; border-bottom: 2px solid #e2e8f0; }
        .graph-pillar { flex: 1; margin: 0 12px; background: #e2e8f0; border-radius: 4px 4px 0 0; position: relative; min-height: 10px; transition: height 0.5s ease-in-out; }
        .pillar-fill-pending { background: #ca8a04; }
        .pillar-fill-confirmed { background: #16a34a; }
        .pillar-fill-cancelled { background: #dc2626; }
        .graph-pillar .pillar-tooltip { position: absolute; top: -30px; left: 50%; transform: translateX(-50%); background: #0f172a; color: white; font-size: 11px; padding: 4px 8px; border-radius: 4px; font-weight: 600; }
        .graph-label-footer { text-align: center; font-size: 12px; color: #64748b; margin-top: 8px; font-weight: 500; }

        /* Structural Ledger Data Grid Tables */
        .table-card-wrapper { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01); }
        table.data-ledger-table { width: 100%; border-collapse: collapse; }
        table.data-ledger-table th, table.data-ledger-table td { padding: 16px 24px; text-align: left; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        table.data-ledger-table th { background: #f8fafc; font-size: 12px; text-transform: uppercase; font-weight: 600; color: #475569; letter-spacing: 0.5px; }
        table.data-ledger-table tr:last-child td { border-bottom: none; }
        
        /* Status Badges */
        .status-pill { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; display: inline-block; }
        .pill-pending { background: #fef3c7; color: #d97706; }
        .pill-confirmed { background: #dcfce7; color: #15803d; }
        .pill-cancelled { background: #fee2e2; color: #b91c1c; }

        /* Interactive Operations Buttons Layouts */
        .action-row-flex { display: flex; gap: 6px; justify-content: flex-end; }
        .control-btn { padding: 8px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: background 0.2s; text-decoration: none; }
        .btn-info { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
        .btn-info:hover { background: #e2e8f0; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }

        /* Modal Overlay Window Layout */
        .modal-overlay-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1000; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; }
        .modal-overlay-backdrop.active-modal { opacity: 1; pointer-events: auto; }
        .modal-box-frame { background: white; width: 100%; max-width: 520px; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; transform: translateY(20px); transition: transform 0.3s ease; }
        .modal-overlay-backdrop.active-modal .modal-box-frame { transform: translateY(0); }
        .modal-header { padding: 20px 24px; background: #0f172a; color: white; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { font-size: 18px; font-weight: 600; }
        .modal-close-trigger { background: transparent; border: none; color: #94a3b8; font-size: 20px; cursor: pointer; }
        .modal-close-trigger:hover { color: white; }
        .modal-body { padding: 24px; display: flex; flex-direction: column; gap: 16px; font-size: 14px; }
        .modal-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 16px; }
        .detail-item-node label { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; display: block; margin-bottom: 2px; }
        .detail-item-node p { font-size: 14px; color: #0f172a; font-weight: 500; }

        /* Reviews Custom Card Layout Grid */
        .reviews-panel-masonry { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px; }
        .admin-review-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01); }
        .review-stars { color: #eab308; font-size: 14px; margin-bottom: 12px; }
        .review-text-quote { font-size: 14px; color: #334155; line-height: 1.6; font-style: italic; margin-bottom: 20px; flex: 1; }
    </style>
</head>
<body>

<aside class="admin-sidebar">
    <div class="sidebar-brand">Haven<span>Hotel</span></div>
    <ul class="sidebar-menu">
        <li class="<?= $active_tab === 'home' ? 'active-tab' : '' ?>" data-target="panel_home">
            <a onclick="switchTab('home')"><i class="fa-solid fa-chart-pie"></i> Home Overview</a>
        </li>
        <li class="<?= $active_tab === 'preservation' ? 'active-tab' : '' ?>" data-target="panel_preservation">
            <a onclick="switchTab('preservation')"><i class="fa-solid fa-bed"></i> Preservation</a>
        </li>
        <li class="<?= $active_tab === 'review' ? 'active-tab' : '' ?>" data-target="panel_review">
            <a onclick="switchTab('review')"><i class="fa-solid fa-star-half-stroke"></i> Guest Reviews</a>
        </li>
        <li style="margin-top: 40px; border-top: 1px solid #1e293b; padding-top: 10px;">
            <a href="../login.php" style="color: #ef4444;"><i class="fa-solid fa-right-from-bracket"></i> Exit Admin</a>
        </li>
    </ul>
</aside>

<main class="admin-stage">
    
    <div id="panel_home" class="tab-panel-view <?= $active_tab === 'home' ? 'active-view' : '' ?>">
        <header class="stage-header">
            <h1>System Analytics Hub</h1>
        </header>
        
        <div class="analytics-grid">
            <div class="analytic-card">
                <div class="card-icon-frame icon-blue"><i class="fa-solid fa-users"></i></div>
                <div class="card-meta-box"><h3>Active Guests</h3><div class="num-val"><?= $roles_metrics['users_count'] ?? 0 ?></div></div>
            </div>
            <div class="analytic-card">
                <div class="card-icon-frame icon-purple"><i class="fa-solid fa-user-shield"></i></div>
                <div class="card-meta-box"><h3>Admin Profiles</h3><div class="num-val"><?= $roles_metrics['admins_count'] ?? 0 ?></div></div>
            </div>
            <div class="analytic-card">
                <div class="card-icon-frame icon-green"><i class="fa-solid fa-book"></i></div>
                <div class="card-meta-box"><h3>Total Stays</h3><div class="num-val"><?= $bookings_metrics['total_bookings'] ?? 0 ?></div></div>
            </div>
            <div class="analytic-card">
                <div class="card-icon-frame icon-gold"><i class="fa-solid fa-hourglass-start"></i></div>
                <div class="card-meta-box"><h3>Pendings</h3><div class="num-val"><?= $bookings_metrics['pending_bookings'] ?? 0 ?></div></div>
            </div>
            <div class="analytic-card">
                <div class="card-icon-frame icon-red"><i class="fa-solid fa-rectangle-xmark"></i></div>
                <div class="card-meta-box"><h3>Canceled</h3><div class="num-val"><?= $bookings_metrics['cancelled_bookings'] ?? 0 ?></div></div>
            </div>
        </div>

        <div class="charts-row">
            <div class="chart-box-container">
                <h3>Reservation Ledger Performance Map</h3>
                <div class="mock-bar-graph">
                    <?php
                    $max = max(1, $bookings_metrics['pending_bookings'], $bookings_metrics['confirmed_bookings'], $bookings_metrics['cancelled_bookings']);
                    $p_height = (($bookings_metrics['pending_bookings'] ?? 0) / $max) * 100;
                    $co_height = (($bookings_metrics['confirmed_bookings'] ?? 0) / $max) * 100;
                    $ca_height = (($bookings_metrics['cancelled_bookings'] ?? 0) / $max) * 100;
                    ?>
                    <div class="graph-pillar pillar-fill-pending" style="height: <?= $p_height ?>%;">
                        <div class="pillar-tooltip"><?= $bookings_metrics['pending_bookings'] ?></div>
                    </div>
                    <div class="graph-pillar pillar-fill-confirmed" style="height: <?= $co_height ?>%;">
                        <div class="pillar-tooltip"><?= $bookings_metrics['confirmed_bookings'] ?></div>
                    </div>
                    <div class="graph-pillar pillar-fill-cancelled" style="height: <?= $ca_height ?>%;">
                        <div class="pillar-tooltip"><?= $bookings_metrics['cancelled_bookings'] ?></div>
                    </div>
                </div>
                <div style="display:flex; justify-content:space-between; margin-top:10px; padding:0 20px;">
                    <span class="graph-label-footer">Pending Review</span>
                    <span class="graph-label-footer">Confirmed Stays</span>
                    <span class="graph-label-footer">Cancelled Ledger</span>
                </div>
            </div>
            
            <div class="chart-box-container" style="display: flex; flex-direction: column; justify-content: center; text-align: center;">
                <h4 style="color:#64748b; font-size:12px; text-transform:uppercase; margin-bottom:6px;">Success Rate Calculation</h4>
                <div style="font-size: 36px; font-weight: 700; color:#16a34a;">
                    <?php
                    $rate = $bookings_metrics['total_bookings'] > 0 ? (($bookings_metrics['confirmed_bookings'] / $bookings_metrics['total_bookings']) * 100) : 0;
                    echo round($rate, 1) . '%';
                    ?>
                </div>
                <p style="font-size:13px; color:#94a3b8; margin-top:8px;">Ratio of approved bookings relative to overall checkouts.</p>
            </div>
        </div>
    </div>

    <div id="panel_preservation" class="tab-panel-view <?= $active_tab === 'preservation' ? 'active-view' : '' ?>">
        <header class="stage-header">
            <h1>Reservations Control Center</h1>
        </header>

        <div class="table-card-wrapper">
            <table class="data-ledger-table">
                <thead>
                    <tr>
                        <th>Reference ID</th>
                        <th>Guest Name</th>
                        <th>Room Type</th>
                        <th>Stay Dates</th>
                        <th>Status</th>
                        <th style="text-align: right;">Operations</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($global_ledger as $booking): 
                        $badge_class = strtolower($booking['booking_status']);
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($booking['booking_reference']) ?></strong></td>
                            <td><?= htmlspecialchars($booking['guest_name'] ?? 'Unregistered Account') ?></td>
                            <td><?= htmlspecialchars($booking['room_type']) ?></td>
                            <td><?= date('M d', strtotime($booking['check_in_date'])) ?> → <?= date('M d, Y', strtotime($booking['check_out_date'])) ?></td>
                            <td><span class="status-pill pill-<?= $badge_class ?>"><?= $booking['booking_status'] ?></span></td>
                            <td style="text-align: right;">
                                <div class="action-row-flex">
                                    <button class="control-btn btn-info" onclick="displayBookingDetails(<?= htmlspecialchars(json_encode($booking)) ?>)">
                                        <i class="fa-solid fa-circle-info"></i> Details
                                    </button>
                                    
                                    <?php if ($booking['booking_status'] === 'Pending'): ?>
                                        <form method="POST" action="admin_dashboard.php" style="display:inline;">
                                            <input type="hidden" name="booking_id" value="<?= $booking['booking_id'] ?>">
                                            <input type="hidden" name="status_value" value="Confirmed">
                                            <button type="submit" name="update_status" class="control-btn btn-success"><i class="fa-solid fa-check"></i> Approve</button>
                                        </form>
                                        <form method="POST" action="admin_dashboard.php" style="display:inline;">
                                            <input type="hidden" name="booking_id" value="<?= $booking['booking_id'] ?>">
                                            <input type="hidden" name="status_value" value="Cancelled">
                                            <button type="submit" name="update_status" class="control-btn btn-danger"><i class="fa-solid fa-ban"></i> Cancel</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="admin_dashboard.php" style="display:inline;">
                                            <input type="hidden" name="booking_id" value="<?= $booking['booking_id'] ?>">
                                            <input type="hidden" name="status_value" value="Pending">
                                            <button type="submit" name="update_status" class="control-btn" style="background:#e2e8f0; color:#475569;"><i class="fa-solid fa-rotate-left"></i> Reset</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="panel_review" class="tab-panel-view <?= $active_tab === 'review' ? 'active-view' : '' ?>">
        <header class="stage-header">
            <h1>Guest Testimonials Log</h1>
        </header>

        <div class="reviews-panel-masonry">
            <?php if (!empty($admin_reviews_list)): ?>
                <?php foreach ($admin_reviews_list as $testimonial): ?>
                    <div class="admin-review-card">
                        <div>
                            <div class="review-stars">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="<?= $i <= $testimonial['rating'] ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="review-text-quote">"<?= htmlspecialchars($testimonial['review_text']) ?>"</p>
                        </div>
                        <div style="border-top:1px solid #f1f5f9; padding-top:14px; margin-top:14px; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <div style="font-size:13px; font-weight:600; color:#1e293b;"><?= htmlspecialchars($testimonial['guest_name'] ?? 'Anonymous Guest') ?></div>
                                <div style="font-size:11px; color:#64748b;"><?= date('M d, Y', strtotime($testimonial['created_at'])) ?></div>
                            </div>
                            <form method="POST" action="admin_dashboard.php" onsubmit="return confirm('Remove testimonial permanent record?');">
                                <input type="hidden" name="testimonial_id" value="<?= $testimonial['id'] ?>">
                                <button type="submit" name="delete_testimonial" class="control-btn btn-danger" style="padding:6px 10px; font-size:11px;"><i class="fa-solid fa-trash-can"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:#64748b; font-style:italic; grid-column:1/-1;">No testimonial logs recorded inside database lines.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<div class="modal-overlay-backdrop" id="global_booking_details_modal">
    <div class="modal-box-frame">
        <div class="modal-header">
            <h2 id="m_ref_id">BKG-00000000</h2>
            <button class="modal-close-trigger" onclick="closeModalView()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-details-grid">
                <div class="detail-item-node"><label>Guest Name</label><p id="m_name">-</p></div>
                <div class="detail-item-node"><label>Email Address</label><p id="m_email">-</p></div>
                <div class="detail-item-node"><label>Phone Contact</label><p id="m_phone">-</p></div>
                <div class="detail-item-node"><label>Accommodation</label><p id="m_room">-</p></div>
                <div class="detail-item-node"><label>Check In</label><p id="m_in">-</p></div>
                <div class="detail-item-node"><label>Check Out</label><p id="m_out">-</p></div>
                <div class="detail-item-node"><label>Headcount</label><p id="m_guests">1 guest</p></div>
                <div class="detail-item-node"><label>Financial Total</label><p id="m_total" style="color:#16a34a; font-weight:700;">$0.00</p></div>
            </div>
            <div class="detail-item-node">
                <label>Special Account Requests</label>
                <p id="m_requests" style="background:#f8fafc; padding:12px; border-radius:6px; font-size:13px; color:#475569; border:1px solid #e2e8f0; line-height:1.5; min-height:40px; white-space:pre-wrap;">None</p>
            </div>
        </div>
    </div>
</div>

<script>
// SPA Front-End Interactive Navigation Router Strategy
function switchTab(tabName) {
    // Hide all viewports
    document.querySelectorAll('.tab-panel-view').forEach(panel => panel.classList.remove('active-view'));
    // Deactivate layout tabs highlight markers
    document.querySelectorAll('.sidebar-menu li').forEach(li => li.classList.remove('active-tab'));
    
    // Activate target viewport interface components
    document.getElementById('panel_' + tabName).classList.add('active-view');
    
    // Select the sidebar item based on click event mapping target matching
    const matchingLi = document.querySelector(`.sidebar-menu li[data-target="panel_${tabName}"]`);
    if(matchingLi) matchingLi.classList.add('active-tab');
    
    // Rewrite path history silently without causing a hard page reload matrix break
    window.history.replaceState(null, null, 'admin_dashboard.php?tab=' + tabName);
}

// Modal Operation Handlers
const modal = document.getElementById('global_booking_details_modal');

function displayBookingDetails(data) {
    document.getElementById('m_ref_id').innerText = data.booking_reference;
    document.getElementById('m_name').innerText = data.guest_name ? data.guest_name : 'Unregistered';
    document.getElementById('m_email').innerText = data.guest_email ? data.guest_email : 'N/A';
    document.getElementById('m_phone').innerText = data.guest_phone ? data.guest_phone : 'N/A';
    document.getElementById('m_room').innerText = data.room_type;
    document.getElementById('m_in').innerText = data.check_in_date;
    document.getElementById('m_out').innerText = data.check_out_date;
    document.getElementById('m_guests').innerText = data.guests + " Registered Stayers";
    document.getElementById('m_total').innerText = "$" + parseFloat(data.total_price).toLocaleString();
    document.getElementById('m_requests').innerText = data.special_requests ? data.special_requests : "No custom requests or instructions attached to reservation entry ledger.";
    
    modal.classList.add('active-modal');
}

function closeModalView() {
    modal.classList.remove('active-modal');
}

// Close modal if user clicks outside the modal box frame background layer area
window.addEventListener('click', function(e) {
    if (e.target === modal) closeModalView();
});
</script>
</body>
</html>
<?php $conn->close(); ?>