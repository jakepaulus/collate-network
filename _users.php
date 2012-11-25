<?php

require_once('include/common.php');
$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

$username = (isset($_GET['username'])) ? clean($_GET['username']) : '';
if(empty($username)) {
  header("HTTP/1.1 500 Internal Error");
  echo $COLLATE['languages']['selected']['invalidrequest'];
  exit();
}
$sql = "select count(*) from users where username='$username'";
$count = mysql_result(mysql_query($sql), 0);
if($count != '1'){ 
  header("HTTP/1.1 500 Internal Error");  
  echo $COLLATE['languages']['selected']['invalidrequest'];
  exit();
}

switch($op){
	
	case "deleteuser";
	AccessControl('5', null);
	delete_user();
	break;
	
	case "editphone";
	edit_phone();
	break;
	
	case "editemail";
	edit_email();
	break;
	
	case "editlanguage";
	edit_language();
	break;
	
	case "editperms";
	AccessControl('5', null);
	edit_perms();
	break;
	
	case "resetpasswd";
	AccessControl('5', null);
	reset_passwd();
	break;
	
	case "ldapexempt";
	AccessControl('5', null);
	set_ldapexempt();
	break;
	
	case "lock";
	AccessControl('5', null);
	set_lock();
	break;
	
	default:
	exit();
}

function delete_user() {
  global $COLLATE;
  global $username;
  
  collate_log('5', "User deleted: $username");
  
  $sql = "DELETE FROM users WHERE username='$username'";
  $result = mysql_query($sql);
  
  $message = str_replace("%username%", "$username", $COLLATE['languages']['selected']['userdeleted']);
  echo $message;
  
  exit();
} // Ends delete_user function

function edit_phone(){
  global $COLLATE;
  global $username;
  
  if (isset($COLLATE['user']['username']) && $COLLATE['user']['username'] == $username){
    // User is editing their own profile
	$accesslevel='3';
  }
  elseif($COLLATE['user']['accesslevel'] >= '5' || $COLLATE['settings']['perms'] > '5') {
    // User is allowed to edit other user's info
	$accesslevel='5';
  }
  else{
    // Access denied
    header("HTTP/1.1 500 Internal Error");
	echo $COLLATE['languages']['selected']['noperms'];
	exit();
  }
  
  $phone = (isset($_POST['value'])) ? clean($_POST['value']) : "";

  $sql = "select phone,email from users where username='$username'";
  list($old_phone,$old_email) = mysql_fetch_row(mysql_query($sql));
  
  if(empty($phone) && empty($old_email)){
    // must supply one form of contact
	header("HTTP/1.1 500 Internal Error");
	echo $COLLATE['languages']['selected']['onecontact'];
	exit();	
  }
  
  $message="User Updated: telephone number changed from $old_phone to $phone";
  collate_log($accesslevel, $message);
  
  $sql = "update users set phone='$phone' where username='$username'";
  $result = mysql_query($sql);
  echo $phone;
  exit();
}

function edit_email(){
  global $COLLATE;
  global $username;
  
  if (isset($COLLATE['user']['username']) && $COLLATE['user']['username'] == $username){
    // User is editing their own profile
	$accesslevel='3';
  }
  elseif($COLLATE['user']['accesslevel'] >= '5' || $COLLATE['settings']['perms'] > '5') {
    // User is allowed to edit other user's info
	$accesslevel='5';
  }
  else{
    // Access denied
    header("HTTP/1.1 500 Internal Error");
	echo $COLLATE['languages']['selected']['noperms'];
	exit();
  }
  
  $email = (isset($_POST['value'])) ? clean($_POST['value']) : "";

  $sql = "select phone,email from users where username='$username'";
  list($old_phone,$old_email) = mysql_fetch_row(mysql_query($sql));
  
  if(empty($old_phone) && empty($email)){
    // must supply one form of contact
	header("HTTP/1.1 500 Internal Error");
	echo $COLLATE['languages']['selected']['onecontact'];
	exit();	
  }
  
  $message="User Updated: email address changed from $old_email to $email";
  collate_log($accesslevel, $message);
  
  $sql = "update users set email='$email' where username='$username'";
  $result = mysql_query($sql);
  echo $email;
  exit();
}

function edit_language(){
  global $COLLATE;
  global $username;
  $language = (isset($_GET['language'])) ? $_GET['language'] : '';
  
  if (isset($COLLATE['user']['username']) && $COLLATE['user']['username'] == $username){
    // User is editing their own profile
	$accesslevel='3';
  }
  elseif($COLLATE['user']['accesslevel'] >= '5' || $COLLATE['settings']['perms'] > '5') {
    // User is allowed to edit other user's info
	$accesslevel='5';
  }
  else{
    // Access denied
    header("HTTP/1.1 500 Internal Error");
	echo $COLLATE['languages']['selected']['noperms'];
	exit();
  }
  
  foreach (glob("languages/*.php") as $filename){
    include $filename;
  }
  if(!isset($languages[$language]['isocode']) || $language != $languages[$language]['isocode'] || empty($language)){
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected']['invalidrequest'];
	exit(); 
  } 

  $sql = "select language from users where username='$username'";
  $old_language = mysql_result(mysql_query($sql), 0);
  
  if($language == $old_language){ exit(); }
  
  $message = "User Updated: $username\'s preferred language changed from ".$languages[$old_language]['languagename']." to ".$languages[$language]['languagename'];
  collate_log($accesslevel, $message);
  
  if(isset($COLLATE['user']['username']) && $username == $COLLATE['user']['username']){
    $_SESSION['language']=$language;
  }
  
  $sql = "update users set language='$language' where username='$username'";
  mysql_query($sql);
  echo $COLLATE['languages']['selected']['settingupdated'];
  exit();
}

function edit_perms(){
  global $COLLATE;
  global $username;
  
  if(isset($COLLATE['user']['username']) && $COLLATE['user']['username'] == $username){
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected']['invalidrequest'];
	exit();
  }

  if (preg_match('/[012345]{1}/', $_GET['accesslevel'])){
    $accesslevel = $_GET['accesslevel'];
  }
  else {
    header("HTTP/1.1 500 Internal Error");
    echo $COLLATE['languages']['selected']['invalidrequest'];
	exit();
  }
  
  $sql = "select accesslevel from users where username='$username'";
  $old_accesslevel = mysql_result(mysql_query($sql), 0);
  
  if($old_accesslevel == $accesslevel){ exit(); }

  $sql = "UPDATE users SET accesslevel='$accesslevel' WHERE username='$username'";
  mysql_query($sql);
  
  $changed = ($old_accesslevel > $accesslevel) ? "decreased" : "increased";
  
  $old_accesslevel = ($old_accesslevel === '0') ? "None" : $old_accesslevel;
  $old_accesslevel = ($old_accesslevel === '1') ? "Read-Only" : $old_accesslevel;
  $old_accesslevel = ($old_accesslevel === '2') ? "Reserve IPs" : $old_accesslevel;
  $old_accesslevel = ($old_accesslevel === '3') ? "Allocate Subnets" : $old_accesslevel;
  $old_accesslevel = ($old_accesslevel === '4') ? "Allocate Blocks" : $old_accesslevel;
  $old_accesslevel = ($old_accesslevel === '5') ? "Admin" : $old_accesslevel;
  
  $accesslevel = ($accesslevel === '0') ? "None" : $accesslevel;
  $accesslevel = ($accesslevel === '1') ? "Read-Only" : $accesslevel;
  $accesslevel = ($accesslevel === '2') ? "Reserve IPs" : $accesslevel;
  $accesslevel = ($accesslevel === '3') ? "Allocate Subnets" : $accesslevel;
  $accesslevel = ($accesslevel === '4') ? "Allocate Blocks" : $accesslevel;
  $accesslevel = ($accesslevel === '5') ? "Admin" : $accesslevel;

  echo $COLLATE['languages']['selected']['settingupdated'];
  collate_log('5', "User Updated: access level for $username $changed from $old_accesslevel to $accesslevel");
  exit();
}

function reset_passwd(){
  global $username;
  $tmppasswd = (isset($_POST['value']) && !empty($_POST['value'])) ? sha1(clean($_POST['value'])) : "";
  
  if(empty($tmppasswd)){ exit(); }
  
  $sql = "update users set tmppasswd='$tmppasswd' where username='$username'";
  mysql_query($sql);
  collate_log('5', "User Updated: Temporary password set for $username");
  echo "[".$COLLATE['languages']['selected']['PasswordSet']."]";
  exit();
}

function set_ldapexempt(){
  global $username;
  global $COLLATE;
  $ldapexempt = (isset($_GET['ldapexempt']) && preg_match("/true|false/", $_GET['ldapexempt'])) ? $_GET['ldapexempt'] : "";
  if(empty($ldapexempt)){
    header("HTTP/1.1 500 Internal Error");
	echo $COLLATE['languages']['selected']['invalidrequest'];
	exit();
  }
  $ldapexempt = ($locked === 'true') ? true : false;
  
  $sql = "select ldapexempt from users where username='$username'";
  $old_ldapexempt = mysql_result(mysql_query($sql), 0);
  
  if($old_ldapexempt == $ldapexempt){ exit(); }
  
  $sql = "update users set ldapexempt=$ldapexempt where username='$username'";
  mysql_query($sql);
  
  if($old_ldapexempt){
    $message = "User Updated: $username uses default authentication setting.";
  }
  else{
    $message = "User Updated: $username is now forced to use database authentication.";
  }
  
  collate_log('5', $message);
  echo $COLLATE['languages']['selected']['settingupdated'];
  exit();
}

function set_lock(){
  global $username;
  global $COLLATE;
  $locked = (isset($_GET['locked']) && preg_match("/true|false/", $_GET['locked'])) ? $_GET['locked'] : "";
  if(empty($locked)){
    header("HTTP/1.1 500 Internal Error");
	echo $COLLATE['languages']['selected']['invalidrequest'];
	exit();
  }
  $locked = ($locked === 'true') ? true : false;
  
  $sql = "select loginattempts from users where username='$username'";
  $loginattempts = mysql_result(mysql_query($sql), 0);
  
  if($locked){ // We're trying to lock the account
    if($loginattempts >= $COLLATE['settings']['loginattempts']){ // It's already locked
	  exit();
	}
	else {
	  $sql = "update users set loginattempts='9' where username='$username'";
	  $log_message = "User Updated: $username account is now locked.";
	}
  }
  else { // We're trying to unlock the account
    if($loginattempts < $COLLATE['settings']['loginattempts']){ // It isn't locked
	  exit();
	}
	else {
	  $sql = "update users set loginattempts='0' where username='$username'";
	  $log_message = "User Updated: $username account is no longer locked.";
	}
  }
  
  mysql_query($sql);
   
  collate_log('5', $log_message);
  echo $COLLATE['languages']['selected']['settingupdated'];
  exit();
}

?>