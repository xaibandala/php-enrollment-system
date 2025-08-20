<?php
/**
 * Mailer helper functions
 */

// PHPMailer via Composer autoload
// Ensure vendor/autoload.php exists
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Inline Email/SMTP configuration (moved from mail_config.php)
// Update these settings with your actual email credentials
if (!defined('SMTP_HOST'))        define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT'))        define('SMTP_PORT', 587);
if (!defined('SMTP_SECURE'))      define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'
if (!defined('SMTP_AUTH'))        define('SMTP_AUTH', true);
if (!defined('SMTP_USERNAME'))    define('SMTP_USERNAME', 'bxaiglenn@gmail.com');
if (!defined('SMTP_PASSWORD'))    define('SMTP_PASSWORD', 'owqkpodtcohiohhv');
if (!defined('SMTP_FROM_EMAIL'))  define('SMTP_FROM_EMAIL', 'bxaiglenn@gmail.com');
if (!defined('SMTP_FROM_NAME'))   define('SMTP_FROM_NAME', 'EduEnroll');
if (!defined('SMTP_DEBUG'))       define('SMTP_DEBUG', 0); // 0,1,2

// Email templates
if (!defined('EMAIL_SUBJECT_VERIFICATION')) define('EMAIL_SUBJECT_VERIFICATION', 'Email Verification - EduEnroll');
if (!defined('EMAIL_SUBJECT_RESET'))        define('EMAIL_SUBJECT_RESET', 'Password Reset - EduEnroll');

// OTP settings
if (!defined('OTP_EXPIRY_MINUTES')) define('OTP_EXPIRY_MINUTES', 10);
if (!defined('OTP_LENGTH'))         define('OTP_LENGTH', 6);

// Log mode (optional)
if (!defined('EMAIL_LOG_MODE')) define('EMAIL_LOG_MODE', false);
if (!defined('EMAIL_LOG_FILE')) define('EMAIL_LOG_FILE', __DIR__ . '/../logs/email.log');

/**
 * Configure and return a PHPMailer instance using SMTP settings in `config/mail_config.php`.
 * @return PHPMailer
 */
function getConfiguredMailer() {
    $mail = new PHPMailer(true);
    // Server settings
    $mail->isSMTP();
    $mail->Host       = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
    $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 25;
    $mail->SMTPAuth   = defined('SMTP_AUTH') ? SMTP_AUTH : false;
    $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : '';
    $mail->Username   = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
    $mail->Password   = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
    $mail->CharSet    = 'UTF-8';
    $mail->isHTML(true);
    if (defined('SMTP_DEBUG')) {
        $mail->SMTPDebug = SMTP_DEBUG; // 0,1,2
    }

    // From
    $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : (defined('SMTP_USERNAME') ? SMTP_USERNAME : 'noreply@example.com');
    $fromName  = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Mailer';
    $mail->setFrom($fromEmail, $fromName);

    return $mail;
}

/**
 * Send a custom email (generic helper for admin compose)
 *
 * @param string $to Recipient email
 * @param string $name Recipient name (optional)
 * @param string $subject Email subject
 * @param string $htmlBody HTML body
 * @param string|null $altBody Plain-text fallback body (optional)
 * @return bool True on success
 */
function sendCustomEmail($to, $name, $subject, $htmlBody, $altBody = null) {
    try {
        $mail = getConfiguredMailer();
        $mail->addAddress($to, $name ?: '');
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $altBody ?? strip_tags($htmlBody);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('PHPMailer custom send error: ' . ($mail->ErrorInfo ?? $e->getMessage()));
        return false;
    }
}

/**
 * Send a password reset email
 * 
 * @param string $to Email address
 * @param string $token Reset token
 * @param string $name User's name
 * @return bool True if email was sent successfully
 */
function sendPasswordResetEmail($to, $token, $name) {
    $subject = 'Password Reset Request';
    $resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]/reset_password.php?token=$token";

    $message = "
    <html>
    <head>
        <title>Password Reset Request</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .button {
                display: inline-block;
                padding: 10px 20px;
                background-color: #0d6efd;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 15px 0;
            }
            .footer {
                margin-top: 30px;
                font-size: 0.9em;
                color: #666;
                border-top: 1px solid #eee;
                padding-top: 10px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Password Reset Request</h2>
            <p>Hello $name,</p>
            <p>We received a request to reset your password. Click the button below to set a new password:</p>
            <p>
                <a href='$resetUrl' class='button'>Reset Password</a>
            </p>
            <p>Or copy and paste this link into your browser:</p>
            <p><code>$resetUrl</code></p>
            <p>This link will expire in 1 hour.</p>
            <p>If you didn't request a password reset, you can safely ignore this email.</p>
            <div class='footer'>
                <p>Best regards,<br>Enrollment System Team</p>
            </div>
        </div>
    </body>
    </html>
    ";

    try {
        $mail = getConfiguredMailer();
        $mail->addAddress($to, $name ?: '');
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = "Hello $name,\n\nUse the link to reset your password: $resetUrl\n\nThis link will expire in 1 hour.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('PHPMailer reset send error: ' . ($mail->ErrorInfo ?? $e->getMessage()));
        return false;
    }
}

/**
 * Send a password changed notification
 * 
 * @param string $to Email address
 * @param string $name User's name
 * @return bool True if email was sent successfully
 */
function sendPasswordChangedNotification($to, $name) {
    $subject = 'Your Password Has Been Changed';
    
    $message = "
    <html>
    <head>
        <title>Password Changed</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .footer { 
                margin-top: 30px; 
                font-size: 0.9em; 
                color: #666; 
                border-top: 1px solid #eee;
                padding-top: 10px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Password Changed Successfully</h2>
            <p>Hello $name,</p>
            <p>This is a confirmation that your password has been changed.</p>
            <p>If you did not make this change, please contact our support team immediately.</p>
            <div class='footer'>
                <p>Best regards,<br>Enrollment System Team</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    try {
        $mail = getConfiguredMailer();
        $mail->addAddress($to, $name ?: '');
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = "Hello $name,\nThis is a confirmation that your password has been changed.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('PHPMailer password-changed send error: ' . ($mail->ErrorInfo ?? $e->getMessage()));
        return false;
    }
}

/**
 * Send a one-time passcode (OTP) for login
 *
 * @param string $to Email address
 * @param string $otp 6-digit OTP code
 * @param string $name User's name
 * @return bool True if email was sent successfully
 */
function sendLoginOtpEmail($to, $otp, $name) {
    $subject = 'Your One-Time Passcode (OTP)';

    $message = "
    <html>
    <head>
        <title>Your One-Time Passcode</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .otp {
                display: inline-block;
                font-size: 28px;
                letter-spacing: 6px;
                font-weight: bold;
                padding: 10px 16px;
                border-radius: 8px;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                margin: 10px 0;
            }
            .note { color: #555; font-size: 0.95em; }
            .footer { 
                margin-top: 30px; 
                font-size: 0.9em; 
                color: #666; 
                border-top: 1px solid #eee;
                padding-top: 10px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Your One-Time Passcode</h2>
            <p>Hello $name,</p>
            <p>Use the following one-time passcode to log in to your account. This code will expire in 5 minutes:</p>
            <div class='otp'>$otp</div>
            <p class='note'>If you did not request this code, you can ignore this email. For security, do not share this code with anyone.</p>
            <div class='footer'>
                <p>Best regards,<br>Enrollment System Team</p>
            </div>
        </div>
    </body>
    </html>
    ";

    try {
        $mail = getConfiguredMailer();
        $mail->addAddress($to, $name ?: '');
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = "Your One-Time Passcode: $otp\nThis code will expire in 5 minutes.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('PHPMailer OTP send error: ' . ($mail->ErrorInfo ?? $e->getMessage()));
        return false;
    }
}
