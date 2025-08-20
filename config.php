<?php
// Rename this file to config.php and set your SMTP credentials.
// Tip: For Gmail, create an App Password and enable "Less secure apps" is deprecated — use App Passwords.

return [
  'smtp_host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
  'smtp_port' => getenv('SMTP_PORT') ?: 587,
  'smtp_secure' => getenv('SMTP_SECURE') ?: PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS, // 'tls'
  'smtp_auth' => true,
  'smtp_username' => getenv('SMTP_USERNAME') ?: 'your-email@example.com',
  'smtp_password' => getenv('SMTP_PASSWORD') ?: 'your-app-password',
  'from_email' => getenv('FROM_EMAIL') ?: 'your-email@example.com',
  'from_name'  => getenv('FROM_NAME')  ?: 'Your Name',
  // Optional: default recipient for testing
  'to_email'   => getenv('TO_EMAIL')   ?: 'recipient@example.com'
];
