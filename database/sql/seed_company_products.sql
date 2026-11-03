-- =====================================================================
-- SunXFinder: 10 products with specs for every live company
-- ---------------------------------------------------------------------
-- Live company = status 'approved', is_active = 1, is_verified = 1, not deleted.
-- Each company gets 3 solar panels, 3 inverters, 3 batteries and 1 accessory,
-- picked from a 30-product catalog so neighbouring companies get different products.
--
-- Safe to run more than once: a company never gets the same model twice,
-- and a product never gets the same spec twice. Missing suppliers are added.
-- Works on MySQL 5.7 and 8 (no window functions). Run on a backup first.
-- The two helper tables use utf8mb4_unicode_ci, the app's collation. If your
-- server tables use another one, change both COLLATE lines to match.
-- =====================================================================

SET NAMES utf8mb4;

START TRANSACTION;

-- ---------------------------------------------------------------------
-- 1. Suppliers the catalog needs (added only if missing)
-- ---------------------------------------------------------------------
INSERT INTO tbl_suppliers (name, is_active, created_at, updated_at)
SELECT s.name, 1, NOW(), NOW()
FROM (
    SELECT 'Jinko Solar' AS name UNION ALL
    SELECT 'LONGi Solar' UNION ALL
    SELECT 'JA Solar' UNION ALL
    SELECT 'Canadian Solar' UNION ALL
    SELECT 'Huawei' UNION ALL
    SELECT 'Deye' UNION ALL
    SELECT 'Growatt' UNION ALL
    SELECT 'Victron Energy' UNION ALL
    SELECT 'BYD' UNION ALL
    SELECT 'Pylontech' UNION ALL
    SELECT 'Sungrow' UNION ALL
    SELECT 'Must Solar' UNION ALL
    SELECT 'Motoma'
) s
WHERE NOT EXISTS (
    SELECT 1 FROM tbl_suppliers t WHERE t.name = s.name AND t.deleted_at IS NULL
);

-- ---------------------------------------------------------------------
-- 2. Product catalog (idx 0-29). The type follows idx MOD 10:
--    0-2 solar panel, 3-5 inverter, 6-8 battery, 9 accessory.
-- ---------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS tmp_seed_products;
CREATE TEMPORARY TABLE tmp_seed_products (
    idx INT PRIMARY KEY,
    supplier VARCHAR(100) NOT NULL,
    name VARCHAR(150) NOT NULL,
    model VARCHAR(100) NOT NULL,
    type VARCHAR(20) NOT NULL,
    description TEXT
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO tmp_seed_products (idx, supplier, name, model, type, description) VALUES
(0,  'Jinko Solar',    'Jinko Tiger Neo 620W Panel',             'JKM620N-66HL4M-BDV',  'solar_panel', 'N-type bifacial TOPCon module for large rooftops and ground systems.'),
(1,  'LONGi Solar',    'LONGi Hi-MO 6 Explorer 580W Panel',      'LR5-72HTH-580M',      'solar_panel', 'High-efficiency HPBC monocrystalline module for homes and businesses.'),
(2,  'JA Solar',       'JA Solar DeepBlue 4.0 Pro 615W Panel',   'JAM66D45-615/LB',     'solar_panel', 'N-type bifacial module with strong output in high heat.'),
(3,  'Deye',           'Deye 5kW Hybrid Inverter',               'SUN-5K-SG04LP1-EU',   'inverter',    'Single-phase hybrid inverter that runs solar, battery and grid together.'),
(4,  'Growatt',        'Growatt SPF 6000 ES Plus Off-Grid Inverter', 'SPF 6000ES PLUS', 'inverter',    'Off-grid inverter with two MPPT chargers, works without the grid.'),
(5,  'Huawei',         'Huawei SUN2000 5kW Hybrid Inverter',     'SUN2000-5KTL-L1',     'inverter',    'Smart single-phase hybrid inverter, ready for the Huawei LUNA battery.'),
(6,  'Pylontech',      'Pylontech US2000C Lithium Battery',      'US2000C',             'battery',     '48 V LiFePO4 battery module, stackable for more storage.'),
(7,  'BYD',            'BYD Battery-Box Premium LVS 4.0',        'LVS 4.0',             'battery',     'Low-voltage LiFePO4 battery tower, expandable in 4 kWh steps.'),
(8,  'Deye',           'Deye SE-G5.1 Pro Lithium Battery',       'SE-G5.1 Pro-B',       'battery',     '51.2 V LiFePO4 battery for Deye and most 48 V hybrid inverters.'),
(9,  'Victron Energy', 'Victron SmartSolar MPPT 150/60',         'SCC115060211',        'other',       'Solar charge controller with Bluetooth monitoring.'),
(10, 'Canadian Solar', 'Canadian Solar TOPBiHiKu6 600W Panel',   'CS6.2-66TB-600',      'solar_panel', 'Bifacial N-type TOPCon module for high-yield systems.'),
(11, 'Jinko Solar',    'Jinko Tiger Neo 475W Panel',             'JKM475N-60HL4-V',     'solar_panel', 'Compact N-type module, a good fit for small home roofs.'),
(12, 'LONGi Solar',    'LONGi Hi-MO X6 450W Panel',              'LR5-54HTH-450M',      'solar_panel', 'Residential HPBC module with an all-black look.'),
(13, 'Must Solar',     'Must PV1800 VHM 5.5kW Off-Grid Inverter', 'PV1800 VHM-5.5K',    'inverter',    'Affordable off-grid inverter with MPPT charger and battery-free mode.'),
(14, 'Victron Energy', 'Victron MultiPlus-II 48/5000',           'PMP482505010',        'inverter',    'Inverter-charger for reliable backup power and off-grid systems.'),
(15, 'Sungrow',        'Sungrow SH10RT 10kW Hybrid Inverter',    'SH10RT',              'inverter',    'Three-phase hybrid inverter for larger homes and businesses.'),
(16, 'Huawei',         'Huawei LUNA2000 5kWh Battery',           'LUNA2000-5-S0',       'battery',     'Modular high-voltage LiFePO4 battery for Huawei hybrid inverters.'),
(17, 'Motoma',         'Motoma 12V 200Ah Gel Battery',           'MS12-200',            'battery',     'Deep-cycle gel battery for budget off-grid systems.'),
(18, 'Pylontech',      'Pylontech US3000C Lithium Battery',      'US3000C',             'battery',     '48 V LiFePO4 battery module for home backup.'),
(19, 'Growatt',        'Growatt ShineWiFi-X Monitoring Module',  'ShineWiFi-X',         'other',       'Wi-Fi dongle to follow your Growatt system in the ShinePhone app.'),
(20, 'JA Solar',       'JA Solar 440W N-type Panel',             'JAM54D40-440/LB',     'solar_panel', 'Compact bifacial N-type module for homes.'),
(21, 'Canadian Solar', 'Canadian Solar HiKu7 670W Panel',        'CS7N-670MS',          'solar_panel', 'Large-format mono module for commercial and farm systems.'),
(22, 'LONGi Solar',    'LONGi Hi-MO 7 610W Panel',               'LR7-72HGD-610M',      'solar_panel', 'Bifacial HPDC module for utility and commercial projects.'),
(23, 'Deye',           'Deye 12kW Three-Phase Hybrid Inverter',  'SUN-12K-SG04LP3-EU',  'inverter',    'Three-phase low-voltage hybrid inverter for businesses and large homes.'),
(24, 'Growatt',        'Growatt MIN 6000TL-XH Hybrid Inverter',  'MIN 6000TL-XH',       'inverter',    'Single-phase hybrid inverter for high-voltage batteries.'),
(25, 'Huawei',         'Huawei SUN2000 10kW Three-Phase Inverter', 'SUN2000-10KTL-M1',  'inverter',    'Three-phase smart inverter with battery support.'),
(26, 'BYD',            'BYD Battery-Box Premium HVS 7.7',        'HVS 7.7',             'battery',     'High-voltage LiFePO4 battery tower for hybrid inverters.'),
(27, 'Growatt',        'Growatt ARK 2.5H Battery Module',        'ARK 2.5H-A1',         'battery',     'High-voltage LiFePO4 module, stack 2 to 8 for more storage.'),
(28, 'Victron Energy', 'Victron Lithium Smart 12.8V 200Ah',      'BAT512120610',        'battery',     'LiFePO4 battery with Bluetooth cell monitoring.'),
(29, 'Must Solar',     'Must PC1800F MPPT 60A Charge Controller', 'PC1800F-60A',        'other',       'MPPT solar charge controller for 12, 24 and 48 V battery banks.');

-- ---------------------------------------------------------------------
-- 3. Specs per catalog model
-- ---------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS tmp_seed_specs;
CREATE TEMPORARY TABLE tmp_seed_specs (
    model VARCHAR(100) NOT NULL,
    spec_name VARCHAR(100) NOT NULL,
    spec_value VARCHAR(150) NOT NULL
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO tmp_seed_specs (model, spec_name, spec_value) VALUES
-- Solar panels
('JKM620N-66HL4M-BDV', 'Power', '620 W'),
('JKM620N-66HL4M-BDV', 'Cell Type', 'N-type TOPCon, bifacial'),
('JKM620N-66HL4M-BDV', 'Module Efficiency', 'Up to 22.95%'),
('JKM620N-66HL4M-BDV', 'Dimensions', '2382 x 1134 x 30 mm'),
('JKM620N-66HL4M-BDV', 'Warranty', '12 years product, 30 years power'),

('LR5-72HTH-580M', 'Power', '580 W'),
('LR5-72HTH-580M', 'Cell Type', 'HPBC monocrystalline'),
('LR5-72HTH-580M', 'Module Efficiency', 'Up to 22.5%'),
('LR5-72HTH-580M', 'Dimensions', '2278 x 1134 x 35 mm'),
('LR5-72HTH-580M', 'Warranty', '15 years product, 25 years power'),

('JAM66D45-615/LB', 'Power', '615 W'),
('JAM66D45-615/LB', 'Cell Type', 'N-type, bifacial'),
('JAM66D45-615/LB', 'Module Efficiency', 'Up to 22.8%'),
('JAM66D45-615/LB', 'Dimensions', '2382 x 1134 x 30 mm'),
('JAM66D45-615/LB', 'Warranty', '12 years product, 30 years power'),

('CS6.2-66TB-600', 'Power', '600 W'),
('CS6.2-66TB-600', 'Cell Type', 'N-type TOPCon, bifacial'),
('CS6.2-66TB-600', 'Module Efficiency', 'Up to 22.2%'),
('CS6.2-66TB-600', 'Dimensions', '2382 x 1134 x 30 mm'),
('CS6.2-66TB-600', 'Warranty', '12 years product, 30 years power'),

('JKM475N-60HL4-V', 'Power', '475 W'),
('JKM475N-60HL4-V', 'Cell Type', 'N-type TOPCon'),
('JKM475N-60HL4-V', 'Module Efficiency', 'Up to 22.3%'),
('JKM475N-60HL4-V', 'Dimensions', '1903 x 1134 x 30 mm'),
('JKM475N-60HL4-V', 'Warranty', '12 years product, 30 years power'),

('LR5-54HTH-450M', 'Power', '450 W'),
('LR5-54HTH-450M', 'Cell Type', 'HPBC monocrystalline'),
('LR5-54HTH-450M', 'Module Efficiency', 'Up to 23.0%'),
('LR5-54HTH-450M', 'Dimensions', '1722 x 1134 x 30 mm'),
('LR5-54HTH-450M', 'Warranty', '15 years product, 25 years power'),

('JAM54D40-440/LB', 'Power', '440 W'),
('JAM54D40-440/LB', 'Cell Type', 'N-type, bifacial'),
('JAM54D40-440/LB', 'Module Efficiency', 'Up to 22.5%'),
('JAM54D40-440/LB', 'Dimensions', '1762 x 1134 x 30 mm'),
('JAM54D40-440/LB', 'Warranty', '12 years product, 30 years power'),

('CS7N-670MS', 'Power', '670 W'),
('CS7N-670MS', 'Cell Type', 'Monocrystalline PERC'),
('CS7N-670MS', 'Module Efficiency', 'Up to 21.6%'),
('CS7N-670MS', 'Dimensions', '2384 x 1303 x 35 mm'),
('CS7N-670MS', 'Warranty', '12 years product, 25 years power'),

('LR7-72HGD-610M', 'Power', '610 W'),
('LR7-72HGD-610M', 'Cell Type', 'HPDC monocrystalline, bifacial'),
('LR7-72HGD-610M', 'Module Efficiency', 'Up to 22.6%'),
('LR7-72HGD-610M', 'Dimensions', '2382 x 1134 x 30 mm'),
('LR7-72HGD-610M', 'Warranty', '12 years product, 30 years power'),

-- Inverters
('SUN-5K-SG04LP1-EU', 'Rated Power', '5 kW'),
('SUN-5K-SG04LP1-EU', 'Inverter Type', 'Hybrid'),
('SUN-5K-SG04LP1-EU', 'Phase', 'Single phase'),
('SUN-5K-SG04LP1-EU', 'Battery Voltage', '48 V'),
('SUN-5K-SG04LP1-EU', 'Max PV Input', '6.5 kW'),

('SPF 6000ES PLUS', 'Rated Power', '6 kW'),
('SPF 6000ES PLUS', 'Inverter Type', 'Off-grid'),
('SPF 6000ES PLUS', 'Phase', 'Single phase'),
('SPF 6000ES PLUS', 'Battery Voltage', '48 V'),
('SPF 6000ES PLUS', 'Max PV Input', '8 kW'),

('SUN2000-5KTL-L1', 'Rated Power', '5 kW'),
('SUN2000-5KTL-L1', 'Inverter Type', 'Hybrid'),
('SUN2000-5KTL-L1', 'Phase', 'Single phase'),
('SUN2000-5KTL-L1', 'Battery Voltage', 'High voltage (LUNA2000)'),
('SUN2000-5KTL-L1', 'Max PV Input', '7.5 kW'),

('PV1800 VHM-5.5K', 'Rated Power', '5.5 kW'),
('PV1800 VHM-5.5K', 'Inverter Type', 'Off-grid'),
('PV1800 VHM-5.5K', 'Phase', 'Single phase'),
('PV1800 VHM-5.5K', 'Battery Voltage', '48 V'),
('PV1800 VHM-5.5K', 'Max PV Input', '6 kW'),

('PMP482505010', 'Rated Power', '5 kVA'),
('PMP482505010', 'Inverter Type', 'Inverter-charger'),
('PMP482505010', 'Phase', 'Single phase'),
('PMP482505010', 'Battery Voltage', '48 V'),
('PMP482505010', 'Charge Current', '70 A'),

('SH10RT', 'Rated Power', '10 kW'),
('SH10RT', 'Inverter Type', 'Hybrid'),
('SH10RT', 'Phase', 'Three phase'),
('SH10RT', 'Battery Voltage', 'High voltage (150-600 V)'),
('SH10RT', 'Max PV Input', '15 kW'),

('SUN-12K-SG04LP3-EU', 'Rated Power', '12 kW'),
('SUN-12K-SG04LP3-EU', 'Inverter Type', 'Hybrid'),
('SUN-12K-SG04LP3-EU', 'Phase', 'Three phase'),
('SUN-12K-SG04LP3-EU', 'Battery Voltage', '48 V'),
('SUN-12K-SG04LP3-EU', 'Max PV Input', '15.6 kW'),

('MIN 6000TL-XH', 'Rated Power', '6 kW'),
('MIN 6000TL-XH', 'Inverter Type', 'Hybrid'),
('MIN 6000TL-XH', 'Phase', 'Single phase'),
('MIN 6000TL-XH', 'Battery Voltage', 'High voltage (ARK battery)'),
('MIN 6000TL-XH', 'Max PV Input', '9 kW'),

('SUN2000-10KTL-M1', 'Rated Power', '10 kW'),
('SUN2000-10KTL-M1', 'Inverter Type', 'Hybrid'),
('SUN2000-10KTL-M1', 'Phase', 'Three phase'),
('SUN2000-10KTL-M1', 'Battery Voltage', 'High voltage (LUNA2000)'),
('SUN2000-10KTL-M1', 'Max PV Input', '15 kW'),

-- Batteries
('US2000C', 'Capacity', '2.4 kWh'),
('US2000C', 'Nominal Voltage', '48 V'),
('US2000C', 'Chemistry', 'LiFePO4'),
('US2000C', 'Cycle Life', '6000+ cycles'),
('US2000C', 'Warranty', '10 years'),

('LVS 4.0', 'Capacity', '4 kWh'),
('LVS 4.0', 'Nominal Voltage', '51.2 V'),
('LVS 4.0', 'Chemistry', 'LiFePO4'),
('LVS 4.0', 'Cycle Life', '6000+ cycles'),
('LVS 4.0', 'Warranty', '10 years'),

('SE-G5.1 Pro-B', 'Capacity', '5.12 kWh'),
('SE-G5.1 Pro-B', 'Nominal Voltage', '51.2 V'),
('SE-G5.1 Pro-B', 'Chemistry', 'LiFePO4'),
('SE-G5.1 Pro-B', 'Cycle Life', '6000+ cycles'),
('SE-G5.1 Pro-B', 'Warranty', '10 years'),

('LUNA2000-5-S0', 'Capacity', '5 kWh'),
('LUNA2000-5-S0', 'Nominal Voltage', '360 V (high voltage)'),
('LUNA2000-5-S0', 'Chemistry', 'LiFePO4'),
('LUNA2000-5-S0', 'Cycle Life', '6000+ cycles'),
('LUNA2000-5-S0', 'Warranty', '10 years'),

('MS12-200', 'Capacity', '2.4 kWh (200 Ah)'),
('MS12-200', 'Nominal Voltage', '12 V'),
('MS12-200', 'Chemistry', 'Gel (deep cycle)'),
('MS12-200', 'Cycle Life', '1200 cycles at 50% depth'),
('MS12-200', 'Warranty', '2 years'),

('US3000C', 'Capacity', '3.55 kWh'),
('US3000C', 'Nominal Voltage', '48 V'),
('US3000C', 'Chemistry', 'LiFePO4'),
('US3000C', 'Cycle Life', '6000+ cycles'),
('US3000C', 'Warranty', '10 years'),

('HVS 7.7', 'Capacity', '7.68 kWh'),
('HVS 7.7', 'Nominal Voltage', '307 V (high voltage)'),
('HVS 7.7', 'Chemistry', 'LiFePO4'),
('HVS 7.7', 'Cycle Life', '6000+ cycles'),
('HVS 7.7', 'Warranty', '10 years'),

('ARK 2.5H-A1', 'Capacity', '2.56 kWh per module'),
('ARK 2.5H-A1', 'Nominal Voltage', '51.2 V per module (high voltage stack)'),
('ARK 2.5H-A1', 'Chemistry', 'LiFePO4'),
('ARK 2.5H-A1', 'Cycle Life', '6000+ cycles'),
('ARK 2.5H-A1', 'Warranty', '10 years'),

('BAT512120610', 'Capacity', '2.56 kWh (200 Ah)'),
('BAT512120610', 'Nominal Voltage', '12.8 V'),
('BAT512120610', 'Chemistry', 'LiFePO4'),
('BAT512120610', 'Cycle Life', '2500+ cycles at 80% depth'),
('BAT512120610', 'Warranty', '5 years'),

-- Accessories
('SCC115060211', 'Product Type', 'MPPT charge controller'),
('SCC115060211', 'Max PV Voltage', '150 V'),
('SCC115060211', 'Charge Current', '60 A'),
('SCC115060211', 'Battery Voltage', '12 / 24 / 36 / 48 V'),
('SCC115060211', 'Monitoring', 'Bluetooth, VictronConnect app'),

('ShineWiFi-X', 'Product Type', 'Monitoring module'),
('ShineWiFi-X', 'Connection', 'Wi-Fi 2.4 GHz'),
('ShineWiFi-X', 'App', 'ShinePhone'),
('ShineWiFi-X', 'Works With', 'Growatt SPF and MIN inverters'),

('PC1800F-60A', 'Product Type', 'MPPT charge controller'),
('PC1800F-60A', 'Max PV Voltage', '145 V'),
('PC1800F-60A', 'Charge Current', '60 A'),
('PC1800F-60A', 'Battery Voltage', '12 / 24 / 48 V');

-- ---------------------------------------------------------------------
-- 4. Products: 10 per live company.
--    MOD(idx + company id, 3) = 0 picks every third catalog row, which always
--    gives 3 panels, 3 inverters, 3 batteries and 1 accessory.
-- ---------------------------------------------------------------------
INSERT INTO tbl_products (company_id, supplier_id, name, model, type, description, is_active, created_at, updated_at)
SELECT
    c.id,
    (SELECT MIN(s.id) FROM tbl_suppliers s WHERE s.name = p.supplier AND s.deleted_at IS NULL),
    p.name,
    p.model,
    p.type,
    p.description,
    1,
    NOW(),
    NOW()
FROM tbl_solar_companies c
JOIN tmp_seed_products p ON MOD(p.idx + c.id, 3) = 0
WHERE c.status = 'approved'
  AND c.is_active = 1
  AND c.is_verified = 1
  AND c.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM tbl_products x
      WHERE x.company_id = c.id AND x.model = p.model AND x.deleted_at IS NULL
  )
ORDER BY c.id, p.idx;

-- ---------------------------------------------------------------------
-- 5. Specs for every catalog product (any company), skipping ones already there.
-- ---------------------------------------------------------------------
INSERT INTO tbl_product_specs (product_id, spec_name, spec_value, created_at, updated_at)
SELECT pr.id, sp.spec_name, sp.spec_value, NOW(), NOW()
FROM tmp_seed_specs sp
JOIN tbl_products pr ON pr.model = sp.model AND pr.deleted_at IS NULL
WHERE NOT EXISTS (
    SELECT 1 FROM tbl_product_specs x
    WHERE x.product_id = pr.id AND x.spec_name = sp.spec_name
);

COMMIT;

DROP TEMPORARY TABLE IF EXISTS tmp_seed_products;
DROP TEMPORARY TABLE IF EXISTS tmp_seed_specs;

-- ---------------------------------------------------------------------
-- Check: products per live company (expect your old ones + 10)
-- ---------------------------------------------------------------------
SELECT c.id, c.name, COUNT(p.id) AS products
FROM tbl_solar_companies c
LEFT JOIN tbl_products p ON p.company_id = c.id AND p.deleted_at IS NULL
WHERE c.status = 'approved' AND c.is_active = 1 AND c.is_verified = 1 AND c.deleted_at IS NULL
GROUP BY c.id, c.name
ORDER BY c.id;
