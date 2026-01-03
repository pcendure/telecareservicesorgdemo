# How to Setup Telecare Services Website on Any Host

This guide provides step-by-step instructions for deploying the Telecare Services website to any web hosting provider. The website consists of static HTML/CSS/JS pages and a PHP backend for handling form submissions (Contact, Referral, Complaints).

## 1. Prerequisites

Before you begin, ensure your hosting environment meets the following requirements:

*   **Web Server**: Apache, Nginx, or IIS (Apache is recommended for simple `.htaccess` support).
*   **PHP**: Version 7.4 or higher.
*   **Database (Optional)**: MySQL or MariaDB (only required if you want to save form submissions to a database; emails will work without this).
*   **Email Services**: The server must be configured to send emails via PHP `mail()` function, or you may need to configure SMTP.

## 2. File Preparation

1.  **Locate Project Files**: Ensure you have the complete `TODAY` directory content.
2.  **Cleanup**: You can exclude the following development files from the upload if desired:
    *   `.vscode/` (if present)
    *   `*.py` (Python automation scripts like `find_duplicates.py`, `validate_links.py`)
    *   `*.md` (Documentation files, including this one)
    *   `.antigravityignore`

## 3. Uploading to Host

1.  **Access your Hosting**: Log in to your hosting control panel (e.g., cPanel, Plesk) or use an FTP client (like FileZilla).
2.  **Navigate to Public Directory**: Go to the public folder, typically named `public_html`, `www`, or `htdocs`.
3.  **Upload Files**: Upload all files and folders from the project root to this directory.
    *   **Crucial Structure**: Ensure `assets/`, `es/`, `contact-handler.php`, and `index.html` are in the root of your public directory.

## 4. Configuration

### A. Email Configuration (Required)

1.  Open `contact-handler.php` (you can edit this before uploading or use the file manager in your hosting panel).
2.  Locate the "Configuration" section near the top (lines 17-20).
3.  **Update Admin Email**: Change `define('ADMIN_EMAIL', 'info@telecareservices.org');` to the email address that should receive form submissions.

### B. Database Configuration (Optional)

If you wish to store a record of all form submissions in a database:

1.  **Create Database**: In your hosting control panel, create a new MySQL database and a user with full permissions for that database.
2.  **Import Schema**:
    *   Open phpMyAdmin (or your preferred database tool).
    *   Select your new database.
    *   Import the `database-schema.sql` file provided in the project files.
3.  **Update Config**:
    *   Open `contact-handler.php`.
    *   Update the database constants with your new credentials:
        ```php
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'your_database_name');
        define('DB_USER', 'your_database_user');
        define('DB_PASS', 'your_database_password');
        ```

### C. Permissions (Linux/Unix Hosts)

Ensure the web server has permission to write to the logs directory if you want error logging:

1.  Create a directory named `logs` in the root (if checking errors) or ensure `contact-handler.php` creates it.
2.  Set permissions for the `cache` directory (created automatically for rate limiting) to `755` or `775`.

## 5. Security Checklist

*   **HTTPS**: Ensure an SSL certificate is installed and active. The forms submit sensitive data.
*   **PHP Version**: Verify your host is running a secure, supported version of PHP.
*   **Hide Errors**: On a production server, ensure `display_errors` is turned Off in your PHP settings (cPanel > Select PHP Version > Options).

## 6. Testing

1.  **Visit the Website**: Go to `https://your-domain.com`.
2.  **Check Navigation**: Click through English and Spanish pages to ensure links work.
3.  **Test Forms**:
    *   Go to **Contact Us**.
    *   Fill out the "Message" form.
    *   Answer the security math question correctly.
    *   Submit and check for the success message.
    *   **Verify Email**: Check the inbox of the `ADMIN_EMAIL` you configured. (Check the Spam folder too).

## 7. Troubleshooting

*   **"Method Not Allowed" or 405 Error**: Ensure your form `action` points correctly to `contact-handler.php`.
*   **Emails not arriving**:
    *   Check your server's mail logs.
    *   Ask your hosting provider if PHP `mail()` is enabled.
    *   Use an SMTP plugin or library instead of standard `mail()` if your host blocks it.
*   **Broken Images/Styles**: ensure the `assets/` folder was uploaded correctly and permissions are set to `644` for files and `755` for directories.
