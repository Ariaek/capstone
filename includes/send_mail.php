<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

function send_ptms_mail($toEmail, $toName, $subject, $htmlBody)
{
    $mail = new PHPMailer(true);

    try {
        // SMTP SETTINGS
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'arrianeaek@gmail.com'; 
        $mail->Password   = 'bzro yred hgkf agda';   
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // EMAIL SETTINGS
        $mail->setFrom('arrianeaek@gmail.com', 'PTMS System');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        $mail->send();

        return [
            'success' => true,
            'message' => 'Email sent'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $mail->ErrorInfo
        ];
    }
}