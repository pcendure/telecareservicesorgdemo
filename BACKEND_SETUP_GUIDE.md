# Telecare Services - Contact Form Backend Setup Guide

## Overview
This backend system handles all contact form submissions (Contact, Referral, and Complaint forms) with enterprise-level security and reliability.

## Features

### Security
- ✅ **CSRF Protection** - Prevents cross-site request forgery attacks
- ✅ **Rate Limiting** - Max 5 submissions per hour per IP
- ✅ **Input Sanitization** - All inputs cleaned and validated
- ✅ **SQL Injection Prevention** - Prepared statements with PDO
- ✅ **Email Validation** - RFC-compliant email checking
- ✅ **Math CAPTCHA** - Simple bot prevention

### Functionality
- ✅ **Multi-form Support** - Contact, Referral, and Complaint forms
- ✅ **Email Notifications** - Sends to admin and confirmation to user
- ✅ **Database Storage** - All submissions logged for record-keeping
- ✅ **Activity Logging** - Tracks all actions for audit trail
- ✅ **Error Logging** - Comprehensive error tracking

## Installation

### Step 1: Database Setup

1. **Create the database:**
```bash
mysql -u root -p < database-schema.sql
```

2. **Configure database credentials** in `contact-handler.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'telecare_db');
define('DB_USER', 'telecare_user');
define('DB_PASS', 'your_secure_password_here');
```

3. **Create database user:**
```sql
CREATE USER 'telecare_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';
GRANT ALL PRIVILEGES ON telecare_db.* TO 'telecare_user'@'localhost';
FLUSH PRIVILEGES;
```

### Step 2: File Structure

Create the following directory structure:
```
/TODAY/
├── contact-handler.php
├── database-schema.sql
├── logs/
│   ├── form-errors.log (auto-created)
│   └── php-errors.log (auto-created)
└── cache/
    └── rate_limit_*.txt (auto-created)
```

### Step 3: Set Permissions

```bash
# Make logs and cache directories writable
chmod 755 logs/
chmod 755 cache/

# Secure the handler file
chmod 644 contact-handler.php
```

### Step 4: Configure Email

Update the admin email in `contact-handler.php`:
```php
define('ADMIN_EMAIL', 'info@telecareservices.org');
```

### Step 5: Update HTML Forms

Add the following to each form in your HTML files:

#### For Contact Form (`contact-us/index.html`):
```html
<form id="contactForm" method="POST" action="../contact-handler.php">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" name="form_type" value="contact">
    
    <!-- Existing form fields -->
    <input type="text" name="first_name" required>
    <input type="text" name="last_name" required>
    <input type="email" name="email" required>
    <input type="tel" name="phone" required>
    <textarea name="message" required></textarea>
    <input type="number" name="math_answer" placeholder="2+2" required>
    
    <button type="submit">Submit</button>
</form>
```

#### For Referral Form:
```html
<form id="referralForm" method="POST" action="../contact-handler.php">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" name="form_type" value="referral">
    
    <!-- Referral-specific fields -->
    <input type="text" name="referrer_name" required>
    <input type="text" name="organization">
    <input type="tel" name="referrer_phone" required>
    <input type="email" name="referrer_email" required>
    <input type="text" name="participant_name" required>
    <input type="text" name="relationship" required>
    <input type="tel" name="participant_phone" required>
    <input type="email" name="participant_email">
    <textarea name="address" required></textarea>
    <textarea name="reason" required></textarea>
    <input type="checkbox" name="services_needed[]" value="Personal Support Services">
    <!-- More checkboxes -->
    <textarea name="additional_details"></textarea>
    <input type="number" name="math_answer" placeholder="2+3" required>
    
    <button type="submit">Submit</button>
</form>
```

#### For Complaint Form:
```html
<form id="complaintForm" method="POST" action="../contact-handler.php">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" name="form_type" value="complaint">
    
    <!-- Complaint-specific fields -->
    <input type="text" name="first_name" required>
    <input type="text" name="last_name" required>
    <input type="email" name="email" required>
    <input type="tel" name="phone" required>
    <textarea name="complaint" required></textarea>
    <input type="number" name="math_answer" placeholder="2+2" required>
    
    <button type="submit">Submit</button>
</form>
```

### Step 6: Add JavaScript for AJAX Submission (Optional)

Add this to your HTML pages for better UX:

```javascript
<script>
document.getElementById('contactForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.textContent = 'Sending...';
    
    try {
        const response = await fetch('../contact-handler.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success message
            alert(result.message);
            this.reset();
        } else {
            // Show error message
            alert('Error: ' + result.message);
            if (result.errors) {
                console.error('Validation errors:', result.errors);
            }
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
        console.error('Submission error:', error);
    } finally {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit';
    }
});
</script>
```

## Testing

### Test Contact Form:
```bash
curl -X POST http://localhost/contact-handler.php \
  -d "form_type=contact" \
  -d "first_name=John" \
  -d "last_name=Doe" \
  -d "email=john@example.com" \
  -d "phone=4432493285" \
  -d "message=Test message" \
  -d "math_answer=4" \
  -d "csrf_token=YOUR_TOKEN_HERE"
```

### Check Database:
```sql
SELECT * FROM form_submissions ORDER BY submitted_at DESC LIMIT 10;
```

### View Logs:
```bash
tail -f logs/form-errors.log
tail -f logs/php-errors.log
```

## Security Checklist

- [ ] Change default admin password in database
- [ ] Update `DB_PASS` with strong password
- [ ] Set proper file permissions (644 for PHP, 755 for directories)
- [ ] Enable HTTPS on production server
- [ ] Configure firewall to block suspicious IPs
- [ ] Set up regular database backups
- [ ] Monitor logs for suspicious activity
- [ ] Test rate limiting functionality
- [ ] Verify CSRF token validation
- [ ] Test email delivery

## Maintenance

### Clear Old Submissions (Monthly):
```sql
DELETE FROM form_submissions 
WHERE submitted_at < DATE_SUB(NOW(), INTERVAL 6 MONTH) 
AND status = 'archived';
```

### Monitor Failed Emails:
```sql
SELECT * FROM email_queue 
WHERE status = 'failed' 
AND attempts >= 3;
```

### Check Submission Statistics:
```sql
SELECT 
    DATE(submitted_at) as date,
    form_type,
    COUNT(*) as count
FROM form_submissions
WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(submitted_at), form_type
ORDER BY date DESC;
```

## Troubleshooting

### Emails Not Sending
1. Check PHP mail configuration: `php -i | grep mail`
2. Verify SMTP settings in php.ini
3. Check email queue: `SELECT * FROM email_queue WHERE status = 'failed'`
4. Review error logs: `tail -f logs/form-errors.log`

### Database Connection Failed
1. Verify credentials in `contact-handler.php`
2. Check MySQL is running: `systemctl status mysql`
3. Test connection: `mysql -u telecare_user -p telecare_db`

### Rate Limiting Issues
1. Clear cache: `rm -rf cache/rate_limit_*`
2. Adjust limit in `contact-handler.php`: `define('MAX_SUBMISSIONS_PER_HOUR', 10);`

## Production Deployment

1. **Disable error display:**
```php
ini_set('display_errors', 0);
error_reporting(0);
```

2. **Enable HTTPS redirect** in `.htaccess`:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

3. **Set up automated backups:**
```bash
# Add to crontab
0 2 * * * mysqldump -u telecare_user -p'password' telecare_db > /backups/telecare_$(date +\%Y\%m\%d).sql
```

4. **Monitor logs with logrotate:**
```bash
# /etc/logrotate.d/telecare
/path/to/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
}
```

## Support

For issues or questions:
- Email: info@telecareservices.org
- Phone: (443) 249-3285

---

**Designed by Endure PC**
