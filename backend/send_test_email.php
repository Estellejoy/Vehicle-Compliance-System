<?php

session_start();
header('Content-Type: application/json; charset=UTF-8');

// Keep this diagnostic endpoint available to administrators only.
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Administrator access is required.']);
    exit;
}

require_once __DIR__ . '/../config/mail.php';

$toEmail = trim((string) ($_POST['to_email'] ?? $_GET['to_email'] ?? getenv('TEST_EMAIL_TO') ?: 'joy.gatiti@strathmore.edu'));
$toName = trim((string) ($_POST['to_name'] ?? $_GET['to_name'] ?? 'Joy Gatiti'));

if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => 'A valid recipient email is required.',
    ]);
    exit;
}

$sent = sendTestEmail($toEmail, $toName);

if ($sent) {
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'message' => 'Test email sent successfully.',
        'to_email' => $toEmail,
    ]);
    exit;
}

http_response_code(500);
echo json_encode([
    'ok' => false,
    'message' => 'Test email could not be sent.',
    'to_email' => $toEmail,
]);
