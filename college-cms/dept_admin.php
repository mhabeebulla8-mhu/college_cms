<?php
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'dept_admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get the admin's assigned department
$stmt_dept = $conn->prepare("SELECT department FROM users WHERE id = ?");
$stmt_dept->bind_param("i", $user_id);
$stmt_dept->execute();
$dept_res = $stmt_dept->get_result();
$user_data = $dept_res->fetch_assoc();
$my_department = $user_data['department'] ?? '';
$allowedStatuses = ['Pending', 'In Progress', 'Under Review', 'Resolved'];
$selectedStatus = trim($_GET['status'] ?? '');

if ($selectedStatus !== '' && !in_array($selectedStatus, $allowedStatuses, true)) {
    $selectedStatus = '';
}

// Handle Status and Remarks Update
if ($my_department !== '' && isset($_POST['update_complaint'])) {
    $id = (int)($_POST['complaint_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'Pending';
    }
    
    $updateSql = "UPDATE complaints SET status = ?, remarks = ? WHERE id = ?";
    if ($status === 'Under Review' || $status === 'In Progress') {
        $updateSql = "UPDATE complaints SET status = ?, remarks = ?, reviewed_at = COALESCE(reviewed_at, CURRENT_TIMESTAMP) WHERE id = ?";
    } elseif ($status === 'Resolved') {
        $updateSql = "UPDATE complaints SET status = ?, remarks = ?, resolved_at = COALESCE(resolved_at, CURRENT_TIMESTAMP) WHERE id = ?";
    }

    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("ssi", $status, $remarks, $id);
    $stmt->execute();
    
    // Email notification if resolved
    if ($status == 'Resolved') {
        $stmt_user = $conn->prepare("SELECT u.email, u.name, c.category FROM complaints c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
        $stmt_user->bind_param("i", $id);
        $stmt_user->execute();
        if ($row = $stmt_user->get_result()->fetch_assoc()) {
            require_once 'mailer.php';
            $to = $row['email'];
            $subject = "Complaint Resolved: " . $row['category'];
            $message = "
                <h2>Complaint Resolved</h2>
                <p>Hello " . htmlspecialchars($row['name']) . ",</p>
                <p>Your complaint regarding <strong>" . htmlspecialchars($row['category']) . "</strong> has been marked as <strong>Resolved</strong> by your department.</p>
                <p><strong>Remarks:</strong> " . htmlspecialchars($remarks) . "</p>
                <p>Thank you for your patience.</p>
                <p>Best regards,<br>" . htmlspecialchars($my_department) . "</p>
            ";
            sendMail($to, $subject, $message);
        }
    }
}

// Handle Forward to Main Admin
if ($my_department !== '' && isset($_POST['forward_complaint'])) {
    $id = (int)($_POST['complaint_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE complaints SET forwarded_to_main = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $forward_msg = "Complaint #$id has been forwarded to the Main Admin.";
}

$stats = ['total' => 0, 'pending' => 0, 'resolved' => 0];
if ($my_department !== '') {
    $stmtStats = $conn->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved
        FROM complaints
        WHERE category = ?
    ");
    $stmtStats->bind_param("s", $my_department);
    $stmtStats->execute();
    $statsRow = $stmtStats->get_result()->fetch_assoc();
    $stats = [
        'total' => (int)($statsRow['total'] ?? 0),
        'pending' => (int)($statsRow['pending'] ?? 0),
        'resolved' => (int)($statsRow['resolved'] ?? 0)
    ];

    if ($selectedStatus !== '') {
        $stmtComplaints = $conn->prepare("
            SELECT c.*, u.name as student_name
            FROM complaints c
            JOIN users u ON c.user_id = u.id
            WHERE c.category = ? AND c.status = ?
            ORDER BY c.created_at DESC
        ");
        $stmtComplaints->bind_param("ss", $my_department, $selectedStatus);
        $stmtComplaints->execute();
        $result = $stmtComplaints->get_result();
    } else {
        $stmtComplaints = $conn->prepare("
            SELECT c.*, u.name as student_name
            FROM complaints c
            JOIN users u ON c.user_id = u.id
            WHERE c.category = ?
            ORDER BY c.created_at DESC
        ");
        $stmtComplaints->bind_param("s", $my_department);
        $stmtComplaints->execute();
        $result = $stmtComplaints->get_result();
    }
} else {
    $result = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dept Admin - <?php echo htmlspecialchars($my_department ?: 'Unassigned'); ?></title>
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
                <li><a href="logout.php" style="color: #ef4444;">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container" style="margin-top: 2rem;">
        <div style="margin-bottom: 2rem;">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h2>
            <p style="color: #64748b;">Managing: <strong><?php echo htmlspecialchars($my_department ?: 'No department assigned'); ?></strong></p>
        </div>

        <?php if(isset($forward_msg)): ?>
            <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem;">
                <?php echo htmlspecialchars($forward_msg); ?>
            </div>
        <?php endif; ?>

        <?php if($my_department === ''): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem;">
                Your department admin account is not assigned to any department yet.
            </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 3rem;">
            <div class="category-box">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Cases</p>
            </div>
            <div class="category-box" style="border-left: 4px solid #f59e0b;">
                <h3 style="color: #b45309;"><?php echo $stats['pending']; ?></h3>
                <p>Pending</p>
            </div>
            <div class="category-box" style="border-left: 4px solid #10b981;">
                <h3 style="color: #047857;"><?php echo $stats['resolved']; ?></h3>
                <p>Resolved</p>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3>Department Complaints</h3>
            <form action="dept_admin.php" method="GET">
                <select name="status" onchange="this.form.submit()" style="padding: 0.5rem; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                    <option value="">All Status</option>
                    <option value="Pending" <?php if($selectedStatus == 'Pending') echo 'selected'; ?>>Pending</option>
                    <option value="Under Review" <?php if($selectedStatus == 'Under Review' || $selectedStatus == 'In Progress') echo 'selected'; ?>>Under Review</option>
                    <option value="Resolved" <?php if($selectedStatus == 'Resolved') echo 'selected'; ?>>Resolved</option>
                </select>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Complaint Details</th>
                        <th>Action & Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;"><?php echo date('d M, Y', strtotime($row['created_at'])); ?></div>
                                    <?php if($row['forwarded_to_main']): ?>
                                        <span class="status-badge" style="background: #f1f5f9; color: #64748b; margin-top: 0.5rem; display: inline-block;">Forwarded to Main Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 350px;">
                                    <?php if(!empty($row['subcategory'])): ?>
                                        <div style="font-size: 0.75rem; font-weight: 700; color: #3b82f6; margin-bottom: 0.25rem; text-transform: uppercase;"><?php echo htmlspecialchars($row['subcategory']); ?></div>
                                    <?php endif; ?>
                                    <div style="font-size: 0.9rem; color: #334155; line-height: 1.5;"><?php echo htmlspecialchars($row['description']); ?></div>
                                    
                                    <?php if(isset($row['priority'])): ?>
                                        <div style="margin-top: 0.5rem;">
                                            <?php 
                                                $prio = strtolower($row['priority']);
                                                $prioColor = ($prio == 'high') ? '#ef4444' : (($prio == 'medium') ? '#f59e0b' : '#3b82f6');
                                            ?>
                                            <span style="font-size: 0.65rem; font-weight: 800; color: <?php echo $prioColor; ?>; text-transform: uppercase;">
                                                ● <?php echo htmlspecialchars($row['priority']); ?> Priority
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if($row['file_path']): ?>
                                        <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" style="color: #3b82f6; font-size: 0.8rem; display: block; margin-top: 0.75rem; font-weight: 600;">View Attachment</a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form action="dept_admin.php" method="POST">
                                        <input type="hidden" name="complaint_id" value="<?php echo $row['id']; ?>">
                                        
                                        <div style="margin-bottom: 0.5rem;">
                                            <select name="status" style="width: 100%; padding: 0.25rem;">
                                                <option value="Pending" <?php if($row['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                                <option value="Under Review" <?php if($row['status'] == 'Under Review' || $row['status'] == 'In Progress') echo 'selected'; ?>>Under Review</option>
                                                <option value="Resolved" <?php if($row['status'] == 'Resolved') echo 'selected'; ?>>Resolved</option>
                                            </select>
                                        </div>
                                        
                                        <div style="margin-bottom: 0.5rem;">
                                            <textarea name="remarks" placeholder="Add remarks..." style="width: 100%; font-size: 0.8rem; border: 1px solid #e2e8f0; border-radius: 4px;"><?php echo htmlspecialchars($row['remarks'] ?? ''); ?></textarea>
                                        </div>

                                        <div style="display: flex; gap: 0.5rem;">
                                            <button type="submit" name="update_complaint" class="btn-block" style="padding: 0.5rem; font-size: 0.75rem;">Save Update</button>
                                            <button type="submit" name="forward_complaint" style="background: #f1f5f9; color: #1e293b; border: 1px solid #e2e8f0; padding: 0.5rem; border-radius: 0.75rem; cursor: pointer; font-size: 0.75rem;">Forward</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 3rem; color: #94a3b8;">No complaints found for your department.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Student Complaint Management System. All rights reserved.</p>
    </footer>
</body>
</html>
