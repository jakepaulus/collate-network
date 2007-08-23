<?php
require_once('./include/common.php');
require_once('./include/header.php');

if(isset($_GET['op'])){
  $op = $_GET['op'];
}
else {
  $op = "show_tail";
}

switch($op){

  case "truncate";
  log_truncate();
  break;
  
  default:
  view_tail();
  break;
}

function log_truncate(){
  global $COLLATE;
  $accesslevel = "5";
  $message = "truncate logs page accessed";
  AccessControl($accesslevel, $message);
  
  if(isset($_GET['action'])){
    $action = clean($_GET['action']);
  }
  else{
    $action = "show warning";
  }
  if($action != "truncate"){ // Show confirmation form
    echo "<h1>Truncate Logs?</h1><br />".
         "<p><b>Are you sure you'd like to truncate the logs?</b> This will delete all log events in the database except the most \n".
		 "recent 500 events. This action is not reversable! \n".
	     "<br /><br /><a href=\"logs.php?op=truncate&amp;action=truncate\">".
	     "<img src=\"./images/apply.gif\" alt=\"confirm\" /></a> &nbsp; <a href=\"logs.php\">".
	     "<img src=\"./images/cancel.gif\" alt=\"cancel\" /></a>";
      require_once('./include/footer.php');
      exit();
  }
  
  // They've confirmed they want to truncate the logs.
  $sql = "SELECT MAX(lid) FROM logs";
  $maxlid = mysql_result(mysql_query($sql), 0);
  $lid = $maxlid - 500;
  
  $sql = "DELETE FROM logs WHERE lid<'$lid'";
  mysql_query($sql);
  
  $level = "5";
  $message = "LOGS TRUNCATED";
  collate_log($level, $message);
  
  $notice = "The logs have been truncated";
  
  header("Location: logs.php?notice=$notice");
  exit();
  
} // Ends log_truncate function


function view_tail() {
  
  global $COLLATE;
  $accesslevel = "1";
  $message = "logs viewed";
  AccessControl($accesslevel, $message); 
  
  echo "<h1>Log Tail:</h1>".
       "<table width=\"100%\"><tr><td align=\"left\"><a href=\"logs.php?op=truncate\"><img src=\"images/remove.gif\" alt=\"X\" /> ".
       "Truncate Logs</a></td><td align=\"right\"><a href=\"search.php\"><img src=\"images/search_small.gif\" alt=\"search\" /> ".
       "Search Logs</a></td></tr></table><br />";
  
  $sql = "SELECT occuredat, username, ipaddress, level, message FROM logs ORDER BY lid DESC LIMIT 0, 12";
  $row = mysql_query($sql);
  
  if(mysql_num_rows($row) < "1"){
    echo "<p>No logs have been generated yet.</p>";
	require_once('./include/footer.php');
	exit();
  }
  echo "<table width=\"100%\"><tr><td><b>Timestamp</b></td><td><b>Username</b></td><td><b>IP Address</b></td>".
       "<td><b>Severity</b></td><td><b>Message</b></td></tr>\n".
	   "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
  while(list($occuredat,$username,$ipaddress, $level,$message) = mysql_fetch_row($row)){
    if($level == "high"){
	  $level = "<b>high</b>";
	}
    echo "<tr><td>$occuredat</td><td>$username</td><td>$ipaddress</td><td>$level</td><td>$message</td></tr>".
	     "<tr><td colspan=\"5\"><hr class=\"division\" /></td></tr>";
  }
  echo "</table>";
  
  require_once('./include/footer.php');
} // Ends view_tail function


?>