<?php

require_once('include/common.php');

$authorization = AccessControl('3', null, false); # null means no log, false means don't redirect
if($authorization === false){ 
  header("HTTP/1.1 401 Unauthorized");
  echo $COLLATE['languages']['selected'][$authorization['error']];
  exit();
}

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){
	
	case "edit";
	edit_subnet();
	break;
	
	case "delete";
	delete_subnet();
	break;
	
	case "search";
	search_subnets();
	break;
	
	case "toggle_stale-scan";
	toggle_stalescan();
	break;
}


function edit_subnet(){
  global $COLLATE;
  global $dbo;
  include 'include/validation_functions.php';

  $subnet_id = (empty($_GET['subnet_id'])) ? '' : $_GET['subnet_id'];
  $edit = (empty($_GET['edit'])) ? '' : $_GET['edit'];
  $value = (empty($_POST['value'])) ? '' : $_POST['value'];
  $username = (isset($COLLATE['user']['username'])) ? $COLLATE['user']['username'] : 'unknown';
  
  if(empty($subnet_id) || !is_numeric($subnet_id) || !preg_match('/name|note/', $edit)){ 
    header("HTTP/1.1 400 Bad Request");
	echo $COLLATE['languages']['selected']['invalidrequest'];
	exit();
  }
  if($edit == 'name'){
    $return = validate_text($value,'subnetname');
  }
  else{
    $return = validate_text($value,'note');
  }
  if($return['0'] === false){
    header("HTTP/1.1 400 Bad Request");
	echo $COLLATE['languages']['selected'][$return['error']];
	exit();
  }
  else{
    $value = $return['1'];
  }
  
  $sql = "SELECT name, start_ip, mask FROM subnets WHERE id='$subnet_id'";
  $result = $dbo -> query($sql);
  list($name,$subnet,$mask) = $result -> fetch(PDO::FETCH_NUM);
  $cidr=subnet2cidr($subnet,$mask);
	
  if($edit == 'name'){
    collate_log('3', "Subnet $name ($cidr) name changed to $value");
	$sql = "UPDATE subnets SET name='$value', modified_by='$username', modified_at=NOW() WHERE id='$subnet_id'";
  }
  elseif($edit == 'note'){
    collate_log('3', "Subnet $name ($cidr) note edited");
	$sql = "UPDATE subnets SET note='$value', modified_by='$username', modified_at=NOW() WHERE id='$subnet_id'";
  }
  else{
    header("HTTP/1.1 400 Bad Request");
	echo $COLLATE['languages']['selected']['shortsubnetname'];
	exit();
  }
 
  $dbo -> query($sql);
  
  echo $value;
} // Ends edit_subnet function

function delete_subnet(){
  global $COLLATE;
  global $dbo;
  include 'include/validation_functions.php';

  $subnet_id = (empty($_GET['subnet_id'])) ? '' : clean($_GET['subnet_id']);
  
  if(empty($subnet_id)){
    header("HTTP/1.1 400 Bad Request");
	echo $COLLATE['languages']['selected']['shortsubnetname'];
	exit();
  }
  
  $sql = "SELECT name, start_ip, mask FROM subnets WHERE id='$subnet_id'";
  $result = $dbo -> query($sql);
	
  if($result -> rowCount() != '1'){
    header("HTTP/1.1 400 Bad Request");
	echo $COLLATE['languages']['selected']['shortsubnetname'];
	exit();
  }
  
  list($name,$subnet,$mask) = $result -> fetch(PDO::FETCH_NUM);
  $cidr=subnet2cidr($subnet,$mask);
  
  collate_log('3', "Subnet $name ($cidr) has been deleted"); 
  
  // First delete all static IPs
  $sql = "DELETE FROM statics WHERE subnet_id='$subnet_id'";
  $dbo -> query($sql);
  
  // Next, remove the acl ACL
  $sql = "DELETE FROM acl WHERE subnet_id='$subnet_id'";
  $dbo -> query($sql);
  
  // Lastly, remove the subnet
  $sql = "DELETE FROM subnets WHERE id='$subnet_id'";
  $dbo -> query($sql);
  
  // The user is informed of success by the fading away of the subnet in the UI
  exit();
  
} // Ends delete_subnet function

function search_subnets(){
  global $COLLATE;
  global $dbo;
  include 'include/validation_functions.php';
  
  $search = (empty($_GET['search'])) ? '' : clean($_GET['search']);
  $search_only = (isset($_GET['searchonly']) && preg_match("/true/", $_GET['searchonly'])) ? true : false;
  $searchonlyparam = ($search_only) ? '&amp;searchonly=true' : '';
  $input_error=false;
  
  if(empty($search)) { exit(); }
  
  if(!strstr($search, '/')){
    echo $COLLATE['languages']['selected']['IPSearchFormat'];
    $input_error=true;
  }
  
  list($ip,$mask) = explode('/', $search);
  
  if(ip2decimal($ip) == FALSE){
    echo $COLLATE['languages']['selected']['IPSearchFormat'];
    $input_error=true;
  }
  
  $ip = long2ip(ip2decimal($ip));  
  if(!strstr($mask, '.') && ($mask <= '0' || $mask >= '32')){
    echo $COLLATE['languages']['selected']['IPSearchFormat'];
    $input_error=true;
  }
  elseif(!strstr($mask, '.')){
    $bin = str_pad('', $mask, '1');
    $bin = str_pad($bin, '32', '0');
    $mask = bindec(substr($bin,0,8)).".".bindec(substr($bin,8,8)).".".bindec(substr($bin,16,8)).".".bindec(substr($bin,24,8));
    $mask = long2ip(ip2decimal($mask));
  }
  elseif(!validate_netmask($mask)){
    echo $COLLATE['languages']['selected']['invalidmask'];
    $input_error=true;
  }
  
  if(!$input_error){

    $long_ip = ip2decimal($ip);
    $long_mask = ip2decimal($mask);
    $long_end_ip = $long_ip | (~$long_mask);
    
    $ipspace = array();
    array_push($ipspace, $long_ip);
	  
    $sql = "SELECT start_ip, end_ip FROM subnets WHERE CAST((start_ip & 0xFFFFFFFF) AS UNSIGNED) >= CAST(('$long_ip' & 0xFFFFFFFF) AS UNSIGNED) AND ".
           "CAST((end_ip & 0xFFFFFFFF) AS UNSIGNED) <= CAST(('$long_end_ip' & 0xFFFFFFFF) AS UNSIGNED) ORDER BY start_ip ASC";
    $subnet_rows = $dbo -> query($sql);
	  
    while(list($subnet_long_start_ip,$subnet_long_end_ip) = $subnet_rows -> fetch(PDO::FETCH_NUM)){
      array_push($ipspace, $subnet_long_start_ip, $subnet_long_end_ip);
    }
    array_push($ipspace, $long_end_ip);
    $ipspace = array_reverse($ipspace);
    
    $ipspace_count = count($ipspace);
  }
  if(!$search_only){
    echo "<p><a href=\"#\" onclick=\"
           new Effect.toggle('blockspace', 'blind', { delay: 0.1 }); 
  		 new Effect.toggle('spacesearch', 'blind', { delay: 0.1 })
  		 \">".$COLLATE['languages']['selected']['showblockspace']."</a></p>\n";
  }
  echo "<h3>".$COLLATE['languages']['selected']['SearchIPSpace']."</h3><br />\n".
	   "<p><b>".$COLLATE['languages']['selected']['Subnet'].":</b> <input id=\"subnetsearch\" type=\"text\" value=\"$search\"><br />".
	   "<button onclick=\"new Ajax.Updater('spacesearch', '_subnets.php?op=search$searchonlyparam&amp;search=' + $('subnetsearch').value);\")\"> ".
	   $COLLATE['languages']['selected']['Go']." </button></p>";
  
  if(!$input_error){
    echo "<h4>".$COLLATE['languages']['selected']['Results'].":</h4>";
    
    echo "<table style=\"width: 100%\"><tr><th>".$COLLATE['languages']['selected']['StartingIP'].
         "</th><th>".$COLLATE['languages']['selected']['EndIP']."</th></tr>";
       
    while(!empty($ipspace)){
      $long_start = array_pop($ipspace);
      if(count($ipspace) != $ipspace_count - '1'){ // Don't subtract 1 from the very first start IP
        $start = long2ip($long_start + 1);
      }
      else{
        $start = long2ip($long_start);
      }
    	
      $long_end = array_pop($ipspace);
      if(count($ipspace) > '1'){
        $end = long2ip($long_end - 1);
      }
      else{
        $end = long2ip($long_end);
      }
    	
      if($long_start + 1 != $long_end && $long_start != $long_end){
        echo "<tr><td>$start</td><td>$end</td></tr>";
      }
    }
    echo "</table>";
  }
  exit();
}

function toggle_stalescan(){
  global $COLLATE;
  global $dbo;
  
  $subnet_id = (isset($_GET['subnet_id']) && preg_match("/[0-9]*/", $_GET['subnet_id'])) ? $_GET['subnet_id'] : '';
  $toggle = (isset($_GET['toggle']) && preg_match("/on|off/", $_GET['toggle'])) ? $_GET['toggle'] : '';
  
  if(empty($subnet_id) || empty($toggle)){
    header("HTTP/1.1 400 Bad Request");
    $notice = 'invalidrequest';
    header("Location: subnets.php?op=modify&subnet_id=$subnet_id&notice=$notice");
    exit();
  }
  
  $sql = "SELECT name, start_ip, mask FROM subnets WHERE id='$subnet_id'";
  $query_result = $dbo -> query($sql);
  if($query_result -> rowCount() !== 1){
    header("HTTP/1.1 400 Bad Request");
    $notice = 'invalidrequest';
    header("Location: subnets.php?op=modify&subnet_id=$subnet_id&notice=$notice");
    exit();
  }
  
  list($subnet_name,$long_start_ip,$long_mask) = $query_result -> fetch(PDO::FETCH_NUM);
  $cidr = subnet2cidr($long_start_ip,$long_mask); 
  
  collate_log('3', "Stale Scan toggled $toggle for Subnet: $subnet_name ($cidr)"); 

  if($toggle == 'on'){
    $stalescan_enabled='1';
    $notice='staletoggleon-notice';
	
	$sql = "UPDATE statics SET failed_scans='0' WHERE subnet_id='$subnet_id'";
    $dbo -> query($sql);
  }
  else{
    $stalescan_enabled='0';
    $notice='staletoggleoff-notice';
	
	$sql = "UPDATE statics SET failed_scans='-1' WHERE subnet_id='$subnet_id'";
    $dbo -> query($sql);
  }
  
  $sql = "UPDATE subnets SET stalescan_enabled=$stalescan_enabled WHERE id='$subnet_id'";
  $dbo -> query($sql);
 
  header("Location: subnets.php?op=modify&subnet_id=$subnet_id&notice=$notice");
  exit();
}
?>
