<?php
require_once('include/common.php');

show_panel();

function show_panel(){
  global $COLLATE;
  AccessControl("1", null); 
  require_once('include/header.php');
  
?>
<h1><?php echo $COLLATE['languages']['selected']['ControlPanel']; ?></h1>
<table style="width: 100%">
<tr><td><br /></td></tr>
<tr>
  <?php if(isset($COLLATE['user']['username'])){ ?>
  <td align="center" style="width: 25%">
    <a href="users.php?op=edit&username=<?php echo $COLLATE['user']['username']; ?>"><img height="48" width="48" alt="<?php echo $COLLATE['languages']['selected']['UpdateProfile']; ?>" src="./images/user.png" /></a>
    <br /><b><?php echo $COLLATE['languages']['selected']['UpdateProfile']; ?></b>
  </td>
  <?php } ?>
  <td align="center" style="width: 25%">
    <a href="./users.php"><img height="48" width="48" alt="<?php echo $COLLATE['languages']['selected']['ManageUsers']; ?>" src="./images/users.gif" /></a>
	<br /><b><?php echo $COLLATE['languages']['selected']['ManageUsers']; ?></b>
  </td>
  <td align="center" style="width: 25%">
    <a href="http://www.collate.info/"><img height="48" width="48" alt="[?]" src="./images/help_large.gif" /></a>
	<br /><b><?php echo $COLLATE['languages']['selected']['Documentation']; ?></b>
  </td>
  <td align="center" style="width: 25%">
    <a href="./logs.php"><img height="48" width="48" alt="[?]" src="./images/logs.gif" /></a>
	<br /><b><?php echo $COLLATE['languages']['selected']['Logs']; ?></b>
  </td>
</tr>
<tr><td colspan="4"><br /></td></tr>
<tr>
  <td align="center" style="width: 25%">
    <a href="search.php?op=search&first=1&second=note&search=Added%20by%20discovery%20addon"><img height="48" width="48" alt="[+]" src="./images/discovered.png"></a>
	<br /><b><?php echo $COLLATE['languages']['selected']['DiscoveredHosts']; ?></b>
  </td>
  <td align="center" style="width: 25%">
    <a href="search.php?op=search&first=1&second=failed_scans&search=4"><img height="48" width="48" alt="[-]" src="./images/stale.gif"></a>
	<br /><b><?php echo $COLLATE['languages']['selected']['StaleHosts']; ?></b></td>
  <?php if(isset($COLLATE['user']['ldapexempt']) && ($COLLATE['settings']['auth_type'] != 'ldap' || $COLLATE['user']['ldapexempt'] === true)){ ?>
    <td align="center" style="width: 25%">
	  <a href="./login.php?op=changepasswd"><img height="48" width="48" alt="" src="./images/password.gif" /></a>
	  <br /><b><?php echo $COLLATE['languages']['selected']['changeyourpassword']; ?></b>
	</td>
  <?php } ?>
  <td align="center" style="width: 25%">
    <a href="./settings.php"><img height="48" width="48" alt="Settings" src="./images/settings.gif" /></a>
	<br /><b><?php echo $COLLATE['languages']['selected']['Settings']; ?></b>
  </td>
  <?php if(!isset($COLLATE['user']['username']) || 
           (isset($COLLATE['user']['ldapexempt']) && (($COLLATE['settings']['auth_type'] != 'ldap' || $COLLATE['user']['ldapexempt'] === true)))){ 
   // If the change password icon is hidden, we want the bulk import icon to be on the second row, not the third unless the user is logged out
  ?>
</tr>
<tr><td colspan="4"><br /></td></tr>
<tr>
  <?php } ?>
  <td align="center" style="width: 25%">
    <a href="./command.php"><img height="48" width="48" alt="" src="./images/bulkimport.png" /></a>
	<br /><b><?php echo $COLLATE['languages']['selected']['BulkImport']; ?></b>
  </td>
</tr>
</table>
<br />
<br />

<?php
}
require_once('include/footer.php');
?>