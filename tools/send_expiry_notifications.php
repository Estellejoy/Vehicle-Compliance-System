<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../backend/notification_service.php';

// This file starts the daily scan when it is called by cron.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script is intended to run from the command line.\n";
    exit(1);
}

try {
    // Scan the vehicles, create today's in-app alerts, and queue their emails.
    $results = vcs_send_expiry_notifications($pdo);

    echo sprintf(
        "Daily compliance notification run complete. Scanned: %d, Alerts: %d, Skipped: %d, Failed mail deliveries: %d\n",
        $results['scanned'],
        $results['alerts'],
        $results['skipped'],
        $results['failed']
    );
    exit(0);
} catch (Throwable $e) {
    error_log('Expiry notification job failed: ' . $e->getMessage());
    echo "Expiry notification job failed.\n";
    exit(1);
}
