<?php

// app/Helpers/mailer.php
// -----------------------------------------------------------------------------
// Funcions senzilles per enviar emails amb PHPMailer.
// -----------------------------------------------------------------------------

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../lib/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../../lib/PHPMailer/src/Exception.php';

/**
 * Envia un email de reset de contrasenya.
 *
 * @return bool true si s'ha enviat bé, false si hi ha hagut error.
 */
function send_password_reset_email(string $toEmail, string $toUsername, string $resetLink, int $minutesValid): bool
{
    // preparo el missatge (HTML + text)
    $safeName = htmlspecialchars($toUsername);

    $subject = 'Reset your Valomen.gg password';

    $htmlBody = "
        <p>Hi <b>{$safeName}</b>,</p>
        <p>You requested a password reset for your Valomen.gg account.</p>
        <p>
            Click this link to set a new password (valid for {$minutesValid} minutes):<br>
            <a href=\"{$resetLink}\">{$resetLink}</a>
        </p>
        <p>If you didn't request this, you can ignore this email.</p>
        <p>— Valomen.gg</p>
    ";

    $textBody = "Hi {$toUsername},\n\n" .
        "You requested a password reset for your Valomen.gg account.\n" .
        "Open this link to set a new password (valid for {$minutesValid} minutes):\n" .
        "{$resetLink}\n\n" .
        "If you didn't request this, you can ignore this email.\n\n" .
        "— Valomen.gg";

    return send_email($toEmail, $toUsername, $subject, $htmlBody, $textBody);
}

/**
 * Funció genèrica per enviar un email.
 */
function send_email(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
{
    $mail = new PHPMailer(true);

    try {
        // configuro SMTP
        $mail->isSMTP();
        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->Host     = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->Port     = SMTP_PORT;

        // Seguretat (tls/ssl)
        if (SMTP_ENCRYPTION === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            // per defecte faig TLS
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        // remitent + destinatari
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        // contingut
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        if ($textBody !== '') {
            $mail->AltBody = $textBody;
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        // si vols, aquí pots guardar logs
        return false;
    }
}