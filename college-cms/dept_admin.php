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
$my_department = $user_data['department'];

// Handle Status and Remarks Update
if (isset($_POST['update_complaint'])) {
    $id = $_POST['complaint_id'];
    $status = $_POST['status'];
    $remarks = $_POST['remarks'];
    
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
            $to = $row['email'];
            $subject = "Complaint Resolved: " . $row['category'];
            $message = "Hello " . $row['name'] . ",\n\nYour complaint regarding '" . $row['category'] . "' has been marked as Resolved by your department.\n\nCollege Admin";
            $headers = "From: " . EMAIL_USER;
            mail($to, $subject, $message, $headers);
        }
    }
}

// Handle Forward to Main Admin
if (isset($_POST['forward_complaint'])) {
    $id = $_POST['complaint_id'];
    $stmt = $conn->prepare("UPDATE complaints SET forwarded_to_main = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $forward_msg = "Complaint #$id has been forwarded to the Main Admin.";
}

// Statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) c FROM complaints WHERE category = '$my_department'")->fetch_assoc()['c'],
    'pending' => $conn->query("SELECT COUNT(*) c FROM complaints WHERE category = '$my_department' AND status = 'Pending'")->fetch_assoc()['c'],
    'resolved' => $conn->query("SELECT COUNT(*) c FROM complaints WHERE category = '$my_department' AND status = 'Resolved'")->fetch_assoc()['c']
];

// Fetch Complaints
$where = "WHERE c.category = '$my_department'";
if (isset($_GET['status']) && $_GET['status'] != '') {
    $s = $_GET['status'];
    $where .= " AND c.status = '$s'";
}
$query = "SELECT c.*, u.name as student_name FROM complaints c JOIN users u ON c.user_id = u.id $where ORDER BY c.created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dept Admin - <?php echo $my_department; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav class="container">
            <div class="logo">
                <h1>Campus<span>Care</span></h1>
                <span>Department Portal</span>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="logout.php" style="color: #ef4444;">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container" style="margin-top: 2rem;">
        <div style="margin-bottom: 2rem;">
            <h2>Welcome, <?php echo $_SESSION['name']; ?></h2>
            <p style="color: #64748b;">Managing: <strong><?php echo $my_department; ?></strong></p>
        </div>

        <?php if(isset($forward_msg)): ?>
            <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem;">
                <?php echo $forward_msg; ?>
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
                    <option value="Pending" <?php if(isset($_GET['status']) && $_GET['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                    <option value="Under Review" <?php if(isset($_GET['status']) && ($_GET['status'] == 'Under Review' || $_GET['status'] == 'In Progress')) echo 'selected'; ?>>Under Review</option>
                    <option value="Resolved" <?php if(isset($_GET['status']) && $_GET['status'] == 'Resolved') echo 'selected'; ?>>Resolved</option>
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
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $row['student_name']; ?></strong>
                                    <div style="font-size: 0.75rem; color: #94a3b8;"><?php echo date('d M, Y', strtotime($row['created_at'])); ?></div>
                                    <?php if($row['forwarded_to_main']): ?>
                                        <span class="status-badge" style="background: #f1f5f9; color: #64748b; margin-top: 0.5rem; display: inline-block;">Forwarded to Main Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 350px;">
                                    <div style="font-size: 0.9rem;"><?php echo $row['description']; ?></div>
                                    <?php if($row['file_path']): ?>
                                        <a href="<?php echo $row['file_path']; ?>" target="_blank" style="color: #3b82f6; font-size: 0.8rem; display: block; margin-top: 0.5rem;">View Attachment</a>
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
                                            <textarea name="remarks" placeholder="Add remarks..." style="width: 100%; font-size: 0.8rem; border: 1px solid #e2e8f0; border-radius: 4px;"><?php echo $row['remarks']; ?></textarea>
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
        <p>CampusCare – Al-Ameen Institute of Information Science</p>
    </footer>
</body>
</html>
