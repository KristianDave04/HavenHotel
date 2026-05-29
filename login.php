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
        $stmt = $conn->prepare("SELECT id, first_name, last_name, password, membership_tier FROM users WHERE email = ?");
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

        /* Split layout container wrapper */
        .split-container {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* LEFT SIDE: LUXURY BG HERO AND OVERLAY DESIGN */
        .hero-side {
            flex: 1;
            background: url('https://lirp.cdn-website.com/5f5d0298/dms3rep/multi/opt/Swimming+Pool+%286%29-640w.jpg') center/cover no-repeat;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 60px;
        }

        /* Bottom dark gradient wrapper to secure high dynamic contrast readability */
        .hero-side::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.45) 0%, rgba(0, 0, 0, 0.1) 40%, rgba(0, 0, 0, 0) 100%);
            z-index: 1;
        }

        /* Quote wrapper component styling matching custom parameters */
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
            color: #2C3E50;
            margin-bottom: 8px;
        }

        .form-header p {
            font-size: 13px;
            color: #888888;
        }

        /* Input Controls Layout Framework */
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

        /* System Action Alert Notifications */
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

        /* Brand Action Button Components */
        .btn-gold {
            width: 100%;
            padding: 14px;
            background: #c69c4f;
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

        .btn-gold:hover {
            background-color: #b58c42;
        }

        .btn-gold:active {
            transform: scale(0.99);
        }

        /* Link Navigation Helpers */
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

        /* Responsive Breakpoints Rules */
        @media (max-width: 900px) {
            .hero-side {
                display: none; /* Collapses picture area neatly on small tablets/mobile frames */
            }
            .form-side {
                padding: 30px 20px;
            }
        }
    </style>
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