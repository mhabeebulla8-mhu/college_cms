<?php
include 'db.php';
require_once 'mailer.php';

// Fallback to prevent fatal errors if mailer helpers are unavailable.
if (!function_exists('analyzePriority')) {
    function analyzePriority($description, $category) {
        return 'Medium';
    }
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$initial_category = isset($_GET['category']) ? $_GET['category'] : "";
if ($_SESSION['role'] !== 'student') {
    header("Location: " . ($_SESSION['role'] == 'admin' ? 'admin.php' : 'dept_admin.php'));
    exit();
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category = trim($_POST['category']);
    $subcategory = $_POST['subcategory'] ?? "";
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    $description = trim($_POST['description']);
    $priority = analyzePriority($description, $category);
    $user_id = $_SESSION['user_id'];
    $file_path = "";

    if ($category === "" || $subcategory === "" || $description === "") {
        $error = "Please complete all required complaint details.";
        goto render_page;
    }

    // File Upload Handling
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = time() . "_" . basename($_FILES["file"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            $file_path = $target_file;
        } else {
            $error = "Failed to upload the supporting document.";
            goto render_page;
        }
    }

    $stmt = $conn->prepare("INSERT INTO complaints (user_id, category, subcategory, is_anonymous, description, file_path, priority) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississs", $user_id, $category, $subcategory, $is_anonymous, $description, $file_path, $priority);

    if ($stmt->execute()) {
        $success = "Complaint submitted successfully!";
        
        // Use Gemini AI to generate a nice email if available
        $studentName = $_SESSION['name'];
        $emailBody = generateGeminiEmail($studentName, $category);
        
        if (!$emailBody) {
            // Fallback content if AI fails or key is missing
            $emailBody = "
                <h2>Complaint Received</h2>
                <p>Dear $studentName,</p>
                <p>Your complaint regarding <strong>$category</strong> has been successfully submitted and will be reviewed shortly by the relevant committee.</p>
                <p>Thank you for bringing this to our attention.</p>
            ";
        }
        
        $to = $_SESSION['email'];
        $subject = "Complaint Received: $category";
        
        // Use PHPMailer via the sendMail function
        sendMail($to, $subject, $emailBody);
    } else {
        $error = "Failed to submit complaint.";
    }
}

render_page:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lodge Complaint - Student Complaint Management System</title>
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="profile.php">Profile</a></li>
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
                <select name="category" id="categorySelect" required onchange="updateSubcategories()">
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
                <label>Specific Sub-category</label>
                <select name="subcategory" id="subcategorySelect" required>
                    <option value="">Select a sub-category</option>
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
            <div class="form-group" style="display: flex; align-items: center; gap: 0.75rem; background: #f8fafc; padding: 1rem; border-radius: 1rem; border: 1px dashed #cbd5e1;">
                <input type="checkbox" name="is_anonymous" id="is_anonymous" style="width: 1.2rem; height: 1.2rem; cursor: pointer;">
                <label for="is_anonymous" style="cursor: pointer; margin-bottom: 0;">
                    <strong>Submit Anonymously</strong>
                    <p style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">Your identity will be hidden from the administration.</p>
                </label>
            </div>
            <button type="submit" class="btn-block">Submit Complaint</button>
        </form>
    </div>

    <script src="js/main.js"></script>
    <script>
        const subcats = {
            "Anti-Sexual Harassment Cell": ["Physical Harassment", "Verbal Misconduct", "Digital/Online Harassment", "Stalking", "Gender Discrimination"],
            "Anti-Ragging Cell": ["Physical Ragging", "Psychological Bullying", "Financial Extortion", "Hostel-related Ragging", "Forceful Activities"],
            "Anti-Harassment Cell": ["Cyber Bullying", "Verbal Abuse", "Social Exclusion", "Teacher/Staff Misconduct", "Discrimination (Caste/Religion)"],
            "Grievance Cell": ["Internal Marks Issue", "Attendance Error", "Exam Schedule Conflict", "Certificate/Document Delay", "Admission Grievance"],
            "Hygiene/Facility Cell": ["Washroom Cleanliness", "Canteen Food Quality", "Drinking Water Issue", "Classroom/Lab Infrastructure", "Library Facilities"],
            "Disciplinary Committee": ["Dress Code Violation", "Mobile Phone Misuse", "Theft/Damage to Property", "Banned Substance Possession", "Fights/Physical Altercations"]
        };

        function updateSubcategories() {
            const category = document.getElementById('categorySelect').value;
            const subSelect = document.getElementById('subcategorySelect');
            subSelect.innerHTML = '<option value="">Select a sub-category</option>';
            
            if (category && subcats[category]) {
                subcats[category].forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s;
                    opt.textContent = s;
                    subSelect.appendChild(opt);
                });
            }
        }

        // Safety net: Force selection from URL if PHP missed it
        window.addEventListener('DOMContentLoaded', (event) => {
            const urlParams = new URLSearchParams(window.location.search);
            let category = urlParams.get('category');
            
            if (category) {
                const select = document.getElementById('categorySelect');
                if (select) {
                    const normalizedTarget = category.trim().toLowerCase();
                    for (let i = 0; i < select.options.length; i++) {
                        if (select.options[i].value.trim().toLowerCase() === normalizedTarget) {
                            select.selectedIndex = i;
                            updateSubcategories();
                            break;
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
