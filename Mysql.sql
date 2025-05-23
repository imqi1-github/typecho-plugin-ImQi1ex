CREATE TABLE IF NOT EXISTS `typecho_links`(
  `lid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'links表主键',
  `name` varchar(50) DEFAULT NULL COMMENT 'links名称',
  `url` varchar(200) DEFAULT NULL COMMENT 'links网址',
  `sort` varchar(50) DEFAULT NULL COMMENT 'links分类',
  `image` varchar(200) DEFAULT NULL COMMENT 'links图片',
  `state` int(10) DEFAULT '1' COMMENT 'links状态',
  `order` int(10) UNSIGNED DEFAULT '0' COMMENT 'links排序',
  PRIMARY KEY  (`lid`)
) ENGINE=MYISAM  DEFAULT CHARSET=%charset%;
