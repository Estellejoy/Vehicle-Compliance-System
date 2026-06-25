<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->query("SELECT user_id, email FROM users WHERE password_hash IS NULL OR password_hash = ''");
$users = $stmt->fetchAll();

$updated = 0;

foreach ($users as $user) {
    $localPart = explode('@', (string)$user['email'], 2)[0];
    $plainPassword = $localPart . '@123';
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);

    $update = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id");
    $update->execute([
        'password_hash' => $hash,
        'user_id' => $user['user_id'],
    ]);

    $updated++;
}

echo "Updated {$updated} user password hash(es)." . PHP_EOL;
