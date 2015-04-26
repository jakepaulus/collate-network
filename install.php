<?php

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){
	case "install";
	install();
	break;
	
	default:
	health_check();
	break;
}

function health_check(){
  # For now, we will be supporting only English for installation
  # we still need to include language files for header.php and nav.php to look right.
  require_once('./languages/en.php');
  $COLLATE["languages"]["selected"] = $languages['en'];
  require_once('./include/header.php');
  
  echo "<h1>Install/Upgrade Collate:Network</h1><br>";
  
  # We need php >= 5.1 due to inet_pton()
  # Cannot continue install without this
  if(phpversion() < 5.1){	
    echo "<p class=\"installfail\">You need PHP version 5.1 or greater to use Collate:Network</p>";
    echo "</div></div>";
    require_once('./include/nav.php');
    echo "</div>";
    exit();
  }
  else{
  	echo "<p class=\"tip\">PHP version check passed</p>";
  }
  
  # We need LDAP support if the user wants it
  if(function_exists('ldap_connect')){
  	echo "<p class=\"tip\">LDAP support detected</p>";
  }
  else{
  	# Warn the user if they don't have it and give help on how to get it
  	echo "<p class=\"installwarn\">LDAP support NOT detected. You can continue, but you will not be able to use LDAP integration.</p>";
  }
  
  # We need mbstring support to detect encoding of uploaded files
  if(function_exists('mb_detect_encoding')){
  	echo "<p class=\"tip\">PHP mbstring support detected</p>";
  }
  else{
  	# Warn the user if they don't have it and give help on how to get it
  	echo "<p class=\"installwarn\">PHP mbstring support is needed to support bulk uploads. You can continue, but this feature will not work.</p>";
  }
  
  # Does the server's php.ini permit uplaods?
  if(ini_get('file_uploads')){
  	echo "<p class=\"tip\">&quot;file_uploads&quot; is on in php.ini</p>";
  }
  else{
  	echo "<p class=\"installwarn\">&quot;file_uploads&quot; is off in php.ini You can continue, but the bulk import feature will not work.</p>";
  }
  
  
  # Can we connect to the database?
  require_once('./include/db_connect.php');
  $dbo = getdbo();
  if(!is_object($dbo)){
    echo "<p class=\"installfail\">$dbo</p>";
	echo "</div></div>";
    require_once('./include/nav.php');
    echo "</div>";
    exit();
  }
  else{
    echo "<p class=\"tip\">Database connection looks good</p>";	  
  }
  
  # php version check and db_connect.php both call exit() and are the only two strict failures we 
  # look for. If they got this far, they can keep going!
  
  echo "<br><form action=\"install.php?op=install\" method=\"POST\"><input type=\"submit\" value=\" Install \"></form>";
  
  echo "</div></div>";
  require_once('./include/nav.php');
  echo "</div>";
  exit();
}

function install(){
  require_once('./include/db_connect.php');
  $dbo = getdbo();
  
  $install = 
  "
  CREATE TABLE `acl` (
    `id` int(9) UNSIGNED NOT NULL auto_increment,
    `name` varchar(25) NOT NULL,
    `start_ip` int(10) NOT NULL,
    `end_ip` int(10) NOT NULL,
    `subnet_id` int(9) UNSIGNED NOT NULL,
    PRIMARY KEY  (`id`)
  ) ENGINE=INNODB DEFAULT CHARSET=latin1;
  
  CREATE TABLE `api-keys` (
   `apikey` varchar(21) NOT NULL,
   `description` varchar(60) NOT NULL,
   `active` tinyint(1) NOT NULL DEFAULT '0',
   PRIMARY KEY (`apikey`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
  
  CREATE TABLE `blocks` (
    `id` int(9) UNSIGNED NOT NULL auto_increment,
    `name` varchar(25) NOT NULL,
    `start_ip` int(10) NOT NULL,
    `end_ip` int(10) NOT NULL,
    `note` varchar(255) NOT NULL,
    `modified_by` varchar(100) NOT NULL,
    `modified_at` datetime NOT NULL,
    `parent_id` int(9) UNSIGNED,
    `type` ENUM('container','ipv4','ipv6') DEFAULT 'ipv4',
    PRIMARY KEY  (`id`),
    UNIQUE KEY `name` (`name`)
  ) ENGINE=INNODB DEFAULT CHARSET=latin1;
  
  CREATE TABLE `ldap-servers` (
    `id` tinyint(4) UNSIGNED NOT NULL auto_increment,
    `domain` varchar(128) NOT NULL,
    `server` varchar(255) NOT NULL,
    PRIMARY KEY  (`id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
  
  
  CREATE TABLE `logs` (
    `id` int(11) UNSIGNED NOT NULL auto_increment,
    `occuredat` datetime NOT NULL,
    `username` varchar(100) NOT NULL,
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
  INSERT INTO `settings` VALUES ('version', '2.3');
  INSERT INTO `settings` VALUES ('perms', '6');
  INSERT INTO `settings` VALUES ('guidance', '');
  INSERT INTO `settings` VALUES ('dns', '');
  INSERT INTO `settings` VALUES ('auth_type', 'db');
  INSERT INTO `settings` VALUES ('domain', '');
  INSERT INTO `settings` VALUES ('language', 'en');
  
  CREATE TABLE `statics` (
    `id` int(10) UNSIGNED NOT NULL auto_increment,
    `ip` int(10) NOT NULL,
    `name` varchar(50) NOT NULL,
    `contact` varchar(25) NOT NULL,
    `note` varchar(255) NOT NULL,
    `subnet_id` int(9) UNSIGNED NOT NULL,
    `modified_by` varchar(100) NOT NULL,
    `modified_at` datetime NOT NULL,
    `failed_scans` INT( 16 ) NOT NULL DEFAULT  '0',
    PRIMARY KEY  (`id`),
    UNIQUE KEY `ip` (`ip`)
  ) ENGINE=INNODB DEFAULT CHARSET=latin1;
  
  CREATE TABLE `subnets` (
    `id` int(9) UNSIGNED NOT NULL auto_increment,
    `name` varchar(60) NOT NULL,
    `start_ip` int(10) NOT NULL,
    `end_ip` int(10) NOT NULL,
    `mask` int(10) NOT NULL,
    `note` varchar(255) NOT NULL,
    `block_id` int(9) UNSIGNED NOT NULL,
    `modified_by` varchar(100) NOT NULL,
    `modified_at` datetime NOT NULL,
    `guidance` longtext NOT NULL,
    `stalescan_enabled` BOOL NOT NULL default true,
    PRIMARY KEY  (`id`)
  ) ENGINE=INNODB DEFAULT CHARSET=latin1;
  
  CREATE TABLE `users` (
    `id` int(9) UNSIGNED NOT NULL auto_increment,
    `username` varchar(100) NOT NULL,
    `passwd` varchar(40) NOT NULL,
    `tmppasswd` varchar(40) NOT NULL,
    `accesslevel` tinyint(1) NOT NULL default '0',
    `phone` varchar(25) NOT NULL,
    `email` varchar(50) NOT NULL,
    `loginattempts` tinyint(1) NOT NULL,
    `passwdexpire` datetime NOT NULL,
    `ldapexempt` tinyint(1) NOT NULL default '0',
    `last_login_at` datetime NOT NULL,
    `language` VARCHAR(2) NOT NULL default 'en',
    PRIMARY KEY  (`id`),
    UNIQUE KEY `username` (`username`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
  
  INSERT INTO logs (occuredat, username, level, message) VALUES (NOW(), 'system', 'high', 'Collate:Network Version 2.3 Installed!')
  ";
  
  $upgrade_from_one_dot_zero = 
  "
  ALTER TABLE subnets ADD guidance LONGTEXT NOT NULL;
  ALTER TABLE blocks CHANGE id id INT( 9 ) NOT NULL AUTO_INCREMENT;
  ALTER TABLE logs CHANGE id id INT( 11 ) NOT NULL AUTO_INCREMENT;
  ALTER TABLE statics CHANGE id id INT( 10 ) NOT NULL AUTO_INCREMENT, CHANGE subnet_id subnet_id INT( 9 ) NOT NULL;
  ALTER TABLE subnets CHANGE id id INT( 9 ) NOT NULL AUTO_INCREMENT , CHANGE block_id block_id INT( 9 ) NOT NULL;
  INSERT INTO settings (name, value) VALUES ('guidance', '');
  ";
  
  $upgrade_from_one_dot_two = 
  "
  ALTER TABLE statics CHANGE name name varchar( 50 ) NO NULL;
  
  ";
  
  $upgrade_from_one_dot_four = 
  "
  CREATE TABLE `ldap-servers` (
    `id` tinyint(4) NOT NULL auto_increment,
    `domain` varchar(128) NOT NULL,
    `server` varchar(255) NOT NULL,
    PRIMARY KEY  (`id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
  INSERT INTO `settings` VALUES ('dns', '');
  INSERT INTO `settings` VALUES ('auth_type', 'db');
  INSERT INTO `settings` VALUES ('domain', '');
  ALTER TABLE statics ADD last_checked_at datetime NOT NULL;
  ALTER TABLE users ADD ldapexempt TINYINT( 1 ) NOT NULL DEFAULT '0';
  UPDATE settings SET value='1.5' WHERE name='version';
  ";
  
  $upgrade_from_one_dot_five =
  "
  ALTER TABLE statics CHANGE last_checked_at failed_scans INT( 16 ) NOT NULL DEFAULT '0';
  UPDATE statics SET failed_scans='0';
  ALTER TABLE users CHANGE username username varchar ( 50 ) NOT NULL;
  UPDATE settings SET value='1.6' WHERE name='version'
  ";
  
  function upgrade_from_one_dot_six () {
  	# Find autoincrement values for each table that uses them
  	$sql = "SELECT LAST_INSERT_ID() from acl";
  	$result = $dbo->exec($sql);
  	$acl_autoincrement = $result->fetchColumn();
  	
  	$sql = "SELECT LAST_INSERT_ID() from blocks";
  	$result = $dbo->exec($sql);
  	$blocks_autoincrement = $result->fetchColumn();
  	
  	$sql = "SELECT LAST_INSERT_ID() from `ldap-servers`";
  	$result = $dbo->exec($sql);
  	$ldap_autoincrement = $result->fetchColumn();
  	
  	$sql = "SELECT LAST_INSERT_ID() from logs";
  	$result = $dbo->exec($sql);
  	$logs_autoincrement = $result->fetchColumn();
  	
  	$sql = "SELECT LAST_INSERT_ID() from statics";
  	$result = $dbo->exec($sql);
  	$statics_autoincrement = $result->fetchColumn();
  	
  	$sql = "SELECT LAST_INSERT_ID() from subnets";
  	$result = $dbo->exec($sql);
  	$subnets_autoincrement = $result->fetchColumn();
  	
  	$sql = "SELECT LAST_INSERT_ID() from users";
  	$result = $dbo->exec($sql);
  	$users_autoincrement = $result->fetchColumn();
  
  	# copy old tables to temporary tables using unsigned INTs, then drop old ones, then rename new to old
  	$data_shuffle =
  		"
  		CREATE TABLE `tmp_acl` (
  		  `id` int(9) UNSIGNED NOT NULL auto_increment,
  		  `name` varchar(25) NOT NULL,
  		  `start_ip` int(10) NOT NULL,
  		  `end_ip` int(10) NOT NULL,
  		  `subnet_id` int(9) UNSIGNED NOT NULL,
  		  PRIMARY KEY  (`id`)
  		) ENGINE=MyISAM AUTO_INCREMENT=$acl_autoincrement DEFAULT CHARSET=latin1;
  		INSERT INTO `tmp_acl` SELECT CAST((id & 0xFFFFFFFF) AS UNSIGNED), name, start_ip, end_ip, apply FROM acl;
  
  		CREATE TABLE `tmp_blocks` (
  		  `id` int(9) UNSIGNED NOT NULL auto_increment,
  		  `name` varchar(25) NOT NULL,
  		  `start_ip` int(10) NOT NULL,
  		  `end_ip` int(10) NOT NULL,
  		  `note` varchar(255) NOT NULL,
  		  `modified_by` varchar(25) NOT NULL,
  		  `modified_at` datetime NOT NULL,
  		  PRIMARY KEY  (`id`),
  		  UNIQUE KEY `name` (`name`)
  		) ENGINE=MyISAM AUTO_INCREMENT=$blocks_autoincrement DEFAULT CHARSET=latin1;		
  		INSERT INTO `tmp_blocks` SELECT CAST((id & 0xFFFFFFFF) AS UNSIGNED), name, start_ip, end_ip, note, modified_by, modified_at FROM blocks;
  
  		CREATE TABLE `tmp_ldap-servers` (
  		  `id` tinyint(4) UNSIGNED NOT NULL auto_increment,
  		  `domain` varchar(128) NOT NULL,
  		  `server` varchar(255) NOT NULL,
  		  PRIMARY KEY  (`id`)
  		) ENGINE=MyISAM AUTO_INCREMENT=$ldap_autoincrement DEFAULT CHARSET=latin1;
  		INSERT INTO `tmp_ldap-servers` SELECT CAST((id & 0xFFFFFFFF) AS UNSIGNED), domain, server FROM `ldap-servers`;
  
  		CREATE TABLE `tmp_logs` (
  		  `id` int(11) UNSIGNED NOT NULL auto_increment,
  		  `occuredat` datetime NOT NULL,
  		  `username` varchar(30) NOT NULL,
  		  `ipaddress` varchar(15) NOT NULL,
  		  `level` varchar(6) NOT NULL,
  		  `message` varchar(255) NOT NULL,
  		  PRIMARY KEY  (`id`),
  		  KEY `username` (`username`)
  		) ENGINE=MyISAM AUTO_INCREMENT=$logs_autoincrement DEFAULT CHARSET=latin1;
  		INSERT INTO `tmp_logs` SELECT CAST((id & 0xFFFFFFFF) AS UNSIGNED), occuredat, username, ipaddress, level, message FROM logs;
  
  		CREATE TABLE `tmp_statics` (
  		  `id` int(10) UNSIGNED NOT NULL auto_increment,
  		  `ip` int(10) NOT NULL,
  		  `name` varchar(50) NOT NULL,
  		  `contact` varchar(25) NOT NULL,
  		  `note` varchar(255) NOT NULL,
  		  `subnet_id` int(9) UNSIGNED NOT NULL,
  		  `modified_by` varchar(25) NOT NULL,
  		  `modified_at` datetime NOT NULL,
  		  `failed_scans` INT( 16 ) NOT NULL DEFAULT  '0',
  		  PRIMARY KEY  (`id`),
  		  UNIQUE KEY `ip` (`ip`)
  		) ENGINE=MyISAM AUTO_INCREMENT=$statics_autoincrement DEFAULT CHARSET=latin1;
  		INSERT INTO `tmp_statics` SELECT CAST((id & 0xFFFFFFFF) AS UNSIGNED), ip, name, contact, note, CAST((subnet_id & 0xFFFFFFFF) AS UNSIGNED), modified_by, modified_at, failed_scans FROM statics;
  
  		CREATE TABLE `tmp_subnets` (
  		  `id` int(9) UNSIGNED NOT NULL auto_increment,
  		  `name` varchar(25) NOT NULL,
  		  `start_ip` int(10) NOT NULL,
  		  `end_ip` int(10) NOT NULL,
  		  `mask` int(10) NOT NULL,
  		  `note` varchar(255) NOT NULL,
  		  `block_id` tinyint(9) UNSIGNED NOT NULL,
  		  `modified_by` varchar(25) NOT NULL,
  		  `modified_at` datetime NOT NULL,
  		  `guidance` longtext NOT NULL,
  		  PRIMARY KEY  (`id`),
  		  UNIQUE KEY `name` (`name`)
  		) ENGINE=MyISAM AUTO_INCREMENT=$subnets_autoincrement DEFAULT CHARSET=latin1;
  		INSERT INTO `tmp_subnets` SELECT CAST((id & 0xFFFFFFFF) AS UNSIGNED), name, start_ip, end_ip, mask, note, CAST((block_id & 0xFFFFFFFF) AS UNSIGNED), modified_by, modified_at, guidance FROM subnets;
  
  		CREATE TABLE `tmp_users` (
  		  `id` int(9) UNSIGNED NOT NULL auto_increment,
  		  `username` varchar(100) NOT NULL,
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
  		) ENGINE=MyISAM AUTO_INCREMENT=$users_autoincrement DEFAULT CHARSET=latin1;
  		INSERT INTO `tmp_users` SELECT CAST((id & 0xFFFFFFFF) AS UNSIGNED), username, passwd, tmppasswd, accesslevel, phone, email, loginattempts, passwdexpire, ldapexempt FROM users;
  		
  		DROP TABLE acl;
  		DROP TABLE blocks;
  		DROP TABLE `ldap-servers`;
  		DROP TABLE logs;
  		DROP TABLE statics;
  		DROP TABLE subnets;
  		DROP TABLE users;
  		
  		RENAME TABLE tmp_acl TO acl;
  		RENAME TABLE tmp_blocks TO blocks;
  		RENAME TABLE `tmp_ldap-servers` TO `ldap-servers`;
  		RENAME TABLE tmp_logs TO logs;
  		RENAME TABLE tmp_statics TO statics;
  		RENAME TABLE tmp_subnets TO subnets;
  		RENAME TABLE tmp_users TO users;
  		DELETE FROM statics WHERE subnet_id != ALL (SELECT id from subnets);
  		UPDATE settings SET value='1.7' WHERE name='version'
  		";
  	$results = multiple_query("$data_shuffle");
  
  	return $results;
  }
  
  $upgrade_from_one_dot_seven = 
  "
  ALTER TABLE subnets CHANGE block_id block_id INT( 9 ) UNSIGNED NOT NULL;
  UPDATE settings SET value='1.7.1' WHERE name='version';
  ";
  
  $upgrade_from_one_dot_seven_dot_one = 
  "
  UPDATE settings SET value='1.7.2' WHERE name='version';
  ";
  
  $upgrade_from_one_dot_seven_dot_two = 
  "
  ALTER TABLE subnets MODIFY name varchar(60);
  ALTER TABLE users ADD last_login_at datetime NOT NULL;
  ALTER TABLE `subnets` DROP index `name`;
  ALTER TABLE `blocks` MODIFY modified_by varchar(100);
  ALTER TABLE `logs` MODIFY username varchar(100);
  ALTER TABLE `statics` MODIFY modified_by varchar(100);
  ALTER TABLE `subnets` MODIFY modified_by varchar(100);
  UPDATE settings SET value='2.0' WHERE name='version';
  ";
  
  $upgrade_from_two_dot_zero =
  "
  INSERT INTO `settings` VALUES ('language', 'en');
  ALTER TABLE  `users` ADD  `language` VARCHAR(2) NOT NULL default 'en';
  UPDATE settings SET value='2.1' WHERE name='version';
  CREATE TABLE `api-keys` (
   `apikey` varchar(21) NOT NULL,
   `description` varchar(60) NOT NULL,
   `active` tinyint(1) NOT NULL DEFAULT '0',
   PRIMARY KEY (`apikey`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
  ALTER TABLE `blocks` ENGINE = INNODB;
  ALTER TABLE `subnets` ENGINE = INNODB;
  ALTER TABLE `acl` ENGINE = INNODB;
  ALTER TABLE `statics` ENGINE = INNODB
  ";
  
  $upgrade_from_two_dot_one =
  "
  ALTER TABLE blocks ADD `parent_id` int(9) UNSIGNED;
  ALTER TABLE blocks ADD `type` ENUM('container','ipv4','ipv6') DEFAULT 'ipv4';
  ALTER TABLE subnets ADD stalescan_enabled BOOL NOT NULL default true; 
  UPDATE settings SET value='2.2' WHERE name='version'
  ";
  
  $upgrade_from_two_dot_two =
  "
  UPDATE settings SET value='2.2.1' WHERE name='version';
  INSERT INTO logs (occuredat, username, level, message) VALUES (NOW(), 'system', 'high', 'Collate:Network upgraded to version 2.2.1!')
  ";
  
  $upgrade_from_two_dot_two_dot_one =
  "
  UPDATE settings SET value='2.3' WHERE name='version';
  INSERT INTO logs (occuredat, username, level, message) VALUES (NOW(), 'system', 'high', 'Collate:Network upgraded to version 2.3!')
  ";
  
  $sql = "select value from settings where name='version'";
  $result = $dbo->exec($sql);
  
  if($result != FALSE) { // See what version we're on
    $version = $result->fetchColumn();
    if($version == '1.0'){
      $results = multiple_query("$upgrade_from_one_dot_zero");
      $results .= multiple_query("$upgrade_from_one_dot_two");
      $results .= multiple_query("$upgrade_from_one_dot_four");
      $results .= multiple_query("$upgrade_from_one_dot_five");
  	$results .= upgrade_from_one_dot_six();
  	$results .= multiple_query("$upgrade_from_one_dot_seven");
  	$results .= multiple_query($upgrade_from_one_dot_seven_dot_one);
  	$results .= multiple_query($upgrade_from_one_dot_seven_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_zero);
  	$results .= multiple_query($upgrade_from_two_dot_one);
  	$results .= multiple_query($upgrade_from_two_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_two_dot_one);
    }
    elseif($version == '1.2'){
      $results = multiple_query("$upgrade_from_one_dot_two");
      $results .= multiple_query("$upgrade_from_one_dot_four");
      $results .= multiple_query("$upgrade_from_one_dot_five");
  	$results .= upgrade_from_one_dot_six();
  	$results .= multiple_query("$upgrade_from_one_dot_seven");
  	$results .= multiple_query($upgrade_from_one_dot_seven_dot_one);
  	$results .= multiple_query($upgrade_from_one_dot_seven_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_zero);
  	$results .= multiple_query($upgrade_from_two_dot_one);
  	$results .= multiple_query($upgrade_from_two_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_two_dot_one);
    }
    elseif($version == '1.3' || $version == '1.4'){
      $results .= multiple_query("$upgrade_from_one_dot_four");
      $results .= multiple_query("$upgrade_from_one_dot_five");
  	$results .= upgrade_from_one_dot_six();
  	$results .= multiple_query("$upgrade_from_one_dot_seven");
  	$results .= multiple_query($upgrade_from_one_dot_seven_dot_one);
  	$results .= multiple_query($upgrade_from_one_dot_seven_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_zero);
  	$results .= multiple_query($upgrade_from_two_dot_one);
  	$results .= multiple_query($upgrade_from_two_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_two_dot_one);
    }
    elseif($version == '1.5'){
      $results .= multiple_query("$upgrade_from_one_dot_five");
  	$results .= upgrade_from_one_dot_six();
  	$results .= multiple_query("$upgrade_from_one_dot_seven");
  	$results .= multiple_query($upgrade_from_one_dot_seven_dot_one);
  	$results .= multiple_query($upgrade_from_one_dot_seven_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_zero);
  	$results .= multiple_query($upgrade_from_two_dot_one);
  	$results .= multiple_query($upgrade_from_two_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_two_dot_one);
    }
    elseif($version == '1.6'){
  	$results = upgrade_from_one_dot_six();
  	$results .= multiple_query("$upgrade_from_one_dot_seven");
  	$results .= multiple_query($upgrade_from_one_dot_seven_dot_one);
  	$results .= multiple_query($upgrade_from_one_dot_seven_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_zero);
  	$results .= multiple_query($upgrade_from_two_dot_one);
  	$results .= multiple_query($upgrade_from_two_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_two_dot_one);
    }
    elseif($version == '1.7'){
      $results = multiple_query("$upgrade_from_one_dot_seven");
  	$results .= multiple_query($upgrade_from_one_dot_seven_dot_one);
  	$results .= multiple_query($upgrade_from_one_dot_seven_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_zero);
  	$results .= multiple_query($upgrade_from_two_dot_one);
  	$results .= multiple_query($upgrade_from_two_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_two_dot_one);
    }
    elseif($version == '1.7.1'){
      $results = multiple_query($upgrade_from_one_dot_seven_dot_one);
  	$results .= multiple_query($upgrade_from_one_dot_seven_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_zero);
  	$results .= multiple_query($upgrade_from_two_dot_one);
  	$results .= multiple_query($upgrade_from_two_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_two_dot_one);
    }
    elseif($version == '1.7.2'){
  	$results = multiple_query($upgrade_from_one_dot_seven_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_zero);
  	$results .= multiple_query($upgrade_from_two_dot_one);
  	$results .= multiple_query($upgrade_from_two_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_two_dot_one);
    }
    elseif($version == '2.0'){
      $results = multiple_query($upgrade_from_two_dot_zero);
  	$results .= multiple_query($upgrade_from_two_dot_one);
  	$results .= multiple_query($upgrade_from_two_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_two_dot_one);
    }
    elseif($version == '2.1'){
  	$results = multiple_query($upgrade_from_two_dot_one);
  	$results .= multiple_query($upgrade_from_two_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_two_dot_one);
    }
    elseif($version == '2.2'){
  	$results = multiple_query($upgrade_from_two_dot_two);
  	$results .= multiple_query($upgrade_from_two_dot_two_dot_one);
    }
    elseif($version == '2.2.1'){
      $results = multiple_query($upgrade_from_two_dot_two_dot_one);
    }
    elseif($version == '2.3'){
      // We're at the current version!
      header("Location: index.php?notice=uptodate-notice");
      exit();
    }
    $notice = "upgradesuccess-notice";
  }
  else{ // We're installing
    $results = multiple_query($install);
    $notice = "installsuccess-notice";
  }
  
  $tok = strtok($results, "<br />");
  
  if($tok){ // There were erors.
  # For now, we will be supporting only English for installation
  require_once('./languages/en.php');
  $COLLATE["languages"]["selected"] = $languages['en'];
  require_once('./include/header.php');
  
    echo "<h1>An error has occured.</h1><br>
          <p>Below you will find the mysql errors that prevented this application from installing properly.</p>";
    echo "$results";
    echo "</div></div>";
    require_once('./include/nav.php');
    echo "</div>";
    exit();
  }
  else{ // Everything went well.
    header("Location: index.php?notice=$notice");
  }
}

function multiple_query($sql){
  $dbo = getdbo();
  $tok = strtok($sql, ";");
  $results = "";

  while ($tok){
    $result = $dbo->exec($tok);
	if(!$result){ 
	  $dbo->errorInfo();
	  $results = "$results<br>".$result[2];
	}
    $tok = strtok(";");
  }
  return $results;
}
?>