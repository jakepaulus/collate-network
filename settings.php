<?php

require_once('./include/common.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op) {
  case "modify";
  process();
  break;
  
  default:
  form();
}

require_once('./include/footer.php');



function form() {
global $COLLATE;
  $accesslevel = "5";
  $message = "settings form accessed";
  AccessControl($accesslevel, $message);
  
require_once('./include/header.php');
  
?>
<h1>Settings</h1>
<br />
<p><b>Current Settings are shown by default. Click Reset at the bottom to see current settings again.</b></p>

<form id="settings" action="settings.php?op=modify" method="post">
  <p><b>Check permissions for the following access:</b><br />
  
  <?php
  
    if($COLLATE['settings']['perms'] == "5"){
	  $checked5 = "checked=\"checked\"";
	}
	elseif($COLLATE['settings']['perms'] == "4"){
	  $checked4 = "checked=\"checked\"";
	}
	elseif($COLLATE['settings']['perms'] == "3"){
	  $checked3 = "checked=\"checked\"";
	}
	elseif($COLLATE['settings']['perms'] == "2"){
	  $checked2 = "checked=\"checked\"";
	}
	elseif($COLLATE['settings']['perms'] == "1"){
	  $checked1 = "checked=\"checked\"";
	}
	else{
	  $checked0 = "checked=\"checked\"";
	}

  ?>
  <blockquote>
    <input type="radio" name="perms" <?php echo $checked1; ?> value="1" />Read-Only<br />
	<input type="radio" name="perms" <?php echo $checked2; ?> value="2" />Reserve IPs<br />
    <input type="radio" name="perms" <?php echo $checked3; ?> value="3" />Allocate Subnets<br />
	<input type="radio" name="perms" <?php echo $checked4; ?> value="4" />Add IP Blocks<br />
	<input type="radio" name="perms" <?php echo $checked5; ?> value="5" />Admin<br />
	<input type="radio" name="perms" <?php echo $checked0; ?> value="6" />None (Turn off authentication)<br />
  </blockquote>
  </p>
  
  <p><b>Number of days before user's passwords expire:</b> (0 for no expiration)<br />
  <input name="accountexpire" type="text" size="10" value="<?php echo $COLLATE['settings']['accountexpire']; ?>" /></p>
  
  <p><b>Minimum Password Length:</b><br />
  <select name="passwdlength">
    <option value="5" <?php if($COLLATE['settings']['passwdlength'] == "5") { echo "selected=\"selected\""; } ?>> 5 </option>
	<option value="6" <?php if($COLLATE['settings']['passwdlength'] == "6") { echo "selected=\"selected\""; } ?>> 6 </option>
	<option value="7" <?php if($COLLATE['settings']['passwdlength'] == "7") { echo "selected=\"selected\""; } ?>> 7 </option>
	<option value="8" <?php if($COLLATE['settings']['passwdlength'] == "8") { echo "selected=\"selected\""; } ?>> 8 </option>
	<option value="9" <?php if($COLLATE['settings']['passwdlength'] == "9") { echo "selected=\"selected\""; } ?>> 9 </option>
	<option value="10" <?php if($COLLATE['settings']['passwdlength'] == "10") { echo "selected=\"selected\""; } ?>> 10 </option>
  </select></p>
  
  <p><b>Number of failed login attempts before account is locked:</b> (0 for infinite)<br />
  <select name="loginattempts">
    <option value="0" <?php if($COLLATE['settings']['loginattempts'] == "0") { echo "selected=\"selected\""; } ?>> 0 </option>
	<option value="1" <?php if($COLLATE['settings']['loginattempts'] == "1") { echo "selected=\"selected\""; } ?>> 1 </option>
	<option value="2" <?php if($COLLATE['settings']['loginattempts'] == "2") { echo "selected=\"selected\""; } ?>> 2 </option>
	<option value="3" <?php if($COLLATE['settings']['loginattempts'] == "3") { echo "selected=\"selected\""; } ?>> 3 </option>
	<option value="4" <?php if($COLLATE['settings']['loginattempts'] == "4") { echo "selected=\"selected\""; } ?>> 4 </option>
	<option value="5" <?php if($COLLATE['settings']['loginattempts'] == "5") { echo "selected=\"selected\""; } ?>> 5 </option>
	<option value="6" <?php if($COLLATE['settings']['loginattempts'] == "6") { echo "selected=\"selected\""; } ?>> 6 </option>
	<option value="7" <?php if($COLLATE['settings']['loginattempts'] == "7") { echo "selected=\"selected\""; } ?>> 7 </option>
	<option value="8" <?php if($COLLATE['settings']['loginattempts'] == "8") { echo "selected=\"selected\""; } ?>> 8 </option>
	<option value="9" <?php if($COLLATE['settings']['loginattempts'] == "9") { echo "selected=\"selected\""; } ?>> 9 </option>
  </select></p>
  
  
  <p><input type="submit" value="Submit" /> <input type="reset" /></p>
  
</form>

<?php
require_once('./include/footer.php');
} // Ends form function

function process() {
global $COLLATE;
  $accesslevel = "5";
  $message = "settings form submitted";
  AccessControl($accesslevel, $message); 
  
 
  $perms = clean($_POST['perms']);
  $accountexpire = clean($_POST['accountexpire']);
  $passwdlength = clean($_POST['passwdlength']);
  $loginattempts = clean($_POST['loginattempts']);

  

  if($COLLATE['settings']['perms'] != $perms){
    // First we need to make sure there is at least one administrator user so we know they don't lock themselves out of the application
	$sql = "SELECT id FROM users WHERE accesslevel='5'";
	$result = mysql_query($sql);
	if(mysql_num_rows($result) < '1'){
	  $notice = "You must create at least one user with administrator rights before changing the permission requirements.";
	  header("Location: settings.php?notice=$notice");
	  exit();
	}
    $sql = "UPDATE settings SET value='$perms' WHERE name='perms'";
	mysql_query($sql);
  } 
  if($COLLATE['settings']['passwdlength'] != $passwdlength) {
    $sql = "UPDATE settings SET value='$passwdlength' WHERE name='passwdlength'";
	mysql_query($sql);
  }
  if($COLLATE['settings']['accountexpire'] != $accountexpire) {
    $sql = "UPDATE settings SET value='$accountexpire' WHERE name='accountexpire'";
    mysql_query($sql);
  }
  if($COLLATE['settings']['loginattempts'] != $loginattempts) { 
    $sql = "UPDATE settings SET value='$loginattempts' WHERE name='loginattempts'";
	mysql_query($sql);
  }
  
  $notice = "Collate:Network Settings have been updated.";
  header("Location: panel.php?notice=$notice");
  exit();
} // Ends process function
?>