<?php
require_once('include/common.php');

# Some functions in this script require higher privileges, but this 
# should catch users not logged in for many cases.
AccessControl('2', null, false); # null means no log, false means don't redirect

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){

    case "edit";
    edit_static();
    break;
    
    case "edit_acl";
	AccessControl('3', null, false); # null means no log, false means don't redirect
    edit_acl();
    break;
    
    case "ping";
    ping_host();
    break;
    
    case "guidance";
    ip_guidance();
    break;
    
    case "edit_guidance";
	AccessControl('3', null, false); # null means no log, false means don't redirect
    edit_guidance();
    break;
    
    case "delete";
    delete_static();
    break;
    
    case "delete_acl";
	AccessControl('3', null, false); # null means no log, false means don't redirect
    delete_acl();
    break;
  
    case "toggle_stale-scan";
    toggle_stalescan();
    break;
}

function edit_static(){
  global $COLLATE;
  $dbo = getdbo();
  include 'include/validation_functions.php';

  $static_id = (empty($_GET['static_id'])) ? '' : $_GET['static_id'];
  $edit = (empty($_GET['edit'])) ? '' : $_GET['edit'];
  $edit = ($edit == 'name') ? 'staticname' : $edit;
  $value = (empty($_POST['value'])) ? '' : $_POST['value'];
  $username = (isset($COLLATE['user']['username'])) ? $COLLATE['user']['username'] : 'unknown';
  
  if(empty($static_id) || !is_numeric($static_id) || !preg_match('/staticname|contact|note/', $edit)){ 
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit();
  }
  
  $return = validate_text($value,$edit);
  if($return['0'] === false){
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected'][$return['error']];
    exit();
  }
  else{
    $value = $return['1'];
  }
  
 
  $result = $dbo -> query("SELECT ip, name FROM statics WHERE id='$static_id'");
  list($long_ip, $name) = $result -> fetch(PDO::FETCH_NUM);
  $static_ip = long2ip($long_ip);
  
  if($edit == 'staticname'){
    collate_log('2', "static IP $static_ip name changed from $name to $value");
    $sql = "UPDATE statics SET name='$value', modified_by='$username', modified_at=NOW() WHERE id='$static_id'";
  }
  elseif($edit == 'contact'){
    collate_log('2', "static IP $static_ip ($name) contact edited");
    $sql = "UPDATE statics SET contact='$value', modified_by='$username', modified_at=NOW() WHERE id='$static_id'";
  }
  else{
    collate_log('2', "static IP $static_ip ($name) note edited");
    $sql = "UPDATE statics SET note='$value', modified_by='$username', modified_at=NOW() WHERE id='$static_id'";
  }
 
  $dbo -> query($sql);  
  echo $value;
  exit();
} // Ends edit_static function


function edit_acl(){
  global $COLLATE;
  $dbo = getdbo();
  include 'include/validation_functions.php';
  
  $acl_id = (isset($_GET['acl_id']) && is_numeric($_GET['acl_id'])) ? $_GET['acl_id'] : '';
  $value = (isset($_POST['value'])) ? $_POST['value'] : '';
  
  if(empty($acl_id)){ 
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit();
  }
  
  $result = validate_text($value,'aclname');
  if($result['0'] === false){
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected'][$result['error']];
    exit();
  }
  else{
    $value = $result['1'];
  }
  
  $sql = "SELECT name FROM subnets WHERE id=(SELECT subnet_id FROM acl WHERE id='$acl_id')";
  $result = $dbo -> query($sql);
  
  if($result -> rowCount() != '1'){
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit();
  }
  
  $subnet_name = $result -> fetchColumn();
  
  collate_log('3', "ACL statement name updated in $subnet_name subnet");
  $sql = "UPDATE acl SET name='$value' where id='$acl_id'";
  
  $dbo -> query($sql);  
  echo $value; 
  exit();
} // Ends edit_acl function


function ping_host(){
  $ip = (empty($_GET['ip'])) ? "" : escapeshellcmd($_GET['ip']);
  if(empty($ip)){ exit(); }
  
  if(!ip2decimal($ip)){ 
    header("HTTP/1.1 400 Bad Request");
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
  $dbo = getdbo();
  
  $subnet_id = (isset($_GET['subnet_id']) && is_numeric($_GET['subnet_id'])) ? $_GET['subnet_id'] : '';
  
  if(empty($subnet_id)){ 
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit();
  }
  
  $sql = "SELECT guidance FROM subnets WHERE id='$subnet_id'";
  $result = $dbo -> query($sql);  
  $guidance = $result -> fetchColumn();

  if(!empty($guidance)){
    $help = $guidance;
  }
  else{ 
    $help = $COLLATE['settings']['guidance'];
  }
  
  echo "<pre><span id=\"guidance\">$help</span></pre>";
  if($COLLATE['user']['accesslevel'] >= '3' || $COLLATE['settings']['perms'] > '3'){
    echo "<script type=\"text/javascript\"><!--\n".
           "  new Ajax.InPlaceEditorWithEmptyText('guidance', '_statics.php?op=edit_guidance&subnet_id=$subnet_id',
              {
              clickToEditText: '".$COLLATE['languages']['selected']['ClicktoEdit']."',
              highlightcolor: '#a5ddf8', rows: '7', cols: '49',
              callback:
                function(form) {
                  new Element.update('notice', '');
                  return Form.serialize(form);
                },
               onFailure: 
                function(transport) {
                  new Element.update('notice', transport.responseText.stripTags());
                }}
              );
          --></script>";
  }
  exit();
} // Ends ip_guidance function  


function edit_guidance(){
  global $COLLATE;
  $dbo = getdbo();
  include 'include/validation_functions.php';
  
  $subnet_id = (isset($_GET['subnet_id']) && is_numeric($_GET['subnet_id'])) ? $_GET['subnet_id'] : '';
  $value = (empty($_POST['value'])) ? '' : clean($_POST['value']); # the guidance column is a longtext field, so is the settings value
  $username = (isset($COLLATE['user']['username'])) ? $COLLATE['user']['username'] : 'unknown';
  
  if(empty($subnet_id)){ 
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit();
  }
      
  $sql = "SELECT name FROM subnets WHERE id='$subnet_id'";
  $result = $dbo -> query($sql);
  
  if($result -> rowCount() != '1'){
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit();
  }
  
  $name = $result -> fetchColumn();
  
  collate_log('3', "IP Guidance edited for $name subnet");  
    
  $sql = "UPDATE subnets SET guidance='$value', modified_by='$username', modified_at=NOW() WHERE id='$subnet_id'";
  
  $dbo -> query($sql);
  
  if(empty($value)){
    $value = $COLLATE['settings']['guidance'];
  }
  
  echo $value;
  exit();

} // End edit_guidance function


function delete_static(){
  global $COLLATE;
  $dbo = getdbo();
  
  $long_ip = (isset($_GET['static_ip'])) ? ip2decimal($_GET['static_ip']) : '';
  
  if(empty($long_ip) || $long_ip === false){
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit();
  }
  
  $static_ip = long2ip($long_ip);
  $long_ip = ip2decimal($static_ip);
  
  collate_log('2', "Static IP deleted: $static_ip");
  $sql = "DELETE FROM statics WHERE ip='$long_ip' LIMIT 1";
  $dbo -> query($sql);
  
  // no success message. the user sees the static entry in the table fade away as feedback
   
  exit();  
  
} // Ends delete_static function


function delete_acl(){
  global $COLLATE;
  $dbo = getdbo();
  
  $acl_id = (isset($_GET['acl_id']) && is_numeric($_GET['acl_id'])) ? $_GET['acl_id'] : '';
  
  if(empty($acl_id)){
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit();
  }
  
  $sql = "SELECT name FROM subnets WHERE id=(SELECT subnet_id FROM acl WHERE id='$acl_id')";
  $result = $dbo -> query($sql);
  
  if($result -> rowCount() != '1'){
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected']['invalidrequest'];
    exit(); 
  }
  
  $subnet_name = $result -> fetchColumn();
  
  collate_log('3', "ACL Statement #$acl_id deleted in $subnet_name subnet");
  
  $sql = "DELETE FROM acl WHERE id='$acl_id'";  
  $dbo -> query($sql);
  exit();
} // Ends delete_acl function


function toggle_stalescan(){
  global $COLLATE;
  global $dbo;
  
  $static_ip = (isset($_GET['static_ip'])) ? $_GET['static_ip'] : '';
  $long_ip = ip2decimal($static_ip);
  $referer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : './search.php?notice=';
  

  if(stristr($referer, 'notice')){
    $referer=preg_replace("/&notice=.*/", "", $referer);
  }
  $referer=$referer.'&notice=';

  if(empty($long_ip) || $long_ip === false){
    header("HTTP/1.1 400 Bad Request");
	echo "test1";
    exit();
  }
  
  # make sure we aren't being asked to toggle for a subnet that has stale scan disabled:
  $sql = "SELECT stalescan_enabled FROM subnets WHERE 
    CAST('$long_ip' & 0xFFFFFFFF AS UNSIGNED) & CAST(mask & 0xFFFFFFFF AS UNSIGNED) = CAST(start_ip & 0xFFFFFFFF AS UNSIGNED)";
  $result = $dbo -> query($sql);
  $subnet_status = $result -> fetchColumn();
  if($subnet_status == false){
	header("HTTP/1.1 400 Bad Request");
    exit();
  }
  
  $sql = "SELECT failed_scans from statics where ip='$long_ip'";
  $result = $dbo -> query($sql);
  $current_count = $result -> fetchColumn();
  if($current_count == -1){
	$new_status = 'on';
	$new_count = 0;
	$new_icon = 'scanning.png';
	$new_icon_text = $COLLATE['languages']['selected']['disablestalescan'];
  }
  else{
    $new_status = 'off';
	$new_count = -1;
	$new_icon = 'skipping.png';
	$new_icon_text = $COLLATE['languages']['selected']['enablestalescan'];
  }
  
  collate_log('2', "Stale Scan toggled $new_status for IP: $static_ip"); 
  
  $sql = "UPDATE statics SET failed_scans='$new_count' WHERE ip='$long_ip' LIMIT 1";
  $dbo -> query($sql);

  echo "<img src=\"./images/$new_icon\" alt=\"\" title=\"$new_icon_text\" />";
  exit();
  
} // Ends delete_static function

?>