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
        $error = "Please enter both corporate email and password.";
    } else {
        // Strict lookup condition checking that the user role field is matching 'admin' explicitly
        $stmt = $conn->prepare("SELECT id, first_name, last_name, password, membership_tier, role FROM users WHERE user_email = ? AND role = 'admin'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify the encrypted password match
            if (password_verify($password, $user['password'])) {
                // Set structural administrative session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['membership_tier'] = $user['membership_tier'];
                $_SESSION['role'] = $user['role']; // Enforces active role routing context

                // Redirect directly to your core dashboard panel workspace code
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error = "Incorrect administrative password. Please try again.";
            }
        } else {
            $error = "Access Denied: No validated administrative account maps onto this identity.";
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
    <title>Haven Hotel - Secure Admin Gate</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./ui.admin/a.login.css">
</head>
<body>

    <div class="split-container">
        
        <div class="hero-side">
            <div class="quote-wrapper">
                <p class="quote-text">
                    "Efficiency is premium quality design applied onto <em>operational reality</em>."
                </p>
                <p class="quote-author">— Haven Corporate Engine Console, 2026</p>
            </div>
        </div>

        <div class="form-side">
            <div class="form-container">
                
                <div class="form-header">
                    <h2>Staff Control Center</h2>
                    <p>Internal Operations Login Portal</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="admin_login.php" method="POST">
                    
                    <div class="input-group">
                        <label for="email">Executive Email Address *</label>
                        <input type="email" id="email" name="email" required placeholder="alex.w@havenhotel.com">
                    </div>

                    <div class="input-group">
                        <label for="password">Security Password *</label>
                        <input type="password" id="password" name="password" required placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn-slate">Sign In to Dashboard</button>
                </form>

                <div class="footer-note">
                    Need to provision a new workspace machine entry? <a href="admin_sign-up.php">Register Here</a>
                </div>

            </div>
        </div>

    </div>

</body>
</html>
