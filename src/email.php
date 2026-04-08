<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/gmail_config.php'; // Use the new secure config

function sendEmail(string $to, string $subject, string $body, ?string $altBody = ''): bool {
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = GMAIL_SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_EMAIL;
        $mail->Password   = GMAIL_PASSWORD;
        $mail->SMTPSecure = GMAIL_SMTP_SECURE;
        $mail->Port       = GMAIL_SMTP_PORT;

        //Recipients
        $mail->setFrom(GMAIL_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error instead of echoing
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
