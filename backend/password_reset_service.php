<?php

require_once __DIR__ . '/auth_helpers.php';
require_once __DIR__ . '/../config/mail.php';

function vcs_password_reset_token_value(): string
{
    return bin2hex(random_bytes(32));
}

function vcs_password_reset_link(string $token): string
{
    $appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost:8080', '/');

    return $appUrl . '/reset-password?token=' . urlencode($token);
}

function vcs_create_password_reset_request(PDO $pdo, array $user): array
{
    // Issue a fresh token, clear stale tokens for the same user, and keep only the hash in storage.
    $token = vcs_password_reset_token_value();
    $tokenHash = hash('sha256', $token);
    $expiresAt = vcs_password_reset_expiry();
    $userId = (int) ($user['user_id'] ?? 0);

    if ($userId <= 0) {
        throw new InvalidArgumentException('A valid user is required to create a password reset request.');
    }

    $cleanup = $pdo->prepare(
        'DELETE FROM password_reset_tokens
         WHERE user_id = :user_id
           AND (used_at IS NOT NULL OR expires_at <= NOW())'
    );
    $cleanup->execute(['user_id' => $userId]);

    $insert = $pdo->prepare(
        'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
         VALUES (:user_id, :token_hash, :expires_at)'
    );
    $insert->execute([
        'user_id' => $userId,
        'token_hash' => $tokenHash,
        'expires_at' => $expiresAt,
    ]);

    return [
        'token' => $token,
        'token_hash' => $tokenHash,
        'expires_at' => $expiresAt,
        'reset_link' => vcs_password_reset_link($token),
    ];
}

function vcs_send_password_reset_link(PDO $pdo, array $user): array
{
    $request = vcs_create_password_reset_request($pdo, $user);
    $mailSent = sendPasswordResetEmail(
        (string) $user['email'],
        (string) $user['name'],
        $request['reset_link']
    );

    return $request + [
        'mail_sent' => $mailSent,
    ];
}

function vcs_find_password_reset_request(PDO $pdo, string $token): ?array
{
    // Look up the reset request by hash and reject used or expired links.
    $tokenHash = hash('sha256', trim($token));

    $stmt = $pdo->prepare(
        'SELECT prt.token_id, prt.user_id, prt.expires_at, prt.used_at,
                u.email
         FROM password_reset_tokens prt
         INNER JOIN users u ON u.user_id = prt.user_id
         WHERE prt.token_hash = :token_hash
         LIMIT 1'
    );
    $stmt->execute(['token_hash' => $tokenHash]);
    $reset = $stmt->fetch();

    if (!$reset) {
        return null;
    }

    if (!empty($reset['used_at']) || strtotime((string) $reset['expires_at']) <= time()) {
        return null;
    }

    return $reset;
}

function vcs_apply_password_reset(PDO $pdo, int $tokenId, int $userId, string $newPassword): void
{
    // Update the password and consume the token in one transaction.
    $pdo->beginTransaction();

    try {
        $update = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id');
        $update->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'user_id' => $userId,
        ]);

        $markUsed = $pdo->prepare(
            'UPDATE password_reset_tokens
             SET used_at = NOW()
             WHERE token_id = :token_id AND used_at IS NULL'
        );
        $markUsed->execute(['token_id' => $tokenId]);

        if ($markUsed->rowCount() !== 1) {
            throw new RuntimeException('Password reset token could not be consumed.');
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}
