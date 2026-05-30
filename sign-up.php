<?php
session_start();

// =========================================================================
// 1. DATABASE CONFIGURATION (Direct phpMyAdmin Connection Engine)
// =========================================================================
$host     = "localhost";
$db_user  = "root";
$db_pass  = "";
$db_name  = "haven_hotel"; // Ensure this matches your phpMyAdmin database name exactly

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

// Fail-safe verification check on database connection status
if ($conn->connect_error) {
    die("Direct Database Connection to phpMyAdmin Failed: " . $conn->connect_error);
}

$error = "";
$success = "";

// =========================================================================
// 2. BACKEND CONTROLLER LOGIC (Triggers on "Register Account" Form Submission)
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize user inputs
    $first_name = trim(filter_var($_POST['first_name'], FILTER_SANITIZE_SPECIAL_CHARS));
    $last_name  = trim(filter_var($_POST['last_name'], FILTER_SANITIZE_SPECIAL_CHARS));
    $email      = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $phone      = trim(filter_var($_POST['phone'], FILTER_SANITIZE_SPECIAL_CHARS));
    $password   = $_POST['password'];

    // Mandatory data verification check
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "Please fill in all mandatory fields marked with an asterisk (*).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address style configuration.";
    } elseif (strlen($password) < 6) {
        $error = "Password security constraint failure: Must be at least 6 characters.";
    } else {
        
        // Check database records to prevent duplicate user profile emails
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE user_email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "This email address is already registered inside phpMyAdmin.";
        } else {
            
            // Securely hash the password string before writing it to database disk space
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // Query parameters mapping to store rows safely in the users table database system
            $insert_stmt = $conn->prepare("INSERT INTO users (first_name, last_name, user_email, phone, password, membership_tier) VALUES (?, ?, ?, ?, ?, 'Regular')");
            $insert_stmt->bind_param("sssss", $first_name, $last_name, $email, $phone, $hashed_password);

            if ($insert_stmt->execute()) {
                $success = "Account successfully written to phpMyAdmin database! Redirecting...";
                
                // Perform automated script navigation directly into your login portal panel
                echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 2000);</script>";
            } else {
                $error = "Critical database record write error execution failure: " . $insert_stmt->error;
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Haven Hotel - Create Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="ui/sign-up.css">
</head>
<body>

    <div class="signup-card">
        <div class="signup-header">
            <h1>Create Account</h1>
            <p>Join Haven Hotel to track reservations and enjoy luxury perks</p>
        </div>

        <form action="" method="POST" class="form-grid">
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="input-wrapper">
                <label for="first_name">First Name<span>*</span></label>
                <input type="text" id="first_name" name="first_name" required placeholder="Evelyn">
            </div>

            <div class="input-wrapper">
                <label for="last_name">Last Name<span>*</span></label>
                <input type="text" id="last_name" name="last_name" required placeholder="Sterling">
            </div>

            <div class="input-wrapper full-width">
                <label for="email">Email Address<span>*</span></label>
                <input type="email" id="email" name="email" required placeholder="evelyn@example.com">
            </div>

            <div class="input-wrapper full-width">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" placeholder="+1 (555) 019-2834">
            </div>

            <div class="input-wrapper full-width">
                <label for="password">Password<span>*</span></label>
                <input type="password" id="password" name="password" required minlength="6" placeholder="••••••••">
            </div>

            <div class="full-width">
                <button type="submit" class="submit-btn">Register Account</button>
            </div>
        </form>

        <div class="footer-note">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </div>

</body>
</html>
