<?php
include 'db.php';

// Restrict access: Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";
$dashboardLink = $_SESSION['role'] === 'admin' ? 'admin.php' : ($_SESSION['role'] === 'dept_admin' ? 'dept_admin.php' : 'dashboard.php');

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    if (empty($name) || empty($email)) {
        $error = "Name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($_SESSION['role'] === 'student' && substr(strtolower($email), -10) !== '@gmail.com') {
        $error = "Student accounts must use a @gmail.com email address.";
    } else {
        // Update user details
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $email, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $success = "Profile updated successfully!";
        } else {
            if ($conn->errno == 1062) { // Duplicate entry for email
                $error = "This email is already registered to another account.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}

// Fetch current user details
$stmt = $conn->prepare("SELECT name, email, university_reg_no FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - Student Complaint Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav class="container">
            <div class="logo">
                <span style="font-size: 1.25rem;">Student Complaint Management System</span>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="<?php echo $dashboardLink; ?>">Dashboard</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container" style="margin-top: 3rem;">
        <div style="max-width: 500px; margin: 0 auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2>Edit Profile</h2>
                <a href="profile.php" style="color: #3b82f6; text-decoration: none; font-size: 0.9rem;">&larr; Back to Profile</a>
            </div>

            <div class="form-container" style="width: 100%; max-width: 100%;">
                <?php if($error): ?>
                    <div style="color: #dc2626; background: #fef2f2; padding: 10px; border-radius: 5px; margin-bottom: 1.5rem; text-align: center; border: 1px solid #fecaca;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if($success): ?>
                    <div style="color: #059669; background: #ecfdf5; padding: 10px; border-radius: 5px; margin-bottom: 1.5rem; text-align: center; border: 1px solid #10b981;">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form action="edit_profile.php" method="POST">
                    <div class="form-group">
                        <label>University Seat Number (USN)</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['university_reg_no']); ?>" disabled style="background-color: #f8fafc; cursor: not-allowed;">
                        <p style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">USN cannot be changed.</p>
                    </div>

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <button type="submit" class="btn-block">Update Profile</button>
                </form>
            </div>
        </div>
    </main>

    <footer style="margin-top: 5rem; padding: 2rem 0; text-align: center; color: #94a3b8; border-top: 1px solid #f1f5f9;">
        <p>&copy; <?php echo date('Y'); ?> Student Complaint Management System</p>
    </footer>
</body>
</html>
