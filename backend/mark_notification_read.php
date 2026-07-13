<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

require_once __DIR__ . '/../config/db.php';

$notificationId = (int) ($_POST['notification_id'] ?? 0);
if ($notificationId <= 0 || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /views/citizen_portal.php');
    exit;
}

$stmt = $pdo->prepare(
    "UPDATE notifications
     SET status = 'Read'
     WHERE notification_id = :notification_id AND user_id = :user_id"
);
$stmt->execute([
    'notification_id' => $notificationId,
    'user_id' => (int) $_SESSION['user_id'],
]);

header('Location: /views/citizen_portal.php');
exit;
