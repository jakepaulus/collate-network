<?php

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){

	case "edit";
	edit_static();
	break;
	
	case "edit_acl";
	edit_acl();
	break;
	
	case "ping";
	ping_host();
	break;
	
	case "guidance";
	ip_guidance();
	break;
	
	case "edit_guidance";
	edit_guidance();
	break;
	
	case "delete";
	delete_static();
	break;
	
	case "delete_acl";
	delete_acl();
	break;
}

function edit_static(){
  require_once('include/common.php');

// EditInPlace POSTS form value as: $_POST['value']

  $static_id = (empty($_GET['static_id'])) ? '' : clean($_GET['static_id']);
  $edit = (empty($_GET['edit'])) ? '' : clean($_GET['edit']);
  $value = (empty($_POST['value'])) ? '' : clean($_POST['value']);
  
  if(empty($static_id) || empty($edit)){ 
    header("HTTP/1.1 500 Internal Error");
	echo "Please select a static reservation to edit.";
	exit();
  }
  elseif(($edit == 'name' || $edit == 'contact') && strlen($value) < '3'){
    header("HTTP/1.1 500 Internal Error");
	echo "This field is required and must be three characters or longer.";
	exit();
  }
 
  $result = mysql_query("SELECT ip, name FROM statics WHERE id='$static_id'");
  list($long_ip, $name) = mysql_fetch_row($result);
  $static_ip = long2ip($long_ip);
  
  if($edit == 'name'){
    AccessControl('2', "static IP $static_ip name changed from $name to $value");
	$sql = "UPDATE statics SET name='$value' WHERE id='$static_id'";
  }
  elseif($edit == 'contact'){
    AccessControl('2', "static IP $static_ip ($name) contact edited");
	$sql = "UPDATE statics SET contact='$value' WHERE id='$static_id'";
  }
  elseif($edit == 'note'){
    AccessControl('2', "static IP $static_ip ($name) note edited");
	$sql = "UPDATE statics SET note='$value' WHERE id='$static_id'";
  }
  else{
    return;
  }
 
  mysql_query($sql);
  
  echo $value;
} // Ends edit_static function


function edit_acl(){
  require_once('include/common.php');
  
// EditInPlace POSTS form value as: $_POST['value']

  $acl_id = (empty($_GET['acl_id'])) ? '' : clean($_GET['acl_id']);
  $edit = (empty($_GET['edit'])) ? '' : clean($_GET['edit']);
  $value = (empty($_POST['value'])) ? '' : clean($_POST['value']);
  
  if(empty($acl_id) || empty($edit)){ return; }
  
  if(empty($value)){
    header("HTTP/1.1 500 Internal Error"); // Tells Ajax.InPlaceEditor that an error has occured.
	echo "You must supply a name for each ACL statement.";  
  }
  
  $sql = "SELECT name FROM subnets WHERE id=(SELECT apply FROM acl WHERE id='$acl_id')";
  $result = mysql_query($sql);
  
  if(mysql_num_rows($result) != '1'){
    header("HTTP/1.1 500 Internal Error"); // Tells Ajax.InPlaceEditor that an error has occured.
	echo "An error has occured. Please contact your administrator if the problem persists.";  
  }
  
  $subnet_name = mysql_result($result, 0, 0);
  
  if($edit == 'name'){
    AccessControl('3', "ACL statement name updated in $subnet_name subnet");
	$sql = "UPDATE acl SET name='$value' where id='$acl_id'";
  }
  else{
    return;
  }
  
  mysql_query($sql);
  
  echo $value; 
} // Ends edit_acl function


function ping_host(){
  $ip = escapeshellcmd($_GET['ip']);
  
  // This prevents someone from passing extra parameters to ping that could be dangerous e.g. DoS the server or use the server to DoS a host....
  if(!ereg("^([1-9][0-9]{0,2})+\.([1-9][0-9]{0,2})+\.([1-9][0-9]{0,2})+\.([1-9][0-9]{0,2})+$", $ip)){ return; }
  
  echo "<pre>";
  if (!strstr($_SERVER['DOCUMENT_ROOT'], ":")){ // *nix system
    system ("ping -c 4 -n $ip");
  }
  else{ // Windows Server
    system("ping -n 4 -w 100 $ip");
  }
  echo "</pre>";
} // Ends ping_host function


function ip_guidance(){
  require_once('include/common.php');
  
  $subnet_id = $_GET['subnet_id'];
  
  if(!is_numeric($subnet_id)){ return; }
  
  $sql = "SELECT guidance FROM subnets WHERE id='$subnet_id'";
  $result = mysql_query($sql);
  
  list($guidance) = mysql_fetch_row($result);

  if(empty($guidance) && empty($COLLATE['settings']['guidance'])){
    $help =  "<p>Sorry, there is no guidance available. This data can be input when allocating or editing a subnet. Default
	     guidance information can be input into the settings page by an administrator.</p>";
  }
  elseif(!empty($guidance)){
	$help = $guidance;
  }
  else{ 
    $help = $COLLATE['settings']['guidance'];
  }
  
  echo "<span id=\"guidance\">$help</span>";
  
} // Ends ip_guidance function  


function edit_guidance(){

  require_once('include/common.php');
  
  $subnet_id = (empty($_GET['subnet_id'])) ? '' : clean($_GET['subnet_id']);
  $value = (empty($_POST['value'])) ? '' : nl2br(clean($_POST['value']));
  
  if(empty($subnet_id) || empty($value)){ 
    header("HTTP/1.1 500 Internal Error"); // Tells Ajax.InPlaceEditor that an error has occured.
	echo "An error has occured. Please contact your administrator if the problem persists.";  
  }
  
  $sql = "SELECT name FROM subnets WHERE id='$subnet_id'";
  $result = mysql_query($sql);
  
  if(mysql_num_rows($result) != '1'){
    header("HTTP/1.1 500 Internal Error"); // Tells Ajax.InPlaceEditor that an error has occured.
	echo "An error has occured. Please contact your administrator if the problem persists.";  
  }
  
  $name = mysql_result($result, 0, 0);
  
  AccessControl('3', "IP Guidance edited for $name subnet");  
    
  $sql = "UPDATE subnets SET guidance='$value' WHERE id='$subnet_id'";
  
  mysql_query($sql);
  
  echo $value;
  exit;

} // End edit_guidance function


function delete_static(){
  require_once('include/common.php');
  
  $static_ip = (empty($_GET['static_ip'])) ? '' : clean($_GET['static_ip']);

  if(empty($static_ip) || !long2ip($static_ip)){
    header("HTTP/1.1 500 Internal Error");
    echo "The static IP you tried to delete is not valid.";
	exit();
  }
  
  $accesslevel = "2";
  $message = "Static IP deleted: $static_ip";
  AccessControl($accesslevel, $message); 

  $long_ip = ip2long($static_ip);
  
  $sql = "DELETE FROM statics WHERE ip='$long_ip' LIMIT 1";
  mysql_query($sql);
    
  echo "The static IP has been successfully deleted.";  
  
} // Ends delete_static function


function delete_acl(){
  require_once('include/common.php');
  
  $acl_id = (empty($_GET['acl_id'])) ? '' : clean($_GET['acl_id']);
  
  if(empty($acl_id)){
    header("HTTP/1.1 500 Internal Error");
	echo "Please select an ACL Statement to delete.";
  }
  
  $sql = "SELECT name FROM subnets WHERE id=(SELECT apply FROM acl WHERE id='$acl_id')";
  $result = mysql_query($sql);
  
  if(mysql_num_rows($result) != '1'){
    header("HTTP/1.1 500 Internal Error"); // Tells Ajax.InPlaceEditor that an error has occured.
	echo "An error has occured. Please contact your administrator if the problem persists.";  
  }
  
  $subnet_name = mysql_result($result, 0, 0);
  
  AccessControl('3', "ACL Statement #$acl_id deleted in $subnet_name subnet");
  
  $sql = "DELETE FROM acl WHERE id='$acl_id'";
  
  mysql_query($sql);
  
  echo "The ACL Statement you selected has been deleted.";

} // Ends delete_acl function


?>