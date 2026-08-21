DROP TABLE IF EXISTS /*TABLE_PREFIX*/t_user_google_login;
CREATE TABLE /*TABLE_PREFIX*/t_user_google_login (
  fk_i_user_id INT(10) UNSIGNED NOT NULL,
  s_oauth_provider VARCHAR(15) NOT NULL,
  s_oauth_uid VARCHAR(50) NOT NULL,
  s_first_name VARCHAR(25) NOT NULL,
  s_last_name VARCHAR(25) NOT NULL,
  s_email VARCHAR(50) NOT NULL,
  s_gender VARCHAR(10) DEFAULT NULL,
  s_locale VARCHAR(10) DEFAULT NULL,
  s_picture VARCHAR(255) DEFAULT NULL,
  s_link VARCHAR(255) NOT NULL,
  dt_modified TIMESTAMP,
  dt_created TIMESTAMP,

  PRIMARY KEY (fk_i_user_id),
  FOREIGN KEY (fk_i_user_id) REFERENCES /*TABLE_PREFIX*/t_user (pk_i_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';