CREATE TABLE `users` (
  `uid` int(11) NOT NULL auto_increment,
  `username` varchar(30) NOT NULL,
  `passwd` varchar(40) NOT NULL,
  `tmppasswd` varchar(40) default NULL,
  `accesslevel` tinyint(1) NOT NULL default '0',
  `phone` varchar(25) NOT NULL,
  `altphone` varchar(25) NOT NULL,
  `address` varchar(100) NOT NULL,
  `city` varchar(75) NOT NULL,
  `state` varchar(75) NOT NULL,
  `zip` varchar(10) NOT NULL,
  `site` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `loginattempts` tinyint(1) NOT NULL,
  `passwdexpire` datetime NOT NULL,
  PRIMARY KEY  (`uid`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='User Table' AUTO_INCREMENT=4 ;


CREATE TABLE `groups` (
  `id` tinyint(3) NOT NULL,
  `name` varchar(50) NOT NULL,
  `user_id` tinyint(11) NOT NULL,
  `notes` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `notes` (`notes`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `ip_addresses` (
  `start_address` binary(32) NOT NULL,
  `end_address` binary(32) NOT NULL,
  `mask` binary(32) NOT NULL,
  `site_id` tinyint(11) NOT NULL,
  `hardwares_id` tinyint(11) NOT NULL,
  `notes` varchar(255) NOT NULL,
  PRIMARY KEY  (`start_address`),
  KEY `end_address` (`end_address`,`notes`),
  KEY `site_id` (`site_id`,`hardwares_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



