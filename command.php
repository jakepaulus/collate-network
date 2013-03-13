<?php

#TODO: all calls to read_in_csv_row must expect the returned errors to be short errors

require_once('./include/common.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){

	case "get";
	get_record();
	break;
	
	case "set";
	set_record();
	break;
	
	case "upload";
	process_file();
	break;
	
	default:
	AccessControl("1", null);
	show_form();
	break;
	
}


function process_file(){
  global $COLLATE;
  include "include/header.php";
  include "include/validation_functions.php";
  echo "<h1>Upload Results</h1><br />";
  
  
  
  $uploaderror = (isset($_FILES['file']['error'])) ? $_FILES['file']['error'] : "UPLOAD_ERR_NO_FILE";

  if($uploaderror != "UPLOAD_ERR_OK"){
    echo "<p><b>".$COLLATE['languages']['selected']['erroroccured'].":</b> ";
    switch ($uploaderror) { 
      case "UPLOAD_ERR_INI_SIZE";
        echo $COLLATE['languages']['selected']['uploadtoobig'];
        break;		
      case "UPLOAD_ERR_FORM_SIZE"; 
        echo $COLLATE['languages']['selected']['uploadtoobig'];
        break;		
      case "UPLOAD_ERR_PARTIAL";
        echo $COLLATE['languages']['selected']['partialupload'];
        break;
      case "UPLOAD_ERR_NO_FILE";
        echo $COLLATE['languages']['selected']['noupload'];
        break; 
      case "UPLOAD_ERR_NO_TMP_DIR";
        echo $COLLATE['languages']['selected']['notmpfolder'];
        break; 
      case "UPLOAD_ERR_CANT_WRITE";
        echo $COLLATE['languages']['selected']['diskwritefail'];
        break; 
      case "UPLOAD_ERR_EXTENSION";
        echo $COLLATE['languages']['selected']['extensionfail'];
        break; 
      default: 
        echo $COLLATE['languages']['selected']['unknownerror'];
        break; 
    }
	echo "</p><br /><p><a href=\"command.php\">".$COLLATE['languages']['selected']['tryagain']."</a></p>";
    include "include/footer.php";
    exit();
  }
  
  $file = $_FILES['file']['tmp_name'];
  $errorcount = '0';
  $sql = '';
  ini_set('auto_detect_line_endings',TRUE);
  if (($handle = fopen($file, "r")) !== FALSE) {
    // forcing the user to order the rows in the CSV file is a little burdensome
	// to enable the rows to be in any order, we load the file into an array, then
	// iterate over the array once per record type
    // The whole thing is done inside an SQL transaction. if any errors are found
	// at any point, the transaction is rolled back.
	$rownum = '0';
	$checkedlinewithcontentforencoding=false;
    while ($currentrow = fgetcsv($handle, '1000', ",", "'")) {
	  if($currentrow['0'] === null && count($currentrow) === '1'){
		  // blank line in csv file
		  $rownum++;
		  continue;
      }
	  if($checkedlinewithcontentforencoding === false){
	    $characterencoding = mb_detect_encoding($currentrow['0']);
		if($characterencoding != "ASCII"){
		  echo "<p>".$COLLATE['languages']['selected']['badencoding']."</p>";
		  echo "<br /><p><a href=\"command.php\">".$COLLATE['languages']['selected']['tryagain']."</a></p>";
          include "include/footer.php";
          exit();
		}
		$checkedlinewithcontentforencoding = true;
	  }

	  
	  if($currentrow === false || ($currentrow['0'] != 'block' && $currentrow['0'] != 'subnet' && 
      $currentrow['0'] != 'acl' && $currentrow['0'] != 'static')){
		// This is not a well-formed record or is an invalid record type
		$errorcount++;
		if($errorcount <= '50'){
	  	  $message = str_replace("%rownum%", $rownum+1, $COLLATE['languages']['selected']['erroronrow']);
	  	  $message = str_replace("%error%", $COLLATE['languages']['selected']['invalidrecord'], $message);
	  	  echo "<p>$message</p>";
	    }
      }
	  
	  $row[$rownum]=$currentrow;
	  $rownum++;
	}
	fclose($handle);
	unset($currentrow);
	unset($rownum);
	
	if($errorcount === '0'){ // don't bother validating data further if we didn't even find a propper csv file
	  mysql_query("START TRANSACTION");
	  $recordprocessingorder = array('block', 'subnet', 'acl', 'static');
	  foreach($recordprocessingorder as $recordtype){
	    foreach($row as $currentrow => $rowdata){
	      if($rowdata['0'] === "$recordtype"){
            $result = read_in_csv_row($rowdata);
	        if($result['error'] === true){
	          $errorcount++;
		    
			  if($errorcount <= '50'){
	  	        $message = str_replace("%rownum%", $currentrow+1, $COLLATE['languages']['selected']['erroronrow']);
	  	        $message = str_replace("%error%", $COLLATE['languages']['selected'][$result['errormessage']], $message);
	  	        echo "<p>$message</p>";	  	    
	  	      }
		    }
	        else{
			  mysql_query($result['sql']);
	  	      $sql .= $result['sql'].';<br><br>'; #### ---> remove this line later <--- #######
	        }
		  }
	    }
		unset($rowdata);
	  }
	  unset($recordtype);
	  
	  
	  if($errorcount !== '0'){
	    mysql_query("ROLLBACK");
	  }
	  else {
	    mysql_query("COMMIT");
	  }
    }
	
	if($errorcount > '50'){
	  echo "<p>".$COLLATE['languages']['selected']['manyimporterr']."</p>";
	}
	if($errorcount != '0'){
	  echo "<br /><p><a href=\"command.php\">".$COLLATE['languages']['selected']['tryagain']."</a></p>";
	}
	else{ // Success!
	  $importedrecords = $currentrow + 1;
	  $successmessage = str_replace("%rows%", $importedrecords, $COLLATE['languages']['selected']['importsuccess']);
	  echo "<p><b>$successmessage</b></p>";
	  $logmessage = "Bulk imported $importedrecords records";
	  collate_log('5', $logmessage);
	}
	
	include "include/footer.php";
    exit();
    
    // report errors if any	
  }  
  include "include/footer.php";
  exit();
}


function show_form(){
  global $COLLATE;
  include "include/header.php";
  echo "<h1>".$COLLATE['languages']['selected']['BulkImport']."</h1>\n".
       "<br />\n".
	   "<p>".$COLLATE['languages']['selected']['uploadwarning']."</p>\n".
	   "<form enctype=\"multipart/form-data\" action=\"command.php?op=upload\" method=\"post\">\n";
  # determine maximum upload size by looking at php.ini settings
  $post_max_size = return_ini_setting_in_bytes(ini_get('post_max_size'));
  $upload_max_size = return_ini_setting_in_bytes(ini_get('upload_max_filesize')); 
  $memory_limit = return_ini_setting_in_bytes(ini_get('memory_limit'));
  $filesizelimit = min($post_max_size, $upload_max_size, $memory_limit);
  
  echo "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"$filesizelimit\" />";
  
  $selectafile = str_replace("%bytes%", $filesizelimit, $COLLATE['languages']['selected']['SelectFile']);
	   
  echo "<p>$selectafile: <a href=\"#\" onclick=\"new Effect.toggle($('tip'),'appear'); return false;\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
       "<input type=\"file\" name=\"file\" accept=\"text/csv\" />\n".
	   "<br /><br />\n".
	   "<input type=\"submit\" value=\"".$COLLATE['languages']['selected']['Go']."\"></p>\n".
	   "</form>\n".
	   "<br />\n".
	   "<div id=\"tip\" class=\"tip\" style=\"display: none;\"><p>".$COLLATE['languages']['selected']['filesizenote']."</p></div>";
  
  include "include/footer.php";
  exit();
}


function return_ini_setting_in_bytes($val) {
  # example 1 in http://php.net/manual/en/function.ini-get.php
  $val = trim($val);
  $last = strtolower($val[strlen($val)-1]);
  switch($last) {
    // The 'G' modifier is available since PHP 5.1.0
    case 'g':
      $val *= 1024;
    case 'm':
      $val *= 1024;
    case 'k':
      $val *= 1024;
  }
  return $val;
}


function read_in_csv_row($row){
  global $COLLATE;
  $recordtype=$row['0'];
  $fieldcount = count($row);
  $result=array();

  /*
   *  Record format:
   *  block: (5 fields)
   *  'block','$block_name','$start_ip','$end_ip','$block_note'
   *  
   *  subnet: (5 fields)
   *  'subnet','$block_name','$subnet_name','$subnet','$subnet_note'
   *  
   *  acl: (4 fields)
   *  'acl','$acl_name','$start_ip','$end_ip'
   *  
   *  static ip: (5 fields)
   *  'static','$static_name','$ip_address','$static_contact','$static_note'
   */
   
  if(($recordtype == 'block' && $fieldcount != '5') ||
     ($recordtype == 'subnet' && $fieldcount != '5') ||
     ($recordtype == 'acl' && $fieldcount != '4') ||
     ($recordtype == 'static' && $fieldcount != '5')){
    $result['error'] = true;
    $result['errormessage'] = 'badfieldcount';
    return $result;
  }
  
  $last_modified_by = (!isset($COLLATE['user']['username'])) ? 'system' : $COLLATE['user']['username'];
  
  if($recordtype == 'block'){
	$block_name = $row['1'];
	$block_start_ip = $row['2'];
	$block_end_ip = $row['3'];
	$block_note = $row['4'];
	
	$validate = validate_text($block_name,'blockname');
	if($validate['0'] === false){
	  $result['error'] = true;
	  $result['errormessage'] = $validate['error'];
	  return $result;
	}
	else{
	  $block_name = $validate['1'];
    }
	$query_result = mysql_query("SELECT id from blocks where name='$block_name'");
	if(mysql_num_rows($query_result) != '0'){
	  $result['error'] = true;
	  $result['errormessage'] = 'duplicatename';
	  return $result;
	}
	
	if(empty($block_end_ip) || ip2decimal($block_end_ip) === false){
	  // subnet
	  $validate = validate_network($block_start_ip,'block');
	  if($validate['0'] === false){
	    $result['error'] = true;
	    $result['errormessage'] = $validate['error'];
	    return $result;
	  }
	  else{
	    $block_start_ip = $validate['start_ip'];
		$block_long_start_ip = $validate['long_start_ip'];
		$block_end_ip = $validate['end_ip'];
		$block_long_end_ip = $validate['long_end_ip'];
	  }
	}
	else{
	  // range
	  $validate = validate_ip_range($block_start_ip,$block_end_ip,'block');
	  if($validate['0'] === false){
	    $result['error'] = true;
	    $result['errormessage'] = $validate['error'];
	    return $result;
	  }
	  else{
	    $block_start_ip = $validate['start_ip'];
		$block_long_start_ip = $validate['long_start_ip'];
		$block_end_ip = $validate['end_ip'];
		$block_long_end_ip = $validate['long_end_ip'];
	  }
	}
	
	$validate = validate_text($block_note,'note');
	if($validate['0'] === false){
	  $result['error'] = true;
	  $result['errormessage'] = $validate['error'];
	  return $result;
	}
	else{
	  $block_note = $validate['1'];
    }
	
    $row_result['error'] = false;
	$row_result['sql'] = "INSERT INTO blocks (name, start_ip, end_ip, note, modified_by, modified_at) 
	                  VALUES('$block_name', '$block_long_start_ip', '$block_long_end_ip', '$block_note', '$last_modified_by', now())";
    return $row_result;
	
  }
  elseif($recordtype == 'subnet'){
    $block_name = $row['1'];
	$subnet_name = $row['2'];
	$subnet = $row['3'];
	$subnet_note = $row['4'];
	
	$validate = validate_text($block_name,'blockname');
	if($validate['0'] === false){
	  $result['error'] = true;
	  $result['errormessage'] = $validate['error'];
	  return $result;
	}
	else{
	  $block_name = $validate['1'];
    }
	$query_result = mysql_query("SELECT id from blocks where name='$block_name'");
	if(mysql_num_rows($query_result) != '1'){
	  $result['error'] = true;
	  $result['errormessage'] = 'blocknotfound';
	  return $result;
	}
	else{
	  $block_id = mysql_result($query_result, 0);
	}
	
	$validate = validate_text($subnet_name,'subnetname');
	if($validate['0'] === false){
	  $result['error'] = true;
	  $result['errormessage'] = $validate['error'];
	  return $result;
	}
	else{
	  $subnet_name = $validate['1'];
    }
	
	$validate = validate_network($subnet);
	if($validate['0'] === false){
	  $result['error'] = true;
	  $result['errormessage'] = $validate['error'];
	  return $result;
	}
	else{
	  $subnet_start_ip = $validate['start_ip'];
	  $subnet_long_start_ip = $validate['long_start_ip'];
	  $subnet_end_ip = $validate['end_ip'];
	  $subnet_long_end_ip = $validate['long_end_ip'];
	  $subnet_mask = $validate['mask'];
	  $subnet_long_mask = $validate['long_mask'];
	}
	
	$validate = validate_text($subnet_note,'note');
	if($validate['0'] === false){
	  $result['error'] = true;
	  $result['errormessage'] = $validate['error'];
	  return $result;
	}
	else{
	  $subnet_note = $validate['1'];
    }
	
	$return['error'] = false;
	$return['sql'] = "INSERT INTO subnets (name, start_ip, end_ip, mask, note, block_id, modified_by, modified_at) 
                      VALUES('$subnet_name', '$subnet_long_start_ip', '$subnet_long_end_ip', '$subnet_long_mask', 
					  '$subnet_note', '$block_id', '$last_modified_by', now())";
	return $return;
	
	
  }
  elseif($recordtype == 'acl'){
	$acl_name = $row['1'];
	$acl_start_ip = $row['2'];
	$acl_end_ip = $row['3'];
	
	$validate = validate_text($acl_name,'blockname');
	if($validate['0'] === false){
	  $result['error'] = true;
	  $result['errormessage'] = $validate['error'];
	  return $result;
	}
	else{
	  $acl_name = $validate['1'];
    }
	
	$validate = validate_ip_range($acl_start_ip,$acl_end_ip,'acl',null);
	if($validate['0'] === false){
	  $result['error'] = true;
	  $result['errormessage'] = $validate['error'];
	  return $result;
	}
	else{
	  $subnet_id = $validate['subnet_id'];
	  $acl_start_ip = $validate['start_ip'];
	  $acl_long_start_ip = $validate['long_start_ip'];
	  $acl_end_ip = $validate['end_ip'];
	  $acl_long_end_ip = $validate['long_end_ip'];
    }
	
	$return['error'] = false;
	$return['sql'] = "INSERT INTO acl (name, start_ip, end_ip, subnet_id) 
	                  VALUES ('$acl_name', '$acl_long_start_ip', '$acl_long_end_ip', '$subnet_id')";
	return $return;
  }
  else{ // $recordtype == static
    $static_name = $row['1'];
	$static_ip = $row['2'];
	$static_long_ip = ip2decimal($static_ip);
	$static_contact = $row['3'];
	$static_note = $row['4'];
	
	
	$validate = validate_text($static_name,'staticname');
	if($validate['0'] === false){
	  $result['error'] = true;
	  $result['errormessage'] = $validate['error'];
	  return $result;
	}
	else{
	  $static_name = $validate['1'];
    }
	
	if($static_long_ip === false){
	  $result['error'] = true;
	  $result['errormessage'] = 'invalidip';
	  return $result;
	}	
    $sql = "SELECT id from subnets where '$static_long_ip' & mask = start_ip";
	$subnet_result = mysql_query($sql);
	
	if(mysql_num_rows($subnet_result) != '1'){
	  $result['error'] = true;
	  $result['errormessage'] = 'subnetnotfound';
	  return $result;
	}
	else{
	  $subnet_id = mysql_result($subnet_result, 0);
	}
	
	// Make sure the static IP isn't in use already or excluded from use via an ACL
	$validate = validate_static_ip($static_long_ip);
	if($validate['0'] === false){
	  $result['error'] = true;
	  $result['errormessage'] = $validate['error'];
	  return $result;
    }
	
	$validate = validate_text($static_contact,'contact');
	if($validate['0'] === false){
	  $result['error'] = true;
	  $result['errormessage'] = $validate['error'];
	  return $result;
	}
	else{
	  $static_contact = $validate['1'];
    }
	
	$validate = validate_text($static_note,'note');
	if($validate['0'] === false){
	  $result['error'] = true;
	  $result['errormessage'] = $validate['error'];
	  return $result;
	}
	else{
	  $static_note = $validate['1'];
    }
	
	$return['error'] = false;
	$return['sql'] = "INSERT INTO statics (ip, name, contact, note, subnet_id, modified_by, modified_at)
                      VALUES('$static_long_ip', '$static_name', '$static_contact', '$static_note', 
					  '$subnet_id', '$last_modified_by', now())";
	return $return;
  }
  // We should never get here
  exit(); 
}


?>