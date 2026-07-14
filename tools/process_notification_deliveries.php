<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../backend/notification_service.php';

// Email retries are kept in a CLI worker rather than a browser request.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script is intended to run from the command line.\n";
    exit(1);
}

try {
    // Failed messages become eligible again after their retry time.
    $result = vcs_process_pending_email_deliveries($pdo);
    echo sprintf(
        "Notification delivery run complete. Processed: %d, Sent: %d, Failed: %d\n",
        $result['processed'],
        $result['sent'],
        $result['failed']
    );
    exit($result['failed'] > 0 ? 1 : 0);
} catch (Throwable $e) {
    error_log('Notification delivery job failed: ' . $e->getMessage());
    echo "Notification delivery job failed.\n";
    exit(1);
}
