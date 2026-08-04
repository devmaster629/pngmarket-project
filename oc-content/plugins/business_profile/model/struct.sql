SET FOREIGN_KEY_CHECKS=0;


DROP TABLE IF EXISTS /*TABLE_PREFIX*/t_user_business_profile;
CREATE TABLE /*TABLE_PREFIX*/t_user_business_profile (
  pk_i_id INT NOT NULL AUTO_INCREMENT,
  fk_i_user_id INT(11) UNSIGNED NOT NULL,
  s_identifier VARCHAR(500),
  b_enabled TINYINT(1) DEFAULT 0,
  b_verified TINYINT(1) DEFAULT 1,
  i_type INT(10) DEFAULT 1,
  s_color VARCHAR(10),
  s_icon VARCHAR(50),
  s_logo VARCHAR(50),
  s_cover VARCHAR(50),
  s_features VARCHAR(1000),
  s_hours VARCHAR(200),
  s_socials VARCHAR(1000),
  s_payments VARCHAR(1000),
  s_category_ids VARCHAR(100),
  s_city_ids VARCHAR(255),
  s_gallery VARCHAR(2000),
  s_videos VARCHAR(2000),
  s_legal_notice VARCHAR(2000),

  PRIMARY KEY (pk_i_id),
  FOREIGN KEY (fk_i_user_id) REFERENCES /*TABLE_PREFIX*/t_user (pk_i_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';


DROP TABLE IF EXISTS /*TABLE_PREFIX*/t_bpr_values;
CREATE TABLE /*TABLE_PREFIX*/t_bpr_values (
  pk_i_id INT NOT NULL AUTO_INCREMENT,
  fk_c_locale_code CHAR(5) NOT NULL,
  s_type VARCHAR(20),
  s_name VARCHAR(100) NOT NULL,

  PRIMARY KEY(pk_i_id, fk_c_locale_code)
) ENGINE=InnoDB DEFAULT CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';


SET FOREIGN_KEY_CHECKS=1;