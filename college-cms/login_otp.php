<?php
require_once 'db.php';

if (!isset($_SESSION['otp_email']) || !isset($_SESSION['otp_user_id'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$email = $_SESSION['otp_email'];
$user_id = $_SESSION['otp_user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp_input = trim($_POST['otp']);
    
    // Get stored OTP
    $stmt = $conn->prepare("SELECT otp_hash, expires_at, attempts FROM otp_codes WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        // Check rate limiting (max 3 attempts)
        if ($row['attempts'] >= 3) {
            $error = "Too many failed attempts. Please request a new OTP.";
        } 
        // Check expiry
        elseif (time() > strtotime($row['expires_at'])) {
            $error = "OTP expired. Please request a new one.";
        }
        // Verify OTP
        elseif (password_verify($otp_input, $row['otp_hash'])) {
            // Success! Create session
            $stmt_u = $conn->prepare("SELECT id, name, role, email FROM users WHERE id = ?");
            $stmt_u->bind_param("i", $user_id);
            $stmt_u->execute();
            $user = $stmt_u->get_result()->fetch_assoc();
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            
            // Delete OTP
            $conn->query("DELETE FROM otp_codes WHERE user_id = $user_id");
            
            // Clear temporary session data
            unset($_SESSION['otp_email']);
            unset($_SESSION['otp_user_id']);
            
            if ($user['role'] == 'admin') {
                header("Location: admin.php");
            } elseif ($user['role'] == 'dept_admin') {
                header("Location: dept_admin.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            // Increment attempts
            $update = $conn->prepare("UPDATE otp_codes SET attempts = attempts + 1 WHERE user_id = ?");
            $update->bind_param("i", $user_id);
            $update->execute();
            $error = "Invalid OTP. You have " . (3 - ($row['attempts'] + 1)) . " attempts left.";
        }
    } else {
        $error = "No active OTP found. Please request a new one.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP - Student CMS</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .otp-input {
            letter-spacing: 0.5rem;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2 style="text-align:center;">Enter OTP</h2>
        <p style="text-align:center; color: #64748b; margin-bottom: 2rem;">Verification code sent to <?php echo htmlspecialchars($email); ?></p>
        
        <?php if($error): ?>
            <div style="color: #dc2626; background: #fef2f2; padding: 10px; border-radius: 5px; margin-bottom: 1rem; text-align: center; border: 1px solid #fecaca;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="login_otp.php" method="POST">
            <div class="form-group">
                <label>Verification Code</label>
                <input type="text" name="otp" required maxlength="6" class="otp-input" placeholder="000000" autocomplete="one-time-code">
            </div>
            <button type="submit" class="btn-block">Verify & Login</button>
        </form>
        
        <div style="text-align:center; margin-top: 2rem;">
            <p id="cooldown-text" style="font-size: 0.9rem; color: #64748b;"></p>
            <a href="resend_otp.php" id="resend-link" style="color: #3b82f6; font-weight: bold; text-decoration: none; display: none;">Resend OTP</a>
        </div>
    </div>

    <script>
        let cooldown = 60;
        const text = document.getElementById('cooldown-text');
        const link = document.getElementById('resend-link');
        
        function updateTimer() {
            if (cooldown > 0) {
                text.innerText = `Resend OTP in ${cooldown}s`;
                cooldown--;
                setTimeout(updateTimer, 1000);
            } else {
                text.style.display = 'none';
                link.style.display = 'inline';
            }
        }
        updateTimer();
    </script>
</body>
</html>
