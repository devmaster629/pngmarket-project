SET FOREIGN_KEY_CHECKS=0;


DROP TABLE IF EXISTS /*TABLE_PREFIX*/t_im_threads;
CREATE TABLE /*TABLE_PREFIX*/t_im_threads(
  i_thread_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  s_title VARCHAR(200),
  fk_i_item_id INT,
  i_from_user_id INT,
  s_from_user_name VARCHAR(100),
  s_from_user_email VARCHAR(100),
  i_from_user_notify INT(1) DEFAULT 1,
  s_from_secret VARCHAR(20),
  i_to_user_id INT,
  s_to_user_name VARCHAR(100),
  s_to_user_email VARCHAR(100),
  i_to_user_notify INT(1) DEFAULT 1,
  s_to_secret VARCHAR(20),
  i_flag INT(1) DEFAULT 0,
  i_offer_id INT NULL,
  d_datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (i_thread_id),
  KEY idx_im_thread_users (i_from_user_id, i_to_user_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';


DROP TABLE IF EXISTS /*TABLE_PREFIX*/t_im_messages;
CREATE TABLE /*TABLE_PREFIX*/t_im_messages(
  pk_i_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  fk_i_thread_id INT,
  i_type INT(1) DEFAULT 0,
  i_read INT(1) DEFAULT 0,
  i_email_sent INT(1) DEFAULT 0,
  s_message VARCHAR(5000),
  s_file VARCHAR(100),
  d_datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (pk_i_id),
  KEY idx_im_email_notify (fk_i_thread_id, i_type, i_read, i_email_sent, d_datetime)
) ENGINE=InnoDB DEFAULT CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';


DROP TABLE IF EXISTS /*TABLE_PREFIX*/t_im_block;
CREATE TABLE /*TABLE_PREFIX*/t_im_block(
  pk_i_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  i_user_id INT,
  s_block_email VARCHAR(100),

PRIMARY KEY (pk_i_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';


SET FOREIGN_KEY_CHECKS=1;