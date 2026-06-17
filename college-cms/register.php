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
    $university_reg_no = strtoupper(trim($_POST['university_reg_no'] ?? ''));
    $id_card_path = "";

    // --- USN (University Reg No) Validation ---
    if (empty($university_reg_no)) {
        $error = "University Reg No (USN) is required!";
        goto render_page;
    }

    // 1. Regex Validation: U + 2 digits + 2 letters + 2 digits + 1 letter + 4 digits
    // Pattern: /^U\d{2}[A-Z]{2}\d{2}[A-Z]\d{4}$/
    if (preg_match('/^U\d{2}[A-Z]{2}\d{2}[A-Z](\d{4})$/', $university_reg_no, $matches)) {
        $serial = (int)$matches[1];
        if ($serial < 1 || $serial > 125) {
            $error = "USN serial number must be between 0001 and 0125!";
            goto render_page;
        }
    } else {
        $error = "Invalid USN format! Example: U18AS23S0064";
        goto render_page;
    }

    // 2. Check if USN is in valid_students table
    try {
        // Ensure table exists
        $conn->query("CREATE TABLE IF NOT EXISTS valid_students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usn VARCHAR(20) NOT NULL UNIQUE,
            name VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Check if this USN is authorized
        $stmt_valid = $conn->prepare("SELECT id FROM valid_students WHERE usn = ?");
        $stmt_valid->bind_param("s", $university_reg_no);
        $stmt_valid->execute();
        $res_valid = $stmt_valid->get_result();
        
        if ($res_valid->num_rows === 0) {
            // If not found, let's check if there are ANY students. If table is empty, auto-authorize the first one for easier setup.
            $count_check = $conn->query("SELECT COUNT(*) as total FROM valid_students");
            $row_count = $count_check->fetch_assoc();
            if ($row_count['total'] == 0) {
                $conn->query("INSERT INTO valid_students (usn, name) VALUES ('$university_reg_no', 'Authorized Student')");
            } else {
                $error = "This USN ($university_reg_no) is not authorized. Please add it to the valid_students table.";
                goto render_page;
            }
        }
    } catch (Exception $e) {
        die("FATAL DATABASE ERROR: " . $e->getMessage() . " (Target: $host:$port, DB: $dbname)");
    } catch (Error $e) {
        die("FATAL SYSTEM ERROR: " . $e->getMessage());
    }

    // 3. Check if USN is already registered in users table
    $stmt_exists = $conn->prepare("SELECT id FROM users WHERE university_reg_no = ?");
    $stmt_exists->bind_param("s", $university_reg_no);
    $stmt_exists->execute();
    if ($stmt_exists->get_result()->num_rows > 0) {
        $error = "This USN is already registered!";
        goto render_page;
    }

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
                
                if ($ch) curl_close($ch);

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
        // Generate 6-digit OTP
        $otp = rand(100000, 999999);
        
        // Store data in session
        $_SESSION['temp_reg'] = [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'university_reg_no' => $university_reg_no,
            'id_card_path' => $id_card_path,
            'otp' => $otp,
            'otp_time' => time()
        ];
        
        // Send Verification Email with OTP
        require_once 'mailer.php';
        $subject = "Your Verification Code - College CMS";
        $message = "
            <h2>Welcome to College CMS</h2>
            <p>Hello $name,</p>
            <p>Your verification code for registration is:</p>
            <h1 style='color: #3b82f6; font-size: 2.5rem; letter-spacing: 5px; text-align: center;'>$otp</h1>
            <p>This code will expire in 10 minutes.</p>
            <p>If you did not request this, please ignore this email.</p>
        ";
        
        $mailSent = sendMail($email, $subject, $message);
        if ($mailSent) {
            header("Location: verify_otp.php");
        } else {
            $error = "Failed to send verification email. Please try again.";
        }
        exit();
    }
}

render_page:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Student Complaint Management System</title>
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
                <label>University Reg No (USN)</label>
                <input type="text" name="university_reg_no" required style="text-transform: uppercase;">
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
