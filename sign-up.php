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
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "This email address is already registered inside phpMyAdmin.";
        } else {
            
            // Securely hash the password string before writing it to database disk space
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // Query parameters mapping to store rows safely in the users table database system
            $insert_stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password, membership_tier) VALUES (?, ?, ?, ?, ?, 'Regular')");
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f7f4ef;
            color: #1e1e1e;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Card container matching the premium layout aesthetic of workspace cards */
        .signup-card {
            background-color: #ffffff;
            border: 1px solid #f2eade;
            border-radius: 12px;
            width: 100%;
            max-width: 550px;
            padding: 40px 50px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.02);
        }

        .signup-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .signup-header h1 {
            font-family: "Playfair Display", serif;
            font-size: 32px;
            font-weight: 600;
            color: #111111;
            margin-bottom: 8px;
        }

        .signup-header p {
            font-size: 13px;
            color: #888888;
        }

        /* Structural Field Mesh Layout Framework Grid */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .full-width {
            grid-column: span 2;
        }

        .input-wrapper {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .input-wrapper label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #7a7a7a;
        }

        .input-wrapper label span {
            color: #c69c4f;
            margin-left: 2px;
        }

        .input-wrapper input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e6ded2;
            background-color: #fff;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            color: #111;
            outline: none;
            transition: border-color 0.15s ease;
        }

        .input-wrapper input:focus {
            border-color: #c69c4f;
        }

        /* Action Feedback System Messaging Alerts CSS */
        .alert {
            grid-column: span 2;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            text-align: center;
        }

        .alert-error {
            background-color: #fdedec;
            color: #cb4335;
            border: 1px solid #fadbd8;
        }

        .alert-success {
            background-color: #e8f8f5;
            color: #1e8449;
            border: 1px solid #d1f2eb;
        }

        /* Active Core Form Action Trigger Button Layout Style */
        .submit-btn {
            width: 100%;
            background-color: #c69c4f;
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: background-color 0.15s ease, transform 0.1s ease;
            margin-top: 10px;
        }

        .submit-btn:hover {
            background-color: #b58c42;
        }

        .submit-btn:active {
            transform: scale(0.98);
        }

        .footer-note {
            text-align: center;
            margin-top: 25px;
            font-size: 13px;
            color: #666;
        }

        .footer-note a {
            color: #c69c4f;
            text-decoration: none;
            font-weight: 600;
        }

        .footer-note a:hover {
            text-decoration: underline;
        }

        /* Screen Responsive Handling Breakpoints */
        @media (max-width: 600px) {
            .signup-card {
                padding: 30px 24px;
            }
            .form-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .full-width {
                grid-column: span 1;
            }
        }
    </style>
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