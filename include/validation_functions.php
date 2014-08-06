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
  
  if(preg_match("/01/", "$binip") || !preg_match("/0/", "$binip")) {
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
  global $COLLATE;
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
	$canbeempty = true;
	break;
	
	case "guidance";
	$length = '255';
	$error = 'guidancelengtherror';
	$canbeempty = true;
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
	$canbeempty = true;
	break;
	
	case "dnshelper";
	$length = '128';
	$error = 'dnshelperlengtherror';
	$canbeempty = true;
	break;
	
	case "apidescription";
	$length = '60';
	$error = 'apidescriptionlengtherror';
	break;
  }
  $function_return=array();
  $text = clean($text);
  if(($canbeempty === false && strlen($text) < '3' ) || strlen($text) > $length){
    $function_return['0'] = false;
	$function_return['error'] = $error;
	return $function_return;
  }
  $function_return['0'] = true;
  $function_return['1'] = $text;
  return $function_return;
}

function validate_ip_range($start_ip,$end_ip,$range_type,$table_id=null){
  global $COLLATE;
  $function_return = array();
  $long_start_ip = ip2decimal($start_ip);
  $long_end_ip = ip2decimal($end_ip);
  $sqltable = ($range_type == 'block') ? 'blocks' : 'acl';
  $function_return['0'] = false;
  
  # are the inputs valid ipv4 addresses?
  if($long_start_ip === false || $long_end_ip === false){
	$function_return['error'] = 'invalidip';
	return $function_return;
  }
  
  # range must have smaller start_ip than end_ip
  if($long_end_ip <= $long_start_ip){
    $function_return['error'] = 'invalidrange';
    return $function_return;
  }
  
  # does this overlap an existing block or acl?
  $overlap_check_sql = "SELECT id FROM $sqltable WHERE 
    ((CAST(start_ip & 0xFFFFFFFF AS UNSIGNED) <= CAST('$long_start_ip' & 0xFFFFFFFF AS UNSIGNED) AND 
	  CAST(end_ip & 0xFFFFFFFF AS UNSIGNED) >= CAST('$long_start_ip' & 0xFFFFFFFF AS UNSIGNED)) 
	OR 
    (CAST(start_ip & 0xFFFFFFFF AS UNSIGNED) <= CAST('$long_end_ip' & 0xFFFFFFFF AS UNSIGNED) AND 
	  CAST(end_ip & 0xFFFFFFFF AS UNSIGNED) >= CAST('$long_end_ip' & 0xFFFFFFFF AS UNSIGNED)) 
	OR
    (CAST(start_ip & 0xFFFFFFFF AS UNSIGNED) >= CAST('$long_start_ip' & 0xFFFFFFFF AS UNSIGNED) AND 
	  CAST(end_ip & 0xFFFFFFFF AS UNSIGNED) <= CAST('$long_end_ip' & 0xFFFFFFFF AS UNSIGNED)))";

  $overlap_check_sql .= ($table_id !== NULL) ? " AND id!='$table_id'" : '';

  $result = mysql_query($overlap_check_sql);
  if(mysql_num_rows($result) != '0'){
    $function_return['error'] = ($range_type == 'block') ? 'blockoverlap-notice' : 'acloverlap-notice';
    return $function_return;
  }
  if($range_type === 'acl'){
	# make sure start and end falls within only one subnet
	# when $table_id is given, make sure it matches the subnet
	# we find
	if($table_id === null){
	  $sql = "SELECT id,name from subnets where CAST('$long_start_ip' AS UNSIGNED) & CAST(mask AS UNSIGNED) = CAST(start_ip AS UNSIGNED) 
              AND CAST('$long_end_ip' AS UNSIGNED) & CAST(mask AS UNSIGNED) = CAST(start_ip AS UNSIGNED)";
	}
	else {
	  $sql = "SELECT id,name from subnets where CAST('$long_start_ip' AS UNSIGNED) & CAST(mask AS UNSIGNED) = CAST(start_ip AS UNSIGNED) 
              AND CAST('$long_end_ip' AS UNSIGNED) & CAST(mask AS UNSIGNED) = CAST(start_ip AS UNSIGNED) AND id='$table_id'";
	}
	$result = mysql_query($sql);
	if(mysql_num_rows($result) != '1'){
	  $function_return['error'] = 'invalidrange';
	  return $function_return;
	}
	list($subnet_id,$subnet_name) =  mysql_fetch_row($result);
	$function_return['subnet_id'] = $subnet_id;
	$function_return['subnet_name'] = $subnet_name;
  }
  elseif($range_type != 'block'){ // we called the function wrong
	$function_return['error'] = 'invalidrequest';
	return $function_return;
  }
  # If we get here, it's a valid acl range
  $function_return['0'] = true;
  $function_return['start_ip'] = $start_ip;
  $function_return['long_start_ip'] = $long_start_ip;
  $function_return['end_ip'] = $end_ip;
  $function_return['long_end_ip'] = $long_end_ip;
  return $function_return;
}

function validate_static_ip($ip){
  /* Returns true if the IP is safe to insert into the database */
  
  $function_return = array();
  
  # is an ipv4 address
  $long_ip = ip2decimal($ip);
  if($long_ip === false){
    $function_return['0'] = false;
	$function_return['error'] = 'invalidip';
	return $function_return;
  }
  
  $function_return['long_ip'] = $long_ip;
  
  # determine if this static falls within a reserved subnet
  $sql = "select id,start_ip,mask from subnets where CAST('$long_ip' AS UNSIGNED) & CAST(mask AS UNSIGNED) = CAST(start_ip AS UNSIGNED)";
  $query_handle = mysql_query($sql);
  if(mysql_num_rows($query_handle) != '1'){
    $function_return['0'] = false;
	$function_return['error'] = 'subnetnotfound';
	return $function_return;
  }
  list($subnet_id,$long_start_ip,$long_mask) = mysql_fetch_row($query_handle);
  $function_return['subnet_id'] = $subnet_id;
  $function_return['long_start_ip'] = $long_start_ip;
  $function_return['long_mask'] = $long_mask;
  
  # determine whether or not this IP falls within an ACL
  $sql = "select id from acl where subnet_id='$subnet_id' AND (CAST(start_ip AS UNSIGNED) <= CAST('$long_ip' AS UNSIGNED) ".
         "AND CAST(end_ip AS UNSIGNED) >= CAST('$long_ip' AS UNSIGNED))";
  $query_handle = mysql_query($sql);
  if(mysql_num_rows($query_handle) != '0'){
    $function_return['0'] = false;
	$function_return['error'] = 'aclmatch';
	return $function_return;
  }
  
  # determine whether or not it is already reserved
  $sql = "SELECT id from statics where ip='$long_ip'";
  $result = mysql_query($sql);
  if(mysql_num_rows($result) != '0'){
    $function_return['0'] = false;
    $function_return['error'] = 'ipalreadyreserved';
    return $function_return;
  }
  $function_return['0'] = true;
  return $function_return;  
}

function validate_network($subnet,$network_type="subnet",$table_id=null){

  $function_return = array();
  
  if(!strstr($subnet, '/')){
    # invalid mask
	$function_return['0'] = false;
    $function_return['error'] = 'invalidmask';
    return $function_return;
  }
  
  list($ip,$mask) = explode('/', $subnet);
  $long_ip = ip2decimal($ip);
  
  if($long_ip === false){
    # invalid ip
    $function_return['0'] = false;
    $function_return['error'] = 'invalidip';
    return $function_return;
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
	$function_return['0'] = false;
    $function_return['error'] = 'invalidmask';
    return $function_return;
  }
  
  $long_start_ip = ($long_ip & $long_mask); // This makes sure they entered the network address and not an IP inside the network
  $start_ip = long2ip($long_start_ip);
  $long_end_ip = $long_ip | (~$long_mask);
  $end_ip = long2ip($long_end_ip);
  
  if($network_type == 'block'){
    # make sure we don't overlap other blocks
	$overlap_check_sql = "SELECT id FROM blocks WHERE 
    ((CAST(start_ip & 0xFFFFFFFF AS UNSIGNED) <= CAST('$long_start_ip' & 0xFFFFFFFF AS UNSIGNED) AND 
	  CAST(end_ip & 0xFFFFFFFF AS UNSIGNED) >= CAST('$long_start_ip' & 0xFFFFFFFF AS UNSIGNED)) 
	OR 
    (CAST(start_ip & 0xFFFFFFFF AS UNSIGNED) <= CAST('$long_end_ip' & 0xFFFFFFFF AS UNSIGNED) AND 
	  CAST(end_ip & 0xFFFFFFFF AS UNSIGNED) >= CAST('$long_end_ip' & 0xFFFFFFFF AS UNSIGNED)) 
	OR
    (CAST(start_ip & 0xFFFFFFFF AS UNSIGNED) >= CAST('$long_start_ip' & 0xFFFFFFFF AS UNSIGNED) AND 
	  CAST(end_ip & 0xFFFFFFFF AS UNSIGNED) <= CAST('$long_end_ip' & 0xFFFFFFFF AS UNSIGNED)))";

    $overlap_check_sql .= ($table_id !== NULL) ? " AND id!='$table_id'" : '';
	
	$result = mysql_query($overlap_check_sql);
    if(mysql_num_rows($result) != '0'){
	  $function_return['0'] = false;
      $function_return['error'] = 'blockoverlap-notice';
      return $function_return;
    }
  }
  else{
    # make sure we don't overlap other subnets
	$sql = "SELECT id FROM subnets WHERE 
	  CAST('$long_start_ip' & 0xFFFFFFFF AS UNSIGNED) & CAST(mask & 0xFFFFFFFF AS UNSIGNED) = CAST(start_ip & 0xFFFFFFFF AS UNSIGNED) OR 
	  CAST(start_ip & 0xFFFFFFFF AS UNSIGNED) & CAST('$long_mask' & 0xFFFFFFFF AS UNSIGNED) = CAST('$long_start_ip' & 0xFFFFFFFF AS UNSIGNED)";
    $result = mysql_query($sql);
	if(mysql_num_rows($result) != '0'){
	  # subnet overlap
	  $function_return['0'] = false;
      $function_return['error'] = 'subnetoverlap-notice';
      return $function_return;
	}
  }
  # everything is ok if we get here
  $function_return['0'] = true;
  $function_return['start_ip'] = $start_ip;
  $function_return['long_start_ip'] = $long_start_ip;
  $function_return['end_ip'] = $end_ip;
  $function_return['long_end_ip'] = $long_end_ip;
  $function_return['mask'] = $mask;
  $function_return['long_mask'] = $long_mask;
  return $function_return;
}

function validate_api_key($apikey){
  $function_return = array();
  if(strlen($apikey) != '21' || preg_match("/[^0-9a-zA-Z]/", $apikey)){
    $function_return['0'] = false;
	$function_return['error'] = 'invalidrequest';
  }
  
  $sql = "SELECT description,active from `api-keys` WHERE apikey='$apikey'";
  $result = mysql_query($sql);
  if(mysql_num_rows($result) != '1'){
    $function_return['0'] = false;
	$function_return['error'] = 'apiaccessdenied';
  }
  
  list($description,$active) = mysql_fetch_row($result);
  
  $active = ($active === '0') ? false : true;
  
  $function_return['0'] = true;  
  $function_return['description'] = $description;
  $function_return['active'] = $active;
  
  return $function_return;
}

?>