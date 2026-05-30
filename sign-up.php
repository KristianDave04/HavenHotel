<?php
// sign-up.php
session_start();

require_once 'classes/Database.php';
require_once 'classes/User.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Instantiate OOP engine layers
    $database = new Database();
    $dbConn = $database->getConnection();
    $userEngine = new User($dbConn);

    // Sanitize user inputs safely
    $first_name = trim(filter_var($_POST['first_name'], FILTER_SANITIZE_SPECIAL_CHARS));
    $last_name  = trim(filter_var($_POST['last_name'], FILTER_SANITIZE_SPECIAL_CHARS));
    $email      = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $phone      = trim(filter_var($_POST['phone'], FILTER_SANITIZE_SPECIAL_CHARS));
    $password   = $_POST['password'];
    $admin_key  = trim($_POST['admin_key'] ?? '');

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "Please fill in all mandatory fields marked with an asterisk (*).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address configuration.";
    } elseif (strlen($password) < 8) {
        $error = "Password security constraint failure: Must be at least 8 characters.";
    } else {
        // Trigger OOP Account creation routines
        $registrationResult = $userEngine->register($first_name, $last_name, $email, $phone, $password, $admin_key);
        
        if ($registrationResult === true) {
            $success = "Account successfully registered to database! Redirecting to credentials gateway...";
            echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 2000);</script>";
        } else {
            $error = $registrationResult;
        }
    }
    
    $database->closeConnection();
}
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

        <form action="sign-up.php" method="POST" class="form-grid">
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error" style="grid-column: span 2; background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 10px; font-size: 14px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" style="grid-column: span 2; background: #f0fdf4; border-left: 4px solid #22c55e; color: #166534; padding: 12px; border-radius: 6px; margin-bottom: 10px; font-size: 14px;"><?php echo $success; ?></div>
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
                <input type="password" id="password" name="password" required minlength="8" placeholder="••••••••">
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
