<?php

require_once __DIR__ . '/../../vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../../vendor/PHPMailer/SMTP.php';
require_once __DIR__ . '/../../vendor/PHPMailer/Exception.php';

class Mail
{
    private static function client(): PHPMailer\PHPMailer\PHPMailer
    {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 587;
        $mail->SMTPSecure = 'tls';
        $mail->SMTPAuth = true;
        $mail->Username = getenv('NEAC_SMTP_USER') ?: 'bbcameroonnationalexam@gmail.com';
        $mail->Password = getenv('NEAC_SMTP_PASS') ?: '';
        $mail->CharSet = 'UTF-8';
        $mail->setFrom('bbcameroonnationalexam@gmail.com', APP_NAME);
        return $mail;
    }

    public static function send(string $to, string $subject, string $body, ?string $replyTo = null): bool
    {
        try {
            $mail = self::client();
            $mail->addAddress($to);
            if ($replyTo) $mail->addReplyTo($replyTo);
            $mail->Subject = $subject;
            $html = '<!doctype html><html><body style="font-family:Inter,system-ui,sans-serif;padding:24px;color:#102032;max-width:560px;margin:0 auto">';
            $html .= '<div style="border-bottom:3px solid #102b4e;padding-bottom:12px;margin-bottom:20px">';
            $html .= '<strong style="font-size:18px;color:#102b4e">' . APP_NAME . '</strong></div>';
            $html .= $body;
            $html .= '<div style="border-top:1px solid #dbe3ea;margin-top:24px;padding-top:12px;font-size:12px;color:#667085">';
            $html .= 'This is an automated message from ' . APP_NAME . '. Please do not reply directly.</div></body></html>';
            $mail->Body = $html;
            $mail->AltBody = strip_tags($body);
            $mail->send();
            return true;
        } catch (Throwable $e) {
            error_log('Mail send failed: ' . $e->getMessage());
            return false;
        }
    }

    public static function sendNotification(string $to, string $subject, string $message, ?string $actionUrl = null, ?string $actionLabel = null): bool
    {
        $body = '<p style="font-size:15px;line-height:1.6;margin:0 0 16px">' . nl2br(e($message)) . '</p>';
        if ($actionUrl && $actionLabel) {
            $body .= '<a href="' . e($actionUrl) . '" style="display:inline-block;background:#102b4e;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:700">' . e($actionLabel) . '</a>';
        }
        return self::send($to, $subject, $body);
    }
}
