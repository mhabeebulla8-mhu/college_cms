<?php
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$allowedStatuses = ['Pending', 'In Progress', 'Under Review', 'Resolved'];
$categories = [
    "Anti-Sexual Harassment Cell",
    "Anti-Ragging Cell",
    "Anti-Harassment Cell",
    "Grievance Cell",
    "Hygiene/Facility Cell",
    "Disciplinary Committee"
];
$selectedCategory = trim($_GET['category'] ?? '');

// Handle Status Update
if (isset($_POST['update_status'])) {
    $id = (int)($_POST['complaint_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'Pending';
    }
    $updateSql = "UPDATE complaints SET status = ? WHERE id = ?";
    if ($status === 'Under Review' || $status === 'In Progress') {
        $updateSql = "UPDATE complaints SET status = ?, reviewed_at = COALESCE(reviewed_at, CURRENT_TIMESTAMP) WHERE id = ?";
    } elseif ($status === 'Resolved') {
        $updateSql = "UPDATE complaints SET status = ?, resolved_at = COALESCE(resolved_at, CURRENT_TIMESTAMP) WHERE id = ?";
    }

    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("si", $status, $id);
    if ($stmt->execute()) {
        if ($status == 'Resolved') {
            // Get user email to notify
            $stmt_user = $conn->prepare("SELECT u.email, u.name, c.category FROM complaints c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
            $stmt_user->bind_param("i", $id);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();
            if ($row = $result_user->fetch_assoc()) {
                require_once 'mailer.php';
                $to = $row['email'];
                $subject = "Complaint Resolved: " . $row['category'];
                $message = "
                    <h2>Complaint Resolved</h2>
                    <p>Hello " . htmlspecialchars($row['name']) . ",</p>
                    <p>Your complaint regarding <strong>" . htmlspecialchars($row['category']) . "</strong> has been marked as <strong>Resolved</strong> by the administration.</p>
                    <p>Thank you for your patience.</p>
                    <p>Best regards,<br>College Administration</p>
                ";
                sendMail($to, $subject, $message);
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        $stmtDelete = $conn->prepare("DELETE FROM complaints WHERE id = ?");
        $stmtDelete->bind_param("i", $id);
        $stmtDelete->execute();
    }
}

if ($selectedCategory !== '' && !in_array($selectedCategory, $categories, true)) {
    $selectedCategory = '';
}

if ($selectedCategory !== '') {
    $stmtComplaints = $conn->prepare("
        SELECT c.*, u.name as student_name
        FROM complaints c
        JOIN users u ON c.user_id = u.id
        WHERE c.category = ?
        ORDER BY c.created_at DESC
    ");
    $stmtComplaints->bind_param("s", $selectedCategory);
    $stmtComplaints->execute();
    $result = $stmtComplaints->get_result();
} else {
    $result = $conn->query("
        SELECT c.*, u.name as student_name
        FROM complaints c
        JOIN users u ON c.user_id = u.id
        ORDER BY c.created_at DESC
    ");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Student Complaint Management System</title>
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
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container" style="margin-top: 3rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2>All Complaints</h2>
            
            <form action="admin.php" method="GET" style="display: flex; gap: 1rem;">
                <select name="category" onchange="this.form.submit()" style="padding: 0.5rem; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                    <option value="">All Categories</option>
                    <option value="Anti-Sexual Harassment Cell" <?php if($selectedCategory == 'Anti-Sexual Harassment Cell') echo 'selected'; ?>>Anti-Sexual Harassment Cell</option>
                    <option value="Anti-Ragging Cell" <?php if($selectedCategory == 'Anti-Ragging Cell') echo 'selected'; ?>>Anti-Ragging Cell</option>
                    <option value="Anti-Harassment Cell" <?php if($selectedCategory == 'Anti-Harassment Cell') echo 'selected'; ?>>Anti-Harassment Cell</option>
                    <option value="Grievance Cell" <?php if($selectedCategory == 'Grievance Cell') echo 'selected'; ?>>Grievance Cell</option>
                    <option value="Hygiene/Facility Cell" <?php if($selectedCategory == 'Hygiene/Facility Cell') echo 'selected'; ?>>Hygiene/Facility Cell</option>
                    <option value="Disciplinary Committee" <?php if($selectedCategory == 'Disciplinary Committee') echo 'selected'; ?>>Disciplinary Committee</option>
                </select>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Complaint Details</th>
                        <th>Status & Priority</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                    <div style="font-size: 0.7rem; color: #94a3b8;"><?php echo date('d M, h:i A', strtotime($row['created_at'])); ?></div>
                                    <?php if (!empty($row['forwarded_to_main'])): ?>
                                        <span style="font-size: 0.65rem; background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 4px; font-weight: bold; margin-top: 4px; display: inline-block;">FORWARDED BY: <?php echo htmlspecialchars($row['category']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 350px;">
                                    <div style="font-size: 0.8rem; font-weight: 700; color: #3b82f6; margin-bottom: 0.25rem;">
                                        <?php echo htmlspecialchars($row['category']); ?>
                                        <?php if(!empty($row['subcategory'])): ?>
                                            <span style="color: #64748b; font-weight: 400;"> » <?php echo htmlspecialchars($row['subcategory']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 0.9rem; color: #334155;"><?php echo htmlspecialchars($row['description']); ?></div>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                        <form action="admin.php" method="POST" style="display: flex; gap: 0.5rem; align-items: center;">
                                            <input type="hidden" name="complaint_id" value="<?php echo $row['id']; ?>">
                                            <select name="status" style="font-size: 0.75rem; padding: 0.25rem; border-radius: 4px; border: 1px solid #e2e8f0;">
                                                <option value="Pending" <?php if($row['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                                <option value="Under Review" <?php if($row['status'] == 'Under Review' || $row['status'] == 'In Progress') echo 'selected'; ?>>Under Review</option>
                                                <option value="Resolved" <?php if($row['status'] == 'Resolved') echo 'selected'; ?>>Resolved</option>
                                            </select>
                                            <button type="submit" name="update_status" style="background: #3b82f6; color: #fff; border: none; padding: 0.35rem 0.75rem; border-radius: 4px; cursor: pointer; font-size: 0.7rem; font-weight: 700;">Update</button>
                                        </form>
                                        
                                        <?php if(isset($row['priority'])): ?>
                                            <?php 
                                                $prio = strtolower($row['priority']);
                                                $prioColor = ($prio == 'high') ? '#ef4444' : (($prio == 'medium') ? '#f59e0b' : '#3b82f6');
                                            ?>
                                            <span style="font-size: 0.65rem; font-weight: 800; color: <?php echo $prioColor; ?>; text-transform: uppercase; letter-spacing: 0.5px;">
                                                ● <?php echo htmlspecialchars($row['priority']); ?> Priority
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 1rem; align-items: center;">
                                        <?php if($row['file_path']): ?>
                                            <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" style="color: #3b82f6; font-size: 0.8rem; font-weight: 600; text-decoration: none;">View File</a>
                                        <?php endif; ?>
                                        <a href="admin.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?')" style="color: #ef4444; font-size: 0.8rem; font-weight: bold; text-decoration: none;">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 3rem; color: #94a3b8;">No complaints found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
