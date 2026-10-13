-- Run in your new fleet_system database after the optional demo seed.
-- These are read-only MySQL/MariaDB checks. Save actual results, not assumed results.

-- Q1: active fleet, five records after the seed.
SELECT id, registration, make, model, mileage, status FROM vehicles WHERE archived=0 ORDER BY registration;

-- Q2: three fictional drivers after the seed.
SELECT id, name, licence_number, licence_expiry, active FROM drivers ORDER BY name;

-- Q3: current assignments; DEMO01 linked to Demo Driver One after the seed.
SELECT v.registration, d.name, a.started_at
FROM assignments a JOIN vehicles v ON v.id=a.vehicle_id JOIN drivers d ON d.id=a.driver_id
WHERE a.ended_at IS NULL;

-- Q4: services joined to their parent vehicle; three rows after the seed.
SELECT v.registration, m.service_date, m.description, m.cost_pence
FROM maintenance m JOIN vehicles v ON v.id=m.vehicle_id ORDER BY m.id;

-- Q5: repair records; two rows after the seed.
SELECT v.registration, r.repair_date, r.description, r.cost_pence
FROM repairs r JOIN vehicles v ON v.id=r.vehicle_id ORDER BY r.id;

-- Q6: upcoming or expired MOT dates within the agreed 30-day warning window.
SELECT registration, mot_expiry FROM vehicles
WHERE archived=0 AND mot_expiry IS NOT NULL AND mot_expiry<=DATE_ADD(CURRENT_DATE,INTERVAL 30 DAY);

-- Q7: missing compliance data must not be interpreted as Current.
SELECT registration FROM vehicles WHERE archived=0 AND (mot_expiry IS NULL OR insurance_expiry IS NULL);

-- Q8: service due by date OR mileage, not both.
SELECT registration, mileage, next_service_date, next_service_mileage FROM vehicles
WHERE archived=0 AND ((next_service_date IS NOT NULL AND next_service_date<CURRENT_DATE)
OR (next_service_mileage IS NOT NULL AND mileage>=next_service_mileage));

-- Q9: exact aggregate of all seeded costs should be 92050 pence, or GBP 920.50.
SELECT COALESCE((SELECT SUM(cost_pence) FROM maintenance),0)
+ COALESCE((SELECT SUM(cost_pence) FROM repairs),0) AS total_cost_pence;

-- Q10: no duplicate active assignments should be returned.
SELECT vehicle_id, COUNT(*) AS active_count FROM assignments WHERE ended_at IS NULL GROUP BY vehicle_id HAVING COUNT(*)>1;

-- Q11: no orphan repairs should be returned.
SELECT r.id FROM repairs r LEFT JOIN vehicles v ON v.id=r.vehicle_id WHERE v.id IS NULL;

-- Q12: inspect the schema including generated unique assignment columns.
SHOW CREATE TABLE assignments;
