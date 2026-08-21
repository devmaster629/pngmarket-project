SET FOREIGN_KEY_CHECKS=0;


DROP TABLE IF EXISTS /*TABLE_PREFIX*/t_user_facebook_js_login;
CREATE TABLE /*TABLE_PREFIX*/t_user_facebook_js_login (
  fk_i_user_id INT(10) UNSIGNED NOT NULL,
  s_oauth_provider VARCHAR(30),
  s_oauth_uid VARCHAR(100),
  s_name VARCHAR(200),
  s_email VARCHAR(200),
  s_picture VARCHAR(255),
  dt_modified TIMESTAMP,
  dt_created TIMESTAMP,

  PRIMARY KEY (fk_i_user_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';


ALTER TABLE /*TABLE_PREFIX*/t_user_facebook_js_login ADD CONSTRAINT /*TABLE_PREFIX*/t_user_facebook_js_login_ibfk_1 FOREIGN KEY (fk_i_user_id) REFERENCES /*TABLE_PREFIX*/t_user(pk_i_id) ON DELETE CASCADE ON UPDATE CASCADE;


SET FOREIGN_KEY_CHECKS=1;