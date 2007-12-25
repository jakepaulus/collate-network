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
#
#
# Here is how we scan. Feel free to modify the options to adjust performance...
# BUT BE CAREFUL
$command = "ping -c 4 -n -A";
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

if(isset($_ARG['h']) || isset($_ARG['help'])){
  echo "\r\n".
       "This script takes two options: \r\n".
	   " -h (or --help): Outputs this message \r\n".
	   " -v (or --verbose): Outputs detail about the progress of the script \r\n".
	   "\r\n".
	   "Please read the documenation for this script at http://code.google.com/p/collate-network/w/list before running \r\n".
       "this on a schedule. \r\n".
	   "\r\n";
  exit();
}

if(isset($_ARG['v']) || isset($_ARG['verbose'])){
  $verbose = 'on';
}
else{
  $verbose = 'off';
}

require_once('../include/db_connect.php');

$pingedhosts = '0';
$updatedhosts = '0';

$sql = "SELECT ip, last_checked_at FROM statics WHERE last_checked_at != '1111-11-11 11:11:11'";
$result = mysql_query($sql);

if(mysql_num_rows($result) < '1'){ exit("\r\nError:\r\nThere are no IPs to check\r\n"); }

while(list($long_ip, $last_checked_at) = mysql_fetch_row($result)){
  $ip = long2ip($long_ip);
  
  $output = &system("$command $ip > /dev/null", $return);
  $pingedhosts++;  
  
  if($return == '0'){ // Host responded
    $sql = "UPDATE statics SET last_checked_at=NOW() WHERE ip='$long_ip'";
	mysql_query($sql);
	$updatedhosts++;
    if($verbose == 'on'){ echo '!'; }
  }
  else{ // Dead host
    if($verbose == 'on'){ echo '.'; }
  }
}
if($verbose == 'on'){
  echo "\r\n".
       "Scanned $pingedhosts hosts and was able to reach $updatedhosts.\r\n".
	   "\r\n";
}

$sql = "REPLACE INTO settings (name, value) VALUES ('last_stale_scan_at', NOW())";
mysql_query($sql);

exit();
?>
