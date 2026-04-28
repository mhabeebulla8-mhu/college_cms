<?php
include 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = trim($_POST['email']);
    $plain_password = $_POST['password'];

    // --- Password Validation Rules ---
    if (strlen($plain_password) < 8) {
        $error = "Password must be at least 8 characters long!";
        goto render_page;
    }
    // 2. Must start with an uppercase letter
    if (!preg_match('/^[A-Z]/', $plain_password)) {
        $error = "Password must start with an uppercase (capital) letter!";
        goto render_page;
    }
    // 3. Must contain at least one lowercase letter
    if (!preg_match('/[a-z]/', $plain_password)) {
        $error = "Password must contain at least one lowercase letter!";
        goto render_page;
    }
    // 4. Must contain at least one uppercase letter (already covered by start, but good for completeness)
    if (!preg_match('/[A-Z]/', $plain_password)) {
        $error = "Password must contain at least one uppercase letter!";
        goto render_page;
    }
    // 5. Must contain at least one number
    if (!preg_match('/[0-9]/', $plain_password)) {
        $error = "Password must contain at least one number!";
        goto render_page;
    }
    // 6. Must contain at least one special character
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $plain_password)) {
        $error = "Password must contain at least one special character (e.g., !@#$%^&*)!";
        goto render_page;
    }

    $password = password_hash($plain_password, PASSWORD_DEFAULT);

    // 1. Basic Format Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
        goto render_page;
    }

    // 2. Strict Gmail Domain Validation
    if (substr(strtolower($email), -10) !== '@gmail.com') {
        $error = "Only @gmail.com addresses are allowed!";
        goto render_page;
    }

    // 3. Verify Domain exists (Checks MX records)
    $domain = substr($email, strpos($email, '@') + 1);
    if (!checkdnsrr($domain, 'MX')) {
        $error = "The email domain does not appear to be real or cannot receive mail.";
        goto render_page;
    }
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

            // AI ID Verification (Gemini)
            if (defined('GEMINI_API_KEY') && GEMINI_API_KEY !== 'your-gemini-api-key') {
                $image_data = base64_encode(file_get_contents($target_file));
                $mime_type = $_FILES['id_card']['type'];
                
                $prompt = "Does this ID card belong to 'AL AMEEN INSTITUTE OF INFORMATION SCIENCES'? Answer only 'YES' or 'NO'. If the text is clearly visible and matches, say YES. If not, say NO.";
                
                $data = [
                    "contents" => [
                        [
                            "parts" => [
                                ["inline_data" => ["mime_type" => $mime_type, "data" => $image_data]],
                                ["text" => $prompt]
                            ]
                        ]
                    ]
                ];

                $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . GEMINI_API_KEY);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                
                $response = curl_exec($ch);
                $result_ai = json_decode($response, true);
                
                if (is_resource($ch) || (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 80000 && $ch instanceof \CurlHandle)) {
                    curl_close($ch);
                }

                $answer = $result_ai['candidates'][0]['content']['parts'][0]['text'] ?? 'NO';
                
                if (trim(strtoupper($answer)) !== 'YES') {
                    unlink($target_file); // Delete the invalid ID
                    $error = "Invalid ID Card! This platform only accepts students from 'AL AMEEN INSTITUTE OF INFORMATION SCIENCES'.";
                    // Stop registration
                    goto render_page;
                }
            }
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
        // Insert user (not verified yet)
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, university_reg_no, id_card_path, is_verified) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("ssssss", $name, $email, $password, $role, $university_reg_no, $id_card_path);
        
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            
            // Generate verification token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $stmt_v = $conn->prepare("INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt_v->bind_param("iss", $user_id, $token, $expires_at);
            $stmt_v->execute();
            
            // Send Verification Email
            require_once 'mailer.php';
            $verify_link = SITE_URL . "/verify_email.php?token=" . $token;
            $subject = "Verify your Account - College CMS";
            $message = "
                <h2>Welcome to College CMS</h2>
                <p>Hello $name, please click the link below to verify your email address and complete your registration:</p>
                <p><a href='$verify_link' style='padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>Verify Email</a></p>
                <p>This link will expire in 24 hours.</p>
            ";
            
            $mailSent = sendMail($email, $subject, $message);
            if ($mailSent) {
                header("Location: login.php?msg=" . urlencode("Registration successful! Please check your email to verify your account."));
            } else {
                // Do not block registration if SMTP is slow/misconfigured.
                header("Location: login.php?msg=" . urlencode("Registration successful. Email sending failed, so contact admin to verify your account."));
            }
            exit();
        } else {
            $error = "Registration failed: " . $conn->error;
        }
    }
}

render_page:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Student CMS</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime(__DIR__ . '/css/style.css'); ?>">
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
                <label>Email Address (@gmail.com only)</label>
                <input type="email" name="email" required placeholder="example@gmail.com" pattern="[a-zA-Z0-9._%+-]+@gmail\.com$">
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
                <div class="password-wrapper">
                    <input type="password" name="password" id="registerPassword" required placeholder="">
                    <button type="button" class="toggle-password" data-target="registerPassword" aria-label="Show password">
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
                <ul style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem; list-style: inside;">
                    <li>At least 8 characters</li>
                    <li>Starts with uppercase letter</li>
                    <li>Contains lowercase & number</li>
                    <li>Contains special character (!@#$)</li>
                </ul>
            </div>
            <button type="submit" class="btn-block">Register</button>
        </form>
        <p style="text-align:center; margin-top: 1.5rem; font-size: 0.9rem;">
            Already have an account? <a href="login.php" style="color: #3b82f6; font-weight: bold;">Login</a>
        </p>
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
