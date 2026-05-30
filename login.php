<?php
session_start();

// Database Connection Settings
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "haven_hotel";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Look up the user by email
        $stmt = $conn->prepare("SELECT id, first_name, last_name, password, membership_tier FROM users WHERE user_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify the encrypted password match
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['membership_tier'] = $user['membership_tier'];

                // Redirect straight to dashboard/index
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Incorrect password. Please try again.";
            }
        } else {
            $error = "No account found with that email address.";
        }
        $stmt->close();
    }
}
$conn->close();
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
                    <div class="alert-error"><?php echo $error; ?></div>
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
