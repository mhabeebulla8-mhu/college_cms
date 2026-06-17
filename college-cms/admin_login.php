<?php
include 'db.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'dept_admin') {
        header("Location: " . ($_SESSION['role'] == 'admin' ? 'admin.php' : 'dept_admin.php'));
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Admin login usually prefers a password over OTP for quicker access
    $stmt = $conn->prepare("SELECT id, name, email, password, role, is_verified FROM users WHERE email = ? AND (role = 'admin' OR role = 'dept_admin')");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            if ($user['is_verified'] == 0) {
                $error = "Admin account not verified. Please contact system owner.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                
                header("Location: " . ($user['role'] == 'admin' ? 'admin.php' : 'dept_admin.php'));
                exit();
            }
        } else {
            $error = "Invalid admin password!";
        }
    } else {
        $error = "Invalid admin credentials or unauthorized access!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Student Complaint Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .admin-login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2.5rem;
            border-radius: 1.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .admin-login-card h2 {
            color: #f8fafc;
            text-align: center;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        .admin-login-card p {
            color: #94a3b8;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }
        .form-group label {
            color: #cbd5e1;
        }
        .form-group input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }
        .form-group input:focus {
            border-color: #3b82f6;
            background: rgba(255, 255, 255, 0.1);
        }
        .btn-admin {
            background: #3b82f6;
            color: white;
            padding: 0.75rem;
            border-radius: 0.75rem;
            font-weight: 600;
            width: 100%;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        .btn-admin:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }
        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            padding: 0.75rem;
            border-radius: 0.5rem;
            text-align: center;
            margin-bottom: 1rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
            font-size: 0.85rem;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: #64748b;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .back-link:hover {
            color: #94a3b8;
        }
        .admin-login-card .toggle-password {
            color: #cbd5e1;
        }
        .admin-login-card .toggle-password:hover {
            color: #f8fafc;
        }
    </style>
</head>
<body>
    <div class="admin-login-card">
        <h2>Admin Portal</h2>
        <p>Restricted access for authorized personnel only</p>

        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="admin_login.php" method="POST">
            <div class="form-group">
                <label>Admin Email</label>
                <input type="email" name="email" required placeholder="admin@college.edu">
            </div>
            <div class="form-group">
                <label>Security Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="adminPassword" required placeholder="••••••••">
                    <button type="button" class="toggle-password" data-target="adminPassword" aria-label="Show password">
                        <span class="eye-open" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 12C2.7 7.7 6.8 5 12 5C17.2 5 21.3 7.7 23 12C21.3 16.3 17.2 19 12 19C6.8 19 2.7 16.3 1 12Z" stroke="currentColor" stroke-width="2"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </span>
                        <span class="eye-closed" aria-hidden="true" style="display:none;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 3L21 21" stroke="currentColor" stroke-width="2"/>
                                <path d="M10.6 10.7C10.2 11.1 10 11.5 10 12C10 13.1 10.9 14 12 14C12.5 14 12.9 13.8 13.3 13.4" stroke="currentColor" stroke-width="2"/>
                                <path d="M6.7 6.8C8.2 5.7 10 5 12 5C17.2 5 21.3 7.7 23 12C22.3 13.7 21.2 15.1 19.8 16.2" stroke="currentColor" stroke-width="2"/>
                                <path d="M1 12C1.7 10.3 2.8 8.9 4.2 7.8" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </span>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-admin">Enter Dashboard</button>
        </form>

        <a href="login.php" class="back-link">← Switch to Student Login</a>
    </div>
    <script>
        document.querySelectorAll('.toggle-password').forEach(function(button) {
            button.addEventListener('click', function() {
                var input = document.getElementById(button.getAttribute('data-target'));
                if (!input) return;

                var openIcon = button.querySelector('.eye-open');
                var closedIcon = button.querySelector('.eye-closed');
                var reveal = input.type === 'password';
                input.type = reveal ? 'text' : 'password';

                if (openIcon && closedIcon) {
                    openIcon.style.display = reveal ? 'none' : 'inline-flex';
                    closedIcon.style.display = reveal ? 'inline-flex' : 'none';
                }

                button.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
            });
        });
    </script>
</body>
</html>
