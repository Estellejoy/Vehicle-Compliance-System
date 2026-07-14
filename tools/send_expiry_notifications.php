<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../backend/notification_service.php';

// The scheduler calls this job directly; it is not a web endpoint.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script is intended to run from the command line.\n";
    exit(1);
}

try {
    // One run checks every active vehicle and creates any alerts due today.
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
