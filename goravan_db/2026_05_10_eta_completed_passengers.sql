ALTER TABLE schedules
    ADD COLUMN estimated_arrival_at DATETIME NULL AFTER departure_time,
    MODIFY COLUMN arrived_at DATETIME NULL DEFAULT NULL;

ALTER TABLE bookings
    MODIFY COLUMN status ENUM('pending','approved','completed','rejected','cancelled') NOT NULL;

UPDATE schedules
SET estimated_arrival_at = COALESCE(estimated_arrival_at, DATE_ADD(CONCAT(departure_date, ' ', departure_time), INTERVAL 2 HOUR))
WHERE estimated_arrival_at IS NULL;

UPDATE schedules
SET trip_status = 'arrived',
    arrived_at = COALESCE(arrived_at, estimated_arrival_at, NOW()),
    updated_at = NOW()
WHERE trip_status IN ('boarding', 'departed')
  AND estimated_arrival_at <= NOW();

UPDATE bookings b
INNER JOIN schedules s ON b.schedule_id_fk = s.schedule_id_pk
SET b.status = 'completed',
    b.updated_at = NOW()
WHERE s.trip_status = 'arrived'
  AND b.status NOT IN ('rejected', 'cancelled', 'completed');
