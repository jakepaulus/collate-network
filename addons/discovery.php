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
$command = "ping -c 3 -n -A";
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

// Create array containing all unreserved IPs in all reserved subnets excluding ACL'd IP space

// loop whole operation over each subnet  
$sql = "SELECT id, start_ip, end_ip FROM subnets";
$results = mysql_query($sql);
while(list($subnet_id,$long_subnet_start_ip,$long_subnet_end_ip) = mysql_fetch_row($results)){
  $first_usable = $long_subnet_start_ip;
  $last_usable = $long_subnet_end_ip - '1';
  $subnet = range($first_usable, $last_usable);
  
  // exclude ACL'd IPs from this array
  $sql = "SELECT start_ip, end_ip FROM acl where apply = '$subnet_id'";
  $results = mysql_query($sql);
  while(list($start_ip, $end_ip) = mysql_fetch_row($results)){
    $acl = range($start_ip, $end_ip);
    $subnet = array_diff($subnet, $acl);
  }
  
  // exclude already reserved static IPs from this array
  $sql = "SELECT ip FROM statics WHERE subnet_id='$subnet_id'";
  $results = mysql_query($sql);
  if(mysql_num_rows($results) > '0'){
    $statics = array();
    while($static_ip = mysql_fetch_row($results)){
      array_push($statics, $static_ip['0']); 
    }
    $subnet = array_diff($subnet, $statics);  
  }
  $subnet = array_reverse($subnet);
  $dotzeroaddress = array_pop($subnet);
  
  
  $pingedhosts = '0';
  $newhosts = '0';
  
  while(!empty($subnet)){
    $ip = long2ip(array_pop($subnet));
    
    $output = &system("$command $ip > /dev/null", $return);
    $pingedhosts++;  
    
    if($return == '0'){ // Host responded
      $long_ip_addr = ip2long($ip);
      
      $sql = "INSERT INTO statics (ip, name, contact, note, subnet_id, modified_by, modified_at) 
  		 VALUES('$long_ip_addr', 'discovered-host', 'Network Admin', 'Added by discovery addon', '$subnet_id', 'system', now())";
    	mysql_query($sql);
    	$newhosts++;
      if($verbose == 'on'){ echo '!'; }
    }
    else{ // No host found
      if($verbose == 'on'){ echo '.'; }
    }
  }
}
if($verbose == 'on'){
  echo "\r\n".
       "$pingedhosts IP addresses were scanned. $newhosts new host(s) were discovered.\r\n".
	     "\r\n";
}

$sql = "REPLACE INTO settings (name, value) VALUES ('last_discovery_at', NOW())";
mysql_query($sql);

exit();
?>