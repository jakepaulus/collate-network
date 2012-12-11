<?php

# returns the input variable with illegal characters stripped from it
function clean($variable){ 

  $invalid = array();
  $invalid['0'] = "'";  // removes single quotes
  $invalid['1'] = '"';  // removes double quotes
  $invalid['2'] = '\\'; // removes backslash

  $variable = str_replace($invalid, '', $variable);
  $variable = strip_tags(trim($variable)); 
  return $variable;
}

// Netmask Validator // modified from the version in the comments on php.net/ip2long
function validate_netmask($ip) {
  if (!ip2decimal($ip)) {
    return false;
  }
  $binip=decbin(ip2decimal($ip));
  
  if(strlen($binip) != 32 && ip2decimal($ip) != 0) {
    return false;
  }
  elseif(preg_match("/01/", "$binip") || !preg_match("/0/", "$binip")) {
    return false;
  }
  else {
    return true;
  }
}

function validate_text($text,$fieldtype){
  # max length values
  #   * 25 for names (subnet, block, static, acl, api key description)
  #   * 80 for notes
  #   * 100 for usernames or contacts
  $length = '25';
  $canbeempty = false;
  switch($fieldtype) {
    case "blockname";
	$error = 'blocknamelength';
	break;
	
	case "subnetname";
	$error = 'subnetnamelength';
	break;
	
	case "aclname";
	$error = 'aclnamelength';
	break;
	
	case "staticname";
	$error = 'staticnamelength';
	break;
	
    case "note";
	$length = '80';
	$error = 'notelengtherror';
	$canbeempty = true;
	break;
	
	case "username";
	$length = '100';
	$error = 'usernamelengtherror';
	break;
	
	case "contact";
	$length = '100';
	$error = 'contactlengtherror';
	break;
	
	case "guidance";
	$length = '255';
	$error = 'guidancelengtherror';
	break;
	
	case "phone";
	$length = '25';
	$error = 'phonelengtherror';
	$canbeempty = true;
	break;
	
	case "email";
	$length = '50';
	$error = 'emaillengtherror';
	$canbeempty = true;
	break;
	
	case "domain";
	$length = '128';
	$error = 'domainlengtherror';
	break;
	
	case "dnshelper";
	$length = '128';
	$error = 'dnshelperlengtherror';
	break;
	
	case "apidescription";
	$length = '60';
	$error = 'apidescriptionlengtherror';
	break;
  }
  $return=array();
  $text = clean($text);
  if(($canbeempty === false && strlen($text) < '3' ) || strlen($text) > $length){
    $return['0'] = false;
	$return['error'] = $error;
	return $return;
  }
  $return['0'] = true;
  $return['1'] = $text;
  return $return;
}

function validate_ip_range($start_ip,$end_ip,$range_type,$subnet_id=null){
  $return = array();
  $long_start_ip = ip2decimal($start_ip);
  $long_end_ip = ip2decimal($end_ip);
  $sqltable = ($range_type == 'block') ? 'blocks' : 'acl';
  $rangeoverlapmessage=str_replace("%rangetype%", $range_type, $COLLATE['languages']['selected']['rangeoverlap-notice']);
  
  # are the inputs valid ipv4 addresses?
  if($long_start_ip === false || $long_end_ip === false){
    $return['0'] = false;
	$return['1'] = $COLLATE['languages']['selected']['invalidip'];
	return $return;
  }
  
  # range must have smaller start_ip than end_ip
  if($long_end_ip <= $long_start_ip){
    $return['0'] = false;
	$return['1'] = $COLLATE['languages']['selected']['invalidrange'];
	return $return;
  }
  
  $sql = "SELECT id FROM $sqltable WHERE (CAST(start_ip AS UNSIGNED) <= CAST('$long_start_ip' AS UNSIGNED) AND CAST(end_ip AS UNSIGNED) >= CAST('$long_start_ip' AS UNSIGNED)) OR 
         (CAST(start_ip AS UNSIGNED) <= CAST('$long_end_ip' AS UNSIGNED) AND CAST(end_ip AS UNSIGNED) >= CAST('$long_end_ip' AS UNSIGNED)) OR
         (CAST(start_ip AS UNSIGNED) >= CAST('$long_start_ip' AS UNSIGNED) AND CAST(end_ip AS UNSIGNED) <= CAST('$long_end_ip' AS UNSIGNED))";

  if($range_type === 'block'){
    #can't overlap other blocks
    $result = mysql_query($sql);
    if(mysql_num_rows($result) != '0'){
	  $return['0'] = false;
	  $return['1'] = $rangeoverlapmessage;
	  return $return;
	}
	# If we get here, it's a valid block range
	return true;
  }
  elseif($range_type === 'acl' && $subnet_id != null){
    # start and end must fall within $subnet	
    $sql = "SELECT start_ip, end_ip FROM subnets WHERE id='$subnet_id'";
    $result = mysql_query($sql);
  
    if(mysql_num_rows($result) != '1'){
      $return['0'] = false;
      $return['1'] = $COLLATE['languages']['selected']['invalidrequest'];
      return $return;
    }
    
    list($long_subnet_start_ip,$long_subnet_end_ip) = mysql_fetch_row($result);
    
    if($long_start_ip < $long_subnet_start_ip || $long_start_ip > $long_subnet_end_ip || 
	   $long_end_ip < $long_start_ip || $long_end_ip > $long_subnet_end_ip){
	  $return['0'] = false;
	  $return['1'] = $COLLATE['languages']['selected']['invalidrange'];
	  return $return;
    }
	# can't overlap other ACLs
	$result = mysql_query($sql);
    if(mysql_num_rows($result) != '0'){
	  $return['0'] = false;
	  $return['1'] = $rangeoverlapmessage;
	  return $return;
	}
	# If we get here, it's a valid acl range
	$return['0'] = true;
	$return['start_ip'] = $start_ip;
	$return['long_start_ip'] = $long_start_ip;
	$return['end_ip'] = $end_ip;
	$return['long_end_ip'] = $long_end_ip;
	return $return;
  }
  # we should never get here
  $return['0'] = false;
  $return['1'] = $COLLATE['languages']['selected']['invalidrequest'];
  return $return;
}

function validate_static_ip($ip){
  /* Returns true if the IP is safe to insert into the database */
  
  $return = array();
  
  # is an ipv4 address
  $long_ip = ip2decimal($ip);
  if($long_ip === false){
    $return['0'] = false;
	$return['error'] = 'invalidip';
	return $return;
  }
  
  # determine whether or not it is already reserved
  $sql = "SELECT id from statics where ip='$long_ip'";
  $result = mysql_query($sql);
  if(mysql_num_rows($result) != '0'){
    $return['0'] = false;
    $return['error'] = 'ipalreadyreserved';
    return $return;
  }
  return true;  
}

function validate_network($subnet,$network_type="subnet"){

  $return = array();
  
  if(!strstr($subnet, '/')){
    # invalid mask
	$return['0'] = false;
    $return['error'] = 'invalidmask';
    return $return;
  }
  
  list($ip,$mask) = explode('/', $subnet);
  $long_ip = ip2decimal($ip);
  
  if($long_ip === false){
    # invalid ip
    $return['0'] = false;
    $return['error'] = 'invalidip';
    return $return;
  }
  
  if(!strstr($mask, '.') && is_numeric($mask) && $mask > '0' && $mask < '32'){ # number of mask bits
    $bin = str_pad('', $mask, '1');
    $bin = str_pad($bin, '32', '0');
    $mask = bindec(substr($bin,0,8)).".".bindec(substr($bin,8,8)).".".bindec(substr($bin,16,8)).".".bindec(substr($bin,24,8));
    $mask = long2ip(ip2decimal($mask));
  }
  $long_mask = ip2decimal($mask);
  if(!validate_netmask($mask) || $long_mask === false){
    #invalid mask
	$return['0'] = false;
    $return['error'] = 'invalidmask';
    return $return;
  }
  
  $long_ip = ($long_ip & $long_mask); // This makes sure they entered the network address and not an IP inside the network
  $long_end_ip = $long_ip | (~$long_mask);
  
  if($network_type == 'block'){
    # make sure we don't overlap other blocks
  }
  else{
    # make sure we don't overlap other subnets
	$sql = "SELECT id FROM subnets WHERE CAST('$long_ip' AS UNSIGNED) & CAST(mask AS UNSIGNED) = CAST(start_ip AS UNSIGNED) OR CAST(start_ip AS UNSIGNED) & CAST('$long_mask' AS UNSIGNED) = CAST('$long_ip' AS UNSIGNED)";
    $result = mysql_query($sql);
	if(mysql_num_rows($result) != '0'){
	  # subnet overlap
	  $return['0'] = false;
      $return['error'] = 'subnetoverlap';
      return $return;
	}
  }
  # everything is ok if we get here
  $return['0'] = true;
  $return['start_ip'] = $start_ip;
  $return['long_start_ip'] = $long_start_ip;
  $return['end_ip'] = $end_ip;
  $return['long_end_ip'] = $long_end_ip;
  $return['mask'] = $mask;
  $return['long_mask'] = $long_mask;
  return $return;
}

function validate_api_key($apikey){
  $return = array();
  if(strlen($apikey) != '21' || preg_match("/[^0-9a-zA-Z]/", $apikey)){
    $return['0'] = false;
	$return['error'] = 'invalidrequest';
  }
  
  $sql = "SELECT description,active from `api-keys` WHERE apikey='$apikey'";
  $result = mysql_query($sql);
  if(mysql_num_rows($result) != '1'){
    $return['0'] = false;
	$return['error'] = 'apiaccessdenied';
  }
  
  list($description,$active) = mysql_fetch_row($result);
  
  $active = ($active === '0') ? false : true;
  
  $return['0'] = true;  
  $return['description'] = $description;
  $return['active'] = $active;
  
  return $return;
}

?>