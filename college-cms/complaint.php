<?php
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$initial_category = isset($_GET['category']) ? $_GET['category'] : "";
if ($_SESSION['role'] == 'admin') {
    header("Location: admin.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category = $_POST['category'];
    $description = $_POST['description'];
    $user_id = $_SESSION['user_id'];
    $file_path = "";

    // File Upload Handling
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $target_dir = "uploads/";
        $file_name = time() . "_" . basename($_FILES["file"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            $file_path = $target_file;
        }
    }

    $stmt = $conn->prepare("INSERT INTO complaints (user_id, category, description, file_path) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $category, $description, $file_path);

    if ($stmt->execute()) {
        $success = "Complaint submitted successfully!";
        
        // Send email notification (XAMPP/PHP version)
        $to = $_SESSION['email'];
        $subject = "Complaint Received: $category";
        $message = "Dear " . $_SESSION['name'] . ",\n\n";
        $message .= "Your complaint regarding $category has been successfully submitted and will be reviewed shortly by the relevant committee.\n\n";
        $message .= "Thank you for bringing this to our attention.\n";
        $headers = "From: " . EMAIL_FROM;

        // Note: mail() might require SMTP setup in XAMPP (php.ini)
        if (!@mail($to, $subject, $message, $headers)) {
            error_log("Failed to send email to $to. Check XAMPP sendmail logs.");
        }
    } else {
        $error = "Failed to submit complaint.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lodge Complaint - Student CMS</title>
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="form-container" style="max-width: 700px;">
        <h2 style="margin-bottom: 1rem;">Lodge a Complaint</h2>
        <p style="color: #64748b; margin-bottom: 2rem;">Please provide detailed information about your grievance.</p>

        <?php if($success): ?>
            <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; text-align: center;">
                <?php echo $success; ?>
                <br><a href="dashboard.php" style="font-weight: bold; text-decoration: underline;">Go to Dashboard</a>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; text-align: center;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="complaint.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Complaint Category</label>
                <select name="category" required>
                    <option value="">Select a category</option>
                    <?php
                    $categories = [
                        "Anti-Sexual Harassment Cell",
                        "Anti-Ragging Cell",
                        "Anti-Harassment Cell",
                        "Grievance Cell",
                        "Hygiene/Facility Cell",
                        "Disciplinary Committee"
                    ];
                    foreach($categories as $cat) {
                        $selected = (strcasecmp(trim($initial_category), trim($cat)) == 0) ? 'selected' : '';
                        echo "<option value=\"$cat\" $selected>$cat</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="6" required placeholder="Describe your complaint..."></textarea>
            </div>
            <div class="form-group">
                <label>Supporting Document (Optional)</label>
                <input type="file" name="file" accept="image/*,.pdf">
            </div>
            <button type="submit" class="btn-block">Submit Complaint</button>
        </form>
    </div>

    <script>
        // Safety net: Force selection from URL if PHP missed it
        window.addEventListener('DOMContentLoaded', (event) => {
            const urlParams = new URLSearchParams(window.location.search);
            let category = urlParams.get('category');
            
            if (category) {
                const select = document.querySelector('select[name="category"]');
                if (select) {
                    // Try exact match first
                    const normalizedTarget = category.trim().toLowerCase();
                    
                    for (let i = 0; i < select.options.length; i++) {
                        const optionValue = select.options[i].value.trim().toLowerCase();
                        if (optionValue === normalizedTarget) {
                            select.selectedIndex = i;
                            break;
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
