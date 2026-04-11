# College Complaint Management System (PHP Version)

This version of the application is designed to be run locally using **XAMPP** or **WAMP**.

## 🛠️ Local Setup Instructions (XAMPP)

1.  **Install XAMPP**: Download and install XAMPP from [apachefriends.org](https://www.apachefriends.org/).
2.  **Copy Files**: Copy the `college-cms` folder into your XAMPP's `htdocs` directory (usually `C:\xampp\htdocs\`).
3.  **Start Services**: Open the XAMPP Control Panel and start **Apache** and **MySQL**.
4.  **Create Database**:
    *   Open your browser and go to `http://localhost/phpmyadmin/`.
    *   Create a new database named `college_cms`.
    *   Click on the `Import` tab and select the `database.sql` file located inside the `college-cms` folder.
    *   Click `Go` to run the script.
5.  **Run the App**: Open your browser and go to `http://localhost/college-cms/`.

## 📂 Project Structure
*   `index.php`: The homepage with category grid and policy.
*   `login.php` & `register.php`: Authentication system.
*   `complaint.php`: Form for students to lodge complaints with file upload.
*   `dashboard.php`: Student dashboard to track status.
*   `admin.php`: Admin panel to manage and resolve complaints.
*   `db.php`: Database connection configuration.
*   `css/style.css`: Modern styling using Flexbox and Grid.
*   `uploads/`: Folder where uploaded documents are stored.

## 🔑 Default Admin Credentials
*   **Email**: `admin@college.edu`
*   **Password**: `admin123`

---
*Note: The live preview in AI Studio uses a Node.js version because PHP is not natively supported for live execution in this specific sandboxed environment. Use the instructions above to run the PHP version on your own computer.*
