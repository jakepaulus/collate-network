<?php
require_once('include/common.php');
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
  
    case "toggle_stale-scan";
    toggle_stalescan();
    break;
}

function edit_static(){
  global $COLLATE;
  include 'include/validation_functions.php';

  $static_id = (empty($_GET['static_id'])) ? '' : $_GET['static_id'];
  $edit = (empty($_GET['edit'])) ? '' : $_GET['edit'];
  $edit = ($edit == 'name') ? 'staticname' : $edit;
  $value = (empty($_POST['value'])) ? '' : $_POST['value'];
  $username = (isset($COLLATE['user']['username'])) ? $COLLATE['user']['username'] : 'unknown';
  
  if(empty($static_id) || !is_numeric($static_id) || !preg_match('/staticname|contact|note/', $edit)){ 
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit();
  }
  
  $return = validate_text($value,$edit);
  if($return['0'] === false){
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected'][$return['error']];
    exit();
  }
  else{
    $value = $return['1'];
  }
  
 
  $result = mysql_query("SELECT ip, name FROM statics WHERE id='$static_id'");
  list($long_ip, $name) = mysql_fetch_row($result);
  $static_ip = long2ip($long_ip);
  
  if($edit == 'staticname'){
    AccessControl('2', "static IP $static_ip name changed from $name to $value");
    $sql = "UPDATE statics SET name='$value', modified_by='$username', modified_at=NOW() WHERE id='$static_id'";
  }
  elseif($edit == 'contact'){
    AccessControl('2', "static IP $static_ip ($name) contact edited");
    $sql = "UPDATE statics SET contact='$value', modified_by='$username', modified_at=NOW() WHERE id='$static_id'";
  }
  else{
    AccessControl('2', "static IP $static_ip ($name) note edited");
    $sql = "UPDATE statics SET note='$value', modified_by='$username', modified_at=NOW() WHERE id='$static_id'";
  }
 
  mysql_query($sql);  
  echo $value;
  exit();
} // Ends edit_static function


function edit_acl(){
  global $COLLATE;
  include 'include/validation_functions.php';
  
  $acl_id = (isset($_GET['acl_id'] && is_numeric($_GET['acl_id'])) ? $_GET['acl_id'] : '';
  $value = (isset($_POST['value'])) ? $_POST['value'] : '';
  
  if(empty($acl_id)){ 
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit();
  }
  
  $result = validate_text($value,'aclname');
  if($result['0'] === false){
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected'][$result['error']];
    exit();
  }
  else{
    $value = $result['1'];
  }
  
  $sql = "SELECT name FROM subnets WHERE id=(SELECT subnet_id FROM acl WHERE id='$acl_id')";
  $result = mysql_query($sql);
  
  if(mysql_num_rows($result) != '1'){
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit();
  }
  
  $subnet_name = mysql_result($result, 0, 0);
  
  AccessControl('3', "ACL statement name updated in $subnet_name subnet");
  $sql = "UPDATE acl SET name='$value' where id='$acl_id'";
  
  mysql_query($sql);  
  echo $value; 
  exit();
} // Ends edit_acl function


function ping_host(){
  $ip = (empty($_GET['ip'])) ? "" : escapeshellcmd($_GET['ip']);
  if(empty($ip)){ exit(); }
  
  if(!ip2decimal($ip)){ 
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit();
  }
  
  echo "<pre>";
  if (!strstr($_SERVER['DOCUMENT_ROOT'], ":")){ // *nix system
    system ("ping -A -c 3 -n -W 1 $ip");
  }
  else{ // Windows system
    system("ping -n 3 -w 500 $ip");
  }
  echo "</pre>";
  exit();
} // Ends ping_host function


function ip_guidance(){
  global $COLLATE;
  
  $subnet_id = (isset($_GET['subnet_id']) && is_numeric($_GET['subnet_id'])) ? $_GET['subnet_id'] : '';
  
  if(empty($subnet_id){ 
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit();
  }
  
  $sql = "SELECT guidance FROM subnets WHERE id='$subnet_id'";
  $result = mysql_query($sql);  
  $guidance = mysql_result($result, 0);

  if(!empty($guidance)){
    $help = $guidance;
  }
  else{ 
    $help = $COLLATE['settings']['guidance'];
  }
  
  echo "<pre><span id=\"guidance\">$help</span></pre>";
  exit();
} // Ends ip_guidance function  


function edit_guidance(){
  global $COLLATE;
  include 'include/validation_functions.php';
  
  $subnet_id = (isset($_GET['subnet_id']) && is_numeric($_GET['subnet_id'])) ? $_GET['subnet_id'] : '';
  $value = (empty($_POST['value'])) ? '' : clean($_POST['value']); # the guidance column is a longtext field, so is the settings value
  $username = (isset($COLLATE['user']['username'])) ? $COLLATE['user']['username'] : 'unknown';
  
  if(empty($subnet_id){ 
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit();
  }
      
  $sql = "SELECT name FROM subnets WHERE id='$subnet_id'";
  $result = mysql_query($sql);
  
  if(mysql_num_rows($result) != '1'){
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit();
  }
  
  $name = mysql_result($result, 0, 0);
  
  AccessControl('3', "IP Guidance edited for $name subnet");  
    
  $sql = "UPDATE subnets SET guidance='$value', modified_by='$username', modified_at=NOW() WHERE id='$subnet_id'";
  
  mysql_query($sql);
  
  if(empty($value)){
    $value = $COLLATE['settings']['guidance'];
  }
  
  echo $value;
  exit();

} // End edit_guidance function


function delete_static(){
  global $COLLATE;
  
  $long_ip = (isset($_GET['static_ip'])) ? ip2decimal($_GET['static_ip']) : '';
  
  if(empty($long_ip) || $long_ip === false){
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit();
  }
  
  $static_ip = long2ip($long_ip);
  
  $accesslevel = "2";
  $message = "Static IP deleted: $static_ip";
  AccessControl($accesslevel, $message); 

  $long_ip = ip2decimal($static_ip);
  
  $sql = "DELETE FROM statics WHERE ip='$long_ip' LIMIT 1";
  mysql_query($sql);
    
  echo $COLLATE['languages']['selected']['staticdeleted'];
  exit();  
  
} // Ends delete_static function


function delete_acl(){
  global $COLLATE;
  
  $acl_id = (isset($_GET['acl_id']) && is_numeric($_GET['acl_id'])) ? $_GET['acl_id'] : '';
  
  if(empty($acl_id)){
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit();
  }
  
  $sql = "SELECT name FROM subnets WHERE id=(SELECT subnet_id FROM acl WHERE id='$acl_id')";
  $result = mysql_query($sql);
  
  if(mysql_num_rows($result) != '1'){
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit(); 
  }
  
  $subnet_name = mysql_result($result, 0, 0);
  
  AccessControl('3', "ACL Statement #$acl_id deleted in $subnet_name subnet");
  
  $sql = "DELETE FROM acl WHERE id='$acl_id'";
  
  mysql_query($sql);
  
  echo $COLLATE['languages']['selected']['acldeleted'];

} // Ends delete_acl function


function toggle_stalescan(){
  global $COLLATE;
  
  $long_ip = (isset($_GET['static_ip'])) ? ip2decimal($_GET['static_ip']) : '';
  $toggle = (isset($_GET['toggle']) && preg_match("/on|off/", $_GET['toggle'])) ? $_GET['toggle'] : '';
  $referer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : './search.php?notice=';
  

  if(stristr($referer, 'notice')){
    $referer=preg_replace("/&notice=.*/", "", $referer);
  }
  $referer=$referer.'&notice=';

  if(empty($long_ip) || $long_ip === false || empty($toggle)){
    header("HTTP/1.1 500 Internal Error");
    $notice = 'invalidrequest'];
    header("Location: $referer"."$notice");
    exit();
  }
  
  $accesslevel = "2";
  $message = "Stale Scan toggled $toggle for IP: $static_ip";
  AccessControl($accesslevel, $message); 

  $long_ip = ip2decimal($static_ip);
  if($toggle == 'on'){
    $count='0';
    $notice='staletoggleon-notice';
  }
  else{
    $count='-1';
    $notice='staletoggleoff-notice';
  }
  
  $sql = "UPDATE statics SET failed_scans='$count' WHERE ip='$long_ip' LIMIT 1";
  mysql_query($sql);

  header("Location: $referer"."$notice");
  exit();
  
} // Ends delete_static function

?>