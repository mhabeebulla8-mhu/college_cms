<?php
require_once 'db.php';

$error = "";
$success = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Find token and check expiry
    $stmt = $conn->prepare("SELECT user_id, expires_at FROM email_verifications WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];
        $expires_at = strtotime($row['expires_at']);
        
        if (time() <= $expires_at) {
            // Update user status
            $conn->begin_transaction();
            try {
                $update = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
                $update->bind_param("i", $user_id);
                $update->execute();
                
                // Delete token
                $delete = $conn->prepare("DELETE FROM email_verifications WHERE user_id = ?");
                $delete->bind_param("i", $user_id);
                $delete->execute();
                
                $conn->commit();
                $success = "Email verified successfully! You can now log in.";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "An error occurred during verification. Please try again.";
            }
        } else {
            $error = "This verification link has expired.";
        }
    } else {
        $error = "Invalid verification link.";
    }
} else {
    $error = "No verification token provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification - Student CMS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="form-container" style="text-align: center;">
        <h2>Email Verification</h2>
        <?php if($error): ?>
            <div style="color: #dc2626; background: #fef2f2; padding: 20px; border-radius: 8px; margin-top: 2rem;">
                <?php echo $error; ?>
            </div>
        <?php else: ?>
            <div style="color: #059669; background: #ecfdf5; padding: 20px; border-radius: 8px; margin-top: 2rem;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        <p style="margin-top: 2rem;">
            <a href="login.php" style="color: #3b82f6; font-weight: bold; text-decoration: none;">Go to Login</a>
        </p>
    </div>
</body>
</html>
