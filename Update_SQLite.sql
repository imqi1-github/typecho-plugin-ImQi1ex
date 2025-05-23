CREATE TABLE `typecho_links_upgrade`
(
    `lid`   INTEGER NOT NULL PRIMARY KEY,
    `name`  varchar(50)  DEFAULT NULL,
    `url`   varchar(200) DEFAULT NULL,
    `sort`  varchar(50)  DEFAULT NULL,
    `image` varchar(200) DEFAULT NULL,
    `state` int(10)      DEFAULT '1',
    `order` int(10)      DEFAULT '0'
);
INSERT INTO `typecho_links_upgrade` (`lid`, `name`, `url`, `sort`, `image`, `state`, `order`)
SELECT `lid`, `name`, `url`, NULL, NULL, NULL, `order`
FROM `typecho_links`;
DROP TABLE `typecho_links`;
CREATE TABLE `typecho_links`
(
    `lid`   INTEGER NOT NULL PRIMARY KEY,
    `name`  varchar(50)  DEFAULT NULL,
    `url`   varchar(200) DEFAULT NULL,
    `sort`  varchar(50)  DEFAULT NULL,
    `image` varchar(200) DEFAULT NULL,
    `state` int(10)      DEFAULT '1',
    `order` int(10)      DEFAULT '0'
);
INSERT INTO `typecho_links`
SELECT *
FROM `typecho_links_upgrade`;
DROP TABLE `typecho_links_upgrade`;
