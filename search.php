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
  

  if(strlen($search) < "3"){
    $notice = "You must enter a search phrase of three characters or more in order to find results.";
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
	    $sql = "SELECT id, name, start_ip, end_ip, mask, note FROM subnets WHERE start_ip & '$long_mask' = '$long_ip' AND
		modified_at > '$fromdate 00:00:00' AND modified_at < '$todate 23:59:59'";
      }
	  else{
	    $sql = "SELECT id, name, start_ip, end_ip, mask, note FROM subnets WHERE $second LIKE '%$search%' AND
		modified_at > '$fromdate 00:00:00' AND modified_at < '$todate 23:59:59'";
	  }
	}
	else{
	  if($second == "ip"){
	    $sql = "SELECT id, name, start_ip, end_ip, mask, note FROM subnets WHERE start_ip & '$long_mask' = '$long_ip'";
      }
	  else{
	    $sql = "SELECT id, name, start_ip, end_ip, mask, note FROM subnets WHERE $second LIKE '%$search%'";
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
		 "<th align=\"left\">Statics Used</th>".
	     "<th align=\"left\">Note</th></tr>\n".
	     "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
		 
 
  while(list($subnet_id,$name,$long_start_ip,$long_end_ip,$long_mask,$note) = mysql_fetch_row($row)){
    $start_ip = long2ip($long_start_ip);
	$mask = long2ip($long_mask);
	
	$subnet_size = $long_end_ip - $long_start_ip;
	
	$sql = "SELECT COUNT(*) FROM statics WHERE subnet_id='$subnet_id'";
	$result = mysql_query($sql);
	$static_count = mysql_result($result, 0, 0);
	
	$sql = "SELECT start_ip, end_ip FROM acl WHERE apply='$subnet_id'";
	$result = mysql_query($sql);
	while(list($long_acl_start,$long_acl_end) = mysql_fetch_row($result)){
	  $subnet_size = $subnet_size - ($long_acl_end - $long_acl_start);
	}
	
	$percent_subnet_used = round('100' * ($static_count / $subnet_size));
	
    echo "<tr\">
	     <td><b>$name</b></td><td>$start_ip</td>
		 <td>$mask</td><td>$percent_subnet_used</td>
		 <td>$note</td></tr>";
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
  
  $first = $first_input = clean($_GET['first']);
  $second = $second_input = clean($_GET['second']);
  $search = $search_input = clean($_GET['search']);
  $fromdate = $fromdate_input = clean($_GET['fromdate']);
  $todate = $todate_input = clean($_GET['todate']);
  $when = $when_input = clean($_GET['when']);
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
	    $sql = "SELECT id, name, start_ip, end_ip, mask, note FROM subnets WHERE start_ip & '$long_mask' = '$long_ip' AND
		modified_at > '$fromdate 00:00:00' AND modified_at < '$todate 23:59:59'";
      }
	  else{
	    $sql = "SELECT id, name, start_ip, end_ip, mask, note FROM subnets WHERE $second LIKE '%$search%' AND
		modified_at > '$fromdate 00:00:00' AND modified_at < '$todate 23:59:59'";
	  }
	}
	else{
	  if($second == "ip"){
	    $sql = "SELECT id, name, start_ip, end_ip, mask, note FROM subnets WHERE start_ip & '$long_mask' = '$long_ip'";
      }
	  else{
	    $sql = "SELECT id, name, start_ip, end_ip, mask, note FROM subnets WHERE $second LIKE '%$search%'";
	  }
	}
  }
  elseif($first == "1"){ // Statics earch
    $first = "static IPs";
	
	if($when == "dates"){
	  $extrasearchdescription = "and the record was last modified between $fromdate and $todate";
	  if($second == "ip"){
	    $sql = "SELECT id, ip, name, contact, note, subnet_id FROM statics WHERE ip & '$long_mask' = '$long_ip' AND
		modified_at > '$fromdate 00:00:00' AND modified_at < '$todate 23:59:59'";
	  }
	  else{
	    $sql = "SELECT id, ip, name, contact, note, subnet_id FROM statics WHERE $second LIKE '%$search%' AND
		modified_at > '$fromdate 00:00:00' AND modified_at < '$todate 23:59:59'";
	  }
	}
	else{
      if($second == "ip"){
	    $sql = "SELECT id, ip, name, contact, note, subnet_id FROM statics WHERE ip & '$long_mask' = '$long_ip'";
	  }
	  else{
	    $sql = "SELECT id, ip, name, contact, note, subnet_id FROM statics WHERE $second LIKE '%$search%'";
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
  $show = (!isset($_GET['show'])) ? $_SESSION['show'] : $_GET['show'];
  
  $_SESSION['show'] = $show;

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
  if($page == '0') { $page = '1'; }
  $lowerlimit = $page * $limit - $limit;
  $sql .= " LIMIT $lowerlimit, $limit";
  $row = mysql_query($sql);
  $rows = mysql_num_rows($row);
   
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
  
  echo "<form action=\"search.php\" method=\"get\"><table width=\"80%\"><tr><td align=\"left\">\n".
       "<p><input type=\"hidden\" name=\"op\" value=\"search\" />
	       <input type=\"hidden\" name=\"first\" value=\"$first_input\" />
	       <input type=\"hidden\" name=\"second\" value=\"$second_input\" />
		   <input type=\"hidden\" name=\"search\" value=\"$search_input\" />
		   <input type=\"hidden\" name=\"when\" value=\"$when_input\" />
		   <input type=\"hidden\" name=\"fromdate\" value=\"$fromdate_input\" />
		   <input type=\"hidden\" name=\"todate\" value=\"$todate_input\" />";
		   
  if($page != '1'){
    $previous_page = $page - 1;
	echo "<a href=\"search.php?op=search&amp;first=$first_input&amp;second=$second_input&amp;search=$search_input&amp;when=$when_input&amp;fromdate=$fromdate_input&amp;todate=$todate_input&amp;page=$previous_page&amp;show=$limit\">
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
    echo "<a href=\"search.php?op=search&amp;first=$first_input&amp;second=$second_input&amp;search=$search_input&amp;when=$when_input&amp;fromdate=$fromdate_input&amp;todate=$todate_input&amp;page=$next_page&amp;show=$limit\">
	      <img src=\"images/next.png\" alt=\" &lt;- \" /></a>";
	
  }
  
  echo "</p></td>
  <td><p>Showing <input name=\"show\" type=\"text\" size=\"3\" value=\"$limit\" /> results per page 
  <input type=\"submit\" value=\" Go \" /></p></td></tr></table></form>";

  if($first == "subnets"){
    echo "<table width=\"100%\">\n". 
	     "<tr><th align=\"left\">Subnet Name</th>".
	     "<th align=\"left\">Network Address</th>".
	     "<th align=\"left\">Subnet Mask</th>".
	     "<th align=\"left\">Statics Used</th></tr>\n".
	     "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
		 
 
while(list($subnet_id,$name,$long_start_ip,$long_end_ip,$long_mask,$note) = mysql_fetch_row($row)){
    $start_ip = long2ip($long_start_ip);
	$mask = long2ip($long_mask);
	
	$subnet_size = $long_end_ip - $long_start_ip;
	
	$sql = "SELECT COUNT(*) FROM statics WHERE subnet_id='$subnet_id'";
	$result = mysql_query($sql);
	$static_count = mysql_result($result, 0, 0);
	
	$sql = "SELECT start_ip, end_ip FROM acl WHERE apply='$subnet_id'";
	$result = mysql_query($sql);
	while(list($long_acl_start,$long_acl_end) = mysql_fetch_row($result)){
	  $subnet_size = $subnet_size - ($long_acl_end - $long_acl_start);
	}
	
	$percent_subnet_used = round('100' * ($static_count / $subnet_size));
	
	if($percent_subnet_used > '90'){
	  $font_color = 'red';
	}
	elseif($percent_subnet_used > '70'){
	  $font_color = 'orange';
	}
	else{
	  $font_color = 'green';
	}
	
	$percent_subnet_used = "<b>~$percent_subnet_used%</b>";
	
    echo "<tr id=\"subnet_".$subnet_id."_row_1\">
	     <td><b><span id=\"edit_name_".$subnet_id."\">$name</span></b></td><td><a href=\"statics.php?subnet_id=$subnet_id\">$start_ip</a></td>
		 <td>$mask</td><td style=\"color: $font_color;\">$percent_subnet_used</td>
		 <td>";
		 
	if($_SESSION['accesslevel'] >= '3' || $COLLATE['settings']['perms'] > '3'){
	  echo " <a href=\"#\" onclick=\"if (confirm('Are you sure you want to delete this object?')) { new Element.update('notice', ''); new Ajax.Updater('notice', '_subnets.php?op=delete&subnet_id=$subnet_id', {onSuccess:function(){ new Effect.Parallel( [new Effect.Fade('subnet_".$subnet_id."_row_1'), new Effect.Fade('subnet_".$subnet_id."_row_2'), new Effect.Fade('subnet_".$subnet_id."_row_3')]); }}); };\"><img src=\"./images/remove.gif\" alt=\"X\" /></a>";
	}
    echo "</td>
		 </tr>\n";
		 
	echo "<tr id=\"subnet_".$subnet_id."_row_2\"><td colspan=\"4\"><span id=\"edit_note_".$subnet_id."\">$note</span></td></tr>\n";
    echo "<tr id=\"subnet_".$subnet_id."_row_3\"><td colspan=\"5\"><hr class=\"division\" /></td></tr>\n";
	
	if($_SESSION['accesslevel'] >= '3' || $COLLATE['settings']['perms'] > '3'){
	       
      $javascript .=
		   "<script type=\"text/javascript\"><!--\n".
	       "  new Ajax.InPlaceEditor('edit_name_".$subnet_id."', '_subnets.php?op=edit&subnet_id=$subnet_id&edit=name',
		      {highlightcolor: '#a5ddf8', 
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
			  );\n".
		   "  new Ajax.InPlaceEditor('edit_note_".$subnet_id."', '_subnets.php?op=edit&subnet_id=$subnet_id&edit=note',
		      {highlightcolor: '#a5ddf8',  
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
			  );\n".
		   "--></script>\n";
	}
  }
	echo "</table>\n";
  }
  elseif($first == "static IPs"){
    echo "<table width=\"100%\"><tr><th>IP Address</th><th>Name</th><th>Contact</th><th>Action</th></tr>".
	     "<tr><td colspan=\"4\"><hr class=\"head\" /></td></tr>\n";
  
  while(list($static_id,$ip,$name,$contact,$note) = mysql_fetch_row($row)){
    $ip = long2ip($ip);
    echo "<tr id=\"static_".$static_id."_row_1\">
	     <td>$ip</td><td><span id=\"edit_name_".$static_id."\">$name</span></td>
		 <td><span id=\"edit_contact_".$static_id."\">$contact</span></td>
		 <td>";
		 
	if($_SESSION['accesslevel'] >= '2' || $COLLATE['settings']['perms'] > '2'){
	  echo " <a href=\"#\" onclick=\"if (confirm('Are you sure you want to delete this object?')) { new Element.update('notice', ''); new Ajax.Updater('notice', '_statics.php?op=delete&static_ip=$ip', {onSuccess:function(){ new Effect.Parallel( [new Effect.Fade('static_".$static_id."_row_1'), new Effect.Fade('static_".$static_id."_row_2'), new Effect.Fade('static_".$static_id."_row_3')]); }}); };\"><img src=\"./images/remove.gif\" alt=\"X\" /></a>";
	}
    echo "</td>
		 </tr>\n";
	echo "<tr id=\"static_".$static_id."_row_2\"><td colspan=\"3\"><span id=\"edit_note_".$static_id."\">$note</span></td></tr>\n";
    echo "<tr id=\"static_".$static_id."_row_3\"><td colspan=\"5\"><hr class=\"division\" /></td></tr>\n";
	
	if($_SESSION['accesslevel'] >= '2' || $COLLATE['settings']['perms'] > '2'){
      $javascript .=	  
	       "<script type=\"text/javascript\"><!--\n".
	       "  new Ajax.InPlaceEditor('edit_name_".$static_id."', '_statics.php?op=edit&static_id=$static_id&edit=name',
		      {highlightcolor: '#a5ddf8', 
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
			  );\n".
		   "  new Ajax.InPlaceEditor('edit_contact_".$static_id."', '_statics.php?op=edit&static_id=$static_id&edit=contact',
		      {highlightcolor: '#a5ddf8',  
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
			  );\n".
		    "  new Ajax.InPlaceEditor('edit_note_".$static_id."', '_statics.php?op=edit&static_id=$static_id&edit=note',
		      {highlightcolor: '#a5ddf8',  
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
			  );\n".
		   "--></script>\n";
	}
  }
    echo "</table>\n";
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
    echo "</table>\n";
  }
  
  echo "<form action=\"search.php\" method=\"get\"><table width=\"80%\"><tr><td align=\"left\">\n".
       "<p><input type=\"hidden\" name=\"op\" value=\"search\" />
	       <input type=\"hidden\" name=\"first\" value=\"$first_input\" />
	       <input type=\"hidden\" name=\"second\" value=\"$second_input\" />
		   <input type=\"hidden\" name=\"search\" value=\"$search_input\" />
		   <input type=\"hidden\" name=\"when\" value=\"$when_input\" />
		   <input type=\"hidden\" name=\"fromdate\" value=\"$fromdate_input\" />
		   <input type=\"hidden\" name=\"todate\" value=\"$todate_input\" />";
		   
  if($page != '1'){
    $previous_page = $page - 1;
	echo "<a href=\"search.php?op=search&amp;first=$first_input&amp;second=$second_input&amp;search=$search_input&amp;when=$when_input&amp;fromdate=$fromdate_input&amp;todate=$todate_input&amp;page=$previous_page&amp;show=$limit\">
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
    echo "<a href=\"search.php?op=search&amp;first=$first_input&amp;second=$second_input&amp;search=$search_input&amp;when=$when_input&amp;fromdate=$fromdate_input&amp;todate=$todate_input&amp;page=$next_page&amp;show=$limit\">
	      <img src=\"images/next.png\" alt=\" &lt;- \" /></a>";
	
  }
  
  echo "</p></td>
  <td><p>Showing <input name=\"show\" type=\"text\" size=\"3\" value=\"$limit\" /> results per page 
  <input type=\"submit\" value=\" Go \" /></p></td></tr></table></form>";
  
  echo $javascript;

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
  <h1>Advanced Search</h1>
  <br />
  <form onload="init();" id="test" action="search.php" method="get">
  <p>Look in<input type="hidden" name="op" value="search" />
  <select name="first" onchange="populate();">
    <option value="0">subnets</option>
	<option value="1">static IPs</option>
	<option value="2">logs</option>
  </select>
  for a/an
  <select name="second">
	<option value="ip">IP</option>
	<option value="name">name</option>
	<option value="note">note</option>
	<option value="modified_by">last modified by</option>
  </select> matching: <input name="search" type="text" /> &nbsp;
  <br />
  <br />
  <input type="radio" name="when" value="all" checked="checked" onclick="new Effect.Fade('extraforms', {duration: 0.2})" /> Search all records <br />
  <input type="radio" name="when" value="dates" onclick="new Effect.Appear('extraforms', {duration: 0.2})" /> Specify a date range<br />
  <div id="extraforms" style="display: none;">
    <br />
    <b>From:</b><br />
      <script type="text/javascript">DateInput('fromdate', 'false', 'YYYY-MM-DD')</script>
	<br />
    <b>To:</b><br />
      <script type="text/javascript">DateInput('todate', 'false', 'YYYY-MM-DD')</script>
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
