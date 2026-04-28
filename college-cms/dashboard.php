<?php
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM complaints WHERE user_id = $user_id ORDER BY created_at DESC";
$result = $conn->query($query);

function formatTimelineTime($value) {
    if (empty($value)) {
        return "";
    }
    return date('d M Y, h:i A', strtotime($value));
}

function getResolvedDuration($createdAt, $resolvedAt) {
    if (empty($createdAt) || empty($resolvedAt)) {
        return "";
    }

    $seconds = strtotime($resolvedAt) - strtotime($createdAt);
    if ($seconds <= 0) {
        return "";
    }

    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);

    if ($days > 0) {
        return "Resolved in " . $days . " day" . ($days > 1 ? "s" : "");
    }
    if ($hours > 0) {
        return "Resolved in " . $hours . " hour" . ($hours > 1 ? "s" : "");
    }
    if ($minutes > 0) {
        return "Resolved in " . $minutes . " minute" . ($minutes > 1 ? "s" : "");
    }
    return "Resolved in less than a minute";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard - Student CMS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav class="container">
            <div class="logo">
                <h1>MSc/BCA College</h1>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="complaint.php">Lodge Complaint</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container" style="margin-top: 3rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2>My Complaints</h2>
            <div style="background: #fff; padding: 0.5rem 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0;">
                <span style="font-size: 0.8rem; color: #64748b; font-weight: bold;">Welcome, </span>
                <span style="font-weight: 800;"><?php echo $_SESSION['name']; ?></span>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>File</th>
                        <th>Timeline</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight: 700; color: #3b82f6;"><?php echo $row['category']; ?></td>
                                <td style="max-width: 400px;"><?php echo substr($row['description'], 0, 100) . (strlen($row['description']) > 100 ? '...' : ''); ?></td>
                                <td>
                                    <?php 
                                        $statusClass = 'status-pending';
                                        if($row['status'] == 'In Progress' || $row['status'] == 'Under Review') $statusClass = 'status-review';
                                        if($row['status'] == 'Resolved') $statusClass = 'status-resolved';
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $row['status']; ?></span>
                                </td>
                                <td>
                                    <?php if($row['file_path']): ?>
                                        <a href="<?php echo $row['file_path']; ?>" target="_blank" style="color: #3b82f6; font-size: 0.8rem;">View File</a>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1; font-size: 0.8rem;">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $isReviewStage = ($row['status'] == 'Under Review' || $row['status'] == 'In Progress');
                                        $isResolvedStage = ($row['status'] == 'Resolved');
                                    ?>
                                    <div class="complaint-timeline">
                                        <span class="timeline-step <?php echo $isReviewStage || $isResolvedStage ? 'done' : 'current'; ?>">
                                            <span class="dot"></span>
                                            Submitted (<?php echo formatTimelineTime($row['created_at']); ?>)
                                        </span>
                                        <span class="timeline-separator">→</span>
                                        <span class="timeline-step <?php echo !empty($row['reviewed_at']) || $isResolvedStage ? 'done' : ($isReviewStage ? 'current' : ''); ?>">
                                            <span class="dot"></span>
                                            Under Review<?php echo !empty($row['reviewed_at']) ? " (" . formatTimelineTime($row['reviewed_at']) . ")" : ""; ?>
                                        </span>
                                        <span class="timeline-separator">→</span>
                                        <span class="timeline-step <?php echo $isResolvedStage ? 'current done' : ''; ?>">
                                            <span class="dot"></span>
                                            Resolved<?php echo !empty($row['resolved_at']) ? " (" . formatTimelineTime($row['resolved_at']) . ")" : ""; ?>
                                        </span>
                                    </div>
                                    <?php if(!empty($row['resolved_at'])): ?>
                                        <div style="font-size: 0.72rem; color: #64748b; margin-top: 0.35rem;">
                                            <?php echo getResolvedDuration($row['created_at'], $row['resolved_at']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 3rem; color: #94a3b8;">No complaints submitted yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
