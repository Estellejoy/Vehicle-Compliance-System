<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array(($_SESSION['role'] ?? ''), ['admin', 'officer'], true)) {
    header('Location: /login');
    exit;
}

require_once '../config/db.php';
require_once __DIR__ . '/auth_helpers.php';

function flash_vehicle_profile(string $type, string $message, string $plateNumber = ''): void
{
    $_SESSION['vehicle_profile_flash'] = [
        'type' => $type,
        'message' => $message,
    ];

    $url = '/views/officer_dashboard.php';
    if ($plateNumber !== '') {
        $url .= '?plate_number=' . urlencode($plateNumber);
    }

    header('Location: ' . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /views/officer_dashboard.php');
    exit;
}

$vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
$plateNumber = trim((string) ($_POST['plate_number'] ?? ''));
$chassisNumber = trim((string) ($_POST['chassis_number'] ?? ''));
$insuranceType = trim((string) ($_POST['insurance_type'] ?? ''));
$paymentPeriod = trim((string) ($_POST['payment_period'] ?? ''));
$licenceClass = trim((string) ($_POST['driver_licence_class'] ?? ''));
$serviceIntervalKm = (int) ($_POST['service_interval_km'] ?? 0);
$odometerKm = (int) ($_POST['odometer_km'] ?? 0);

if ($vehicleId <= 0) {
    flash_vehicle_profile('warning', 'Select a vehicle first.', $plateNumber);
}

if ($chassisNumber === '' || strlen($chassisNumber) < 8) {
    flash_vehicle_profile('warning', 'Enter a valid chassis number / VIN.', $plateNumber);
}

if (!array_key_exists($insuranceType, vcs_vehicle_insurance_types())) {
    flash_vehicle_profile('warning', 'Choose a valid insurance type.', $plateNumber);
}

if (!array_key_exists($paymentPeriod, vcs_vehicle_payment_periods())) {
    flash_vehicle_profile('warning', 'Choose a valid payment period.', $plateNumber);
}

if (!array_key_exists($licenceClass, vcs_vehicle_licence_classes())) {
    flash_vehicle_profile('warning', 'Choose a valid driver licence class.', $plateNumber);
}

if ($serviceIntervalKm < 1000) {
    $serviceIntervalKm = 5000;
}

if ($odometerKm < 0) {
    $odometerKm = 0;
}

try {
    $update = $pdo->prepare(
        'UPDATE vehicles
         SET chassis_number = :chassis_number,
             insurance_type = :insurance_type,
             payment_period = :payment_period,
             driver_licence_class = :driver_licence_class,
             odometer_km = :odometer_km,
             service_interval_km = :service_interval_km,
             next_probable_service_km = :next_probable_service_km
         WHERE vehicle_id = :vehicle_id'
    );
    $update->execute([
        'chassis_number' => $chassisNumber,
        'insurance_type' => $insuranceType,
        'payment_period' => $paymentPeriod,
        'driver_licence_class' => $licenceClass,
        'odometer_km' => $odometerKm,
        'service_interval_km' => $serviceIntervalKm,
        'next_probable_service_km' => $odometerKm + $serviceIntervalKm,
        'vehicle_id' => $vehicleId,
    ]);

    if ($plateNumber !== '') {
        $sync = $pdo->prepare(
            'UPDATE service_records
             SET service_interval_km = :service_interval_km,
                 next_service_odometer_km = COALESCE(last_service_odometer_km, :odometer_km) + :service_interval_km
             WHERE vehicle_id = :vehicle_id
             ORDER BY service_id DESC
             LIMIT 1'
        );
        $sync->execute([
            'service_interval_km' => $serviceIntervalKm,
            'odometer_km' => $odometerKm,
            'vehicle_id' => $vehicleId,
        ]);
    }

    flash_vehicle_profile('success', 'Vehicle profile updated successfully.', $plateNumber);
} catch (PDOException $e) {
    error_log($e->getMessage());
    flash_vehicle_profile('danger', 'Database error while updating the vehicle profile.', $plateNumber);
}
