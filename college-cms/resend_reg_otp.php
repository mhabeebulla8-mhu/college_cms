<?php
include 'db.php';
require_once 'mailer.php';

if (!isset($_SESSION['temp_reg'])) {
    header("Location: register.php");
    exit();
}

$temp_data = $_SESSION['temp_reg'];
$name = $temp_data['name'];
$email = $temp_data['email'];

// Cooldown check (60 seconds)
if (isset($temp_data['otp_time']) && (time() - $temp_data['otp_time'] < 60)) {
    header("Location: verify_otp.php?error=Please wait 60 seconds before requesting a new OTP.");
    exit();
}

// Generate new OTP
$otp = rand(100000, 999999);
$_SESSION['temp_reg']['otp'] = $otp;
$_SESSION['temp_reg']['otp_time'] = time();

$subject = "Your New Verification Code - College CMS";
$message = "
    <h2>Welcome to College CMS</h2>
    <p>Hello $name,</p>
    <p>Your new verification code for registration is:</p>
    <h1 style='color: #3b82f6; font-size: 2.5rem; letter-spacing: 5px; text-align: center;'>$otp</h1>
    <p>This code will expire in 10 minutes.</p>
    <p>If you did not request this, please ignore this email.</p>
";

if (sendMail($email, $subject, $message)) {
    header("Location: verify_otp.php?msg=A new verification code has been sent to your email.");
} else {
    header("Location: verify_otp.php?error=Failed to send verification email. Please try again.");
}
exit();
?>
