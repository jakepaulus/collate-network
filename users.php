<?php
/**
 * Please see /include/common.php for documentation on common.php, the $COLLATE global array used by this program, and the AccessControl function used widely.
 */
require_once('./include/common.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){

	case "add";
	user_form();
	break;
		
	case "submit";
	submit_user();
	break;
		
	case "edit"; 
	user_form();
	break;
		
	default:
	AccessControl("1", null);
	list_users();
	break;
	
}

require_once('./include/footer.php');

function list_users(){
  global $COLLATE;
  require_once('./include/header.php');
  
  $sql = "SELECT username, phone, email, last_login_at FROM users ORDER BY username"; 
  $result = mysql_query($sql);
    
  echo "<h1>".$COLLATE['languages']['selected']['Users']."</h1>\n".
       "<p style=\"text-align: right;\"><a href=\"users.php?op=add\">".
	   "<img src=\"images/add.gif\" alt=\"\" /> ".$COLLATE['languages']['selected']['AddaUser']." </a></p>";

  if (mysql_num_rows($result) == '0'){
    echo "<br /><p>".$COLLATE['languages']['selected']['nousers']."</p>";
  }
  else {  
	   
    echo "<table width=\"100%\"><tr>".
	     "<th>".$COLLATE['languages']['selected']['Username']."</th>".
		 "<th>".$COLLATE['languages']['selected']['Telephone']."</th>".
		 "<th>".$COLLATE['languages']['selected']['Email']."</th>".
		 "<th>".$COLLATE['languages']['selected']['LastLogin']."</th></tr>".
	     "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>";
	     
    while(list($username,$phone,$email,$lastlogin) = mysql_fetch_row($result)){
      echo "<tr id=\"user_${username}_row_1\"><td>$username</td><td>$phone</td><td>$email</td><td>$lastlogin</td>
	       <td style=\"text-align: right;\">\n";
	  if ($COLLATE['user']['accesslevel'] == '5' || $COLLATE['settings']['perms'] > '5') {
	    echo "<a href=\"users.php?op=edit&username=$username\">".
			 "<img src=\"./images/modify.gif\" alt=\"edit\" title=\"".$COLLATE['languages']['selected']['EditUser']."\" /></a>
			 &nbsp; <a href=\"#\" onclick=\"
		      if (confirm('".$COLLATE['languages']['selected']['confirmdelete']."')) {
                new Element.update('notice', ''); 
				new Ajax.Updater('notice', '_users.php?op=deleteuser&amp;username=$username', {
				  onSuccess:function(){ new Effect.Parallel([new Effect.Fade('user_${username}_row_1'), new Effect.Fade('user_${username}_row_2')]); }
				});
               };
			   return false;
			   \"><img src=\"./images/remove.gif\" alt=\"X\" title=\"".$COLLATE['languages']['selected']['DeleteUser']."\" /></a>";
	  }
	  echo "</td></tr>".
	       "<tr id=\"user_${username}_row_2\"><td colspan=\"5\"><hr class=\"division\" /></td></tr>";
    }
    
    echo "</table>";
  }

} // Ends list_users function

function user_form(){
  global $COLLATE;
  global $op;
  
  $username = (isset($_GET['username'])) ? $_GET['username'] : '';
  $phone = (isset($_GET['phone'])) ? $_GET['phone'] : '';
  $email = (isset($_GET['email'])) ? $_GET['email'] : '';
  $ldapexempt = (isset($_GET['ldapexempt'])) ? $_GET['ldapexempt'] : '0';
  $accesslevel = '0';
  $loginattempts = '';
  $language = $COLLATE['languages']['selected']['isocode'];
 
  if(isset($COLLATE['user']['username']) && $COLLATE['user']['username'] == $username){
    AccessControl('0', null); # users can edit their own profile
  }
  else{
    AccessControl('5', null);
  }
  
  if($op == 'edit'){
    $sql = "SELECT accesslevel, phone, email, loginattempts, ldapexempt, language FROM users WHERE username='$username'";
    $result = mysql_query($sql);
    if(mysql_num_rows($result) != '1'){
      $notice = $COLLATE['languages']['selected']['invalidrequest'];
      header("Location: users.php?notice=$notice");
      exit();
    }
    list($accesslevel,$phone,$email,$loginattempts,$ldapexempt,$current_language) = mysql_fetch_row($result);
	$title = $COLLATE['languages']['selected']['EditUser'].": $username";
	$action_url = 'users.php?op=submit&edit=true';
  }
  else{
    $title = $COLLATE['languages']['selected']['AddaUser'];
	$action_url = 'users.php?op=submit';
  }
  
  require_once('./include/header.php');
  
  echo "<h1>$title</h1>\n".
       "<br />\n<form action=\"$action_url\" method=\"post\">\n";
	   
  if($op !== 'edit'){
    echo "<p><b>".$COLLATE['languages']['selected']['Username'].":</b> <input name=\"username\" type=\"text\" value=\"$username\" /></p>\n";
  }
  else{
    echo "<input type=\"hidden\" name=\"username\" value=\"$username\">";
  }
  
  echo "<p><b>".$COLLATE['languages']['selected']['Telephone'].":</b> <input name=\"phone\" type=\"text\" value=\"$phone\" /></p>\n".
       "<p><b>".$COLLATE['languages']['selected']['Email'].":</b> <input name=\"email\" type=\"text\" value=\"$email\" /></p>\n";
	   
  foreach (glob("languages/*.php") as $filename){
    include $filename;
  }
  echo "<p><b>".$COLLATE['languages']['selected']['PreferredLanguage'].":</b> <select name=\"languages\" onchange=\"
        new Ajax.Updater('generalnotice', '_settings.php?op=updatelanguage&amp;language=' + this.value);\">";
  foreach ($languages as $language){
    if($current_language == $language['isocode']){
      $selected = "selected=\"selected\"";
    }
    else {
      $selected = "";
    }
    echo "<option value=\"".$language['isocode']."\" $selected/> ".$language['languagename']." </option>\n";
  }
  echo "</select></p>";  
  
  if(($COLLATE['user']['accesslevel'] == '5' || $COLLATE['settings']['perms'] > '5') && $username !== $_SESSION['username']) {
    echo "<p><b>".$COLLATE['languages']['selected']['UserAccessLevel'].":</b><br />\n";
	
    $checked0 = ($accesslevel == '0') ? "checked=\"checked\"" : '';
    $checked1 = ($accesslevel == '1') ? "checked=\"checked\"" : '';
    $checked2 = ($accesslevel == '2') ? "checked=\"checked\"" : '';
    $checked3 = ($accesslevel == '3') ? "checked=\"checked\"" : '';
    $checked4 = ($accesslevel == '4') ? "checked=\"checked\"" : '';
    $checked5 = ($accesslevel == '5') ? "checked=\"checked\"" : '';
     
    echo "<input type=\"radio\" name=\"perms\" $checked0 value=\"0\" />".$COLLATE['languages']['selected']['None']."<br />\n".
         "<input type=\"radio\" name=\"perms\" $checked1 value=\"1\" />".$COLLATE['languages']['selected']['ReadOnly']."<br />\n".
         "<input type=\"radio\" name=\"perms\" $checked2 value=\"2\" />".$COLLATE['languages']['selected']['ReserveIPs']."<br />\n".
         "<input type=\"radio\" name=\"perms\" $checked3 value=\"3\" />".$COLLATE['languages']['selected']['AllocateSubnets']."<br />\n".
         "<input type=\"radio\" name=\"perms\" $checked4 value=\"4\" />".$COLLATE['languages']['selected']['AllocateBlocks']."<br />\n".
         "<input type=\"radio\" name=\"perms\" $checked5 value=\"5\" />".$COLLATE['languages']['selected']['Admin']."<br />\n".
         "</p>\n";
  }
	   
  if (isset($COLLATE['user']['username']) && $COLLATE['user']['username'] == $username){ // change password
    echo "<p><b><a href=\"login.php?op=changepasswd\">".$COLLATE['languages']['selected']['changeyourpassword']."</a></b></p>\n"; 
  }
  elseif ($COLLATE['user']['accesslevel'] == '5' || $COLLATE['settings']['perms'] > '5') {
    echo "<p><b>".$COLLATE['languages']['selected']['SetTempPass'].":</b>
	     <input id=\"tmppasswd\" name=\"tmppasswd\" type=\"text\" size=\"15\" /></p>\n";

    $ldapexempt = ($ldapexempt) ? "checked=\"checked\"" : "";
    $locked = ($loginattempts >= $COLLATE['settings']['loginattempts']) ? "checked=\"checked\"" : "";
    
    echo "<p><input type=\"checkbox\" name=\"ldapexempt\" $ldapexempt /> ".$COLLATE['languages']['selected']['Forcedbauth']."</p>\n".
         "<p><input type=\"checkbox\" name=\"locked\" $locked /> ".$COLLATE['languages']['selected']['Lockaccount']."<br /></p>";
  }
  
  echo "<input type=\"submit\" value=\" ".$COLLATE['languages']['selected']['Go']." \" /></p>\n".
       "</form>";

} // Ends add_user_form function

function submit_user(){
  global $COLLATE;
  include 'include/validation_functions.php';
  
 
  # validations are organized by all checks that don't require db lookups, then all that do
  # in the order that the vars are listed below
  
  $username = (isset($_POST['username'])) ? $_POST['username'] : '';
  $tmppasswd = (isset($_POST['tmppasswd']) && !empty($_POST['tmppasswd'])) ? sha1(clean($_POST['tmppasswd'])) : '';
  $phone = (isset($_POST['phone'])) ? $_POST['phone'] : '';
  $email = (isset($_POST['email'])) ? $_POST['email'] : '';
  $language = (isset($_POST['languages'])) ? $_POST['languages'] : '';
  $perms = (isset($_POST['perms']) && preg_match("/^[012345]{1}$/", $_POST['perms'])) ? $_POST['perms'] : '';
  $locked = (isset($_POST['locked'])) ? 'on' : 'off';
  $loginattempts = ($locked == 'on') ? '9' : '0';
  $ldapexempt = (isset($_POST['ldapexempt']) && $_POST['ldapexempt'] == "on") ? true : false;
  $edit = (isset($_GET['edit']) && preg_match("/true|false/", $_GET['edit'])) ? true : false;
  
  $logged_in_user = (isset($COLLATE['user']['username'])) ? $COLLATE['user']['username'] : '';
  if($logged_in_user != $username){
    AccessControl('5', null);
  }

  if($edit === false){
    $return = validate_text($username,'username');
    if ($return['0'] === false){ 
      $notice = $return['error']; 
      header("Location: users.php?op=add&username=$username&phone=$phone&email=$email&notice=$notice");
      exit();
    }
	$action = 'add';
  }
  else{
    $action = 'edit';
  }
  
  $return = validate_text($phone,'phone');
  if ($return['0'] === false){ 
    $notice = $return['error']; 
    header("Location: users.php?op=$action&username=$username&phone=$phone&email=$email&notice=$notice");
	exit();
  }
  
  $return = validate_text($email,'email');
  if ($return['0'] === false){ 
    $notice = $return['error']; 
    header("Location: users.php?op=$action&username=$username&phone=$phone&email=$email&notice=$notice");
	exit();
  }
  
  if(empty($email) && empty($phone)) {
    $notice = "onecontact";
    header("Location: users.php?op=$action&username=$username&phone=$phone&email=$email&notice=$notice");
	exit();
  }
  
  foreach (glob("languages/*.php") as $filename){
    include $filename;
  }
  if(!isset($languages[$language]['isocode']) || $language != $languages[$language]['isocode']){
	header("Location: users.php?op=$action&username=$username&phone=$phone&email=$email&notice=invalidrequest");
	exit();
  } 

  $test = mysql_query("SELECT id FROM users WHERE username='$username'");
  if(mysql_num_rows($test) > "0" && $edit === false) { #duplicate user
    $notice = "nameconflict-notice";
    header("Location: users.php?op=add&username=$username&phone=$phone&email=$email&notice=$notice");
    exit();
  }
  elseif(mysql_num_rows($test) !== 1 && $edit !== false){ #can't edit a user that doesn't exist
    $notice = "invalidrequest";
    header("Location: users.php?op=add&username=$username&phone=$phone&email=$email&notice=$notice");
    exit();
  }
  
  if($edit === false){
    $sql = "INSERT INTO users (username, tmppasswd, accesslevel, phone, email, loginattempts, ldapexempt, language) 
           VALUES('$username', '$tmppasswd', '$perms', '$phone', '$email', '$loginattempts', '$ldapexempt', '$language')";
  }
  else{
    if($COLLATE['user']['accesslevel'] == '5' || $COLLATE['settings']['perms'] > '5') { #can update all vars
	  if(empty($tmppasswd)){
	    $sql = "UPDATE users SET accesslevel='$perms', phone='$phone', email='$email', loginattempts='$loginattempts', 
		        ldapexempt='$ldapexempt', language='$language' 
	            WHERE username='$username'";
	  }
	  else{
        $sql = "UPDATE users SET tmppasswd='$tmppasswd', accesslevel='$perms', phone='$phone',
	            email='$email', loginattempts='$loginattempts', ldapexempt='$ldapexempt', language='$language' 
	            WHERE username='$username'";
	  }
	}
	else{ # can only update basic info
	  $sql = "UPDATE users SET username='$username', phone='$phone', email='$email', language='$language' 
	          WHERE username='$username'";
	}
  }
  
  if($edit === false){
    $message = "User added: $username";
	$notice = "useradded-notice";
  }
  else{
    $message = "User updated: $username";
	$notice = "userupdated-notice";
  }
  collate_log('5', $message); // adds and modifications are always logged
  
  mysql_query($sql);  
  
  header("Location: users.php?op=edit&username=$username&notice=$notice");
  exit();

} // Ends process_new_user function

?>
