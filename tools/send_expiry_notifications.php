<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../backend/notification_service.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script is intended to run from the command line.\n";
    exit(1);
}

try {
    $results = vcs_send_expiry_notifications($pdo);

    echo sprintf(
        "Expiry notification run complete. Insurance: %d, Licence: %d, Skipped: %d, Failed mail deliveries: %d\n",
        $results['insurance'],
        $results['licence'],
        $results['skipped'],
        $results['failed']
    );
    exit(0);
} catch (Throwable $e) {
    error_log('Expiry notification job failed: ' . $e->getMessage());
    echo "Expiry notification job failed.\n";
    exit(1);
}
