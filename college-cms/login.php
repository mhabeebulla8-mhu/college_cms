<?php
include 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    // Step 2: Check if email exists and is_verified = true
    $stmt = $conn->prepare("SELECT id, name, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if ($user['is_verified'] == 0) {
            $error = "Email not verified. Please check your inbox.";
        } else {
            // Step 3: Send OTP to email
            $otp = rand(100000, 999999);
            $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
            $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            
            // Clean old OTPs for this user
            $del = $conn->prepare("DELETE FROM otp_codes WHERE user_id = ?");
            $del->bind_param("i", $user['id']);
            $del->execute();
            
            // Store hashed OTP
            $stmt_otp = $conn->prepare("INSERT INTO otp_codes (user_id, otp_hash, expires_at) VALUES (?, ?, ?)");
            $stmt_otp->bind_param("iss", $user['id'], $otp_hash, $expires_at);
            
            if ($stmt_otp->execute()) {
                require_once 'mailer.php';
                $subject = "Login OTP - College CMS";
                $message = "
                    <h2>Login Verification</h2>
                    <p>Hello {$user['name']},</p>
                    <p>Your One-Time Password (OTP) for login is: <b style='font-size: 1.5rem; color: #3b82f6;'>$otp</b></p>
                    <p>This code will expire in 5 minutes.</p>
                    <p>If you did not request this, please secure your account.</p>
                ";
                
                if (sendMail($email, $subject, $message)) {
                    $_SESSION['otp_email'] = $email;
                    $_SESSION['otp_user_id'] = $user['id'];
                    header("Location: login_otp.php");
                    exit();
                } else {
                    $error = "Failed to send OTP. Please check your internet connection.";
                }
            }
        }
    } else {
        $error = "Email not registered!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Student CMS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="form-container">
        <h2 id="login-title" style="text-align:center; margin-bottom: 0.5rem;">Welcome Back</h2>
        <p id="login-subtitle" style="text-align:center; color: #64748b; font-size: 0.9rem; margin-bottom: 2rem;">Login to access your CMS dashboard</p>
        
        <?php if(isset($_GET['msg'])): ?>
            <div style="color: green; margin-bottom: 1rem; text-align: center;"><?php echo $_GET['msg']; ?></div>
        <?php endif; ?>

        <?php if(isset($_GET['msg'])): ?>
            <div style="color: #059669; background: #ecfdf5; padding: 10px; border-radius: 5px; margin-bottom: 1rem; text-align: center; border: 1px solid #10b981;">
                <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div style="color: #dc2626; background: #fef2f2; padding: 10px; border-radius: 5px; margin-bottom: 1rem; text-align: center; border: 1px solid #fecaca;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="Enter your registered email">
            </div>
            <button type="submit" class="btn-block">Send OTP</button>
        </form>
        <p style="text-align:center; margin-top: 1.5rem; font-size: 0.9rem;">
            Don't have an account? <a href="register.php" style="color: #3b82f6; font-weight: bold;">Register</a>
        </p>
    </div>

</body>
</html>
