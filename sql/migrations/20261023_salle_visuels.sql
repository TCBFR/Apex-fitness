SET @salle_description_absente := (
  SELECT COUNT(*) = 0 FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'salle' AND COLUMN_NAME = 'description_salle'
);
SET @salle_description_sql := IF(@salle_description_absente,
  'ALTER TABLE salle ADD description_salle TEXT NULL AFTER localisationsalle', 'SELECT 1');
PREPARE salle_description_stmt FROM @salle_description_sql;
EXECUTE salle_description_stmt;
DEALLOCATE PREPARE salle_description_stmt;

SET @salle_image_absente := (
  SELECT COUNT(*) = 0 FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'salle' AND COLUMN_NAME = 'image_salle'
);
SET @salle_image_sql := IF(@salle_image_absente,
  'ALTER TABLE salle ADD image_salle VARCHAR(255) NULL AFTER description_salle', 'SELECT 1');
PREPARE salle_image_stmt FROM @salle_image_sql;
EXECUTE salle_image_stmt;
DEALLOCATE PREPARE salle_image_stmt;
