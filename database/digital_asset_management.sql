-- Digital Asset Management System - MySQL 8 / MariaDB 10.4+
-- Import this file directly in phpMyAdmin.
CREATE DATABASE IF NOT EXISTS digital_asset_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE digital_asset_management;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS password_resets, notifications, activity_logs, share_links, downloads, favorites, collection_shares, collection_assets, collections, asset_tags, tags, asset_versions, assets, categories, allowed_file_types, settings, users, roles;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE roles (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id TINYINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  profile_picture VARCHAR(255) NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  last_login DATETIME NULL,
  failed_login_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_users_role_status (role_id, status),
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id INT UNSIGNED NULL,
  name VARCHAR(100) NOT NULL,
  description TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_category_parent_name (parent_id, name),
  CONSTRAINT fk_category_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_category_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE assets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asset_code VARCHAR(40) NOT NULL UNIQUE,
  original_filename VARCHAR(255) NOT NULL,
  stored_filename VARCHAR(255) NOT NULL UNIQUE,
  title VARCHAR(190) NOT NULL,
  description TEXT NULL,
  file_extension VARCHAR(15) NOT NULL,
  mime_type VARCHAR(150) NOT NULL,
  file_size BIGINT UNSIGNED NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  thumbnail_path VARCHAR(500) NULL,
  width INT UNSIGNED NULL,
  height INT UNSIGNED NULL,
  category_id INT UNSIGNED NULL,
  uploaded_by INT UNSIGNED NOT NULL,
  download_count INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('active','archived') NOT NULL DEFAULT 'active',
  visibility ENUM('private','organization','public') NOT NULL DEFAULT 'organization',
  version_number DECIMAL(8,2) NOT NULL DEFAULT 1.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_assets_library (deleted_at, status, created_at),
  INDEX idx_assets_category (category_id),
  INDEX idx_assets_uploader (uploaded_by),
  INDEX idx_assets_type (file_extension),
  FULLTEXT KEY ft_assets_search (title, description, original_filename),
  CONSTRAINT fk_asset_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_asset_user FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE asset_versions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asset_id BIGINT UNSIGNED NOT NULL,
  version_number DECIMAL(8,2) NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  stored_filename VARCHAR(255) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  file_extension VARCHAR(15) NOT NULL,
  mime_type VARCHAR(150) NOT NULL,
  file_size BIGINT UNSIGNED NOT NULL,
  uploaded_by INT UNSIGNED NOT NULL,
  version_notes VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_asset_version (asset_id, version_number),
  CONSTRAINT fk_version_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
  CONSTRAINT fk_version_user FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE tags (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tag_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE asset_tags (
  asset_id BIGINT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (asset_id, tag_id),
  CONSTRAINT fk_at_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
  CONSTRAINT fk_at_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE collections (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  description TEXT NULL,
  owner_id INT UNSIGNED NOT NULL,
  visibility ENUM('private','shared','organization') NOT NULL DEFAULT 'private',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_collection_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE collection_assets (
  collection_id INT UNSIGNED NOT NULL,
  asset_id BIGINT UNSIGNED NOT NULL,
  added_by INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (collection_id, asset_id),
  CONSTRAINT fk_ca_collection FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE,
  CONSTRAINT fk_ca_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
  CONSTRAINT fk_ca_user FOREIGN KEY (added_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE collection_shares (
  collection_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  permission ENUM('view','download') NOT NULL DEFAULT 'view',
  PRIMARY KEY (collection_id, user_id),
  CONSTRAINT fk_cs_collection FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE,
  CONSTRAINT fk_cs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE favorites (
  user_id INT UNSIGNED NOT NULL,
  asset_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, asset_id),
  CONSTRAINT fk_favorite_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_favorite_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE downloads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asset_id BIGINT UNSIGNED NOT NULL,
  version_id BIGINT UNSIGNED NULL,
  user_id INT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_download_date (downloaded_at),
  CONSTRAINT fk_download_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
  CONSTRAINT fk_download_version FOREIGN KEY (version_id) REFERENCES asset_versions(id) ON DELETE SET NULL,
  CONSTRAINT fk_download_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE share_links (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asset_id BIGINT UNSIGNED NOT NULL,
  created_by INT UNSIGNED NOT NULL,
  token CHAR(64) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  expires_at DATETIME NULL,
  download_allowed BOOLEAN NOT NULL DEFAULT TRUE,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  access_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_share_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
  CONSTRAINT fk_share_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  description VARCHAR(500) NOT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_activity_date_action (created_at, action),
  CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  message VARCHAR(500) NOT NULL,
  link VARCHAR(255) NULL,
  is_read BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notification_user_read (user_id, is_read),
  CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT NULL,
  value_type ENUM('string','integer','boolean','json') NOT NULL DEFAULT 'string',
  updated_by INT UNSIGNED NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_setting_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE allowed_file_types (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  extension VARCHAR(15) NOT NULL UNIQUE,
  mime_type VARCHAR(150) NOT NULL,
  type_group VARCHAR(30) NOT NULL,
  is_enabled BOOLEAN NOT NULL DEFAULT TRUE,
  max_size_mb INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO roles (id, name, description) VALUES
(1,'Administrator','Full system administration'),
(2,'Asset Manager','Upload and manage organizational assets'),
(3,'Viewer','Browse authorized assets');

-- Development administrator password: Admin123! (change immediately after first login)
INSERT INTO users (id, role_id, name, email, password_hash, status) VALUES
(1,1,'System Administrator','admin@dam.local','$2y$10$SNB4qz1ix8/0BNcEi7N5nej21rr6bqV1XPPx3cew2YpgGCZRIaATK','active');

INSERT INTO categories (name, description, created_by) VALUES
('Branding','Logos, identity guidelines and brand assets',1),
('Marketing','Campaign and promotional materials',1),
('Documents','Company documents and reference files',1),
('Photography','Photographic assets',1),
('Videos','Video content',1),
('Audio','Audio recordings and music',1),
('Reports','Analytics and business reports',1),
('Templates','Reusable templates',1),
('Product Assets','Product imagery and collateral',1),
('Social Media','Social media creative',1);

INSERT INTO settings (setting_key, setting_value, value_type, updated_by) VALUES
('system_name','Digital Asset Management','string',1),
('system_logo','','string',1),
('max_upload_size_mb','50','integer',1),
('allowed_file_formats','jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,mp3,wav,mp4,webm,zip','string',1),
('default_user_role','3','integer',1),
('assets_per_page','24','integer',1),
('public_sharing_enabled','1','boolean',1),
('default_share_expiry_days','7','integer',1);

INSERT INTO allowed_file_types (extension,mime_type,type_group) VALUES
('jpg','image/jpeg','images'),('jpeg','image/jpeg','images'),('png','image/png','images'),
('gif','image/gif','images'),('webp','image/webp','images'),('svg','image/svg+xml','images'),
('pdf','application/pdf','documents'),('doc','application/msword','documents'),
('docx','application/vnd.openxmlformats-officedocument.wordprocessingml.document','documents'),
('xls','application/vnd.ms-excel','documents'),('xlsx','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','documents'),
('ppt','application/vnd.ms-powerpoint','documents'),('pptx','application/vnd.openxmlformats-officedocument.presentationml.presentation','documents'),
('txt','text/plain','documents'),('csv','text/csv','documents'),('mp3','audio/mpeg','audio'),
('wav','audio/wav','audio'),('mp4','video/mp4','videos'),('webm','video/webm','videos'),('zip','application/zip','archives');
