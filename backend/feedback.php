<?php
session_start();

require_once '../config/db.php';
require_once __DIR__ . '/auth_helpers.php';

function feedback_flash(string $type, string $message): void
{
    // Store the user message in session and send the visitor back to the feedback section.
    $_SESSION['feedback_flash'] = [
        'type' => $type,
        'message' => $message,
    ];

    header('Location: /index.php#feedback');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php#feedback');
    exit;
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$category = trim((string) ($_POST['category'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$sourcePage = trim((string) ($_POST['source_page'] ?? 'index.php'));
$userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

// Basic validation keeps the public form lightweight while still rejecting empty submissions.
if ($name === '' || $email === '' || $message === '') {
    feedback_flash('danger', 'Please fill in your name, email address, and feedback message.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    feedback_flash('danger', 'Enter a valid email address.');
}

$nameLength = function_exists('mb_strlen') ? mb_strlen($name) : strlen($name);
$messageLength = function_exists('mb_strlen') ? mb_strlen($message) : strlen($message);

if ($nameLength < 2 || $messageLength < 10) {
    feedback_flash('warning', 'Please add a little more detail so we can understand your feedback.');
}

try {
    if (!vcs_has_table($pdo, 'feedback_messages')) {
        feedback_flash('warning', 'Feedback storage is not available yet. Apply the feedback migration first.');
    }

    $insert = $pdo->prepare(
        'INSERT INTO feedback_messages (
            name,
            email,
            category,
            message,
            source_page,
            user_agent,
            created_at
        ) VALUES (
            :name,
            :email,
            :category,
            :message,
            :source_page,
            :user_agent,
            NOW()
        )'
    );
    $insert->execute([
        'name' => $name,
        'email' => $email,
        'category' => $category !== '' ? $category : 'general',
        'message' => $message,
        'source_page' => $sourcePage !== '' ? $sourcePage : 'index.php',
        'user_agent' => $userAgent,
    ]);

    feedback_flash('success', 'Thanks for the feedback. We’ve received your message and will review it.');
} catch (PDOException $e) {
    error_log($e->getMessage());
    feedback_flash('danger', 'Database error while saving your feedback.');
}
