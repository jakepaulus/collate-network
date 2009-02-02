<?php
require_once('./include/common.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){

  case "truncate";
  AccessControl("5", "Truncate Logs form accessed");
  log_truncate();
  break;
  
  default:
  AccessControl("1", "Log tail viewed");
  view_logs();
  break;
}

function log_truncate(){
  global $COLLATE;
  
  if(isset($_GET['action'])){
    $action = clean($_GET['action']);
  }
  else{
    $action = "show warning";
  }
  if($action != "truncate"){ // Show confirmation form
    require_once('./include/header.php');
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
  $sql = "SELECT MAX(id) FROM logs";
  $maxid = mysql_result(mysql_query($sql), 0);
  $id = $maxid - 500;
  
  $sql = "DELETE FROM logs WHERE id<'$id'";
  mysql_query($sql);
  
  $level = "5";
  $message = "LOGS TRUNCATED";
  collate_log($level, $message);
  
  $notice = "The logs have been truncated";
  
  header("Location: logs.php?notice=$notice");
  exit();
  
} // Ends log_truncate function

function view_logs() {
  require_once('./include/header.php');
  global $COLLATE;
  
  $page = (!isset($_GET['page'])) ? "1" : $_GET['page'];
  $show = (!isset($_GET['show'])) ? $_SESSION['show'] : $_GET['show'];
  
  $_SESSION['show'] = $show;
  
 $sql = "SELECT occuredat, username, ipaddress, level, message FROM logs ORDER BY id DESC";
  
  if(is_numeric($show) && $show <= '250' && $show > '5'){
    $limit = $show;
  }
  elseif($show > '250'){
    echo "<div class=\"tip\"><p>You can only ask for up to 250 results per page.</p></div>";
	$limit = '250';
  }
  else{
    $limit = "10";
  }
  $result = mysql_query($sql);
  $totalrows = mysql_num_rows($result);
  $numofpages = ceil($totalrows/$limit);
  if($page > $numofpages){
    $page = $numofpages;
  }
  if($page == '0'){ $page = '1';} // Keeps errors from occuring in the following SQL query if no rows have been added yet.
  $lowerlimit = $page * $limit - $limit;
  $sql .= " LIMIT $lowerlimit, $limit";
  $row = mysql_query($sql);
  $rows = mysql_num_rows($row);
  
  echo "<h1>Logs</h1>".
       "<form action=\"logs.php\" method=\"get\"><table width=\"100%\"><tr><td align=\"left\">\n";
	   
  if($page != '1'){
    $previous_page = $page - 1;
	echo "<a href=\"logs.php?page=$previous_page&amp;show=$limit\">
	      <img src=\"images/prev.png\" alt=\" &gt;- \" /></a> ";
  }
  
  echo "Page: <select onchange=\"this.form.submit();\" name=\"page\">";
  
  $listed_page = '1';
  while($listed_page <= $numofpages){
    if($listed_page == $page){
	  echo "<option value=\"$listed_page\" selected=\"selected\"> $listed_page </option>";
	}
	else{
	  echo "<option value=\"$listed_page\"> $listed_page </option>";
	}
	$listed_page++;
  }

  echo "</select> out of $numofpages";
  
  if($page != $numofpages){
    $next_page = $page + 1;
    echo "<a href=\"logs.php?page=$next_page&amp;show=$limit\">
	      <img src=\"images/next.png\" alt=\" &lt;- \" /></a>";
	
  }
  
  echo "</td>
        <td><p>Showing <input name=\"show\" type=\"text\" size=\"3\" value=\"$limit\" /> results per page 
        <input type=\"submit\" value=\" Go \" /></p></td>";
   

 echo "<td align=\"right\"><a href=\"logs.php?op=truncate\"><img src=\"images/remove.gif\" alt=\"X\" /> ".
       "Truncate Logs</a></td></tr></table></form>\n";
 
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
  
  if($rows < 1){
    echo "<p>No logs have been generated yet.</p>";
  }
  echo "<p>&nbsp;</p>";
  echo "<form action=\"logs.php\" method=\"get\"><table width=\"80%\"><tr><td align=\"left\">\n";
	   
  if($page != '1'){
    $previous_page = $page - 1;
	echo "<a href=\"logs.php?page=$previous_page&amp;show=$limit\">
	      <img src=\"images/prev.png\" alt=\" &gt;- \" /></a> ";
  }
	   
  echo "Page: <select onchange=\"this.form.submit();\" name=\"page\">";
  
  $listed_page = '1';
  
  while($listed_page <= $numofpages){
    if($listed_page == $page){
	  echo "<option value=\"$listed_page\" selected=\"selected\"> $listed_page </option>";
	}
	else{
	  echo "<option value=\"$listed_page\"> $listed_page </option>";
	}
	$listed_page++;
  }

  echo "</select> out of $numofpages";
  
  if($page != $numofpages){
    $next_page = $page + 1;
    echo "<a href=\"logs.php?page=$next_page&amp;show=$limit\">
	      <img src=\"images/next.png\" alt=\" &lt;- \" /></a>";
	
  }
  
  echo "</td>
  <td><p>Showing <input name=\"show\" type=\"text\" size=\"3\" value=\"$limit\" /> results per page 
  <input type=\"submit\" value=\" Go \" /></p></td></tr></table></form>";
  
  require_once('./include/footer.php');
} // Ends view_tail function


?>