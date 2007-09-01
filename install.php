<?php

require_once('./include/db_connect.php');

$sql = 
"
DROP TABLE IF EXISTS `acl`;;
CREATE TABLE `acl` (
  `id` tinyint(9) NOT NULL auto_increment,
  `name` varchar(25) NOT NULL,
  `start_ip` int(10) NOT NULL,
  `end_ip` int(10) NOT NULL,
  `apply` varchar(25) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;;

DROP TABLE IF EXISTS `blocks`;;
CREATE TABLE `blocks` (
  `id` tinyint(9) NOT NULL auto_increment,
  `name` varchar(25) NOT NULL,
  `start_ip` int(10) NOT NULL,
  `end_ip` int(10) NOT NULL,
  `note` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;;

DROP TABLE IF EXISTS `logs`;;
CREATE TABLE `logs` (
  `id` tinyint(11) NOT NULL auto_increment,
  `occuredat` datetime NOT NULL,
  `username` varchar(30) NOT NULL,
  `ipaddress` varchar(15) NOT NULL,
  `level` varchar(6) NOT NULL,
  `message` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;;

DROP TABLE IF EXISTS `settings`;;
CREATE TABLE `settings` (
  `name` varchar(50) NOT NULL,
  `value` varchar(50) NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;;

INSERT INTO `settings` VALUES ('passwdlength', '5');;
INSERT INTO `settings` VALUES ('accountexpire', '60');;
INSERT INTO `settings` VALUES ('loginattempts', '4');;
INSERT INTO `settings` VALUES ('version', '1.0');;
INSERT INTO `settings` VALUES ('perms', '6');;

DROP TABLE IF EXISTS `statics`;;
CREATE TABLE `statics` (
  `id` tinyint(10) NOT NULL auto_increment,
  `ip` int(10) NOT NULL,
  `name` varchar(25) NOT NULL,
  `contact` varchar(25) NOT NULL,
  `note` varchar(255) NOT NULL,
  `subnet_id` tinyint(9) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;;

DROP TABLE IF EXISTS `subnets`;;
CREATE TABLE `subnets` (
  `id` tinyint(9) NOT NULL auto_increment,
  `name` varchar(25) NOT NULL,
  `start_ip` int(10) NOT NULL,
  `end_ip` int(10) NOT NULL,
  `mask` int(10) NOT NULL,
  `note` varchar(255) NOT NULL,
  `block_id` tinyint(9) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;;

DROP TABLE IF EXISTS `users`;;
CREATE TABLE `users` (
  `id` int(9) NOT NULL auto_increment,
  `username` varchar(25) NOT NULL,
  `passwd` varchar(40) NOT NULL,
  `tmppasswd` varchar(40) NOT NULL,
  `accesslevel` tinyint(1) NOT NULL default '0',
  `phone` varchar(25) NOT NULL,
  `email` varchar(50) NOT NULL,
  `loginattempts` tinyint(1) NOT NULL,
  `passwdexpire` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
";

$results = multiple_query($sql);

$tok = strtok($results, "<br />");

if($tok){ // There were erors.
    ?>
  <html>
  <head>
    <title>Error!</title>
  </head>
  <body>
  <h1>An error has occured.</h1>
  <p>Below you will find the mysql erros that prevented this application from installing properly.</p>
  <?php
  echo $results;
  ?>
  </body>
  </html>
  <?php
}
else{ // Everything went well.
  $notice = "This application has been successfully installed. Please delete install.php from your web server.";
  header("Location: index.php?notice=$notice");
}

function multiple_query($sql)
   {
   $tok = strtok($sql, ";;");
   while ($tok)
       {
       mysql_query($tok);
	   $results = mysql_error()."<br />$results<br /><br />";
       $tok = strtok(";;");
       }
   return $results;
   }
   
?>