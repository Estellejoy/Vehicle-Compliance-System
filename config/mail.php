<?php

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

// The functions below build the different messages used by the application.
function sendVerificationEmail(string $toEmail, string $toName, string $verifyLink): bool
{
    $subject = 'Verify your Vehicle Compliance System account';
    $htmlBody = buildVerificationHtml($toName, $verifyLink);
    $textBody = buildVerificationText($toName, $verifyLink);

    return sendMailMessage($toEmail, $toName, $subject, $htmlBody, $textBody);
}

function sendLoginVerificationEmail(string $toEmail, string $toName, string $verificationCode, string $verifyPageUrl): bool
{
    $subject = 'Your Vehicle Compliance System login code';
    $htmlBody = buildLoginVerificationHtml($toName, $verificationCode, $verifyPageUrl);
    $textBody = buildLoginVerificationText($toName, $verificationCode, $verifyPageUrl);

    return sendMailMessage($toEmail, $toName, $subject, $htmlBody, $textBody);
}

function sendTestEmail(string $toEmail, string $toName = 'Vehicle Compliance System Test'): bool
{
    $subject = 'Vehicle Compliance System test email';
    $htmlBody = buildTestHtml($toName);
    $textBody = buildTestText($toName);

    return sendMailMessage($toEmail, $toName, $subject, $htmlBody, $textBody);
}

function sendPasswordResetEmail(string $toEmail, string $toName, string $resetLink): bool
{
    $subject = 'Reset your Vehicle Compliance System password';
    $htmlBody = buildPasswordResetHtml($toName, $resetLink);
    $textBody = buildPasswordResetText($toName, $resetLink);

    return sendMailMessage($toEmail, $toName, $subject, $htmlBody, $textBody);
}

function sendVehicleExpiryReminderEmail(
    string $toEmail,
    string $toName,
    string $plateNumber,
    string $expiryType,
    string $expiryDate,
    int $daysRemaining
): bool {
    $safeType = strtolower(trim($expiryType)) === 'licence' ? 'driving licence' : 'insurance';
    $subject = 'Vehicle compliance reminder for ' . $plateNumber;
    $htmlBody = buildVehicleExpiryReminderHtml($toName, $plateNumber, $safeType, $expiryDate, $daysRemaining);
    $textBody = buildVehicleExpiryReminderText($toName, $plateNumber, $safeType, $expiryDate, $daysRemaining);

    return sendMailMessage($toEmail, $toName, $subject, $htmlBody, $textBody);
}

function sendVehicleInspectionStatusEmail(
    string $toEmail,
    string $toName,
    string $plateNumber,
    string $inspectionStatus,
    string $checkedAt,
    string $checkedBy = '',
    string $failureReason = ''
): bool {
    $subject = 'Inspection status updated for ' . $plateNumber;
    $htmlBody = buildVehicleInspectionStatusHtml($toName, $plateNumber, $inspectionStatus, $checkedAt, $checkedBy, $failureReason);
    $textBody = buildVehicleInspectionStatusText($toName, $plateNumber, $inspectionStatus, $checkedAt, $checkedBy, $failureReason);

    return sendMailMessage($toEmail, $toName, $subject, $htmlBody, $textBody);
}

function sendVehicleComplianceAlertEmail(string $toEmail, string $toName, string $plateNumber, array $issues): bool
{
    $subject = 'Vehicle compliance alert for ' . $plateNumber;
    return sendMailMessage(
        $toEmail,
        $toName,
        $subject,
        buildVehicleComplianceAlertHtml($toName, $plateNumber, $issues),
        buildVehicleComplianceAlertText($toName, $plateNumber, $issues)
    );
}

function sendExceptionAlertEmail(string $toEmail, string $toName, string $subject, string $details): bool
{
    $htmlBody = buildExceptionAlertHtml($toName, $subject, $details);
    $textBody = buildExceptionAlertText($toName, $subject, $details);

    return sendMailMessage($toEmail, $toName, $subject, $htmlBody, $textBody);
}

function sendMailMessage(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): bool
{
    $host = trim((string) (getenv('MAIL_HOST') ?: ''));
    $port = (int) (getenv('MAIL_PORT') ?: 587);
    $username = trim((string) (getenv('MAIL_USERNAME') ?: ''));
    $password = preg_replace('/\s+/', '', (string) (getenv('MAIL_PASSWORD') ?: ''));
    $encryptionSetting = getenv('MAIL_ENCRYPTION');
    $encryption = $encryptionSetting === false ? 'tls' : strtolower(trim((string) $encryptionSetting));
    $fromAddress = trim((string) (getenv('MAIL_FROM_ADDRESS') ?: $username ?: 'no-reply@example.com'));
    $fromName = trim((string) (getenv('MAIL_FROM_NAME') ?: 'Vehicle Compliance System'));
    $replyToAddress = trim((string) (getenv('MAIL_REPLY_TO_ADDRESS') ?: $fromAddress));
    $smtpAuth = true;

    // Read the SMTP settings before creating the PHPMailer message.
    if ($host === '') {
        error_log('Email not sent: MAIL_HOST is missing. Add a root .env file or inject SMTP settings into the runtime.');
        return false;
    }

    // Switch to the local Mailpit server when Gmail has no credentials.
    if ($host === 'smtp.gmail.com' && ($username === '' || $password === '')) {
        $host = 'mailpit';
        $port = 1025;
        $username = '';
        $password = '';
        $encryption = '';
        $smtpAuth = false;
    } elseif ($username === '' || $password === '') {
        $smtpAuth = false;
    }

    $mailer = new PHPMailer(true);

    try {
        $mailer->isSMTP();
        $mailer->Host = $host;
        $mailer->Port = $port;
        $mailer->SMTPAuth = $smtpAuth;
        if ($smtpAuth) {
            $mailer->Username = $username;
            $mailer->Password = $password;
        }
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

        // Set both HTML and plain-text versions so the email works in different inboxes.
        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body = $htmlBody;
        $mailer->AltBody = $textBody;

        return $mailer->send();
    } catch (Throwable $e) {
        error_log('Email send failed: ' . $e->getMessage());
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

function buildLoginVerificationHtml(string $toName, string $verificationCode, string $verifyPageUrl): string
{
    $safeName = htmlspecialchars($toName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeCode = htmlspecialchars($verificationCode, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeLink = htmlspecialchars($verifyPageUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return <<<HTML
<p>Hello {$safeName},</p>
<p>We received a login attempt for your Vehicle Compliance System account.</p>
<p>Your verification code is:</p>
<p><strong>{$safeCode}</strong></p>
<p>Enter this code on the verification page within 15 minutes:</p>
<p><a href="{$safeLink}">Open verification page</a></p>
<p>If this was not you, ignore this email and your dashboard will remain locked.</p>
HTML;
}

function buildLoginVerificationText(string $toName, string $verificationCode, string $verifyPageUrl): string
{
    return "Hello {$toName},\n\n"
        . "We received a login attempt for your Vehicle Compliance System account.\n"
        . "Your verification code is: {$verificationCode}\n"
        . "Enter this code on the verification page within 15 minutes:\n{$verifyPageUrl}\n\n"
        . "If this was not you, ignore this email and your dashboard will remain locked.";
}

function buildPasswordResetHtml(string $toName, string $resetLink): string
{
    $safeName = htmlspecialchars($toName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeLink = htmlspecialchars($resetLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return <<<HTML
<p>Hello {$safeName},</p>
<p>We received a request to reset your Vehicle Compliance System password.</p>
<p>This link expires in 1 hour:</p>
<p><a href="{$safeLink}">Reset password</a></p>
<p>If you did not request this, you can ignore this email.</p>
HTML;
}

function buildPasswordResetText(string $toName, string $resetLink): string
{
    return "Hello {$toName},\n\n"
        . "We received a request to reset your Vehicle Compliance System password.\n"
        . "This link expires in 1 hour:\n{$resetLink}\n\n"
        . "If you did not request this, you can ignore this email.";
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

function buildVehicleExpiryReminderHtml(
    string $toName,
    string $plateNumber,
    string $expiryType,
    string $expiryDate,
    int $daysRemaining
): string {
    $safeName = htmlspecialchars($toName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safePlate = htmlspecialchars($plateNumber, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeType = htmlspecialchars($expiryType, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeDate = htmlspecialchars($expiryDate, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return <<<HTML
<p>Hello {$safeName},</p>
<p>This is a reminder that your {$safeType} for vehicle {$safePlate} expires in {$daysRemaining} days.</p>
<p><strong>Expiry date:</strong> {$safeDate}</p>
<p>Please renew it before the expiry date to stay compliant.</p>
HTML;
}

function buildVehicleExpiryReminderText(
    string $toName,
    string $plateNumber,
    string $expiryType,
    string $expiryDate,
    int $daysRemaining
): string {
    return "Hello {$toName},\n\n"
        . "This is a reminder that your {$expiryType} for vehicle {$plateNumber} expires in {$daysRemaining} days.\n"
        . "Expiry date: {$expiryDate}\n\n"
        . "Please renew it before the expiry date to stay compliant.";
}

function buildVehicleInspectionStatusHtml(
    string $toName,
    string $plateNumber,
    string $inspectionStatus,
    string $checkedAt,
    string $checkedBy = '',
    string $failureReason = ''
): string {
    $safeName = htmlspecialchars($toName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safePlate = htmlspecialchars($plateNumber, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeStatus = htmlspecialchars($inspectionStatus, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeCheckedAt = htmlspecialchars($checkedAt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeCheckedBy = htmlspecialchars($checkedBy, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $inspectorLine = $safeCheckedBy !== '' ? "<p><strong>Checked by:</strong> {$safeCheckedBy}</p>" : '';
    $safeReason = htmlspecialchars($failureReason, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $reasonLine = $safeReason !== '' ? "<p><strong>Reason:</strong> {$safeReason}</p>" : '';

    return <<<HTML
<p>Hello {$safeName},</p>
<p>Your vehicle {$safePlate} inspection status has been updated to <strong>{$safeStatus}</strong>.</p>
<p><strong>Checked at:</strong> {$safeCheckedAt}</p>
{$inspectorLine}
{$reasonLine}
<p>You can sign in to review the latest compliance details.</p>
HTML;
}

function buildVehicleInspectionStatusText(
    string $toName,
    string $plateNumber,
    string $inspectionStatus,
    string $checkedAt,
    string $checkedBy = '',
    string $failureReason = ''
): string {
    $message = "Hello {$toName},\n\n"
        . "Your vehicle {$plateNumber} inspection status has been updated to {$inspectionStatus}.\n"
        . "Checked at: {$checkedAt}\n";

    if ($checkedBy !== '') {
        $message .= "Checked by: {$checkedBy}\n";
    }
    if ($failureReason !== '') {
        $message .= "Reason: {$failureReason}\n";
    }

    return $message . "\nYou can sign in to review the latest compliance details.";
}

function buildVehicleComplianceAlertHtml(string $toName, string $plateNumber, array $issues): string
{
    $safeName = htmlspecialchars($toName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safePlate = htmlspecialchars($plateNumber, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $items = '';
    foreach ($issues as $issue) {
        $items .= '<li>' . htmlspecialchars((string) $issue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
    }

    return "<p>Hello {$safeName},</p><p>Vehicle <strong>{$safePlate}</strong> currently requires attention:</p><ul>{$items}</ul><p>Please update the affected records and remain compliant.</p>";
}

function buildVehicleComplianceAlertText(string $toName, string $plateNumber, array $issues): string
{
    return "Hello {$toName},\n\nVehicle {$plateNumber} currently requires attention:\n- "
        . implode("\n- ", array_map('strval', $issues))
        . "\n\nPlease update the affected records and remain compliant.";
}

function buildExceptionAlertHtml(string $toName, string $subject, string $details): string
{
    $safeName = htmlspecialchars($toName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeSubject = htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeDetails = nl2br(htmlspecialchars($details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

    return <<<HTML
<p>Hello {$safeName},</p>
<p><strong>{$safeSubject}</strong></p>
<p>{$safeDetails}</p>
HTML;
}

function buildExceptionAlertText(string $toName, string $subject, string $details): string
{
    return "Hello {$toName},\n\n{$subject}\n\n{$details}";
}
