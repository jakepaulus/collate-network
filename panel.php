<?php
require_once('include/common.php');

show_panel();

function show_panel(){
  global $COLLATE;

  $accesslevel = "1";
  $message = "control panel accessed";
  AccessControl($accesslevel, $message); 
require_once('./include/header.php');
?>
<h1>Control Panel</h1>
<table width="100%">
<tr><td><br /></td></tr>
<tr>
<td align="center" style="width: 25%"><a href="./users.php"><img height="48" width="48" alt="Users" src="./images/users.gif" /><br /></a><b>Manage Users</b></td>
<td align="center" style="width: 25%"><a href="./blocks.php"><img height="48" width="48" alt="Blocks" src="./images/block.gif" /></a><br /><b>Manage IP</b></td>
<td align="center" style="width: 25%"><a href="http://code.google.com/p/collate-network/w/list"><img height="48" width="48" alt="[?]" src="./images/help_large.gif" /></a><br />
<b>Documentation</b></td>
<td align="center" style="width: 25%"><a href="./logs.php"><img height="48" width="48" alt="[?]" src="./images/logs.gif" /></a><br /><b>Logs</b></td>
</tr>
<tr><td><br /></td></tr>
<tr>
<td align="center" style="width: 25%"><a href="search.php?op=search&first=1&second=name&search=discovered"><img height="48" width="48" alt="Discovered Hosts" src="./images/discovered.png"></a><br /><b>Discovered Hosts</b></td>
<?php if(isset($_SESSION['username']) && $_SESSION['auth_type'] != 'ldap'){ ?>
<td align="center" style="width: 25%"><a href="./login.php?op=changepasswd"><img height="48" width="48" alt="Change Password" src="./images/password.gif" /></a><br />
<b>Change Your Password</b></td>
<?php } ?>
<td align="center" style="width: 25%"><a href="./settings.php"><img height="48" width="48" alt="Settings" src="./images/settings.gif" /></a><br /><b>Settings</b></td>
</tr>
</table>
<br />
<br />

<?
}
require_once('./include/footer.php');
?>