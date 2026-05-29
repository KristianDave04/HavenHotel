<?php
session_start();

// Redirect to sign-in if no valid tracking payload is found
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "haven_hotel_db");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $room_type = $_POST['room_type'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $guests = intval($_POST['guests']);
    $special_requests = htmlspecialchars($_POST['special_requests']);
    $price_per_night = floatval($_POST['price_per_night']);
    
    // Calculate total reservation duration nights
    $date1 = new DateTime($check_in);
    $date2 = new DateTime($check_out);
    $nights = $date1->diff($date2)->days;
    $total_price = $price_per_night * $nights;
    
    // Generate an authentic randomized reservation configuration sequence reference (Image 9 structural format)
    $booking_reference = "BKG-" . rand(10000000, 99999999);
    
    $stmt = $conn->prepare("INSERT INTO bookings (booking_reference, user_id, room_type, check_in, check_out, guests, special_requests, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed')");
    $stmt->bind_param("sisssisd", $booking_reference, $user_id, $room_type, $check_in, $check_out, $guests, $special_requests, $total_price);
    
    if ($stmt->execute()) {
        // Save execution outcomes into the state machine for the receipt markup output panel view
        $_SESSION['last_booking'] = [
            'ref' => $booking_reference,
            'room' => $room_type,
            'check_in' => $check_in,
            'check_out' => $check_out,
            'total' => $total_price
        ];
        header("Location: success.php");
        exit();
    } else {
        echo "Error execution sequence mapping failure: " . $stmt->error;
    }
}
?>