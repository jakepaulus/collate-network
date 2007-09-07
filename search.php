<?php
/*
  * Please see /include/common.php for documentation on common.php and the $COLLATE global array used by this application as well as the AccessControl function used widely.
 */
require_once('./include/common.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){

  case "download";
  download();
  break;
  
  case "search";
  search();
  break;
  
  default: 
  require_once('./include/header.php');
  show_form();
  break;
}

/*
 * The download function takes the same GET inputs as the search function but outputs to an excel file that the user can download.
 * Excel can interpret html, so we're just outputting everything the search function does from <table> to </table> and then forcing
 * a save dialog using the header() function. The download function has to be a separate page because we've already produced output 
 * to the browser in the search function that we don't want in the spreadsheet by the time we get to the actual search results.
 */

function download(){
  $accesslevel = "1";
  $message = "search exported";
  AccessControl($accesslevel, $message); 
    
  $first = clean($_GET['first']);
  $second = clean($_GET['second']);
  $search = clean($_GET['search']);
  $fromdate = clean($_GET['from_year'])."-".clean($_GET['from_month'])."-".clean($_GET['from_day']);
  $todate = clean($_GET['to_year'])."-".clean($_GET['to_month'])."-".clean($_GET['to_day']);
  $when = clean($_GET['when']);
    if($fromdate == $todate){ // The user forgot to move the button back to "all" without selecting specific dates
    $when = "all";
  }
  

  if(strlen($search) <= "3"){
    $notice = "You must enter a search phrase of four characters or more in order to find results.";
	header("Location: search.php?notice=$notice");
	exit();
  }
  
  if(($first == '0' || $first == '1') && $second == "ip"){
     
    if(!strstr($search, '/')){
	  $ip = $search;
	  $mask = '32';
	}
	else{
      list($ip,$mask) = explode('/', $search);
	}
  
    if(ip2long($ip) == FALSE){
      $notice = "The IP you have entered is not valid.";
      header("Location: search.php?notice=$notice");
	  exit();
    }
  
    $ip = long2ip(ip2long($ip));  
    if(!strstr($mask, '.') && ($mask <= '0' || $mask >= '32')){
      $notice = "The IP you have specified is not valid. The mask cannot be 0 or 32 bits long.";
      header("Location: search.php?notice=$notice");
      exit();
    }
    elseif(!strstr($mask, '.')){
      $bin = str_pad('', $mask, '1');
	  $bin = str_pad($bin, '32', '0');
	  $mask = bindec(substr($bin,0,8)).".".bindec(substr($bin,8,8)).".".bindec(substr($bin,16,8)).".".bindec(substr($bin,24,8));
      $mask = long2ip(ip2long($mask));
    }
    elseif(!checkNetmask($mask)){
      $notice = "The mask you have specified is not valid.";
      header("Location: search.php?notice=$notice");
	  exit();
    }
  }
  $long_ip = ip2long($ip);
  $long_mask = ip2long($mask);
  
  if($first == "0") { // Subnet search
    $first = "subnets";
	$First = "Subnets";
	
	if($when == "dates"){
	  $extrasearchdescription = "and the record was last modified between $fromdate and $todate";
	  if($second == "ip"){
	    $sql = "SELECT id, name, start_ip, mask, note FROM subnets WHERE start_ip & '$long_mask' = '$long_ip' AND
		modified_at > '$fromdate 00:00:00' AND modified_at < '$todate 23:59:59'";
      }
	  else{
	    $sql = "SELECT id, name, start_ip, mask, note FROM subnets WHERE $second LIKE '%$search%' AND
		modified_at > '$fromdate 00:00:00' AND modified_at < '$todate 23:59:59'";
	  }
	}
	else{
	  if($second == "ip"){
	    $sql = "SELECT id, name, start_ip, mask, note FROM subnets WHERE start_ip & '$long_mask' = '$long_ip'";
      }
	  else{
	    $sql = "SELECT id, name, start_ip, mask, note FROM subnets WHERE $second LIKE '%$search%'";
	  }
	}
  }
  elseif($first == "1"){ // Statics earch
    $first = "static IPs";
	
	if($when == "dates"){
	  $extrasearchdescription = "and the record was last modified between $fromdate and $todate";
	  if($second == "ip"){
	    $sql = "SELECT id, ip, name, contact, note FROM statics WHERE ip & '$long_mask' = '$long_ip' AND
		modified_at > '$fromdate 00:00:00' AND modified_at < '$todate 23:59:59'";
	  }
	  else{
	    $sql = "SELECT id, ip, name, contact, note FROM statics WHERE $second LIKE '%$search%' AND
		modified_at > '$fromdate 00:00:00' AND modified_at < '$todate 23:59:59'";
	  }
	}
	else{
      if($second == "ip"){
	    $sql = "SELECT id, ip, name, contact, note FROM statics WHERE ip & '$long_mask' = '$long_ip'";
	  }
	  else{
	    $sql = "SELECT id, ip, name, contact, note FROM statics WHERE $second LIKE '%$search%'";
	  }
	}
  }
  elseif($first == "2"){ // They're trying to search logs
    $first = "logs";
	$First = "Logs";
	$Second = ucfirst($second);
	if($when == "dates"){
	  $extrasearchdescription = "and the event occured between $fromdate and $todate";
	  $sql = "SELECT occuredat, username, ipaddress, level, message FROM logs WHERE $second LIKE '%$search%' AND ".
	         "occuredat<'$fromdate 00:00:00' AND occuredat>'$todate 23:59:59' ORDER BY id DESC";
	}
	else{
	  $sql = "SELECT occuredat, username, ipaddress, level, message FROM logs WHERE $second LIKE '%$search%' ORDER BY id DESC";
	}
  }

  $row = mysql_query($sql);
  $totalrows = mysql_num_rows($row);
 
  if($totalrows < "1"){
    echo "<p><b>You searched for:</b><br />All $first where \"$second\" is like \"$search\" $extrasearchdescription</p>".
         "<hr class=\"head\" />".
         "<p><b>No results were found that matched your search.</b></p>";
	require_once('./include/footer.php');
	exit();
  }
  
  ob_start();

  if($first == "subnets"){
    echo "<table width=\"100%\">\n". 
	     "<tr><th align=\"left\">Subnet Name</th>".
	     "<th align=\"left\">Network Address</th>".
	     "<th align=\"left\">Subnet Mask</th>".
	     "<th align=\"left\">Note</th></tr>\n".
	     "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
		 
 
    while(list($subnet_id,$name,$long_start_ip,$long_mask,$note) = mysql_fetch_row($row)){
      $start_ip = long2ip($long_start_ip);
	  $mask = long2ip($long_mask);
	
      echo "<tr>
	       <td><b><a href=\"statics.php?subnet_id=$subnet_id\">$name</a></b></td><td>$start_ip</td>
	       <td>$mask</td>
		   <td>$note</td>
		   </tr>\n";
    }
	echo "</table>\n";
  }
  elseif($first == "static IPs"){
    echo "<table width=\"100%\"><tr><th>IP Address</th><th>Name</th><th>Contact</th><th>Note</th></tr>".
	     "<tr><td colspan=\"4\"><hr class=\"head\" /></td></tr>\n";
  
    while(list($static_id,$ip,$name,$contact,$note) = mysql_fetch_row($row)){
      $ip = long2ip($ip);
      echo "<tr>
	       <td>$ip</td><td>$name</td><td>$contact</td>
	       <td>$note</td>
	       </tr>\n";
    }
    echo "</table><br />";
  }
  elseif($first == "logs"){
    echo "<table width=\"100%\"><tr><td><b>Timestamp</b></td><td><b>Username</b></td><td><b>IP Address</b></td>".
         "<td><b>Severity</b></td><td><b>Message</b></td></tr>\n";
    while(list($occuredat,$username,$ipaddress, $level,$message) = mysql_fetch_row($row)){
      echo "<tr><td>$occuredat</td><td>$username</td><td>$ipaddress</td><td>$level</td><td>$message</td></tr>";
    }
  }
  echo "</table>";

  $fileout = ob_get_contents();
  ob_end_clean();
  $size = strlen(pack("A", $fileout));
  $size = ceil($size/8);
  header("Cache-Control: "); //keeps ie happy
  header("Pragma: "); //keeps ie happy
  header("Content-type: application/ms-excel"); // content type
  header("Content-Length: $size");
  header("Content-Disposition: attachment; filename=\"search.xls\"");
  echo $fileout;
}

function search(){

  $export = (!isset($_GET['export'])) ? 'off' : $_GET['export'];
  
  if($export == "on"){ // The download function has to be a separate page because we've already produced output to the browser in this function that we don't want in the spreadsheet.
    $uri = $_SERVER['REQUEST_URI'];
	$uri = str_replace("op=search", "op=download", $uri);
	header("Location: $uri");
	exit();
  }
   
  global $COLLATE;
  $accesslevel = "1";
  $message = "search conducted";
  AccessControl($accesslevel, $message); 
  
  $first = clean($_GET['first']);
  $second = clean($_GET['second']);
  $search = clean($_GET['search']);
  $fromdate = clean($_GET['fromdate']);
  $todate = clean($_GET['todate']);
  $when = clean($_GET['when']);
  if($fromdate == $todate){ // The user forgot to move the button back to "all" without selecting specific dates
    $when = "all";
  }
    
  if(strlen($search) < "3"){
    $notice = "<p>You must enter a search phrase of three characters or more in order to find results.</p>";
	header("Location: search.php?notice=$notice");
	exit();
  }
  
  if(($first == '0' || $first == '1') && $second == "ip"){
  
    if(!strstr($search, '/')){
	  $ip = $search;
	  $mask = '32';
	}
	else{
      list($ip,$mask) = explode('/', $search);
	}
  
    if(ip2long($ip) == FALSE){
      $notice = "The IP you have entered is not valid.";
      header("Location: search.php?notice=$notice");
	  exit();
    }
  
    $ip = long2ip(ip2long($ip));  
    if(!strstr($mask, '.') && ($mask <= '0' || $mask > '32')){
      $notice = "The IP you have specified is not valid. The mask cannot be 0 bits long or longer than 32 bits.";
      header("Location: search.php?notice=$notice");
      exit();
    }
    elseif(!strstr($mask, '.')){
      $bin = str_pad('', $mask, '1');
	  $bin = str_pad($bin, '32', '0');
	  $mask = bindec(substr($bin,0,8)).".".bindec(substr($bin,8,8)).".".bindec(substr($bin,16,8)).".".bindec(substr($bin,24,8));
      $mask = long2ip(ip2long($mask));
    }
    elseif(!checkNetmask($mask)){
      $notice = "The mask you have specified is not valid.";
      header("Location: search.php?notice=$notice");
	  exit();
    }
  }
  $long_ip = ip2long($ip);
  $long_mask = ip2long($mask);
  
  if($first == "0") { // Subnet search
    $first = "subnets";
	$First = "Subnets";
	
	if($when == "dates"){
	  $extrasearchdescription = "and the record was last modified between $fromdate and $todate";
	  if($second == "ip"){
	    $sql = "SELECT id, name, start_ip, mask, note FROM subnets WHERE start_ip & '$long_mask' = '$long_ip' AND
		modified_at > '$fromdate 00:00:00' AND modified_at < '$todate 23:59:59'";
      }
	  else{
	    $sql = "SELECT id, name, start_ip, mask, note FROM subnets WHERE $second LIKE '%$search%' AND
		modified_at > '$fromdate 00:00:00' AND modified_at < '$todate 23:59:59'";
	  }
	}
	else{
	  if($second == "ip"){
	    $sql = "SELECT id, name, start_ip, mask, note FROM subnets WHERE start_ip & '$long_mask' = '$long_ip'";
      }
	  else{
	    $sql = "SELECT id, name, start_ip, mask, note FROM subnets WHERE $second LIKE '%$search%'";
	  }
	}
  }
  elseif($first == "1"){ // Statics earch
    $first = "static IPs";
	
	if($when == "dates"){
	  $extrasearchdescription = "and the record was last modified between $fromdate and $todate";
	  if($second == "ip"){
	    $sql = "SELECT id, ip, name, contact, note FROM statics WHERE ip & '$long_mask' = '$long_ip' AND
		modified_at > '$fromdate 00:00:00' AND modified_at < '$todate 23:59:59'";
	  }
	  else{
	    $sql = "SELECT id, ip, name, contact, note FROM statics WHERE $second LIKE '%$search%' AND
		modified_at > '$fromdate 00:00:00' AND modified_at < '$todate 23:59:59'";
	  }
	}
	else{
      if($second == "ip"){
	    $sql = "SELECT id, ip, name, contact, note FROM statics WHERE ip & '$long_mask' = '$long_ip'";
	  }
	  else{
	    $sql = "SELECT id, ip, name, contact, note FROM statics WHERE $second LIKE '%$search%'";
	  }
	}
  }
  elseif($first == "2"){ // They're trying to search logs
    $first = "logs";
	$First = "Logs";
	$Second = ucfirst($second);
	if($when == "dates"){
	  $extrasearchdescription = "and the event occured between $fromdate and $todate";
	  $sql = "SELECT occuredat, username, ipaddress, level, message FROM logs WHERE $second LIKE '%$search%' AND ".
	         "occuredat>='$fromdate 00:00:00' AND occuredat<='$todate 23:59:59' ORDER BY id DESC";
	}
	else{
	  $sql = "SELECT occuredat, username, ipaddress, level, message FROM logs WHERE $second LIKE '%$search%' ORDER BY id DESC";
	}
  }
  if($second == "username"){
    $Second = "User";
  }
  
  $page = (!isset($_GET['page'])) ? "1" : $_GET['page'];

  $limit = "10";
  $lowerlimit = $page * $limit - $limit;
  $totalrows = mysql_num_rows(mysql_query($sql));
  $sql .= " LIMIT $lowerlimit, $limit";
  $row = mysql_query($sql);
  $rows = mysql_num_rows($row);
  $numofpages = ceil($totalrows/$limit); 
  if(!isset($extrasearchdescription)){
    $extrasearchdescription = "";
  }
  
  require_once('include/header.php');
  echo "<h1>Search Results</h1><br />\n".
       "<p><b>You searched for:</b><br />All $first where \"$second\" is like \"$search\" $extrasearchdescription</p>\n".
       "<hr class=\"head\" />\n";

  if($totalrows < "1"){
    echo "<p><b>No results were found that matched your search.</b></p>";
	require_once('./include/footer.php');
	exit();
  }

  if($first == "subnets"){
    echo "<table width=\"100%\">\n". 
	     "<tr><th align=\"left\">Subnet Name</th>".
	     "<th align=\"left\">Network Address</th>".
	     "<th align=\"left\">Subnet Mask</th>".
	     "<th align=\"left\">Actions</th></tr>\n".
	     "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
		 
 
    while(list($subnet_id,$name,$long_start_ip,$long_mask,$note) = mysql_fetch_row($row)){
      $start_ip = long2ip($long_start_ip);
	  $mask = long2ip($long_mask);
	
      echo "<tr>
	       <td><b><a href=\"statics.php?subnet_id=$subnet_id\">$name</a></b></td><td>$start_ip</td>
	       <td>$mask</td>
		   <td><a href=\"subnets.php?op=delete&amp;block_id=$block_id&amp;subnet_id=$subnet_id\"><img src=\"./images/remove.gif\" alt=\"X\" /></a> &nbsp;
		   &nbsp;<a href=\"subnets.php?op=edit&amp;subnet_id=$subnet_id\"><img src=\"./images/edit.gif\" alt=\"edit\" /></td>
		   </tr>\n";
	  echo "<tr><td>$note<td></tr>\n";
      echo "<tr><td colspan=\"5\"><hr class=\"division\" /></td></tr>\n";
    }
	echo "</table>\n";
  }
  elseif($first == "static IPs"){
    echo "<table width=\"100%\"><tr><th>IP Address</th><th>Name</th><th>Contact</th><th>Delete?</th></tr>".
	     "<tr><td colspan=\"4\"><hr class=\"head\" /></td></tr>\n";
  
    while(list($static_id,$ip,$name,$contact,$note) = mysql_fetch_row($row)){
      $ip = long2ip($ip);
      echo "<tr>
	       <td>$ip</td><td>$name</td><td>$contact</td>
	       <td><a href=\"statics.php?op=delete&amp;subnet_id=$subnet_id&amp;static_ip=$ip\"><img src=\"./images/remove.gif\" alt=\"X\" /></a></td>
	       </tr>\n";
	  echo "<tr><td>$note<td></tr>\n";
      echo "<tr><td colspan=\"5\"><hr class=\"division\" /></td></tr>\n";
    }
    echo "</table><br />";
  }
  elseif($first == "logs"){
    echo "<table width=\"100%\"><tr><td><b>Timestamp</b></td><td><b>Username</b></td><td><b>IP Address</b></td>".
         "<td><b>Severity</b></td><td><b>Message</b></td></tr>\n".
	     "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
		 
    while(list($occuredat,$username,$ipaddress, $level,$message) = mysql_fetch_row($row)){
      if($level == "high"){
	    $level = "<b>$level</b>";
      }
	  echo "<tr><td>$occuredat</td><td>$username</td><td>$ipaddress</td><td>$level</td><td>$message</td></tr>".
	       "<tr><td colspan=\"5\"><hr class=\"division\" /></td></tr>";
    }
    echo "</table>";
  }
  
  $goto = $_SERVER['REQUEST_URI'];
  if(stristr($_SERVER['REQUEST_URI'], "page")){
    $goto = preg_replace("{[&]*page=[0-9]*}", '', $goto); // Matches a string containing page=[zero or more numeric characters] and replaces with nothing
  }
  if(preg_match("/\?[a-zA-Z]/", $goto)){  // At this point there could be a ? mark, but it might not be followed by anything...in which case we don't want to append an &.
    $goto = $goto."&amp;";
  }
  elseif(!stristr($goto, "?")){
    $goto = $goto."?";
  }  
    
  if($numofpages > "1") {
    if($page != "1") { // Generate Prev link only if previous pages exist.
      $pageprev = $page - "1";
       echo "<a href=\"{$goto}page=$pageprev\"> Prev </a>";
    }
    
	if($numofpages < "10"){
	  $i = "1";
      while($i < $page) { // Build all page number links up to the current page
        echo "<a href=\"{$goto}page=$i\"> $i </a>";
	    $i++;
      }
	}
	else {
	  if($page > "4") {
	    echo "...";
	  }
	  $i = $page - "3";
	  while($i < $page ) { // Build all page number links up to the current page
	    if($i > "0"){
          echo "<a href=\"{$goto}page=$i\"> $i </a>";
	    }
		$i++;
      }
	}
    echo "[$page]"; // List Current page
	
	if($numofpages < "10"){	
      $i = $page + "1"; // Now we'll build all the page numbers after the current page if they exist.
      while(($numofpages-$page > "0") && ($i < $numofpages + "1")) {
        echo "<a href=\"{$goto}page=$i\"> $i </a>";
        $i++;
      }
	}
	else{
	  $i = $page + "1";
	  $j = "1";
	  while(($numofpages-$page > "0") && ($i <= $numofpages) && ($j <= "3")) {
        echo "<a href=\"{$goto}page=$i\"> $i </a>";
        $i++;
		$j++;
      }
	  if($i <= $numofpages){
	    echo "...";
	  }
	}
    if($page < $numofpages) { // Generate Next link if there is a page after this one
      $nextpage = $page + "1";
	  echo "<a href=\"{$goto}page=$nextpage\"> Next </a>";
	}
  }
    
    // Regardless of how many pages there are, well show how many records there are and what records we're displaying.
	
    if($lowerlimit == "0") { // The program is happy to start counting with 0, humans aren't.
      $lowerlimit = "1";
    }
	else{
	  $lowerlimit++;
	}
	$upperlimit = $lowerlimit + $limit - 1;
	if($upperlimit > $totalrows) {
	  $upperlimit = $totalrows;
	}
	if($result_count <= $totalrows){
	  $howmany = "$lowerlimit - $upperlimit out of";
	}
	else{
	  $howmany = "";
	}
    echo "<br />\n<br />\nShowing $howmany $totalrows results.<br />\n"; 
  require_once('./include/footer.php'); 

  require_once('./include/footer.php');
  
} // Ends search function

/*
 * The search form uses the script.aculo.us javascript library as well as options.js which is taken from
 * http://www.quirksmode.org/js/options.html. I have modified options.js to call scriptaculous functions that
 * enable actions on changes of drop down lists. Options.js enables dynamic drop-down list contents based on the
 * selection in a previous drop-down list.
 */

function show_form(){
  global $COLLATE;
  $accesslevel = "1";
  $message = "search form accessed";
  AccessControl($accesslevel, $message); 
  
  require_once('include/header.php');
  
  ?>
  <script type="text/javascript" src="javascripts/options.js"></script>
  <script type="text/javascript" src="javascripts/calendarDateInput.js">
  /***********************************************
  * Jason's Date Input Calendar- By Jason Moon http://calendar.moonscript.com/dateinput.cfm
  * Script featured on and available at http://www.dynamicdrive.com
  * Keep this notice intact for use.
  ***********************************************/
  </script>
  <script type="text/javascript">
    window.onload = init();
  </script>
  <h1>Search</h1>
  <br />
  <form onload="init();" id="test" action="search.php" method="get">
  <p><b>Search:</b><br />
  <input type="hidden" name="op" value="search" />
  <select name="first" onchange="populate();">
    <option value="0">Subnets</option>
	<option value="1">Static IPs</option>
	<option value="2">Logs</option>
  </select>
  matching
  <select name="second">
	<option value="ip">IP</option>
	<option value="name">name</option>
	<option value="note">note</option>
	<option value="modified_by">last modified by</option>
  </select>: <input name="search" type="text" /> &nbsp;
  <br />
  <br />
  <input type="radio" name="when" value="all" checked="checked" onclick="new Effect.Fade('extraforms', {duration: 0.2})" /> in all records <br />
  <input type="radio" name="when" value="dates" onclick="new Effect.Appear('extraforms', {duration: 0.2})" /> specify a date range<br />
  <div id="extraforms" style="display: none;">
    <br />
    <b>From:</b><br />
      <script>DateInput('fromdate', 'false', 'YYYY-MM-DD')</script>
	<br />
    <b>To:</b><br />
      <script>DateInput('todate', 'false', 'YYYY-MM-DD')</script>
  </div>
  <br />
  <br />
  <input type="checkbox" name="export" /> Export Results as a Microsoft Excel compatible spreadsheet<br />
  <br />
  <input type="submit" value=" Go " /></p>
  </form>
  <br />
  <?php
  require_once('./include/footer.php');
} // Ends list_searches function


// Netmask Validator // from the comments on php.net/ip2long
function checkNetmask($ip) {
 if (!ip2long($ip)) {
  return false;
 } elseif(strlen(decbin(ip2long($ip))) != 32 && ip2long($ip) != 0) {
  return false;
 } elseif(ereg('01',decbin(ip2long($ip))) || !ereg('0',decbin(ip2long($ip)))) {
  return false;
 } else {
  return true;
 }
}

?>
