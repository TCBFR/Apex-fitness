SET @activite_catalogue_image_path_absente := (
  SELECT COUNT(*) = 0
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'activite_catalogue'
    AND COLUMN_NAME = 'image_path'
);
SET @activite_catalogue_image_path_sql := IF(
  @activite_catalogue_image_path_absente,
  'ALTER TABLE activite_catalogue ADD image_path VARCHAR(255) NULL AFTER description',
  'SELECT 1'
);
PREPARE activite_catalogue_image_path_stmt FROM @activite_catalogue_image_path_sql;
EXECUTE activite_catalogue_image_path_stmt;
DEALLOCATE PREPARE activite_catalogue_image_path_stmt;
