<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MSc/BCA College Institute - Student CMS</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <nav class="container">
            <div class="logo">
                <h1>MSc/BCA College Institute</h1>
                <span>Student CMS</span>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="#policy">Policy</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="<?php echo $_SESSION['role'] == 'admin' ? 'admin.php' : 'dashboard.php'; ?>">Dashboard</a></li>
                    <li><a href="logout.php" class="btn-login">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="btn-login">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="container">
        <section class="hero">
            <h2>Student <span>CMS</span></h2>
            <p>A secure platform for students to voice their concerns.</p>
        </section>

        <div class="main-grid">
            <section class="categories-section">
                <div class="category-grid">
                    <?php
                    $categories = [
                        "Anti-Sexual Harassment Cell" => "🚨",
                        "Anti-Ragging Cell" => "👥",
                        "Anti-Harassment Cell" => "🤝",
                        "Grievance Cell" => "⚖️",
                        "Hygiene/Facility Cell" => "🏢",
                        "Disciplinary Committee" => "🔨"
                    ];
                    foreach($categories as $name => $icon):
                    ?>
                    <div class="category-box" 
                         onclick="window.location.href='complaint.php?category=<?php echo rawurlencode($name); ?>'" 
                         onmouseover="updatePolicy('<?php echo $name; ?>')">
                        <div class="icon"><?php echo $icon; ?></div>
                        <h3><?php echo $name; ?></h3>
                        <a href="complaint.php?category=<?php echo rawurlencode($name); ?>" class="btn-direct-link" onclick="event.stopPropagation()">Lodge Directly →</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <aside id="policy" class="policy-section">
                <h3 id="policy-title">Policy & Guidelines</h3>
                <div class="policy-content">
                    <h4 id="policy-subtitle">When to submit?</h4>
                    <p id="policy-desc">Submit a complaint when you witness or experience any policy violations.</p>
                    
                    <h4 id="guidelines-title">Guidelines</h4>
                    <ul id="policy-list">
                        <li>Be factual and clear.</li>
                        <li>Upload proof if available.</li>
                        <li>Confidentiality is maintained.</li>
                        <li>No false complaints allowed.</li>
                    </ul>

                    <div class="sidebar-action" style="margin-top: 2rem;">
                        <a href="complaint.php" id="lodge-complaint-btn" class="btn-primary" style="display: block; text-align: center; width: 100%;">Lodge Complaint</a>
                    </div>

                    <div id="back-btn-container" style="margin-top: 1rem; display: none;">
                        <button onclick="updatePolicy('General')" style="background: none; border: none; color: #3b82f6; font-weight: bold; cursor: pointer; font-size: 0.8rem;">← Back to General</button>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 MSc/BCA College Institute. All rights reserved.</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
