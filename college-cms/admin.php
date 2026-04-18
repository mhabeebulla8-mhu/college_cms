<?php
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle Status Update
if (isset($_POST['update_status'])) {
    $id = $_POST['complaint_id'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE complaints SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    if ($stmt->execute()) {
        if ($status == 'Resolved') {
            // Get user email to notify
            $stmt_user = $conn->prepare("SELECT u.email, u.name, c.category FROM complaints c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
            $stmt_user->bind_param("i", $id);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();
            if ($row = $result_user->fetch_assoc()) {
                $to = $row['email'];
                $subject = "Complaint Resolved: " . $row['category'];
                $message = "Hello " . $row['name'] . ",\n\nYour complaint regarding '" . $row['category'] . "' has been marked as Resolved by the administration.\n\nThank you for bringing this to our attention.\n\nCollege Admin";
                $headers = "From: " . EMAIL_USER;
                mail($to, $subject, $message, $headers);
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM complaints WHERE id = $id");
}

// Filters
$where = "WHERE 1=1";
if (isset($_GET['category']) && $_GET['category'] != '') {
    $cat = $_GET['category'];
    $where .= " AND category = '$cat'";
}

$query = "SELECT c.*, u.name as student_name FROM complaints c JOIN users u ON c.user_id = u.id $where ORDER BY c.created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Student CMS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav class="container">
            <div class="logo">
                <h1>MSc/BCA College Admin</h1>
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
                    <option value="Anti-Sexual Harassment Cell" <?php if(isset($_GET['category']) && $_GET['category'] == 'Anti-Sexual Harassment Cell') echo 'selected'; ?>>Anti-Sexual Harassment Cell</option>
                    <option value="Anti-Ragging Cell" <?php if(isset($_GET['category']) && $_GET['category'] == 'Anti-Ragging Cell') echo 'selected'; ?>>Anti-Ragging Cell</option>
                    <option value="Anti-Harassment Cell" <?php if(isset($_GET['category']) && $_GET['category'] == 'Anti-Harassment Cell') echo 'selected'; ?>>Anti-Harassment Cell</option>
                    <option value="Grievance Cell" <?php if(isset($_GET['category']) && $_GET['category'] == 'Grievance Cell') echo 'selected'; ?>>Grievance Cell</option>
                    <option value="Hygiene/Facility Cell" <?php if(isset($_GET['category']) && $_GET['category'] == 'Hygiene/Facility Cell') echo 'selected'; ?>>Hygiene/Facility Cell</option>
                    <option value="Disciplinary Committee" <?php if(isset($_GET['category']) && $_GET['category'] == 'Disciplinary Committee') echo 'selected'; ?>>Disciplinary Committee</option>
                </select>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700;"><?php echo $row['student_name']; ?></div>
                                    <div style="font-size: 0.7rem; color: #94a3b8;"><?php echo date('d M, h:i A', strtotime($row['created_at'])); ?></div>
                                </td>
                                <td style="font-size: 0.8rem; font-weight: 600;"><?php echo $row['category']; ?></td>
                                <td style="max-width: 300px; font-size: 0.9rem;"><?php echo $row['description']; ?></td>
                                <td>
                                    <form action="admin.php" method="POST" style="display: flex; gap: 0.5rem; align-items: center;">
                                        <input type="hidden" name="complaint_id" value="<?php echo $row['id']; ?>">
                                        <select name="status" style="font-size: 0.75rem; padding: 0.25rem;">
                                            <option value="Pending" <?php if($row['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                            <option value="In Progress" <?php if($row['status'] == 'In Progress') echo 'selected'; ?>>In Progress</option>
                                            <option value="Resolved" <?php if($row['status'] == 'Resolved') echo 'selected'; ?>>Resolved</option>
                                        </select>
                                        <button type="submit" name="update_status" style="background: #3b82f6; color: #fff; border: none; padding: 0.25rem 0.5rem; border-radius: 0.25rem; cursor: pointer; font-size: 0.7rem;">Update</button>
                                    </form>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 1rem; align-items: center;">
                                        <?php if($row['file_path']): ?>
                                            <a href="<?php echo $row['file_path']; ?>" target="_blank" style="color: #3b82f6; font-size: 0.8rem;">View File</a>
                                        <?php endif; ?>
                                        <a href="admin.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?')" style="color: #ef4444; font-size: 0.8rem; font-weight: bold;">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 3rem; color: #94a3b8;">No complaints found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
