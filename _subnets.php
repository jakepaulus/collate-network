<?php

require_once('include/common.php');
$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){
	
	case "edit";
	edit_subnet();
	break;
	
	case "delete";
	delete_subnet();
	break;
	
	case "search";
	AccessControl('1', null);
	search_subnets();
	break;
}


function edit_subnet(){
  global $COLLATE;

  $subnet_id = (empty($_GET['subnet_id'])) ? '' : clean($_GET['subnet_id']);
  $edit = (empty($_GET['edit'])) ? '' : clean($_GET['edit']);
  $value = (empty($_POST['value'])) ? '' : clean($_POST['value']);
  $username = (isset($COLLATE['user']['username'])) ? $COLLATE['user']['username'] : 'unknown';
  
  if(empty($subnet_id) || empty($edit)){ 
    header("HTTP/1.1 500 Internal Error");
	echo $COLLATE['languages']['selected']['invalidrequest'];
	exit();
  }
  elseif($edit == 'name' && (strlen($value) < '3' OR strlen($value) > '60')){
    header("HTTP/1.1 500 Internal Error");
	echo $COLLATE['languages']['selected']['shortsubnetname'];
	exit();
  }
  
  $result = mysql_query("SELECT name, start_ip, mask FROM subnets WHERE id='$subnet_id'");
  list($name,$subnet,$mask) = mysql_fetch_row($result);
  $cidr=subnet2cidr($subnet,$mask);
	
  if($edit == 'name'){
    AccessControl('3', "Subnet $name ($cidr) name changed to $value");
	$sql = "UPDATE subnets SET name='$value', modified_by='$username', modified_at=NOW() WHERE id='$subnet_id'";
  }
  elseif($edit == 'note'){
    AccessControl('3', "Subnet $name ($cidr) note edited");
	$sql = "UPDATE subnets SET note='$value', modified_by='$username', modified_at=NOW() WHERE id='$subnet_id'";
  }
  else{
    header("HTTP/1.1 500 Internal Error");
	echo $COLLATE['languages']['selected']['shortsubnetname'];
	exit();
  }
 
  mysql_query($sql);
  
  echo $value;
} // Ends edit_subnet function


function delete_subnet(){
  global $COLLATE;

  $subnet_id = (empty($_GET['subnet_id'])) ? '' : clean($_GET['subnet_id']);
  
  if(empty($subnet_id)){
    header("HTTP/1.1 500 Internal Error");
	echo $COLLATE['languages']['selected']['shortsubnetname'];
	exit();
  }
  
  $result = mysql_query("SELECT name, start_ip, mask FROM subnets WHERE id='$subnet_id'");
	
  if(mysql_num_rows($result) != '1'){
    header("HTTP/1.1 500 Internal Error");
	echo $COLLATE['languages']['selected']['shortsubnetname'];
	exit();
  }
  
  list($name,$subnet,$mask) = mysql_fetch_row($result);
  $cidr=subnet2cidr($subnet,$mask);
  
  $accesslevel = "3";
  $message = "Subnet $name ($cidr) has been deleted";
  AccessControl($accesslevel, $message); 
  
  // First delete all static IPs
  $sql = "DELETE FROM statics WHERE subnet_id='$subnet_id'";
  mysql_query($sql);
  
  // Next, remove the acl ACL
  $sql = "DELETE FROM acl WHERE subnet_id='$subnet_id'";
  mysql_query($sql);
  
  // Lastly, remove the subnet
  $sql = "DELETE FROM subnets WHERE id='$subnet_id'";
  mysql_query($sql);
  
  $message = str_replace("%name%", "$name", $COLLATE['languages']['selected']['subnetdeleted']);
  $message = str_replace("%cidr%", "$cidr", $message);
  
  echo $message;
  exit();
  
} // Ends delete_subnet function

function search_subnets(){
  global $COLLATE;
  
  $search = (empty($_GET['search'])) ? '' : clean($_GET['search']);
  if(empty($search)) { exit(); }
  
  echo "<p><a href=\"#\" onclick=\"
         new Effect.toggle('blockspace', 'blind', { delay: 0.1 }); 
		 new Effect.toggle('spacesearch', 'blind', { delay: 0.1 })
		 \">".$COLLATE['languages']['selected']['showblockspace']."</a></p>\n".
		 "<h3>".$COLLATE['languages']['selected']['SearchIPSpace']."</h3><br />\n".
		 "<p><b>".$COLLATE['languages']['selected']['Subnet'].":</b> <input id=\"subnetsearch\" type=\"text\" value=\"$search\"><br />".
		 "<button onclick=\"new Ajax.Updater('spacesearch', '_subnets.php?op=search&amp;search=' + $('subnetsearch').value);\")\"> ".
		 $COLLATE['languages']['selected']['Go']." </button></p>";

  echo "<h4>".$COLLATE['languages']['selected']['Results'].":</h4>";
  
  if(!strstr($search, '/')){
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected']['IPSearchFormat'];
    exit();
  }
  
  list($ip,$mask) = explode('/', $search);
  
  if(ip2decimal($ip) == FALSE){
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected']['IPSearchFormat'];
    exit();
  }
  
  $ip = long2ip(ip2decimal($ip));  
  if(!strstr($mask, '.') && ($mask <= '0' || $mask >= '32')){
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected']['IPSearchFormat'];
    exit();
  }
  elseif(!strstr($mask, '.')){
    $bin = str_pad('', $mask, '1');
    $bin = str_pad($bin, '32', '0');
    $mask = bindec(substr($bin,0,8)).".".bindec(substr($bin,8,8)).".".bindec(substr($bin,16,8)).".".bindec(substr($bin,24,8));
    $mask = long2ip(ip2decimal($mask));
  }
  elseif(!checkNetmask($mask)){
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected']['invalidmask'];
    exit();
  }
	
  $long_ip = ip2decimal($ip);
  $long_mask = ip2decimal($mask);
  $long_end_ip = $long_ip | (~$long_mask);
  
  $ipspace = array();
  array_push($ipspace, $long_ip);
	
  $sql = "SELECT start_ip, end_ip FROM subnets WHERE CAST((start_ip & 0xFFFFFFFF) AS UNSIGNED) >= CAST(('$long_ip' & 0xFFFFFFFF) AS UNSIGNED) AND ".
         "CAST((end_ip & 0xFFFFFFFF) AS UNSIGNED) <= CAST(('$long_end_ip' & 0xFFFFFFFF) AS UNSIGNED) ORDER BY start_ip ASC";
  $subnet_rows = mysql_query($sql);
	
  while(list($subnet_long_start_ip,$subnet_long_end_ip) = mysql_fetch_row($subnet_rows)){
    array_push($ipspace, $subnet_long_start_ip, $subnet_long_end_ip);
  }
  array_push($ipspace, $long_end_ip);
  $ipspace = array_reverse($ipspace);
  
  $ipspace_count = count($ipspace);
  
  echo "<table width=\"100%\"><tr><th>".$COLLATE['languages']['selected']['StartingIP'].
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
  exit();
}

?>
