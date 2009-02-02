<?php

require_once('./include/common.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op) {
  case "modify";
  AccessControl('5', "settings form submitted"); 
  process();
  break;
  
  default:
  AccessControl('5', "settings form accessed");
  require_once('./include/header.php');
  form();
}

require_once('./include/footer.php');



function form() {
global $COLLATE;

?>
<h1>Settings</h1>
<br />
<h3>Authorization</h3>
<hr />
<form id="settings" action="settings.php?op=modify" method="post">
<div style="margin-left: 25px;">
  <p><b>Check permissions for the following access:</b></p>
  
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
  <ul class="plain">
    <li><input type="radio" name="perms" <?php echo $checked1; ?> value="0" />Read-Only (Must login to see/do anything)</li>
	<li><input type="radio" name="perms" <?php echo $checked2; ?> value="2" />Reserve IPs</li>
    <li><input type="radio" name="perms" <?php echo $checked3; ?> value="3" />Allocate Subnets</li>
	<li><input type="radio" name="perms" <?php echo $checked4; ?> value="4" />Add IP Blocks</li>
	<li><input type="radio" name="perms" <?php echo $checked5; ?> value="5" />Admin</li>
	<li><input type="radio" name="perms" <?php echo $checked0; ?> value="6" />None (Turn off authentication)</li>
  </ul>
  </div>
  <p>&nbsp;</p>
  <h3>Authentication</h3>
  <hr />
  <p>&nbsp;</p>
  <div style="margin-left: 20px;">
  <p><b>Default Authentication Method:</b></p>
  <ul class="plain">
    <li><input type="radio" name="auth_type" value="db" <?php if($COLLATE['settings']['auth_type'] == 'db'){ echo "checked=\"checked\""; } ?> />Database</li>
    <li><input type="radio" name="auth_type" value="ldap" <?php if($COLLATE['settings']['auth_type'] == 'ldap'){ echo "checked=\"checked\""; } ?> />LDAP</li>
  </ul>
  
	<table width="70%">
	<?php
	$sql = "select id,domain, server from `ldap-servers`";
	$result = mysql_query($sql);
	if(mysql_num_rows($result) == '0'){
	  ?>
	  <tr><th>Domain</th><th>Authentication Server</th></tr>
	  <tr><td><input type="text" name="new_domain" /></td><td><input type="text" name="new_ldap_server" /></td></tr>
	  <?php
	}
	else{
	  ?>
	  <tr><th>Domain</th><th>LDAP Server</th><td><a href="#" onclick="javascript:Effect.toggle($('add_domain'),'appear',{duration:0})"><img src="./images/add.gif" alt="Add" /> Add a Domain </a></td></tr>
	  <?php
	  while(list($id,$domain,$server) = mysql_fetch_row($result)){
	    echo "<tr id=\"ldap_server_$id\"><td>$domain</td><td>$server</td><td><a href=\"#\" onclick=\"if (confirm('Are you sure you want to delete this object?')) { new Element.update('notice', ''); new Ajax.Updater('notice', '_settings.php?op=delete_ldap_server&ldap_server_id=$id', {onSuccess:function(){ new Effect.Fade('ldap_server_".$id."') }}); };\"><img src=\"./images/remove.gif\" alt=\"X\" /></a></td></tr>\n";
	  }
	  ?>
	  <tr id="add_domain" style="display: none;"><td><input type="text" name="new_domain" /></td><td><input type="text" name="new_ldap_server" /></td></tr>
	  <?php
	}
	
	?>
	</table>
	<p>&nbsp;</p>
	
	<p><b>Default Domain Name:</b> (ignored when "@" present in username or for database authentication )<br />
	<input name="domain" type="text" size="20" value="<?php echo $COLLATE['settings']['domain']; ?>" /></p>
  
	<p><b>Number of days before user's passwords expire:</b> (0 for no expiration, ignored for LDAP users)<br />
	<input name="accountexpire" type="text" size="10" value="<?php echo $COLLATE['settings']['accountexpire']; ?>" /></p>

	<p><b>Minimum Password Length:</b> (not applicable to LDAP users)<br />
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
  </div>
  <p>&nbsp;</p>
  <h3>User Guidance</h3>
  <hr />
  <p>&nbsp;</p>
  <div style="margin-left: 25px;">
  <p><b>Default IP Usage Guidance:</b> (Optional)<br />
  <textarea name="guidance" rows="10" cols="45"><?php echo $COLLATE['settings']['guidance']; ?></textarea></p>
  
  <p><b>DNS Servers</b> (Optional)<br />
  <input name="dns" type="text" size="30" value="<?php echo $COLLATE['settings']['dns']; ?>" /><br />&nbsp;</p>
  </div>
  <p><input type="submit" value="Submit" /> <a href="panel.php">Cancel</a></p>
  
</form>

<?php
require_once('./include/footer.php');
} // Ends form function

function process() {
  global $COLLATE;

  $perms = clean($_POST['perms']);
  $auth_type = clean($_POST['auth_type']);
  $new_domain = clean($_POST['new_domain']);
  $new_ldap_server = clean($_POST['new_ldap_server']);
  $domain = clean($_POST['domain']);
  $accountexpire = clean($_POST['accountexpire']);
  $passwdlength = clean($_POST['passwdlength']);
  $loginattempts = clean($_POST['loginattempts']);
  $guidance = clean($_POST['guidance']);
  $dns = clean($_POST['dns']);
  

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
  if($COLLATE['settings']['auth_type'] != $auth_type) {
    if($auth_type == 'ldap'){
	  if (!function_exists('ldap_connect')){
	    $notice = "Your server does not currently support LDAP authentication. Database authentication will be used. All other ";
	  }
	  else{
	    $sql = "UPDATE settings SET value='$auth_type' WHERE name='auth_type'";
	  }
	}
	else{
      $sql = "UPDATE settings SET value='$auth_type' WHERE name='auth_type'";
	}
	mysql_query($sql);
  }  
  
  if(!empty($new_domain) && !empty($new_ldap_server)){
    // new server - make changes...maybe
	$sql = "INSERT INTO `ldap-servers` (domain, server) VALUES ('$new_domain', '$new_ldap_server')";
	mysql_query($sql);
  }
  elseif(!empty($new_domain) || !empty($new_ldap_server)){
    // filled one but not the other - error
	$notice = "A field was left blank with the new LDAP server you tried to enter. The new server entry has not been added. All other ";
  }  
  if($COLLATE['settings']['domain'] != $domain) {
    $sql = "UPDATE settings SET value='$domain' WHERE name='domain'";
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
  if($COLLATE['settings']['guidance'] != $guidance) {
    $sql = "UPDATE settings SET value='$guidance' WHERE name='guidance'";
	mysql_query($sql);
  }
  if($COLLATE['settings']['dns'] != $dns){
    $sql = "UPDATE settings SET value='$dns' WHERE name='dns'";
	mysql_query($sql);
  }
  
  $notice .= "Collate:Network Settings have been updated.";
  header("Location: panel.php?notice=$notice");
  exit();
} // Ends process function
?>