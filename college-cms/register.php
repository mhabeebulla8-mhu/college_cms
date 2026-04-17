<?php
include 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'student';
    $university_reg_no = $_POST['university_reg_no'] ?? '';
    $id_card_path = "";

    // File Upload Handling for ID Card
    if (isset($_FILES['id_card']) && $_FILES['id_card']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = "id_" . time() . "_" . basename($_FILES["id_card"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["id_card"]["tmp_name"], $target_file)) {
            $id_card_path = $target_file;
        }
    }

    // Check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $error = "Email already registered!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, university_reg_no, id_card_path) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $password, $role, $university_reg_no, $id_card_path);
        
        if ($stmt->execute()) {
            header("Location: login.php?msg=Registration successful! Please login.");
            exit();
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Student CMS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="form-container" style="max-width: 600px;">
        <h2 style="text-align:center; margin-bottom: 2rem;">Create Account</h2>
        
        <?php if($error): ?>
            <div style="color: red; margin-bottom: 1rem; text-align: center;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required placeholder="">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="">
            </div>
            <div class="form-group">
                <label>University Reg No</label>
                <input type="text" name="university_reg_no" required placeholder="">
            </div>
            <div class="form-group">
                <label>Upload ID Card</label>
                <input type="file" name="id_card" required accept="image/*,.pdf">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="">
            </div>
            <button type="submit" class="btn-block">Register</button>
        </form>
        <p style="text-align:center; margin-top: 1.5rem; font-size: 0.9rem;">
            Already have an account? <a href="login.php" style="color: #3b82f6; font-weight: bold;">Login</a>
        </p>
    </div>
</body>
</html>
