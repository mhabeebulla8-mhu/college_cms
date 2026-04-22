<?php
require_once 'db.php';
require_once 'mailer.php';

if (!isset($_SESSION['otp_email']) || !isset($_SESSION['otp_user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['otp_user_id'];
$email = $_SESSION['otp_email'];

// Cooldown check (60 seconds)
$stmt_check = $conn->prepare("SELECT created_at FROM otp_codes WHERE user_id = ?");
$stmt_check->bind_param("i", $user_id);
$stmt_check->execute();
$res = $stmt_check->get_result();

if ($res->num_rows == 1) {
    $row = $res->fetch_assoc();
    $last_sent = strtotime($row['created_at']);
    if (time() - $last_sent < 60) {
        header("Location: login_otp.php?error=Please wait 60 seconds before requesting a new OTP.");
        exit();
    }
}

// Generate new OTP
$otp = rand(100000, 999999);
$otp_hash = password_hash($otp, PASSWORD_DEFAULT);
$expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Update existing OTP
$upd = $conn->prepare("UPDATE otp_codes SET otp_hash = ?, expires_at = ?, attempts = 0, created_at = CURRENT_TIMESTAMP WHERE user_id = ?");
$upd->bind_param("ssi", $otp_hash, $expires_at, $user_id);

if ($upd->execute()) {
    $subject = "Your New Login OTP - College CMS";
    $message = "
        <h2>New Login Verification</h2>
        <p>Your new One-Time Password (OTP) for login is: <b style='font-size: 1.5rem; color: #3b82f6;'>$otp</b></p>
        <p>This code will expire in 5 minutes.</p>
    ";
    
    if (sendMail($email, $subject, $message)) {
        header("Location: login_otp.php?msg=A new OTP has been sent to your email.");
    } else {
        header("Location: login_otp.php?error=Failed to send email.");
    }
} else {
    header("Location: login_otp.php?error=An error occurred.");
}
exit();
?>
