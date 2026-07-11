-- Presentation cleanup so only Joy Gatiti's demo vehicle hits the 14-day reminder rule.

UPDATE compliance_records
SET insurance_expiry = DATE_ADD(CURDATE(), INTERVAL 15 DAY),
    insurance_status = 'Valid'
WHERE compliance_id = 2;

DELETE FROM notifications
WHERE vehicle_id = 2
  AND event_code LIKE 'insurance_expiry_reminder:2:%';

