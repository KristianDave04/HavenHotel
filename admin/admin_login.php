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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #ffffff;
            color: #111111;
            min-height: 100vh;
        }

        .split-container {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* LEFT SIDE: LUXURY BG HERO (Uses an office/workspace aesthetic wallpaper) */
        .hero-side {
            flex: 1;
            background: url('https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=1200') center/cover no-repeat;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 60px;
        }

        .hero-side::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(15, 23, 42, 0.85) 0%, rgba(15, 23, 42, 0.3) 50%, rgba(0, 0, 0, 0) 100%);
            z-index: 1;
        }

        .quote-wrapper {
            position: relative;
            z-index: 10;
            max-width: 85%;
        }

        .quote-text {
            font-family: 'Playfair Display', serif;
            color: #ffffff;
            font-size: 32px;
            font-weight: 500;
            line-height: 1.35;
            margin-bottom: 12px;
            text-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
        }

        .quote-text em {
            font-style: italic;
            font-weight: 400;
            color: #c69c4f;
        }

        .quote-author {
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
            font-weight: 400;
            letter-spacing: 0.5px;
        }

        /* RIGHT SIDE: FORMS CONTEXT CONTAINER */
        .form-side {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #ffffff;
            padding: 40px;
        }

        .form-container {
            width: 100%;
            max-width: 420px;
        }

        .form-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .form-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .form-header p {
            font-size: 12px;
            color: #ef4444;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 22px;
        }

        .input-group label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #7a7a7a;
            letter-spacing: 0.8px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e6ded2;
            background-color: #ffffff;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            color: #111111;
            outline: none;
            transition: border-color 0.15s ease;
        }

        .input-group input:focus {
            border-color: #c69c4f;
        }

        .alert-error {
            color: #cb4335;
            background: #fdedec;
            border: 1px solid #fadbd8;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .btn-slate {
            width: 100%;
            padding: 14px;
            background: #0f172a;
            border: none;
            color: #ffffff;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: background-color 0.15s ease, transform 0.1s ease;
            margin-top: 10px;
        }

        .btn-slate:hover {
            background-color: #1e293b;
        }

        .btn-slate:active {
            transform: scale(0.99);
        }

        .footer-note {
            text-align: center;
            margin-top: 30px;
            font-size: 13px;
            color: #666666;
        }

        .footer-note a {
            color: #c69c4f;
            text-decoration: none;
            font-weight: 600;
        }

        .footer-note a:hover {
            text-decoration: underline;
        }

        @media (max-width: 900px) {
            .hero-side { display: none; }
            .form-side { padding: 30px 20px; }
        }
    </style>
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