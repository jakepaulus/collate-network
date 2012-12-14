<?php
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
  $rownum = '1';
  $errorcount = '0';
  $sql = '';
  if (($handle = fopen($file, "r")) !== FALSE) {
    // forcing the user to order the rows in the CSV file is a little burdensome
	// to enable the rows to be in any order, we will go through the file once
	// for each record type, then a fifth time to catch rows that might be invalid
    // The whole thing will have to be done inside a transaction :/
    while (($row = fgetcsv($handle, '1000', ",", "'")) !== FALSE) {
      $result = read_in_csv_row($row);
	  if($result['error'] === true){
	    $errorcount++;
		if($errorcount <= '50'){
		  $message = str_replace("%rownum%", $rownum, $COLLATE['languages']['selected']['erroronrow']);
		  $message = str_replace("%error%", $result['errormessage'], $message);
		  echo "<p>$message</p>";
		  
		}
	  }
	  else{
	    // Append SQL to send later as a large multi_query() if the whole file validates
		$sql .= $return['sql'].';\n'; #### ---> remove \n later <--- #######
	  }
	  $rownum++;
    }
	fclose($handle);
	
	if($errorcount > '50'){
	  echo "<p>".$COLLATE['languages']['selected']['manyimporterr']."</p>";
	}
	if($errorcount != '0'){
	  echo "<br /><p><a href=\"command.php\">".$COLLATE['languages']['selected']['tryagain']."</a></p>";
	  include "include/footer.php";
      exit();
	}
	else{
	  // execute multi_query($sql) and output some success message
	  echo "<pre>$sql</pre>";
	}    
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


function read_in_csv_row($row,$action='validate'){
  global $COLLATE;
  $recordtype=$row['0'];
  $fieldcount = count($row);
  $result=array();

  /*
   *  Record format (input or output):
   *  block: (6 fields)
   *  'block','$block_name','$start_ip','$end_ip','$block_note','$last_modified_by'
   *  
   *  subnet: (7 fields)
   *  'subnet','$block_name','$subnet_name','$network_address','$subnet_mask','$subnet_note','$last_modified_by'
   *  
   *  acl: (4 fields)
   *  'acl','$acl_name','$start_ip','$end_ip'
   *  
   *  static ip: (8 fields)
   *  'static','$ip_address','$static_name','$static_contact','$faild_scans','$static_note','$last_modified_by'
   */
   
  if($recordtype != 'block' && $recordtype != 'subnet' && $recordtype != 'acl' && $recordtype != 'static'){
    $result['error'] = true;
    $result['errormessage'] = $COLLATE['languages']['selected']['invalidrecord'];
    return $result;
  }
  
  if(($recordtype == 'block' && $fieldcount != '8') ||
     ($recordtype == 'subnet' && $fieldcount != '9') ||
     ($recordtype == 'acl' && $fieldcount != '6') ||
     ($recordtype == 'static' && $fieldcount != '10')){
    $result['error'] = true;
    $result['errormessage'] = $COLLATE['languages']['selected']['badfieldcount'];
    return $result;
  }
  
  if($recordtype == 'block'){
	$block_name = $row['1'];
	$block_start_ip = $row['2'];
	$block_end_ip = $row['3'];
	$block_note = $row['4'];
	$last_modified_by = $row['5'];
	
	$validate = validate_text($block_name,'blockname');
	if($validate['0'] === false){
	  $result['error'] = true;
	  $result['errormessage'] = $validate['error'];
	  return $result;
	}
	else{
	  $block_name = $return['1'];
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
	  $validate = validate_ip_range($block_start_ip,$block_end_ip);
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
	  $block_note = $return['1'];
    }
	
	$validate = validate_text($block_contact,'contact');
	if($validate['0'] === false){
	  $result['error'] = true;
	  $result['errormessage'] = $validate['error'];
	  return $result;
	}
	else{
	  $block_contact = $return['1'];
    }
	
    $return['error'] = false;
	$return['sql'] = "INSERT INTO blocks (name, start_ip, end_ip, note, modified_by, modified_at) 
	                  VALUES('$block_name', '$block_long_start_ip', '$block_long_end_ip', '$block_note', '$block_contact', now())";
    return $return;
	
  }
  elseif($recordtype == 'subnet'){
    $block_name = $row['1'];
	$subnet_name = $row['2'];
	$subnet_ip = $row['3'];
	$subnet_mask = $row['4'];
	$subnet_note = $row['5'];
	$last_modified_by = $row['6'];
	$last_modified_at = $row['7'];
  }
  elseif($recordtype == 'acl'){
    $subnet_id = $row['1'];
	$acl_id = $row['2'];
	$acl_name = $row['3'];
	$acl_start_ip = $row['4'];
	$acl_end_ip = $row['5'];
  }
  else{ // $recordtype == static
    $subnet_id = $row['1'];
	$static_id = $row['2'];
	$static_ip = $row['3'];
	$static_name = $row['4'];
	$static_contact = $row['5'];
	$static_scans = $row['6'];
	$static_note = $row['7'];
	$last_modified_by = $row['8'];
	$last_modified_at = $row['9'];
  }
  // We should never get here
  exit(); 
}



?>