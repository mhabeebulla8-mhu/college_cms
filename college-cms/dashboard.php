<?php
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM complaints WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

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
    <title>Student Dashboard - Student Complaint Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .nav-links a.active {
            color: #3b82f6;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 0.25rem;
        }
        .priority-badge {
            font-size: 0.65rem;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .priority-high { background: #fee2e2; color: #991b1b; }
        .priority-medium { background: #fef3c7; color: #92400e; }
        .priority-low { background: #f0f9ff; color: #075985; }
    </style>
</head>
<body>
    <header>
        <nav class="container">
            <div class="logo">
                <span style="font-size: 1.25rem;">Student Complaint Management System</span>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="complaint.php">Lodge Complaint</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container" style="margin-top: 3rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h2 style="font-size: 1.8rem; font-weight: 800; color: #1e293b;">My Complaints</h2>
                <p style="color: #64748b; font-size: 0.9rem;">Track the status and resolution of your grievances.</p>
            </div>
            <div style="background: #fff; padding: 0.75rem 1.5rem; border-radius: 1rem; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;">Welcome, </span>
                <span style="font-weight: 800; color: #3b82f6;"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Details</th>
                        <th>Description</th>
                        <th>Status & Priority</th>
                        <th>Attachment</th>
                        <th>Timeline</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($row['category']); ?></div>
                                    <?php if(!empty($row['subcategory'])): ?>
                                        <div style="font-size: 0.75rem; color: #64748b;"><?php echo htmlspecialchars($row['subcategory']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 350px;">
                                    <div style="font-size: 0.9rem; color: #334155; line-height: 1.5;">
                                        <?php 
                                            $desc = htmlspecialchars($row['description']);
                                            echo strlen($desc) > 120 ? substr($desc, 0, 120) . '...' : $desc; 
                                        ?>
                                    </div>
                                    <?php if(!empty($row['remarks'])): ?>
                                        <div style="margin-top: 0.75rem; padding: 0.5rem 0.75rem; background: #f8fafc; border-left: 3px solid #3b82f6; border-radius: 4px;">
                                            <span style="font-size: 0.7rem; font-weight: 700; color: #3b82f6; text-transform: uppercase; display: block; margin-bottom: 2px;">Admin Remarks</span>
                                            <p style="font-size: 0.8rem; color: #475569; font-style: italic;"><?php echo htmlspecialchars($row['remarks']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: flex-start;">
                                        <?php 
                                            $statusClass = 'status-pending';
                                            if($row['status'] == 'In Progress') $statusClass = 'status-progress';
                                            if($row['status'] == 'Under Review') $statusClass = 'status-review';
                                            if($row['status'] == 'Resolved') $statusClass = 'status-resolved';
                                        ?>
                                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                                        
                                        <?php if(isset($row['priority'])): ?>
                                            <?php 
                                                $prio = strtolower($row['priority']);
                                                $prioClass = 'priority-' . $prio;
                                            ?>
                                            <span class="priority-badge <?php echo $prioClass; ?>">
                                                <?php echo htmlspecialchars($row['priority']); ?> Priority
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if($row['file_path']): ?>
                                        <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 0.4rem; color: #3b82f6; font-size: 0.8rem; font-weight: 600; text-decoration: none; padding: 0.4rem 0.75rem; background: #eff6ff; border-radius: 0.5rem; transition: background 0.2s;">
                                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1; font-size: 0.8rem; font-style: italic;">No attachment</span>
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
                                            <span style="white-space: nowrap;">Submitted (<?php echo date('M d', strtotime($row['created_at'])); ?>)</span>
                                        </span>
                                        <span class="timeline-separator">→</span>
                                        <span class="timeline-step <?php echo !empty($row['reviewed_at']) || $isResolvedStage ? 'done' : ($isReviewStage ? 'current' : ''); ?>">
                                            <span class="dot"></span>
                                            <span style="white-space: nowrap;">In Review</span>
                                        </span>
                                        <span class="timeline-separator">→</span>
                                        <span class="timeline-step <?php echo $isResolvedStage ? 'current done' : ''; ?>">
                                            <span class="dot"></span>
                                            <span style="white-space: nowrap;">Resolved</span>
                                        </span>
                                    </div>
                                    <?php if(!empty($row['resolved_at'])): ?>
                                        <div style="font-size: 0.72rem; color: #059669; font-weight: 600; margin-top: 0.35rem; display: flex; align-items: center; gap: 0.25rem;">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                                            <?php echo getResolvedDuration($row['created_at'], $row['resolved_at']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 5rem 2rem;">
                                <div style="margin-bottom: 1.5rem; color: #cbd5e1;">
                                    <svg width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin: 0 auto; display: block;"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <h3 style="color: #64748b; margin-bottom: 0.5rem;">No complaints yet</h3>
                                <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 1.5rem;">If you have any grievances, we are here to help.</p>
                                <a href="complaint.php" class="btn-primary" style="padding: 0.75rem 2rem; font-size: 0.9rem;">Submit Your First Complaint</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
