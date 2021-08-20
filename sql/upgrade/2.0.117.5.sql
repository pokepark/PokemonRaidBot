ALTER TABLE attendance ADD COLUMN IF NOT EXISTS remote tinyint(1) unsigned DEFAULT '0';
