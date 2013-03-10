<?php
/**
 * Please see /include/common.php for documentation on common.php, the $COLLATE global array used by this program, and the AccessControl function used widely.
 */
require_once('./include/common.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){

	case "add";
	AccessControl('5', null);
	add_user_form();
	break;
		
	case "submit"; 
	AccessControl('5', null);
	submit_user();
	break;
		
	case "edit"; 
	edit_user_form();
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
		 "<th>".$COLLATE['languages']['selected']['LastLogin']."</th>".
		 "<th>".$COLLATE['languages']['selected']['Actions']."</th></tr>".
	     "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>";
	     
    while(list($username,$phone,$email,$lastlogin) = mysql_fetch_row($result)){
      echo "<tr id=\"user_${username}_row_1\"><td>$username</td><td>$phone</td><td>$email</td><td>$lastlogin</td>
	       <td>";
	  if ($COLLATE['user']['accesslevel'] == '5' || $COLLATE['settings']['perms'] > '5') {
	    echo "<a href=\"#\" onclick=\"
		      if (confirm('".$COLLATE['languages']['selected']['confirmdelete']."')) {
                new Element.update('notice', ''); 
				new Ajax.Updater('notice', '_users.php?op=deleteuser&amp;username=$username', {
				  onSuccess:function(){ new Effect.Parallel([new Effect.Fade('user_${username}_row_1'), new Effect.Fade('user_${username}_row_2')]); }
				});
               };
			   return false;
			   \"><img src=\"./images/remove.gif\" alt=\"X\" title=\"".$COLLATE['languages']['selected']['DeleteUser']."\" /></a> &nbsp".
	  	     "&nbsp;<a href=\"users.php?op=edit&amp;username=$username\">".
			 "<img src=\"./images/modify.gif\" alt=\"edit\" title=\"".$COLLATE['languages']['selected']['EditUser']."\" /></a>";
	  }
	  echo "</td></tr>".
	       "<tr id=\"user_${username}_row_2\"><td colspan=\"5\"><hr class=\"division\" /></td></tr>";
    }
    
    echo "</table>";
  }

} // Ends list_users function

function add_user_form(){
  global $COLLATE;
  global $op;
  
  $username = (isset($_GET['username'])) ? $_GET['username'] : '';
  $phone = (isset($_GET['phone'])) ? $_GET['phone'] : '';
  $email = (isset($_GET['email'])) ? $_GET['email'] : '';
  $ldapexempt = (isset($_GET['ldapexempt'])) ? $_GET['ldapexempt'] : '0';
  $accesslevel = '0';
  $loginattempts = '';
  $language = $COLLATE['languages']['selected']['isocode'];
  
  
  require_once('./include/header.php');
  
  echo "<h1>".$COLLATE['languages']['selected']['AddaUser']."</h1>\n".
       "<br />\n<form action=\"users.php?op=submit\" method=\"post\">\n".
       "<p><b>".$COLLATE['languages']['selected']['Username'].":</b> <input name=\"username\" type=\"text\" value=\"$username\" /></p>\n".
       "<p><b>".$COLLATE['languages']['selected']['Telephone'].":</b> <input name=\"phone\" type=\"text\" value=\"$phone\" /></p>\n".
       "<p><b>".$COLLATE['languages']['selected']['Email'].":</b> <input name=\"email\" type=\"text\" value=\"$email\" /></p>\n";
	   
  foreach (glob("languages/*.php") as $filename){
    include $filename;
  }
  echo "<p><b>".$COLLATE['languages']['selected']['PreferredLanguage'].":</b> <select name=\"languages\" onchange=\"
        new Ajax.Updater('generalnotice', '_settings.php?op=updatelanguage&amp;language=' + this.value);\">";
  foreach ($languages as $language){
    if($COLLATE['languages']['selected']['isocode'] == $language['isocode']){
      $selected = "selected=\"selected\"";
    }
    else {
      $selected = "";
    }
    echo "<option value=\"".$language['isocode']."\" $selected/> ".$language['languagename']." </option>\n";
  }
  echo "</select></p>";
  
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
  
  echo "<p><b>".$COLLATE['languages']['selected']['SetTempPass'].":</b><input id=\"tmppasswd\" name=\"tmppasswd\" type=\"text\" size=\"15\" /></p>\n";

  $ldapexempt = ($ldapexempt) ? "checked=\"checked\"" : "";
  $locked = ($loginattempts >= $COLLATE['settings']['loginattempts']) ? "checked=\"checked\"" : "";

  echo "<p><input type=\"checkbox\" name=\"ldapexempt\" $ldapexempt /> ".$COLLATE['languages']['selected']['Forcedbauth']."</p>\n".
       "<p><input type=\"checkbox\" name=\"locked\" $locked /> ".$COLLATE['languages']['selected']['Lockaccount']."<br /></p>".
       "<input type=\"submit\" value=\" ".$COLLATE['languages']['selected']['Go']." \" /></p>\n".
       "</form>";

} // Ends add_user_form function

function edit_user_form(){
  global $COLLATE;
  global $op;
  
  $username = (isset($_GET['username'])) ? $_GET['username'] : '';  
  if(empty($username)){
    $notice = 'invalidrequest';
    header("Location: users.php?notice=$notice");
    exit();
  }  
  
  if(isset($COLLATE['user']['username']) && $COLLATE['user']['username'] == $username){
    AccessControl('0', null); # users can edit their own profile
  }
  else {
    AccessControl('5', null); #admins can edit anyone's profile
  }
   
  $sql = "SELECT passwd, tmppasswd, accesslevel, phone, email, loginattempts, ldapexempt, language FROM users WHERE username='$username'";
  $result = mysql_query($sql);
  if(mysql_num_rows($result) != '1'){
    $notice = $COLLATE['languages']['selected']['invalidrequest'];
    header("Location: users.php?notice=$notice");
    exit();
  }
  list($passwd,$tmppasswd,$accesslevel,$phone,$email,$loginattempts,$ldapexempt,$current_language) = mysql_fetch_row($result);
  
  require_once('./include/header.php');
  
  echo "<h1>".$COLLATE['languages']['selected']['EditUser'].": $username</h1>\n".
       "<br />\n".
       "<p><b>".$COLLATE['languages']['selected']['Telephone'].":</b> <span id=\"phone\">$phone</span></p>\n".
	   "<script type=\"text/javascript\"><!--
          new Ajax.InPlaceEditor('phone', '_users.php?op=editphone&username=$username',
            {
			clickToEditText: '".$COLLATE['languages']['selected']['ClicktoEdit']."',
			 highlightcolor: '#a5ddf8',  
             callback:
               function(form) {
                 new Element.update('notice', '');
                 return Form.serialize(form);
               },
             onFailure: 
               function(transport) {
                 new Element.update('notice', transport.responseText.stripTags());
               }
            }
          );
		--></script>".
       "<p><b>".$COLLATE['languages']['selected']['Email'].":</b> <span id=\"email\">$email</span></p>\n".
	   "<script type=\"text/javascript\"><!--
          new Ajax.InPlaceEditor('email', '_users.php?op=editemail&username=$username',
            {
			 clickToEditText: '".$COLLATE['languages']['selected']['ClicktoEdit']."',
			 highlightcolor: '#a5ddf8',  
             callback:
               function(form) {
                 new Element.update('notice', '');
                 return Form.serialize(form);
               },
             onFailure: 
               function(transport) {
                 new Element.update('notice', transport.responseText.stripTags());
               }
            }
          );
		--></script>";

  foreach (glob("languages/*.php") as $filename){
    include $filename;
  }
  echo "<p><b>".$COLLATE['languages']['selected']['PreferredLanguage'].":</b> <select name=\"languages\" onchange=\"
        new Ajax.Updater('notice', '_users.php?op=editlanguage&username={$username}&amp;language=' + this.value);\">";
  foreach ($languages as $language){
    if($languages[$current_language]['isocode'] == $language['isocode']){
      $selected = "selected=\"selected\"";
    }
    else {
      $selected = "";
    }
    echo "<option value=\"".$language['isocode']."\" $selected /> ".$language['languagename']." </option>\n";
  }
  echo "</select></p>";
  
  if ($COLLATE['user']['accesslevel'] == '5' || $COLLATE['settings']['perms'] > '5') {  
    echo "<p><b>".$COLLATE['languages']['selected']['UserAccessLevel'].":</b><br />\n";
    
    $checked0 = ($accesslevel == '0') ? "checked=\"checked\"" : '';
    $checked1 = ($accesslevel == '1') ? "checked=\"checked\"" : '';
    $checked2 = ($accesslevel == '2') ? "checked=\"checked\"" : '';
    $checked3 = ($accesslevel == '3') ? "checked=\"checked\"" : '';
    $checked4 = ($accesslevel == '4') ? "checked=\"checked\"" : '';
    $checked5 = ($accesslevel == '5') ? "checked=\"checked\"" : '';
     
    echo "<input type=\"radio\" name=\"accesslevel\" $checked0 onchange=\"new Ajax.Updater('notice', '_users.php?op=editperms&amp;accesslevel=0&username=$username');\" />".
		  $COLLATE['languages']['selected']['None']."<br />\n".
         "<input type=\"radio\" name=\"accesslevel\" $checked1 onchange=\"new Ajax.Updater('notice', '_users.php?op=editperms&amp;accesslevel=1&username=$username');\" />".
		 $COLLATE['languages']['selected']['ReadOnly']."<br />\n".
	     "<input type=\"radio\" name=\"accesslevel\" $checked2 onchange=\"new Ajax.Updater('notice', '_users.php?op=editperms&amp;accesslevel=2&username=$username');\" />".
		 $COLLATE['languages']['selected']['ReserveIPs']."<br />\n".
         "<input type=\"radio\" name=\"accesslevel\" $checked3 onchange=\"new Ajax.Updater('notice', '_users.php?op=editperms&amp;accesslevel=3&username=$username');\" />".
		 $COLLATE['languages']['selected']['AllocateSubnets']."<br />\n".
	     "<input type=\"radio\" name=\"accesslevel\" $checked4 onchange=\"new Ajax.Updater('notice', '_users.php?op=editperms&amp;accesslevel=4&username=$username');\" />".
		 $COLLATE['languages']['selected']['AllocateBlocks']."<br />\n".
	     "<input type=\"radio\" name=\"accesslevel\" $checked5 onchange=\"new Ajax.Updater('notice', '_users.php?op=editperms&amp;accesslevel=5&username=$username');\" />".
		 $COLLATE['languages']['selected']['Admin']."<br />\n".
         "</p>\n";
  }
  if (isset($COLLATE['user']['username']) && $COLLATE['user']['username'] == $username){ // change password
    echo "<p><b><a href=\"login.php?op=changepasswd\">".$COLLATE['languages']['selected']['changeyourpassword']."</a></b></p>\n"; 
  }
  elseif ($COLLATE['user']['accesslevel'] == '5' || $COLLATE['settings']['perms'] > '5') {
    echo "<p><b>".$COLLATE['languages']['selected']['SetTempPass'].":</b> <span id=\"tmppasswd\"></span></p>\n".
	     "<script type=\"text/javascript\"><!--
               new Ajax.InPlaceEditor('tmppasswd', '_users.php?op=resetpasswd&username=$username',
                 {
				 clickToEditText: '".$COLLATE['languages']['selected']['ClicktoEdit']."',
	     		 highlightcolor: '#a5ddf8',  
                  callback:
                    function(form) {
                      new Element.update('notice', '');
                      return Form.serialize(form);
                    },
                  onFailure: 
                    function(transport) {
                      new Element.update('notice', transport.responseText.stripTags());
                    }
                 }
               );
	     	--></script>";
  }
  if ($COLLATE['user']['accesslevel'] == '5' || $COLLATE['settings']['perms'] > '5') {
	
	$ldapexempt = ($ldapexempt) ? "checked=\"checked\"" : "";
    $locked = ($loginattempts >= $COLLATE['settings']['loginattempts']) ? "checked=\"checked\"" : "";

    echo "<p><input type=\"checkbox\" value=\"ldapexempt\" $ldapexempt onchange=\"
	      new Ajax.Updater('notice', '_users.php?op=ldapexempt&username={$username}&amp;ldapexempt=' + this.checked);\"/> ".
		  $COLLATE['languages']['selected']['Forcedbauth']."</p>\n".
         "<p><input type=\"checkbox\" value=\"locked\" $locked onchange=\"
		  new Ajax.Updater('notice', '_users.php?op=lock&username={$username}&amp;locked=' + this.checked);\"/> ".
		  $COLLATE['languages']['selected']['Lockaccount']."<br /></p>";
  }
} // Ends edit_user_form function

function submit_user(){
  global $COLLATE;
  include 'include/validation_functions.php';
  
  $username = (isset($_POST['username'])) ? $_POST['username'] : '';
  $tmppasswd = sha1(clean($_POST['tmppasswd']));
  $phone = (isset($_POST['phone'])) ? $_POST['phone'] : '';
  $email = (isset($_POST['email'])) ? $_POST['email'] : '';
  $language = (isset($_POST['languages'])) ? $_POST['languages'] : '';
  $perms = (isset($_POST['perms']) && preg_match("/^[012345]{1}$/", $_POST['perms'])) ? $_POST['perms'] : '';
  $locked = (isset($_POST['locked'])) ? 'on' : 'off';
  $loginattempts = ($locked == 'on') ? '9' : '0';
  $ldapexempt = (isset($_POST['ldapexempt']) && $_POST['ldapexempt'] == "on") ? true : false;

  
  $return = validate_text($username,'username');
  if ($return['0'] === false){ 
    $notice = $return['error']; 
    header("Location: users.php?op=$action&username=$username&phone=$phone&email=$email&notice=$notice");
	exit();
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
  if(mysql_num_rows($test) > "0") {
    $notice = "nameconflict-notice";
    header("Location: users.php?op=add&username=$username&phone=$phone&email=$email&notice=$notice");
    exit();
  }

  $sql = "INSERT INTO users (username, tmppasswd, accesslevel, phone, email, loginattempts, ldapexempt, language) 
         VALUES('$username', '$tmppasswd', '$perms', '$phone', '$email', '$loginattempts', '$ldapexempt', '$language')";
  
  $message = "User added: $username";  
  collate_log('5', $message); // We only want to generate logs if something is actually happening...not each time the user is tossed back to the user add form.
  
  mysql_query($sql);
  
  $notice = "useradded-notice";
  header("Location: users.php?notice=$notice");
  exit();

} // Ends process_new_user function

?>
