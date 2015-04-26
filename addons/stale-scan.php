#!/usr/bin/php 

<?php
#--------------------------------------------------------------------------------
# stale-scan.php written by Jake Paulus for Collate:Network
# http://collate.info/ 
# 
# Please refer to the documentation for this script linked on the page above
#
#
# Here is how we scan. Feel free to modify the options to adjust performance...
# BUT BE CAREFUL
$command = "ping -c 3 -n -A -w 2";
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
$dbo = getdbo();

$pingedhosts = '0';
$goodhosts = '0';

$sql = "SELECT ip FROM statics WHERE failed_scans != '-1'";
$result = $dbo -> query($sql);

if($result -> rowCount() < '1'){ exit("\r\nError:\r\nThere are no IPs to check\r\n"); }

while(list($long_ip) = $result -> fetch(PDO::FETCH_NUM)){
  $ip = long2ip($long_ip);
  
  $output = &system("$command $ip > /dev/null", $return);
  $pingedhosts++;  
  
  if($return == '0'){ // Host responded
    $sql = "UPDATE statics SET failed_scans='0' WHERE ip='$long_ip'";
    $goodhosts++;
    if($verbose == 'on'){ echo '!'; }
  }
  else{ // Dead host 
    $sql = "UPDATE statics SET failed_scans=failed_scans+1 WHERE ip='$long_ip'";
    if($verbose == 'on'){ echo '.'; }
  }
  $dbo -> query($sql);
}
if($verbose == 'on'){
  echo "\r\n".
       "Scanned $pingedhosts hosts and was able to reach $goodhosts.\r\n".
	   "\r\n";
}

$sql = "REPLACE INTO settings (name, value) VALUES ('last_stale_scan_at', NOW())";
$dbo -> query($sql);

exit();
?>