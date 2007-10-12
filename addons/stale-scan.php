#!/usr/bin/php 

<?php
#--------------------------------------------------------------------------------
# stale-scan.php written by Jake Paulus for Collate:Network
# http://collate.info/ 
# 
# Please refer to the documentation for this script in the docs directory or
# at the following URL:
#
# http://code.google.com/p/collate-network/w/list
#--------------------------------------------------------------------------------

// We don't want this script to be run from a browser by accident
if(!empty($_SERVER['REMOTE_ADDR'])){ exit(); }

$_ARG = array();
foreach ($argv as $arg) {
  if (ereg('--([^=]+)=(.*)',$arg,$reg)) {
     $_ARG[$reg[1]] = $reg[2];
  }
  elseif(ereg('-([a-zA-Z0-9])',$arg,$reg)) {
    $_ARG[$reg[1]] = true;
  }
}

if((!empty($_ARG['v']) && $_ARG['v'] == true) || (!empty($_ARG['verbose']) && $_ARG['verbose'] == true)){
  $verbose = 'on';
}
else{
  $verbose = 'off';
}

require_once('../include/db_connect.php');

$pingedhosts = '0';
$updatedhosts = '0';

$sql = "SELECT ip FROM statics WHERE last_checked_at != '1111-11-11 11:11:11'";
$result = mysql_query($sql);

if(mysql_num_rows($result) < '1'){ exit("\r\nError:\r\nThere are no IPs to check\r\n"); }

while(list($long_ip) = mysql_fetch_row($result)){
  $ip = long2ip($long_ip);
  
  $output = &system("ping -c 4 -n -A $ip > /dev/null", $return);
  $pingedhosts++;  
  
  if($return == '0'){ // Host responded
    $sql = "UPDATE statics SET last_checked_at=NOW() WHERE ip='$long_ip'";
	mysql_query($sql);
	$updatedhosts++;
	if($verbose == 'on'){ echo '!'; }
  }
  else{
    if($verbose == 'on'){ echo '.'; }
  }
}
if($verbose == 'on'){
  echo "\r\n".
       "Scanned $pingedhosts hosts and was able to reach $updatedhosts.\r\n".
	   "\r\n";
}
exit();
?>
