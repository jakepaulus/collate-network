<?php
$install = 
"
CREATE TABLE `acl` (
  `id` int(9) NOT NULL auto_increment,
  `name` varchar(25) NOT NULL,
  `start_ip` int(10) NOT NULL,
  `end_ip` int(10) NOT NULL,
  `apply` varchar(25) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `blocks` (
  `id` int(9) NOT NULL auto_increment,
  `name` varchar(25) NOT NULL,
  `start_ip` int(10) NOT NULL,
  `end_ip` int(10) NOT NULL,
  `note` varchar(255) NOT NULL,
  `modified_by` varchar(25) NOT NULL,
  `modified_at` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `ldap-servers` (
  `id` tinyint(4) NOT NULL auto_increment,
  `domain` varchar(128) NOT NULL,
  `server` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `logs` (
  `id` int(11) NOT NULL auto_increment,
  `occuredat` datetime NOT NULL,
  `username` varchar(30) NOT NULL,
  `ipaddress` varchar(15) NOT NULL,
  `level` varchar(6) NOT NULL,
  `message` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `settings` (
  `name` varchar(50) NOT NULL,
  `value` longtext NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `settings` VALUES ('passwdlength', '5');
INSERT INTO `settings` VALUES ('accountexpire', '60');
INSERT INTO `settings` VALUES ('loginattempts', '4');
INSERT INTO `settings` VALUES ('version', '1.4+ (dev)');
INSERT INTO `settings` VALUES ('perms', '6');
INSERT INTO `settings` VALUES ('guidance', '');
INSERT INTO `settings` VALUES ('dns', '');
INSERT INTO `settings` VALUES ('auth_type', 'db');
INSERT INTO `settings` VALUES ('domain', '');

CREATE TABLE `statics` (
  `id` int(10) NOT NULL auto_increment,
  `ip` int(10) NOT NULL,
  `name` varchar(50) NOT NULL,
  `contact` varchar(25) NOT NULL,
  `note` varchar(255) NOT NULL,
  `subnet_id` int(9) NOT NULL,
  `modified_by` varchar(25) NOT NULL,
  `modified_at` datetime NOT NULL,
  `last_checked_at` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `subnets` (
  `id` int(9) NOT NULL auto_increment,
  `name` varchar(25) NOT NULL,
  `start_ip` int(10) NOT NULL,
  `end_ip` int(10) NOT NULL,
  `mask` int(10) NOT NULL,
  `note` varchar(255) NOT NULL,
  `block_id` tinyint(9) NOT NULL,
  `modified_by` varchar(25) NOT NULL,
  `modified_at` datetime NOT NULL,
  `guidance` longtext NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

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
  `ldapexempt` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO logs (occuredat, username, level, message) VALUES (NOW(), 'system', 'high', 'Collate:Network Version 1.5 Installed!')
";

$upgrade_from_one_dot_zero = 
"
ALTER TABLE subnets ADD guidance LONGTEXT NOT NULL;
ALTER TABLE blocks CHANGE id id INT( 9 ) NOT NULL AUTO_INCREMENT;
ALTER TABLE logs CHANGE id id INT( 11 ) NOT NULL AUTO_INCREMENT;
ALTER TABLE statics CHANGE id id INT( 10 ) NOT NULL AUTO_INCREMENT, CHANGE subnet_id subnet_id INT( 9 ) NOT NULL;
ALTER TABLE subnets CHANGE id id INT( 9 ) NOT NULL AUTO_INCREMENT , CHANGE block_id block_id INT( 9 ) NOT NULL;
INSERT INTO settings (name, value) VALUES ('guidance', '');
UPDATE settings SET value = '1.2' WHERE name = 'version';
";

$upgrade_from_one_dot_two = 
"
ALTER TABLE statics CHANGE name name varchar( 50 ) NO NULL;
UPDATE settings SET value='1.3' WHERE name='version';
";

$upgrade_from_one_dot_three = 
"
UPDATE settings SET value='1.4' WHERE name='version';
";

$upgrade_from_one_dot_four = 
"
ALTER TABLE statics ADD last_checked_at datetime NOT NULL;
INSERT INTO settings VALUES ('dns', '');
INSERT INTO settings VALUES ('auth_type', 'db');
INSERT INTO settings VALUES ('domain', 'example.com');
UPDATE settings SET value='1.5' WHERE name='version';
ALTER TABLE users ADD ldapexempt TINYINT( 1 ) NOT NULL DEFAULT '0';
CREATE TABLE `ldap-servers` (
  id TINYINT( 4 ) NOT NULL AUTO_INCREMENT ,
  domain VARCHAR( 128 ) NOT NULL ,
  server VARCHAR( 255 ) NOT NULL ,
  PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
INSERT INTO logs (occuredat, username, level, message) VALUES (NOW(), 'system', 'high', 'Collate:Network upgraded to version 1.5!')
";


require_once('./include/db_connect.php');

$sql = "select value from settings where name='version'";
$result = mysql_query($sql);

if(mysql_num_rows($result) != '0') { // See what version we're on
  $version = mysql_result($result, 0, 0);
  if($version == '1.0'){
    $results = multiple_query("$upgrade_from_one_dot_zero");
	$results .= multiple_query("$upgrade_from_one_dot_two");
	$results .= multiple_query("$upgrade_from_one_dot_three");
	$results .= multiple_query("$upgrade_from_one_dot_four");
  }
  elseif($version == '1.2'){
    $results = multiple_query("$upgrade_from_one_dot_two");
	$results .= multiple_query("$upgrade_from_one_dot_three");
	$results .= multiple_query("$upgrade_from_one_dot_four");
  }
  elseif($version == '1.3'){
    $results = multiple_query("$upgrade_from_one_dot_three");
	$results .= multiple_query("$upgrade_from_one_dot_four");
  }
  elseif($version == '1.4'){
    $results = multiple_query("$upgrade_from_one_dot_four");
  }
  $notice = "This application has been successfully upgraded to version 1.5. Please delete install.php from your web server.";
}
else{ // We're installing
  $results = multiple_query($install);
  $notice = "This application has been successfully installed. Please delete install.php from your web server.";
}


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
  <?php echo $results; ?>
  </body>
  </html>
<?php
}
else{ // Everything went well.
  
  header("Location: index.php?notice=$notice");
}

function multiple_query($sql){
  $tok = strtok($sql, ";");
  while ($tok){
    mysql_query($tok);
	$results = mysql_error()."<br />$results<br /><br />";
    $tok = strtok(";");
  }
  return $results;
}
?>