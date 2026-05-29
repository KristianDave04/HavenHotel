<?php
session_start();
if(!isset($_SESSION['last_booking'])) {
    header("Location: dashboard.php");
    exit();
}
$b = $_SESSION['last_booking'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation Confirmed!</title>
    <style>
        body { font-family: sans-serif; background-color: #FAF8F5; text-align: center; padding-top: 100px; }
        .card { background: white; max-width: 500px; margin: 0 auto; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .ref-banner { background: #C5A059; color: white; padding: 10px; font-weight: bold; margin: 20px 0; border-radius: 4px; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; text-align: left; }
        .btn { display: inline-block; padding: 12px 24px; background: #C5A059; color: white; text-decoration: none; border-radius: 20px; font-size: 14px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="color: #2C3E50;">Reservation Confirmed!</h2>
        <p>Wonderful! Your stay at Haven Hotel has been successfully reserved.</p>
        
        <div class="ref-banner">
            BOOKING REFERENCE <br> <?php echo $b['ref']; ?>
        </div>
        
        <div class="detail-row"><span>Room</span> <strong><?php echo $b['room']; ?></strong></div>
        <div class="detail-row"><span>Check-In</span> <strong><?php echo $b['check_in']; ?></strong></div>
        <div class="detail-row"><span>Check-Out</span> <strong><?php echo $b['check_out']; ?></strong></div>
        <div class="detail-row"><span>Total Charged</span> <strong style="color:#C5A059;">$<?php echo number_format($b['total']); ?></strong></div>
        
        <a href="dashboard.php" class="btn">View My Bookings</a>
    </div>
</body>
</html> 