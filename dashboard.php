<?php
session_start();

// 1. STRICT USER AUTHENTICATION CONSTRAINT CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. DATABASE CONFIGURATION CONNECTIONS
$host     = "localhost";
$db_user  = "root";
$db_pass  = "";
$db_name  = "haven_hotel";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Direct Database Connection to User Dashboard Failed: " . $conn->connect_error);
}

// Extract identity parameters from current session bounds securely
$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Pull fresh user profiling metrics (e.g., membership tier configurations) directly out of the database
$user_stmt = $conn->prepare("SELECT membership_tier, created_at FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_profile = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

$membership_tier = $user_profile['membership_tier'] ?? 'Regular';
$member_since    = isset($user_profile['created_at']) ? date('M Y', strtotime($user_profile['created_at'])) : date('M Y');

// 3. FETCH ALL LOGGED RESERVATIONS FOR THIS PARTICULAR USER BOUND
$bookings_list = [];
$booking_stmt = $conn->prepare("
    SELECT b.*, r.image_url 
    FROM bookings b 
    LEFT JOIN rooms r ON b.room_id = r.room_id 
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC
");
$booking_stmt->bind_param("i", $user_id);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();

// Counters initialized to segment stats on telemetry display matrices
$count_pending   = 0;
$count_confirmed = 0;
$count_cancelled = 0;

while ($row = $booking_result->fetch_assoc()) {
    $bookings_list[] = $row;
    if ($row['booking_status'] === 'Pending')   $count_pending++;
    if ($row['booking_status'] === 'Confirmed') $count_confirmed++;
    if ($row['booking_status'] === 'Cancelled') $count_cancelled++;
}
$booking_stmt->close();
$conn->close();

// Extrapolate greeting salutation string out cleanly
$name_segments = explode(' ', $user_name);
$first_name    = !empty($name_segments[0]) ? $name_segments[0] : $user_name;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Haven Hotel - Guest Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="ui/dashboard.css">
</head>
<body>

    <header class="navbar">
        <div class="logo">Haven<span>Hotel</span></div>
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
        <a href="login.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket" style="margin-right:6px;"></i> Logout</a>
    </header>

    <section class="dashboard-hero">
        <div class="hero-welcome">
            <h1>Hello, <?= htmlspecialchars(strtoupper($first_name)); ?></h1>
            <p>Manage your luxury stay requests, track reservations, and view confirmation slips.</p>
        </div>
        <div class="user-tier-badge">
            <span>Membership Status</span>
            <strong><?= htmlspecialchars($membership_tier); ?> Member</strong>
        </div>
    </section>

    <main class="main-container">
        
        <div class="metrics-grid">
            <div class="metric-card" onclick="filterLedgerTarget('All')">
                <div class="metric-icon icon-all"><i class="fa-solid fa-list-check"></i></div>
                <div class="metric-data">
                    <span>Total Requests</span>
                    <h2><?= count($bookings_list) ?></h2>
                </div>
            </div>
            <div class="metric-card" onclick="filterLedgerTarget('Pending')">
                <div class="metric-icon icon-pending"><i class="fa-regular fa-clock"></i></div>
                <div class="metric-data">
                    <span>Pending Approval</span>
                    <h2><?= $count_pending ?></h2>
                </div>
            </div>
            <div class="metric-card" onclick="filterLedgerTarget('Confirmed')">
                <div class="metric-icon icon-confirmed"><i class="fa-regular fa-calendar-check"></i></div>
                <div class="metric-data">
                    <span>Confirmed Stays</span>
                    <h2><?= $count_confirmed ?></h2>
                </div>
            </div>
            <div class="metric-card" onclick="filterLedgerTarget('Cancelled')">
                <div class="metric-icon icon-cancelled"><i class="fa-regular fa-circle-xmark"></i></div>
                <div class="metric-data">
                    <span>Cancelled Requests</span>
                    <h2><?= $count_cancelled ?></h2>
                </div>
            </div>
        </div>

        <div class="section-header">
            <h2 id="ledger_view_title">Your Reservation Ledger (All)</h2>
            <a href="book.php" class="new-booking-trigger"><i class="fa-solid fa-plus"></i> Reserve Another Room</a>
        </div>

        <div class="bookings-stack" id="ledger_interactive_stack_target">
            </div>

        <div class="section-header">
            <h2><i class="fa-solid fa-plane-arrival" style="color: #16a34a; margin-right: 8px;"></i> Confirmed Upcoming Stays Portfolio</h2>
        </div>
        <div class="confirmed-grid-showcase" id="confirmed_cards_showroom_target">
            </div>

    </main>

<div class="modal-overlay-backdrop" id="user_booking_inspect_modal">
    <div class="modal-box-frame">
        <h2 id="m_ref_id" style="font-family:'Playfair Display', serif; margin-bottom:16px; color:#0f172a;">BKG-CODE</h2>
        <div style="font-size:14px; display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px; border-top:1px solid #f1f5f9; padding-top:16px;">
            <div><label style="color:#64748b; font-size:11px; display:block; font-weight:600; text-transform:uppercase;">Accommodation</label><span id="m_type" style="font-weight:600; color:#0f172a;"></span></div>
            <div><label style="color:#64748b; font-size:11px; display:block; font-weight:600; text-transform:uppercase;">Total Rate Charged</label><span id="m_total" style="color:#c69c4f; font-weight:700; font-size:16px;"></span></div>
            <div><label style="color:#64748b; font-size:11px; display:block; font-weight:600; text-transform:uppercase;">Check In Date</label><span id="m_in" style="color:#1e293b;"></span></div>
            <div><label style="color:#64748b; font-size:11px; display:block; font-weight:600; text-transform:uppercase;">Check Out Date</label><span id="m_out" style="color:#1e293b;"></span></div>
            <div style="grid-column: span 2;"><label style="color:#64748b; font-size:11px; display:block; font-weight:600; text-transform:uppercase;">Registered Guests</label><span id="m_guests" style="color:#1e293b;"></span></div>
            <div style="grid-column: span 2;"><label style="color:#64748b; font-size:11px; display:block; font-weight:600; text-transform:uppercase;">Special Request Notes</label><span id="m_requests" style="font-style:italic; color:#475569;"></span></div>
        </div>
        <button class="new-booking-trigger" style="width:100%; justify-content:center; background:#ef4444;" onclick="closeModal('user_booking_inspect_modal')">Close Inspection Window</button>
    </div>
</div>

<script src="dashboard.js"></script>
<script>
// Parse native array data list directly out from SQL memory boundary space
const localReservationsDataset = <?= json_encode($bookings_list); ?>;

// Utility function to convert dates safely on the client side
function formatClientDate(dateStr) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateStr).toLocaleDateString('en-US', options);
}

// Interactive Ledger Programmatic Routing Function
function filterLedgerTarget(statusFilter) {
    const stackContainer = document.getElementById('ledger_interactive_stack_target');
    const titleHeader = document.getElementById('ledger_view_title');
    
    titleHeader.innerText = `Your Reservation Ledger (${statusFilter})`;
    stackContainer.innerHTML = "";
    
    const matchedRows = (statusFilter === 'All') 
        ? localReservationsDataset 
        : localReservationsDataset.filter(b => b.booking_status === statusFilter);
        
    if (matchedRows.length === 0) {
        stackContainer.innerHTML = `
            <div class="empty-state-card">
                <i class="fa-solid fa-hotel"></i>
                <h3>No Bookings Found</h3>
                <p>There are no reservation entries filed under your profile matching the "${statusFilter}" state.</p>
            </div>`;
        return;
    }
    
    matchedRows.forEach(b => {
        const fallbackImg = "https://images.unsplash.com/photo-1566665797739-1674de7a421a?q=80&w=1200";
        const imgSrc = b.image_url ? b.image_url : fallbackImg;
        const statusClass = b.booking_status.toLowerCase();
        
        const card = document.createElement('div');
        card.className = "booking-item-card";
        card.innerHTML = `
            <div class="card-image-frame">
                <img src="${imgSrc}" alt="Room Accommodation Preview">
            </div>
            <div class="card-details-box">
                <div class="details-top">
                    <div class="room-title-meta">
                        <h3>${b.room_type}</h3>
                        <p>REF TOKEN: ${b.booking_reference}</p>
                    </div>
                    <span class="status-badge status-${statusClass}">${b.booking_status}</span>
                </div>
                <div class="details-middle">
                    <div class="meta-date-block">
                        <span>Check-In Date</span>
                        <strong>${formatClientDate(b.check_in_date)}</strong>
                    </div>
                    <div class="meta-date-block">
                        <span>Check-Out Date</span>
                        <strong>${formatClientDate(b.check_out_date)}</strong>
                    </div>
                    <div class="meta-date-block">
                        <span>Guests Registered</span>
                        <strong>${b.guests} Individual(s)</strong>
                    </div>
                </div>
                <div class="details-bottom">
                    <div class="special-request-note">
                        ${b.special_requests ? `<i class="fa-regular fa-comment-dots" style="margin-right: 4px; color: #c69c4f;"></i> <strong>Requests:</strong> "${b.special_requests}"` : `<span style="color: #94a3b8;"><i class="fa-regular fa-comment"></i> No instructions provided</span>`}
                    </div>
                    <div class="price-charged-tag" style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                        <span>Total Gross Cost</span>
                        <h4 style="margin-bottom: 8px;">$${parseFloat(b.total_price).toLocaleString(undefined, {minimumFractionDigits: 2})}</h4>
                        <button class="new-booking-trigger" style="padding: 6px 16px; font-size: 11px; background: #0f172a;" onclick='displayInspectDetailsModal(${JSON.stringify(b)})'>View Details</button>
                    </div>
                </div>
            </div>
        `;
        stackContainer.appendChild(card);
    });
}

// Renders individual portfolio target modules for Confirmed stays
function renderConfirmedShowroomCards() {
    const showcaseContainer = document.getElementById('confirmed_cards_showroom_target');
    showcaseContainer.innerHTML = "";
    
    const confirmedStays = localReservationsDataset.filter(b => b.booking_status === 'Confirmed');
    
    if (confirmedStays.length === 0) {
        showcaseContainer.innerHTML = `<div style="grid-column: 1/-1; background: white; padding: 30px; border-radius:12px; border:1px dashed #c69c4f; text-align:center; color:#64748b; font-size:14px; width: 100%;">No upcoming confirmed itineraries are currently registered to your profile.</div>`;
        return;
    }
    
    confirmedStays.forEach(b => {
        const card = document.createElement('div');
        card.className = "confirmed-status-card";
        card.onclick = () => displayInspectDetailsModal(b);
        card.innerHTML = `
            <span class="confirmed-card-badge"><i class="fa-solid fa-passport"></i> Active Stay</span>
            <h3 style="font-family:'Playfair Display', serif; font-size:18px; margin-bottom:4px; color:#0f172a;">${b.room_type}</h3>
            <p style="font-size:12px; color:#64748b; margin-bottom:16px;">Ref Code: <strong>${b.booking_reference}</strong></p>
            <div style="border-top:1px solid #f1f5f9; padding-top:12px; display:flex; justify-content:space-between; align-items:center; font-size:12px;">
                <div style="color: #475569;"><i class="fa-solid fa-calendar-days" style="color:#c69c4f; margin-right:4px;"></i> ${formatClientDate(b.check_in_date)}</div>
                <div style="font-weight:700; color:#c69c4f; font-size:15px;">$${parseFloat(b.total_price).toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
            </div>
        `;
        showcaseContainer.appendChild(card);
    });
}

// Modal handling routines
function displayInspectDetailsModal(data) {
    document.getElementById('m_ref_id').innerText = "Reservation: " + data.booking_reference;
    document.getElementById('m_type').innerText = data.room_type;
    document.getElementById('m_in').innerText = formatClientDate(data.check_in_date);
    document.getElementById('m_out').innerText = formatClientDate(data.check_out_date);
    document.getElementById('m_guests').innerText = data.guests + " Registered Member(s)";
    document.getElementById('m_requests').innerText = data.special_requests ? data.special_requests : "No custom requirements provided.";
    document.getElementById('m_total').innerText = "$" + parseFloat(data.total_price).toLocaleString(undefined, {minimumFractionDigits: 2});
    
    document.getElementById('user_booking_inspect_modal').classList.add('active-modal');
}

function closeModal(id) { 
    document.getElementById(id).classList.remove('active-modal'); 
}

// Runtime bootstrapping logic execution hooks
window.onload = function() {
    filterLedgerTarget('All');
    renderConfirmedShowroomCards();
};
</script>
</body>
</html>
