<?php
// public/send.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config.php';

$to = filter_input(INPUT_POST, 'to', FILTER_VALIDATE_EMAIL);
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$cc = filter_input(INPUT_POST, 'cc', FILTER_VALIDATE_EMAIL);

if (!$to || $subject === '' || $message === '') {
  http_response_code(422);
  exit('Invalid input. Please go back and check the form.');
}

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = $config['smtp_auth'];
    $mail->Username   = $config['smtp_username'];
    $mail->Password   = $config['smtp_password'];
    $mail->SMTPSecure = $config['smtp_secure'];
    $mail->Port       = (int)$config['smtp_port'];

    // Recipients
    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($to);
    if ($cc) $mail->addCC($cc);

    // Attachments
    if (!empty($_FILES['attachment']['name'])) {
        $tmp = $_FILES['attachment']['tmp_name'];
        $name = basename($_FILES['attachment']['name']);
        if (is_uploaded_file($tmp)) {
            $mail->addAttachment($tmp, $name);
        }
    }

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    $mail->AltBody = $message;

    $mail->send();

    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><title>Sent</title></head><body class="bg-light"><div class="container py-5"><div class="alert alert-success"><strong>Success!</strong> Your message was sent to ' . htmlspecialchars($to) . '.</div><a class="btn btn-secondary" href="index.php">Back</a></div></body></html>';

} catch (Exception $e) {
    http_response_code(500);
    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><title>Error</title></head><body class="bg-light"><div class="container py-5"><div class="alert alert-danger"><strong>Failed to send.</strong><br>Mailer Error: ' . htmlspecialchars($mail->ErrorInfo) . '</div><a class="btn btn-secondary" href="index.php">Back</a></div></body></html>';
}
