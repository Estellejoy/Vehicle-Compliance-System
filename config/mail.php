<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

function sendVerificationEmail(string $toEmail, string $toName, string $verifyLink): bool
{
    $subject = 'Verify your Vehicle Compliance System account';
    $htmlBody = buildVerificationHtml($toName, $verifyLink);
    $textBody = buildVerificationText($toName, $verifyLink);

    return sendMailMessage($toEmail, $toName, $subject, $htmlBody, $textBody);
}

function sendTestEmail(string $toEmail, string $toName = 'Vehicle Compliance System Test'): bool
{
    $subject = 'Vehicle Compliance System test email';
    $htmlBody = buildTestHtml($toName);
    $textBody = buildTestText($toName);

    return sendMailMessage($toEmail, $toName, $subject, $htmlBody, $textBody);
}

function sendMailMessage(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): bool
{
    $host = trim((string) (getenv('MAIL_HOST') ?: ''));
    $port = (int) (getenv('MAIL_PORT') ?: 587);
    $username = trim((string) (getenv('MAIL_USERNAME') ?: ''));
    $password = preg_replace('/\s+/', '', (string) (getenv('MAIL_PASSWORD') ?: ''));
    $encryption = strtolower(trim((string) (getenv('MAIL_ENCRYPTION') ?: 'tls')));
    $fromAddress = trim((string) (getenv('MAIL_FROM_ADDRESS') ?: $username ?: 'no-reply@localhost'));
    $fromName = trim((string) (getenv('MAIL_FROM_NAME') ?: 'Vehicle Compliance System'));
    $replyToAddress = trim((string) (getenv('MAIL_REPLY_TO_ADDRESS') ?: $fromAddress));

    if ($host === '' || $username === '' || $password === '') {
        error_log('Email not sent: MAIL_HOST, MAIL_USERNAME, or MAIL_PASSWORD is missing.');
        return false;
    }

    $mailer = new PHPMailer(true);

    try {
        $mailer->isSMTP();
        $mailer->Host = $host;
        $mailer->Port = $port;
        $mailer->SMTPAuth = true;
        $mailer->Username = $username;
        $mailer->Password = $password;
        $mailer->CharSet = 'UTF-8';
        $mailer->setFrom($fromAddress, $fromName);
        $mailer->addAddress($toEmail, $toName);

        if ($replyToAddress !== '') {
            $mailer->addReplyTo($replyToAddress, $fromName);
        }

        if ($encryption === 'ssl' || $encryption === 'tls') {
            $mailer->SMTPSecure = $encryption;
        } else {
            $mailer->SMTPAutoTLS = false;
        }

        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body = $htmlBody;
        $mailer->AltBody = $textBody;

        return $mailer->send();
    } catch (Throwable $e) {
        error_log('Verification email failed: ' . $e->getMessage());
        return false;
    }
}

function buildVerificationHtml(string $toName, string $verifyLink): string
{
    $safeName = htmlspecialchars($toName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeLink = htmlspecialchars($verifyLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return <<<HTML
<p>Hello {$safeName},</p>
<p>Please verify your account within 24 hours:</p>
<p><a href="{$safeLink}">Verify account</a></p>
<p>If you did not create this account, you can ignore this message.</p>
HTML;
}

function buildVerificationText(string $toName, string $verifyLink): string
{
    return "Hello {$toName},\n\n"
        . "Please verify your account within 24 hours:\n{$verifyLink}\n\n"
        . "If you did not create this account, you can ignore this message.";
}

function buildTestHtml(string $toName): string
{
    $safeName = htmlspecialchars($toName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return <<<HTML
<p>Hello {$safeName},</p>
<p>This is a test email from Vehicle Compliance System.</p>
<p>If you received this message, the SMTP configuration is working.</p>
HTML;
}

function buildTestText(string $toName): string
{
    return "Hello {$toName},\n\n"
        . "This is a test email from Vehicle Compliance System.\n"
        . "If you received this message, the SMTP configuration is working.";
}
