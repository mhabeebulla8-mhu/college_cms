# 🎓 College Complaint Management System

A robust, PHP-based web application designed to help college students easily and securely register grievances, complaints, and queries. The system automatically categorizes complaints and routes them to specific departmental administration cells for fast, organized resolution.

## ✨ Features

- **Multi-Role System:** Dedicated portals for Students, Department Admins, and Super Admins.
- **Smart Routing:** Complaints are categorized (e.g., Anti-Ragging, Sexual Harassment, Facilities) and routed directly to the relevant committee.
- **Automated Email Notifications:** Users receive beautifully formatted HTML email updates when a complaint is registered, tracked, or resolved (powered by PHPMailer).
- **Anonymous Submissions:** Students have the option to hide their identity when lodging sensitive complaints.
- **File Attachments:** Support for uploading PDF documents and images as evidence.
- **OTP Verification:** Secure login and registration using One-Time Passwords (OTP).
- **AI Integration (Optional):** Ability to analyze complaint priority dynamically using Google Gemini AI.

---

## 🛠️ Local Setup Instructions (XAMPP / WAMP)

Since this is a native PHP & MySQL application, you need a local web server to run it.

### 1. Prerequisites
- Download and install [XAMPP](https://www.apachefriends.org/) (or WAMP).
- Clone or download this repository.

### 2. Deployment
- Copy the entire `college-cms` folder into your XAMPP server\'s public directory:
  - **Windows:** `C:\xampp\htdocs\`
  - **Mac:** `/Applications/XAMPP/xamppfiles/htdocs/`

### 3. Database Configuration
- Open the XAMPP Control Panel and **Start** both Apache and MySQL.
- Open your browser and go to `http://localhost/phpmyadmin/`.
- Create a new database and name it exactly: `college_cms`.
- Click on the `Import` tab and upload the `database.sql` file located inside the `college-cms` folder.
- *Optional:* To populate default Department Admins, import the `add_admins.sql` file as well.

### 4. Email Configuration (SMTP)
To enable email notifications, open `college-cms/db.php` and update the constants with your Gmail App Password:
```php
define(\'EMAIL_USER\', \'your-email@gmail.com\');
define(\'EMAIL_PASS\', \'your-16-digit-app-password\');
```

### 5. Run the Application
- Open your browser and navigate to: `http://localhost/college-cms/`

---

## 🔑 Default Test Credentials

If you imported `add_admins.sql`, you can test the system using the following accounts:

### Super Admin
- **Email:** `admin@college.edu`
- **Password:** `admin123`

### Department Admins (Password for all is `dept123`)
| Department / Cell | Email Address |
| :--- | :--- |
| Anti-Sexual Harassment Cell | `sexual-harassment@college.edu` |
| Anti-Ragging Cell | `ragging@college.edu` |
| Anti-Harassment Cell | `harassment@college.edu` |
| Grievance Cell | `grievance@college.edu` |
| Hygiene/Facility Cell | `hygiene@college.edu` |
| Disciplinary Committee | `discipline@college.edu` |

---

## 📂 Project Structure

- `index.php` - Homepage, guidelines, and login portal
- `complaint.php` - Secure student form for lodging complaints
- `mailer.php` - Email template generation and SMTP logic
- `admin.php` - Global system administration
- `dept_admin.php` - Isolated dashboard for specific committee admins
- `db.php` - Database and environment secrets configuration

---
*Developed for Al Ameen Institute of Information Sciences.*
