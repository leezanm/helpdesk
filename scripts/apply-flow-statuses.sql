-- One-time data update for the new aduan flow (flow baru.md).
-- Apply to an EXISTING database (staging/production) that already has the
-- original statuses 1-6. Fresh databases get this from the seeders instead.
--
-- Safe/idempotent: renames labels for 2 & 3, inserts 7 & 8 only if missing.
-- Data (report_status_id values) is NOT changed, so existing "Selesai" (5)
-- reports stay correct.
--
-- Usage:
--   mysql -u <user> -p <database> < scripts/apply-flow-statuses.sql

UPDATE `_report_statuses`
   SET `desc` = 'Baru',
       `reference` = 'Aduan baru dihantar oleh pengadu',
       `class` = 'badge bg-info'
 WHERE id = 2;

UPDATE `_report_statuses`
   SET `desc` = 'Aduan Disemak',
       `reference` = 'Aduan telah disemak oleh peg. helpdesk',
       `class` = 'badge bg-primary'
 WHERE id = 3;

INSERT INTO `_report_statuses` (id, `desc`, reference, class, created_at, updated_at)
SELECT 7, 'Selesai Tindakan', 'Peg. bertanggungjawab telah membuat tindakan', 'badge bg-success', NOW(), NOW()
 WHERE NOT EXISTS (SELECT 1 FROM `_report_statuses` WHERE id = 7);

INSERT INTO `_report_statuses` (id, `desc`, reference, class, created_at, updated_at)
SELECT 8, 'Tindakan Lanjut Diperlukan', 'Aduan tidak dapat diselesaikan, perlu tindakan lanjut', 'badge bg-danger', NOW(), NOW()
 WHERE NOT EXISTS (SELECT 1 FROM `_report_statuses` WHERE id = 8);

INSERT INTO `_report_trail_statuses` (id, `desc`, class, created_at, updated_at)
SELECT 9, 'Selesai Tindakan', 'badge bg-success', NOW(), NOW()
 WHERE NOT EXISTS (SELECT 1 FROM `_report_trail_statuses` WHERE id = 9);

INSERT INTO `_report_trail_statuses` (id, `desc`, class, created_at, updated_at)
SELECT 10, 'Tindakan Lanjut Diperlukan', 'badge bg-danger', NOW(), NOW()
 WHERE NOT EXISTS (SELECT 1 FROM `_report_trail_statuses` WHERE id = 10);
