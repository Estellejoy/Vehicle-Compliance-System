UPDATE vehicles
SET chassis_number = COALESCE(
        chassis_number,
        CASE make
            WHEN 'Toyota' THEN CONCAT('JT1', LPAD((vehicle_id * 7919) + year, 14, '0'))
            WHEN 'Mazda' THEN CONCAT('JM1', LPAD((vehicle_id * 7919) + year, 14, '0'))
            WHEN 'Honda' THEN CONCAT('JHM', LPAD((vehicle_id * 7919) + year, 14, '0'))
            WHEN 'Subaru' THEN CONCAT('JF1', LPAD((vehicle_id * 7919) + year, 14, '0'))
            WHEN 'Nissan' THEN CONCAT('JN1', LPAD((vehicle_id * 7919) + year, 14, '0'))
            ELSE CONCAT('JTD', LPAD((vehicle_id * 7919) + year, 14, '0'))
        END
    ),
    insurance_type = COALESCE(insurance_type, CASE WHEN MOD(vehicle_id, 2) = 0 THEN 'Comprehensive' ELSE 'Third Party' END),
    payment_period = COALESCE(payment_period, 'Annual'),
    driver_licence_class = COALESCE(driver_licence_class, CASE WHEN year >= 2020 THEN 'B' ELSE 'C1' END),
    odometer_km = COALESCE(
        odometer_km,
        CASE
            WHEN year >= 2023 THEN 18000 + (vehicle_id * 220)
            WHEN year >= 2020 THEN 32000 + (vehicle_id * 260)
            WHEN year >= 2017 THEN 48000 + (vehicle_id * 310)
            ELSE 62000 + (vehicle_id * 340)
        END
    ),
    service_interval_km = COALESCE(
        service_interval_km,
        CASE
            WHEN year >= 2023 THEN 7000
            WHEN year >= 2020 THEN 8000
            WHEN year >= 2017 THEN 9000
            ELSE 10000
        END
    ),
    next_probable_service_km = COALESCE(next_probable_service_km, COALESCE(odometer_km, 32000 + (vehicle_id * 260)) + COALESCE(service_interval_km, 8000));
