<?php
require_once('./include/common.php');
require_once('./include/header.php');
  $accesslevel = "1";
  $message = "control panel accessed";
  AccessControl($accesslevel, $message); 

?>
<br />
<table width="100%">
<tr>
<td align="center" style="width: 25%"><a href="./users.php"><img height="48" width="48" alt="Users" src="./images/users.gif" /><br /></a><b>Manage Users</b></td>
<td align="center" style="width: 25%"><a href="./blocks.php"><img height="48" width="48" alt="Blocks" src="./images/block.png" /></a><br /><b>Manage IP</b></td>
<td align="center" style="width: 25%"><a href="./docs/documentation.txt"><img height="48" width="48" alt="[?]" src="./images/help_large.gif" /></a><br />
<b>Documentation</b></td>
<td align="center" style="width: 25%"><a href="./logs.php"><img height="48" width="48" alt="[?]" src="./images/logs.gif" /></a><br /><b>Logs</b></td>
</tr>
<tr><td><br /></td></tr>
<tr>
<?php if(isset($_SESSION['username'])){ ?>
<td align="center" style="width: 25%"><a href="./login.php?op=changepasswd"><img height="48" width="48" alt="[?]" src="./images/password.gif" /></a><br />
<b>Change Your Password</b></td>
<?php } ?>

<?php if($COLLATE['user']['accesslevel'] == "5" || $COLLATE['settings']['checklevel5perms'] === "0"){ ?>
<td align="center" style="width: 25%"><a href="./settings.php"><img height="48" width="48" alt="Settings" src="./images/settings.gif" /></a><br /><b>Settings</b></td>
<?php } ?>
<td align="center" style="width: 25%">&nbsp;</td>
<td align="center" style="width: 25%">&nbsp;</td>
</tr>
</table>
<br />
<?
require_once('./include/footer.php');
?>