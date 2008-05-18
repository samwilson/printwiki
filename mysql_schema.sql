DROP TABLE IF EXISTS diffs;
DROP TABLE IF EXISTS pages;

CREATE TABLE `pages` (
  `name` 		varchar(200) 	NOT NULL,
  `auth_level` 	int(2) 			NOT NULL default '0',
  `body` 		text 			NOT NULL,
  PRIMARY KEY 	(`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='PrintWiki Pages';

CREATE TABLE `diffs` (
  `id` int(12) NOT NULL auto_increment,
  `page_name` varchar(200) NOT NULL,
  `time` 		timestamp 		NOT NULL default CURRENT_TIMESTAMP,
  `diff` 		text 			NOT NULL,
  PRIMARY KEY  	(`id`),
  FOREIGN KEY 	page (`page_name`) REFERENCES `pages` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='PrintWiki Page Differences';
