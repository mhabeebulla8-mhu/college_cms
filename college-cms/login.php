<?php
include 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $email;
            
            if ($user['role'] == 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Student CMS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="form-container">
        <div class="login-tabs" style="display: flex; background: #f1f5f9; padding: 4px; rounded: 12px; margin-bottom: 2rem;">
            <button id="student-tab" onclick="switchLogin('student')" style="flex: 1; border: none; padding: 10px; font-weight: bold; border-radius: 8px; cursor: pointer; background: #fff; color: #2563eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">Student</button>
            <button id="admin-tab" onclick="switchLogin('admin')" style="flex: 1; border: none; padding: 10px; font-weight: bold; border-radius: 8px; cursor: pointer; background: transparent; color: #64748b;">Admin</button>
        </div>

        <h2 id="login-title" style="text-align:center; margin-bottom: 0.5rem;">Welcome Back</h2>
        <p id="login-subtitle" style="text-align:center; color: #64748b; font-size: 0.9rem; margin-bottom: 2rem;">Login to access your CMS dashboard</p>
        
        <?php if(isset($_GET['msg'])): ?>
            <div style="color: green; margin-bottom: 1rem; text-align: center;"><?php echo $_GET['msg']; ?></div>
        <?php endif; ?>

        <?php if($error): ?>
            <div style="color: red; margin-bottom: 1rem; text-align: center;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="">
            </div>
            <button type="submit" class="btn-block">Login</button>
        </form>
        <p style="text-align:center; margin-top: 1.5rem; font-size: 0.9rem;">
            Don't have an account? <a href="register.php" style="color: #3b82f6; font-weight: bold;">Register</a>
        </p>
    </div>
    <script>
        function switchLogin(type) {
            const studentTab = document.getElementById('student-tab');
            const adminTab = document.getElementById('admin-tab');
            const title = document.getElementById('login-title');
            const subtitle = document.getElementById('login-subtitle');

            if (type === 'admin') {
                adminTab.style.background = '#fff';
                adminTab.style.color = '#2563eb';
                adminTab.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';
                
                studentTab.style.background = 'transparent';
                studentTab.style.color = '#64748b';
                studentTab.style.boxShadow = 'none';
                
                title.innerText = 'Admin Portal';
                subtitle.innerText = 'Access the administrative control panel';
            } else {
                studentTab.style.background = '#fff';
                studentTab.style.color = '#2563eb';
                studentTab.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';
                
                adminTab.style.background = 'transparent';
                adminTab.style.color = '#64748b';
                adminTab.style.boxShadow = 'none';
                
                title.innerText = 'Welcome Back';
                subtitle.innerText = 'Login to access your CMS dashboard';
            }
        }
    </script>
</body>
</html>
