<?php
// login.php
session_start();

require_once 'classes/Database.php';
require_once 'classes/User.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $dbConn = $database->getConnection();
    $userEngine = new User($dbConn);

    $email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Authenticate with user object layer method loop context
        $loginResult = $userEngine->login($email, $password);

        if ($loginResult !== false && $loginResult['status'] === true) {
            // Evaluates role parameter output values to guide view dashboard endpoints
            if ($loginResult['role'] === 'Admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = "Access Denied: Invalid configuration credentials matching records.";
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
    <title>Haven Hotel - Sign In</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="ui/login.css">
</head>
<body>

    <div class="split-container">
        
        <div class="hero-side">
            <div class="quote-wrapper">
                <p class="quote-text">
                    "A stay that feels like <em>coming home</em>."
                </p>
                <p class="quote-author">— Haven Hotel Guest, March 2026</p>
            </div>
        </div>

        <div class="form-side">
            <div class="form-container">
                
                <div class="form-header">
                    <h2>Welcome Back</h2>
                    <p>Sign in to manage your reservations and access exclusive member benefits.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert-error" style="background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    
                    <div class="input-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required placeholder="evelyn@example.com">
                    </div>

                    <div class="input-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn-gold">Sign In to Haven</button>
                </form>

                <div class="footer-note">
                    Don't have an account? <a href="sign-up.php">Create Account</a>
                </div>

            </div>
        </div>

    </div>

</body>
</html>
