  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(128) NOT NULL DEFAULT '',
  `ip_geo` text NOT NULL,
  `username` varchar(128) NOT NULL DEFAULT '',
  `gateway` varchar(128) NOT NULL DEFAULT '',
  `dateline` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`),
  KEY `dateline` (`dateline`)
