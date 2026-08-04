SET FOREIGN_KEY_CHECKS=0;


DROP TABLE IF EXISTS /*TABLE_PREFIX*/t_attribute;
CREATE TABLE /*TABLE_PREFIX*/t_attribute (
  pk_i_id INT NOT NULL AUTO_INCREMENT,
  s_identifier VARCHAR(100),
  b_enabled TINYINT(1) DEFAULT 1,
  b_required TINYINT(1) DEFAULT 0,
  b_search TINYINT(1) DEFAULT 0,
  b_hook TINYINT(1) DEFAULT 1,
  b_values_all TINYINT(1) DEFAULT 0,
  s_category_id VARCHAR(1000),
  i_order INT(10) DEFAULT 1,
  s_type VARCHAR(20),
  s_search_type VARCHAR(20) DEFAULT NULL,
  b_search_range TINYINT(1) DEFAULT 0,
  b_check_single TINYINT(1) DEFAULT 0,
  s_search_engine VARCHAR(20) DEFAULT 'AND',
  s_search_values_all TINYINT(1) DEFAULT 0,
  s_icon VARCHAR(200) DEFAULT NULL,
  fk_i_linked_to_attr_id INT DEFAULT NULL,
  s_restrict_type VARCHAR(20) DEFAULT NULL,

  PRIMARY KEY (pk_i_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';


DROP TABLE IF EXISTS /*TABLE_PREFIX*/t_attribute_value;
CREATE TABLE /*TABLE_PREFIX*/t_attribute_value (
  pk_i_id INT NOT NULL AUTO_INCREMENT,
  fk_i_attribute_id INT NOT NULL,
  fk_i_parent_id INT(10) DEFAULT NULL,
  s_image VARCHAR(200),
  i_order INT(10) DEFAULT 1,


  PRIMARY KEY (pk_i_id),
  FOREIGN KEY (fk_i_attribute_id) REFERENCES /*TABLE_PREFIX*/t_attribute (pk_i_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';



DROP TABLE IF EXISTS /*TABLE_PREFIX*/t_item_attribute;
CREATE TABLE /*TABLE_PREFIX*/t_item_attribute (
  pk_i_id INT NOT NULL AUTO_INCREMENT,
  fk_i_item_id INT UNSIGNED NOT NULL,
  fk_i_attribute_id INT NOT NULL,
  fk_i_attribute_value_id INT,
  s_value VARCHAR(1000),

  PRIMARY KEY (pk_i_id),
  FOREIGN KEY (fk_i_item_id) REFERENCES /*TABLE_PREFIX*/t_item (pk_i_id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (fk_i_attribute_id) REFERENCES /*TABLE_PREFIX*/t_attribute (pk_i_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';



DROP TABLE IF EXISTS /*TABLE_PREFIX*/t_attribute_locale;
CREATE TABLE /*TABLE_PREFIX*/t_attribute_locale (
  pk_i_id INT NOT NULL AUTO_INCREMENT,
  fk_i_attribute_id INT NOT NULL,
  fk_c_locale_code CHAR(5) NOT NULL,
  s_name VARCHAR(200) NULL,

  PRIMARY KEY (pk_i_id),
  FOREIGN KEY (fk_i_attribute_id) REFERENCES /*TABLE_PREFIX*/t_attribute (pk_i_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';




DROP TABLE IF EXISTS /*TABLE_PREFIX*/t_attribute_value_locale;
CREATE TABLE /*TABLE_PREFIX*/t_attribute_value_locale (
  pk_i_id INT NOT NULL AUTO_INCREMENT,
  fk_i_attribute_value_id INT NOT NULL,
  fk_c_locale_code CHAR(5) NOT NULL,
  s_name VARCHAR(200) NULL,

  PRIMARY KEY (pk_i_id),
  FOREIGN KEY (fk_i_attribute_value_id) REFERENCES /*TABLE_PREFIX*/t_attribute_value (pk_i_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';


SET FOREIGN_KEY_CHECKS=1;




INSERT INTO /*TABLE_PREFIX*/t_attribute (pk_i_id, s_identifier, b_enabled, b_required, b_search, b_hook, b_values_all, s_category_id, i_order, s_type) VALUES
(1, 'make', 1, 1, 1, 1, 1, '', 1, 'SELECT'),
(2, 'accessories', 1, NULL, NULL, 1, 1, '', 2, 'CHECKBOX'),
(3, 'body', 1, 1, 1, 1, NULL, '', 3, 'RADIO'),
(4, 'fuel', 1, NULL, 1, 1, NULL, '', 4, 'SELECT'),
(5, 'seats', 1, NULL, NULL, 1, NULL, '', 5, 'TEXT'),
(6, 'phone', 1, NULL, NULL, 1, NULL, '', 6, 'PHONE'),
(7, 'transmission', 1, NULL, 1, 1, 1, '', 7, 'RADIO'),
(8, 'condition', 1, NULL, NULL, 1, NULL, '', 8, 'TEXTAREA');

ALTER TABLE /*TABLE_PREFIX*/t_attribute MODIFY pk_i_id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;COMMIT;



INSERT INTO /*TABLE_PREFIX*/t_attribute_locale (pk_i_id, fk_i_attribute_id, fk_c_locale_code, s_name) VALUES
(1, 4, 'en_US', 'Fuel'),
(2, 3, 'en_US', 'Body'),
(3, 2, 'en_US', 'Accessories'),
(4, 1, 'en_US', 'Car Make'),
(5, 5, 'en_US', 'Seats'),
(6, 6, 'en_US', 'Contact Phone'),
(7, 7, 'en_US', 'Transmission'),
(8, 8, 'en_US', 'Car Condition');

ALTER TABLE /*TABLE_PREFIX*/t_attribute_locale MODIFY pk_i_id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;



INSERT INTO /*TABLE_PREFIX*/t_attribute_value (pk_i_id, fk_i_attribute_id, fk_i_parent_id, s_image, i_order) VALUES
(1, 4, NULL, 'default/cars/engine.png', 9999),
(2, 4, NULL, 'default/cars/engine-1.png', 9999),
(3, 4, NULL, 'default/cars/engine.png', 9999),
(4, 4, NULL, 'default/cars/engine-1.png', 9999),
(5, 3, NULL, '', 9999),
(6, 3, NULL, '', 9999),
(7, 3, NULL, '', 9999),
(8, 3, NULL, '', 9999),
(9, 2, NULL, '', 9999),
(10, 2, NULL, '', 9999),
(11, 2, NULL, '', 9999),
(12, 2, NULL, '', 9999),
(13, 2, NULL, '', 9999),
(14, 2, NULL, '', 9999),
(15, 2, NULL, '', 9999),
(16, 2, NULL, '', 9999),
(17, 2, NULL, '', 9999),
(18, 1, NULL, '', 1),
(19, 1, NULL, '', 8),
(20, 1, NULL, '', 17),
(21, 1, NULL, '', 21),
(22, 1, NULL, '', 27),
(23, 1, 18, '', 2),
(24, 1, 18, '', 5),
(25, 1, 18, '', 7),
(26, 1, 23, '', 3),
(27, 1, 23, '', 4),
(28, 1, 24, '', 6),
(29, 1, 19, '', 9),
(30, 1, 19, '', 12),
(31, 1, 19, '', 15),
(32, 1, 29, '', 11),
(33, 1, 29, '', 10),
(34, 1, 30, '', 13),
(35, 1, 31, '', 16),
(36, 1, 30, '', 14),
(37, 1, 20, '', 18),
(38, 1, 20, '', 19),
(39, 1, 20, '', 20),
(41, 1, 21, '', 22),
(42, 1, 21, '', 24),
(43, 1, 21, '', 26),
(44, 1, 41, '', 23),
(45, 1, 42, '', 25),
(46, 1, 22, '', 28),
(47, 1, 22, '', 29),
(48, 1, 22, '', 30),
(49, 7, NULL, 'default/cars/063-gear-2.png', 9999),
(50, 7, NULL, 'default/cars/067-gear-1.png', 9999);

ALTER TABLE /*TABLE_PREFIX*/t_attribute_value MODIFY pk_i_id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;



INSERT INTO /*TABLE_PREFIX*/t_attribute_value_locale (pk_i_id, fk_i_attribute_value_id, fk_c_locale_code, s_name) VALUES
(1, 1, 'en_US', 'Gasoline'),
(2, 2, 'en_US', 'Diesel'),
(3, 3, 'en_US', 'Hybrid'),
(4, 4, 'en_US', 'Electric'),
(5, 5, 'en_US', 'Sedan'),
(6, 6, 'en_US', 'Hatchback'),
(7, 7, 'en_US', 'Combi'),
(8, 8, 'en_US', 'Coupe'),
(9, 9, 'en_US', 'ABS'),
(10, 10, 'en_US', 'ESP'),
(11, 11, 'en_US', 'Imobilizer'),
(12, 12, 'en_US', 'Heating'),
(13, 13, 'en_US', 'Aircondition'),
(14, 14, 'en_US', 'Metallic Color'),
(15, 15, 'en_US', 'Navigation'),
(16, 16, 'en_US', 'Radio'),
(17, 17, 'en_US', 'Warranty'),
(18, 18, 'en_US', 'Mercedes'),
(19, 23, 'en_US', 'A-Class'),
(20, 26, 'en_US', 'All Road'),
(21, 27, 'en_US', '4x4'),
(22, 24, 'en_US', 'B-Class'),
(23, 28, 'en_US', 'Combi'),
(24, 25, 'en_US', 'GLS-Class'),
(25, 19, 'en_US', 'Volkswagen'),
(26, 29, 'en_US', 'Golf'),
(27, 33, 'en_US', 'Sedan'),
(28, 32, 'en_US', 'Combi'),
(29, 30, 'en_US', 'Passat'),
(30, 34, 'en_US', 'SUV'),
(31, 36, 'en_US', 'All Track'),
(32, 31, 'en_US', 'Amarok'),
(33, 35, 'en_US', '4x4'),
(34, 20, 'en_US', 'Seat'),
(35, 21, 'en_US', 'Opel'),
(36, 22, 'en_US', 'Skoda'),
(37, 37, 'en_US', 'Alhambra'),
(38, 38, 'en_US', 'Leon'),
(39, 39, 'en_US', 'Altea'),
(40, 41, 'en_US', 'Astra'),
(41, 44, 'en_US', 'R-Line'),
(42, 42, 'en_US', 'Insignia'),
(43, 45, 'en_US', 'R-Line'),
(44, 43, 'en_US', 'Grand Tour'),
(45, 46, 'en_US', 'Fabia'),
(46, 47, 'en_US', 'Octavia'),
(47, 48, 'en_US', 'Superb'),
(48, 49, 'en_US', 'Manual'),
(49, 50, 'en_US', 'Automatic');

ALTER TABLE /*TABLE_PREFIX*/t_attribute_value_locale MODIFY pk_i_id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;
