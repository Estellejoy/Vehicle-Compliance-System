UPDATE service_records
SET service_notes = COALESCE(
        service_notes,
        CASE service_details
            WHEN 'Diagnostics' THEN 'Diagnostics: scanned error codes, inspected battery, brakes, tyres, lights, fluids, and suspension.'
            WHEN 'Tyre rotation' THEN 'Tyre rotation: rotated tyres, checked pressure, wheel balance, alignment, and tread wear.'
            WHEN 'Brake inspection' THEN 'Brake inspection: inspected pads, discs, brake fluid, calipers, and handbrake performance.'
            WHEN 'Oil change' THEN 'Oil change: replaced engine oil and filter, checked fluids, battery, tyres, and lights.'
            WHEN 'Engine tune-up' THEN 'Engine tune-up: checked spark plugs, ignition, air intake, filters, belts, fluids, and road test.'
            WHEN 'Full service' THEN 'Full service: changed oil and filters, inspected brakes, tyres, suspension, fluids, lights, and diagnostics.'
            ELSE CONCAT(service_details, ': inspected oil, filters, brakes, tyres, fluids, lights, and diagnostics.')
        END
    ),
    last_service_odometer_km = COALESCE(last_service_odometer_km, 38000 + (service_id * 875)),
    service_interval_km = COALESCE(
        service_interval_km,
        CASE service_details
            WHEN 'Diagnostics' THEN 5000
            WHEN 'Tyre rotation' THEN 7500
            WHEN 'Brake inspection' THEN 8000
            WHEN 'Oil change' THEN 5000
            WHEN 'Engine tune-up' THEN 10000
            WHEN 'Full service' THEN 10000
            ELSE 6000
        END
    );

UPDATE service_records
SET next_service_odometer_km = COALESCE(
        next_service_odometer_km,
        COALESCE(last_service_odometer_km, 38000 + (service_id * 875)) + COALESCE(service_interval_km, 5000)
    );
