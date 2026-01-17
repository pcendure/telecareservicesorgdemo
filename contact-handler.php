<?php
/**
 * Telecare Services - Contact Form Handler
 * Handles form submissions for Contact, Referral, and Complaint forms
 * 
 * Security Features:
 * - CSRF token validation
 * - Input sanitization
 * - Rate limiting
 * - Email validation
 * - SQL injection prevention
 */

// Start session for CSRF protection
session_start();

// Configuration
define('ADMIN_EMAIL', 'info@telecareservices.org');
define('SITE_NAME', 'Telecare Services');
define('MAX_SUBMISSIONS_PER_HOUR', 5);

// Database configuration (optional - for storing submissions)
define('DB_HOST', 'localhost');
define('DB_NAME', 'telecare_db');
define('DB_USER', 'telecare_user');
define('DB_PASS', 'your_secure_password');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php-errors.log');

/**
 * Main form handler
 */
class ContactFormHandler {
    private $db;
    private $errors = [];
    private $formType;
    
    public function __construct() {
        $this->connectDatabase();
    }
    
    /**
     * Connect to database
     */
    private function connectDatabase() {
        try {
            $this->db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            $this->logError("Database connection failed: " . $e->getMessage());
            // Continue without database - emails will still work
            $this->db = null;
        }
    }
    
    /**
     * Process form submission
     */
    public function processSubmission() {
        // Check request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('Invalid request method', 405);
            return;
        }
        
        // Validate CSRF token
        if (!$this->validateCSRFToken()) {
            $this->sendError('Invalid security token. Please refresh and try again.', 403);
            return;
        }
        
        // Check rate limiting
        if (!$this->checkRateLimit()) {
            $this->sendError('Too many submissions. Please try again later.', 429);
            return;
        }
        
        // Determine form type
        $this->formType = $this->sanitize($_POST['form_type'] ?? 'contact');
        
        // Validate and process based on form type
        switch ($this->formType) {
            case 'contact':
                $this->processContactForm();
                break;
            case 'referral':
                $this->processReferralForm();
                break;
            case 'complaint':
                $this->processComplaintForm();
                break;
            default:
                $this->sendError('Invalid form type', 400);
                return;
        }
        
        // If validation passed, send emails and save to database
        if (empty($this->errors)) {
            $this->sendEmails();
            $this->saveToDatabase();
            $this->sendSuccess();
        } else {
            $this->sendError('Validation failed', 400);
        }
    }
    
    /**
     * Validate CSRF token
     */
    private function validateCSRFToken() {
        $token = $_POST['csrf_token'] ?? '';
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Check rate limiting
     */
    private function checkRateLimit() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $cacheFile = __DIR__ . '/cache/rate_limit_' . md5($ip) . '.txt';
        
        if (!file_exists(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0755, true);
        }
        
        $submissions = [];
        if (file_exists($cacheFile)) {
            $submissions = json_decode(file_get_contents($cacheFile), true) ?? [];
        }
        
        // Remove submissions older than 1 hour
        $oneHourAgo = time() - 3600;
        $submissions = array_filter($submissions, function($timestamp) use ($oneHourAgo) {
            return $timestamp > $oneHourAgo;
        });
        
        // Check if limit exceeded
        if (count($submissions) >= MAX_SUBMISSIONS_PER_HOUR) {
            return false;
        }
        
        // Add current submission
        $submissions[] = time();
        file_put_contents($cacheFile, json_encode($submissions));
        
        return true;
    }
    
    /**
     * Process contact form
     */
    private function processContactForm() {
        $data = [
            'first_name' => $this->sanitize($_POST['first_name'] ?? ''),
            'last_name' => $this->sanitize($_POST['last_name'] ?? ''),
            'email' => $this->sanitize($_POST['email'] ?? ''),
            'phone' => $this->sanitize($_POST['phone'] ?? ''),
            'message' => $this->sanitize($_POST['message'] ?? ''),
            'math_answer' => $this->sanitize($_POST['math_answer'] ?? '')
        ];
        
        // Validation
        if (empty($data['first_name'])) {
            $this->errors[] = 'First name is required';
        }
        if (empty($data['last_name'])) {
            $this->errors[] = 'Last name is required';
        }
        if (!$this->validateEmail($data['email'])) {
            $this->errors[] = 'Valid email is required';
        }
        if (!$this->validatePhone($data['phone'])) {
            $this->errors[] = 'Valid phone number is required';
        }
        if (empty($data['message'])) {
            $this->errors[] = 'Message is required';
        }
        if ($data['math_answer'] != '4') {
            $this->errors[] = 'Incorrect math answer';
        }
        
        $_SESSION['form_data'] = $data;
    }
    
    /**
     * Process referral form
     */
    private function processReferralForm() {
        $data = [
            'referrer_name' => $this->sanitize($_POST['referrer_name'] ?? ''),
            'organization' => $this->sanitize($_POST['organization'] ?? ''),
            'referrer_phone' => $this->sanitize($_POST['referrer_phone'] ?? ''),
            'referrer_email' => $this->sanitize($_POST['referrer_email'] ?? ''),
            'participant_name' => $this->sanitize($_POST['participant_name'] ?? ''),
            'relationship' => $this->sanitize($_POST['relationship'] ?? ''),
            'participant_phone' => $this->sanitize($_POST['participant_phone'] ?? ''),
            'participant_email' => $this->sanitize($_POST['participant_email'] ?? ''),
            'address' => $this->sanitize($_POST['address'] ?? ''),
            'reason' => $this->sanitize($_POST['reason'] ?? ''),
            'services_needed' => $_POST['services_needed'] ?? [],
            'additional_details' => $this->sanitize($_POST['additional_details'] ?? ''),
            'math_answer' => $this->sanitize($_POST['math_answer'] ?? '')
        ];
        
        // Validation
        if (empty($data['referrer_name'])) {
            $this->errors[] = 'Referrer name is required';
        }
        if (!$this->validateEmail($data['referrer_email'])) {
            $this->errors[] = 'Valid referrer email is required';
        }
        if (!$this->validatePhone($data['referrer_phone'])) {
            $this->errors[] = 'Valid referrer phone is required';
        }
        if (empty($data['participant_name'])) {
            $this->errors[] = 'Participant name is required';
        }
        if (empty($data['relationship'])) {
            $this->errors[] = 'Relationship to participant is required';
        }
        if (empty($data['address'])) {
            $this->errors[] = 'Address is required';
        }
        if (empty($data['reason'])) {
            $this->errors[] = 'Reason for referral is required';
        }
        if ($data['math_answer'] != '5') {
            $this->errors[] = 'Incorrect math answer';
        }
        
        $_SESSION['form_data'] = $data;
    }
    
    /**
     * Process complaint form
     */
    private function processComplaintForm() {
        $data = [
            'first_name' => $this->sanitize($_POST['first_name'] ?? ''),
            'last_name' => $this->sanitize($_POST['last_name'] ?? ''),
            'email' => $this->sanitize($_POST['email'] ?? ''),
            'phone' => $this->sanitize($_POST['phone'] ?? ''),
            'complaint' => $this->sanitize($_POST['complaint'] ?? ''),
            'math_answer' => $this->sanitize($_POST['math_answer'] ?? '')
        ];
        
        // Validation
        if (empty($data['first_name'])) {
            $this->errors[] = 'First name is required';
        }
        if (empty($data['last_name'])) {
            $this->errors[] = 'Last name is required';
        }
        if (!$this->validateEmail($data['email'])) {
            $this->errors[] = 'Valid email is required';
        }
        if (!$this->validatePhone($data['phone'])) {
            $this->errors[] = 'Valid phone number is required';
        }
        if (empty($data['complaint'])) {
            $this->errors[] = 'Complaint details are required';
        }
        if ($data['math_answer'] != '4') {
            $this->errors[] = 'Incorrect math answer';
        }
        
        $_SESSION['form_data'] = $data;
    }
    
    /**
     * Send notification emails
     */
    private function sendEmails() {
        $data = $_SESSION['form_data'];
        $subject = $this->getEmailSubject();
        $message = $this->getEmailMessage($data);
        
        // Email headers
        $headers = [
            'From: ' . SITE_NAME . ' <noreply@telecareservices.org>',
            'Reply-To: ' . ($data['email'] ?? $data['referrer_email'] ?? ADMIN_EMAIL),
            'X-Mailer: PHP/' . phpversion(),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        // Send to admin
        $success = mail(ADMIN_EMAIL, $subject, $message, implode("\r\n", $headers));
        
        if (!$success) {
            $this->logError("Failed to send email for form type: " . $this->formType);
        }
        
        // Send confirmation to user
        $this->sendConfirmationEmail($data);
    }
    
    /**
     * Send confirmation email to user
     */
    private function sendConfirmationEmail($data) {
        $email = $data['email'] ?? $data['referrer_email'] ?? null;
        
        if (!$email) return;
        
        $subject = 'Thank you for contacting ' . SITE_NAME;
        $message = $this->getConfirmationMessage($data);
        
        $headers = [
            'From: ' . SITE_NAME . ' <noreply@telecareservices.org>',
            'Reply-To: ' . ADMIN_EMAIL,
            'X-Mailer: PHP/' . phpversion(),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        mail($email, $subject, $message, implode("\r\n", $headers));
    }
    
    /**
     * Get email subject based on form type
     */
    private function getEmailSubject() {
        switch ($this->formType) {
            case 'contact':
                return 'New Contact Form Submission - ' . SITE_NAME;
            case 'referral':
                return 'New Referral Submission - ' . SITE_NAME;
            case 'complaint':
                return 'New Complaint Submission - ' . SITE_NAME;
            default:
                return 'New Form Submission - ' . SITE_NAME;
        }
    }
    
    /**
     * Get email message body
     */
    private function getEmailMessage($data) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #7A9D8C; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #555; }
        .value { margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>' . $this->getEmailSubject() . '</h2>
        </div>
        <div class="content">';
        
        foreach ($data as $key => $value) {
            if ($key === 'math_answer') continue;
            
            $label = ucwords(str_replace('_', ' ', $key));
            
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            
            $html .= '<div class="field">
                <div class="label">' . htmlspecialchars($label) . ':</div>
                <div class="value">' . nl2br(htmlspecialchars($value)) . '</div>
            </div>';
        }
        
        $html .= '<div class="field">
                <div class="label">Submitted:</div>
                <div class="value">' . date('F j, Y g:i A') . '</div>
            </div>
            <div class="field">
                <div class="label">IP Address:</div>
                <div class="value">' . $_SERVER['REMOTE_ADDR'] . '</div>
            </div>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Get confirmation message for user
     */
    private function getConfirmationMessage($data) {
        $name = $data['first_name'] ?? $data['referrer_name'] ?? 'Valued Contact';
        
        return '<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #7A9D8C; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Thank You for Contacting Us</h2>
        </div>
        <div class="content">
            <p>Dear ' . htmlspecialchars($name) . ',</p>
            <p>Thank you for reaching out to Telecare Services. We have received your submission and will respond within 24 hours.</p>
            <p>If you need immediate assistance, please call us at:</p>
            <p><strong>Main Office:</strong> (443) 249-3285<br>
            <strong>Silver Spring Office:</strong> (301) 576-0555</p>
            <p>Best regards,<br>
            <strong>Telecare Services Team</strong><br>
            <em>Empowering Lives, Building Communities</em></p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Save submission to database
     */
    private function saveToDatabase() {
        if (!$this->db) return;
        
        try {
            $data = $_SESSION['form_data'];
            
            $sql = "INSERT INTO form_submissions 
                    (form_type, data, ip_address, user_agent, submitted_at) 
                    VALUES (:form_type, :data, :ip, :user_agent, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'form_type' => $this->formType,
                'data' => json_encode($data),
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
        } catch (PDOException $e) {
            $this->logError("Database insert failed: " . $e->getMessage());
        }
    }
    
    /**
     * Sanitize input
     */
    private function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email
     */
    private function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone
     */
    private function validatePhone($phone) {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        // Check if it's a valid length (10 digits for US)
        return strlen($cleaned) >= 10;
    }
    
    /**
     * Send success response
     */
    private function sendSuccess() {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Thank you! Your submission has been received. We will contact you within 24 hours.'
        ]);
        exit;
    }
    
    /**
     * Send error response
     */
    private function sendError($message, $code = 400) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $this->errors
        ]);
        exit;
    }
    
    /**
     * Log error
     */
    private function logError($message) {
        $logDir = __DIR__ . '/logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/form-errors.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process form if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $handler = new ContactFormHandler();
    $handler->processSubmission();
}
?>
