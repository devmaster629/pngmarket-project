SET FOREIGN_KEY_CHECKS=0;


DROP TABLE IF EXISTS /*TABLE_PREFIX*/t_item_epsilon;
CREATE TABLE /*TABLE_PREFIX*/t_item_epsilon(
  fk_i_item_id INT(11) UNSIGNED NOT NULL UNIQUE,
  s_phone VARCHAR(100),
  i_condition TINYINT(1),
  i_transaction TINYINT(1),
  i_sold TINYINT(1),

  PRIMARY KEY (fk_i_item_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';

INSERT INTO /*TABLE_PREFIX*/t_item_epsilon (fk_i_item_id) SELECT pk_i_id FROM /*TABLE_PREFIX*/t_item;



DROP TABLE IF EXISTS /*TABLE_PREFIX*/t_item_stats_epsilon;
CREATE TABLE /*TABLE_PREFIX*/t_item_stats_epsilon(
  fk_i_item_id INT(11) UNSIGNED NOT NULL,
  i_num_phone_clicks INT(10) DEFAULT 0,
  dt_date DATE,

  PRIMARY KEY (fk_i_item_id, dt_date)
) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';



DROP TABLE IF EXISTS /*TABLE_PREFIX*/t_category_epsilon;
CREATE TABLE /*TABLE_PREFIX*/t_category_epsilon(
  fk_i_category_id INT(11) UNSIGNED NOT NULL UNIQUE,
  s_color VARCHAR(24),
  s_icon VARCHAR(100),

  PRIMARY KEY (fk_i_category_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';

INSERT INTO /*TABLE_PREFIX*/t_category_epsilon (fk_i_category_id) SELECT pk_i_id FROM /*TABLE_PREFIX*/t_category;


ALTER TABLE /*TABLE_PREFIX*/t_item_epsilon ADD CONSTRAINT /*TABLE_PREFIX*/t_item_epsilon_ibfk_1 FOREIGN KEY (fk_i_item_id) REFERENCES /*TABLE_PREFIX*/t_item(pk_i_id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE /*TABLE_PREFIX*/t_item_stats_epsilon ADD CONSTRAINT /*TABLE_PREFIX*/t_item_stats_epsilon_ibfk_1 FOREIGN KEY (fk_i_item_id) REFERENCES /*TABLE_PREFIX*/t_item(pk_i_id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE /*TABLE_PREFIX*/t_category_epsilon ADD CONSTRAINT /*TABLE_PREFIX*/t_category_epsilon_ibfk_1 FOREIGN KEY (fk_i_category_id) REFERENCES /*TABLE_PREFIX*/t_category(pk_i_id) ON DELETE CASCADE ON UPDATE CASCADE;


SET FOREIGN_KEY_CHECKS=1;