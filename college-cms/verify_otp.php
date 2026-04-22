<?php
include 'db.php';

if (!isset($_SESSION['temp_reg'])) {
    header("Location: register.php");
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = $_POST['otp'];
    $temp_data = $_SESSION['temp_reg'];

    // Check if OTP is correct and not expired (10 minutes)
    if ($entered_otp == $temp_data['otp']) {
        if (time() - $temp_data['otp_time'] < 600) {
            // OTP is valid! Create the account
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, university_reg_no, id_card_path) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", 
                $temp_data['name'], 
                $temp_data['email'], 
                $temp_data['password'], 
                $temp_data['role'], 
                $temp_data['university_reg_no'], 
                $temp_data['id_card_path']
            );

            if ($stmt->execute()) {
                // Clear temporary session
                unset($_SESSION['temp_reg']);
                header("Location: login.php?msg=Email verified! Account created successfully.");
                exit();
            } else {
                $error = "Database error. Please try again.";
            }
        } else {
            $error = "OTP has expired. Please register again.";
            unset($_SESSION['temp_reg']);
        }
    } else {
        $error = "Invalid OTP code. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Email - College CMS</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .otp-input {
            letter-spacing: 10px;
            font-size: 24px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2 style="text-align:center;">Email Verification</h2>
        <p style="text-align:center; color: #666; margin-bottom: 2rem;">
            A 6-digit verification code has been sent to:<br>
            <strong><?php echo $_SESSION['temp_reg']['email']; ?></strong>
        </p>

        <?php if($error): ?>
            <div style="color: red; margin-bottom: 1rem; text-align: center;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="verify_otp.php" method="POST">
            <div class="form-group">
                <label>Enter 6-Digit Code</label>
                <input type="text" name="otp" class="otp-input" maxlength="6" required placeholder="000000" autocomplete="off">
            </div>
            <button type="submit" class="btn-block">Verify & Create Account</button>
        </form>
        
        <p style="text-align:center; margin-top: 1.5rem; font-size: 0.9rem;">
            Didn't get the code? <a href="register.php" style="color: #3b82f6;">Try registering again</a>
        </p>
    </div>
</body>
</html>
