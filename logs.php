<?php
require_once('./include/common.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){

  case "truncate";
  AccessControl("5", null);
  log_truncate();
  break;
  
  default:
  AccessControl("1", null);
  view_logs();
  break;
}

function log_truncate(){
  global $COLLATE;
  global $dbo;
  
  include "include/validation_functions.php";
  
  if(isset($_GET['action'])){
    $action = clean($_GET['action']);
  }
  else{
    $action = "show warning";
  }
  if($action != "truncate"){ // Show confirmation form
    require_once('./include/header.php');
    echo $COLLATE['languages']['selected']['confirmtruncate']." \n".
	     "<br /><br /><a href=\"logs.php?op=truncate&amp;action=truncate\">".
	     "<img src=\"./images/apply.gif\" alt=\"".$COLLATE['languages']['selected']['altconfirm']."\" /></a> &nbsp; <a href=\"logs.php\">".
	     "<img src=\"./images/cancel.gif\" alt=\"".$COLLATE['languages']['selected']['altcancel']."\" /></a>";
    require_once('./include/footer.php');
    exit();
  }
  
  // They've confirmed they want to truncate the logs.
  $sql = "SELECT MAX(id) FROM logs";
  $result = $dbo -> query($sql);
  $maxid = $result -> fetchColumn();
  $id = $maxid - 500;
    
  $sql = "DELETE FROM logs WHERE id<'$id'";
  $dbo -> query($sql);
  
  $level = "5";
  $message = "LOGS TRUNCATED";
  collate_log($level, $message);
  
  $notice = "truncatesuccess-notice";
  
  header("Location: logs.php?notice=$notice");
  exit();
  
} // Ends log_truncate function

function view_logs() {
  global $COLLATE;
  global $dbo;
  require_once('./include/header.php');
  
  echo "<h1>".$COLLATE['languages']['selected']['Logs']."</h1>
        <div style=\"float: left; width: 70%;\">";       
  
  $sql = "SELECT occuredat, username, ipaddress, level, message FROM logs ORDER BY id DESC";
  $hiddenformvars='';
  $sql = pageselector($sql,$hiddenformvars);
  $row = $dbo -> query($sql);
  $rows = $row -> rowCount();
  
  echo "</div>";
  
  echo "<div style=\"float: left; width: 25%; text-align:right; padding:5px;\">
       <a href=\"logs.php?op=truncate\"><img src=\"images/remove.gif\" alt=\"X\" />".$COLLATE['languages']['selected']['TruncateLogs']."
	   </a></div><p style=\"clear: left; display: done;\">";
 
  echo "<table style=\"width: 100%\"><tr><td><b>".$COLLATE['languages']['selected']['Timestamp']."</b></td>\n
        <td><b>".$COLLATE['languages']['selected']['Username']."</b></td>\n
		<td><b>".$COLLATE['languages']['selected']['IPAddress']."</b></td>\n
        <td><b>".$COLLATE['languages']['selected']['Severity']."</b></td>\n
	    <td><b>".$COLLATE['languages']['selected']['Message']."</b></td></tr>\n
	    <tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
  while(list($occuredat,$username,$ipaddress, $level,$message) = $row -> fetch(PDO::FETCH_NUM)){
    if($level == "high"){
	  $level = "<b>high</b>";
	}
    echo "<tr><td>$occuredat</td><td>$username</td><td>$ipaddress</td><td>$level</td><td>$message</td></tr>".
	     "<tr><td colspan=\"5\"><hr class=\"division\" /></td></tr>";
  }
  echo "</table><br />";
  
  if($rows < '1'){
    echo "<p>".$COLLATE['languages']['selected']['nologs']."</p>";
  }
  
  $sql = "SELECT occuredat, username, ipaddress, level, message FROM logs ORDER BY id DESC";
  $hiddenformvars='';
  pageselector($sql,$hiddenformvars);
  
  require_once('./include/footer.php');
} // Ends view_tail function


?>