<?php
include 'db.php';

// Restrict access: Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch the logged-in user's details using prepared statements
$stmt = $conn->prepare("SELECT name, email, university_reg_no, role, id_card_path, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    // Should not happen if session is valid
    session_destroy();
    header("Location: login.php");
    exit();
}

$dashboardLink = $user['role'] === 'admin' ? 'admin.php' : ($user['role'] === 'dept_admin' ? 'dept_admin.php' : 'dashboard.php');
$showComplaintLink = $user['role'] === 'student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - Student Complaint Management System</title>
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
                <?php if($showComplaintLink): ?>
                    <li><a href="complaint.php">Lodge Complaint</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container" style="margin-top: 3rem;">
        <div style="max-width: 600px; margin: 0 auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2>My Profile</h2>
                <a href="<?php echo $dashboardLink; ?>" style="color: #3b82f6; text-decoration: none; font-size: 0.9rem;">&larr; Back to Dashboard</a>
            </div>

            <div class="form-container" style="width: 100%; max-width: 100%; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <div style="width: 80px; height: 80px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; margin: 0 auto 1rem;">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <h3 style="margin: 0; font-size: 1.25rem;"><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p style="color: #64748b; font-size: 0.9rem; margin-top: 0.25rem;"><?php echo ucfirst($user['role']); ?></p>
                </div>

                <div style="display: grid; gap: 1.5rem;">
                    <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;">
                        <label style="display: block; font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: bold; margin-bottom: 0.25rem;">University Seat Number (USN)</label>
                        <span style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($user['university_reg_no'] ?: 'N/A'); ?></span>
                    </div>

                    <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;">
                        <label style="display: block; font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: bold; margin-bottom: 0.25rem;">Email Address</label>
                        <span style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>

                    <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;">
                        <label style="display: block; font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: bold; margin-bottom: 0.25rem;">Member Since</label>
                        <span style="font-weight: 600; color: #1e293b;"><?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
                    </div>

                    <?php if($user['id_card_path']): ?>
                    <div>
                        <label style="display: block; font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: bold; margin-bottom: 0.5rem;">ID Card Document</label>
                        <a href="<?php echo htmlspecialchars($user['id_card_path']); ?>" target="_blank" style="display: inline-flex; align-items: center; color: #3b82f6; font-weight: 600; text-decoration: none; font-size: 0.9rem;">
                            <svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            View Document
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 2.5rem; display: flex; gap: 1rem;">
                    <a href="edit_profile.php" class="btn-block" style="text-align: center; text-decoration: none; background: #3b82f6; color: white;">Edit Profile</a>
                </div>
            </div>
        </div>
    </main>

    <footer style="margin-top: 5rem; padding: 2rem 0; text-align: center; color: #94a3b8; border-top: 1px solid #f1f5f9;">
        <p>&copy; <?php echo date('Y'); ?> Student Complaint Management System</p>
    </footer>
</body>
</html>
