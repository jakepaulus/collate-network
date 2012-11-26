<?php
session_start();
// Let the sessions expire when the user closes the browser window or logs out. Hopefully no timeouts
ini_set("session.gc_maxlifetime", "86400");


//------------- Build COLLATE array ------------------------------------------------------------

$COLLATE = array();

if(isset($_SESSION['accesslevel'])){
  $COLLATE['user']['accesslevel'] = $_SESSION['accesslevel'];
  $COLLATE['user']['username'] = $_SESSION['username'];
  $COLLATE['user']['language'] = $_SESSION['language'];
  $COLLATE['user']['ldapexempt'] = $_SESSION['ldapexempt'];
}
else{
  $COLLATE['user']['accesslevel'] = "0";
}


// ---------- Default show -----------------------------------------------------------------------
$_SESSION['show'] = (!isset($_SESSION['show'])) ? '10' : $_SESSION['show'];


//---------- Populate $COLLATE['settings'] with settings from db ----------------------------------
require_once dirname(__FILE__).'/db_connect.php'; 

$sql = "SELECT name, value FROM settings";
$result = mysql_query($sql);
  
while ($column = mysql_fetch_assoc($result)) {
  // $COLLATE['settings']['setting_name'] will be set to the seting's value.
  $COLLATE['settings'][$column['name']] = $column['value'];
}  


//------------- Set the language ------------------------------------------------------------

$COLLATE['languages'] = array();

if(isset($COLLATE['user']['language'])){
  require_once dirname(__FILE__)."/../languages/".$COLLATE['user']['language'].".php";
  $COLLATE["languages"]["selected"] = $languages[$COLLATE['user']['language']];
  
}
else{
  require_once dirname(__FILE__)."/../languages/".$COLLATE['settings']['language'].".php";
  $COLLATE["languages"]["selected"] = $languages[$COLLATE['settings']['language']];
}

// --------------- Prevent Unwanted Access ---------------------------------------------------

/**
 * The goal of this section is to compare $_SESSION['accesslevel'] with the $accesslevel
 * parameter and allow or deny access. Each function has a hard-coded value 
 * to check for to allow the function to run. When AccessControl has determined the
 * user has enough access for the function, it will stop further checks.
 * 
 * Access Level 0 = Access denied completely: User can see index.php and login.php (this is the default for a new user)
 * Access Level 1 = Read-Only access, no changes can be made
 * Access Level 2 = Can make changes to statics table
 * Access Level 3 = Can make changes to subnets table + level 2
 * Access Level 4 = Can make changes to blocks table + level 3
 * Access Level 5 = Full control of the application including setting changes, user's access level modifications, and user password resets.
 */

 function AccessControl($accesslevel, $message) {
   global $COLLATE;
   
  if($accesslevel < $COLLATE['settings']['perms']) { // We're not requiring loggin or logging
    return;
  }
  elseif(!isset($_SESSION['username'])) { // the user isn't logged in.
    $returnto = urlencode($_SERVER['REQUEST_URI']); // return the user to where they came from with this var
    $notice = "login-notice";
    header("Location: login.php?notice=$notice&returnto=$returnto");
    exit();
  }
  elseif($_SESSION['accesslevel'] >= $accesslevel){
    if($accesslevel > "1"){
      collate_log($accesslevel, $message);
	}
    return; // Access is allowed
  }
  // if we've gotten this far in the function, we've not met any condition to allow access so access is denied.
  $notice = "perms-notice";
  header("Location: index.php?notice=$notice");
  exit();  
  
} // Ends AccessControl function


function clean($variable){ 

  $invalid = array();
  $invalid['0'] = '"'; // removes single quotes
  $invalid['1'] = '\\"'; // removes single quotes (escaped quotes would leave slashes, which look ugly where they dont belong)
  $invalid['2'] = '\''; // removes double quotes
  $invalid['3'] = '\\\''; // removes double quotes (escaped quotes would leave slashes, which look ugly where they dont belong)
  $invalid['4'] = ';'; // simicolons are used as the separator for API calls, so we don't allow them in the data.

  $variable = str_replace($invalid, '', $variable);
  $variable = strip_tags(trim($variable)); 
  return $variable;
}

//------------Logging Function------------------------------------------------------
function collate_log($accesslevel, $message){
  if ($message == null){ return; }
    
  global $COLLATE;
  $ipaddress = $_SERVER['REMOTE_ADDR'];
  
  if($accesslevel <= "2"){ $level = "low"; }
  if($accesslevel == "3"){ $level = "normal"; }
  if($accesslevel >= "4"){ $level = "high"; }
  
  $username = (!isset($COLLATE['user']['username'])) ? 'system' : $COLLATE['user']['username'];
  
  $sql = "INSERT INTO logs (occuredat, username, ipaddress, level, message) VALUES(NOW(), '$username', '$ipaddress', '$level', '$message')";
  mysql_query($sql);
 
} // Ends collate_log function


# Really dumb/ugly/embarassing hack to make the integer representation
# of IP addresses larger than 127.255.255.255 look like signed integers
# as they would be represented on 32-bit systems...on 64-bit systems.
# Please don't point and laugh too much at me for this.
function ip2decimal($ip) {
        $special_number = '2147483648';
		if (ip2long($ip) === false){ return false; }
        $long_ip = ip2long($ip);
        if ($long_ip == $special_number){
                $long_ip = -1*$ip;
        }
        if($long_ip > $special_number){
                $difference = $long_ip - $special_number;
                $long_ip = -$special_number + $difference;

        }
        return $long_ip;

}

# this function takes a subnet number and mask in decimal and returns
# a subnet number and mask in cidr notation. E.g.: 
# $ip = 167772160 and $mask = -256 is returned as "10.0.0.0/24"
function subnet2cidr($ip,$mask){
	$ip=long2ip($ip);
	$mask=substr_count(decbin($mask), '1');
	return "$ip/$mask";
}

// Netmask Validator // modified from the version in the comments on php.net/ip2long
function checkNetmask($ip) {
 if (!ip2decimal($ip)) {
  return false;
 }
 $binip=decbin(ip2decimal($ip));
 
 if(strlen($binip) != 32 && ip2decimal($ip) != 0) {
  return false;
 }
 elseif(preg_match("/01/", "$binip") || !preg_match("/0/", "$binip")) {
  return false;
 }
 else {
  return true;
 }
}

function pageselector($sql,$hiddenformvars){
  global $COLLATE;
     
  #Note: If you have no hidden variables to pass to the page select form
  #such as is done on the search page, just pass an empty string
  
  $result = mysql_query($sql);
  $totalrows = mysql_num_rows($result);
  
  $page = (!isset($_GET['page'])) ? "1" : $_GET['page'];
  $show = (!isset($_GET['show'])) ? $_SESSION['show'] : $_GET['show'];
  
  $url = preg_replace("/page[^i]\w++&*|show[^i]\w++&*/","",$_SERVER['REQUEST_URI']);
  if(!strstr($url, "?")){ #script name with no GET variables passed
    $url .= '?';
  }
  elseif(!preg_match("/\?$|&$/", $url)){ #maintain GET variables
    $url .= '&';
  }
  
  if(is_numeric($show) && $show <= '250' && $show > '5'){
    $limit = $show;
  }
  elseif($show > '250'){
    echo "<div class=\"tip\"><p>".$COLLATE['languages']['selected']['listlimitnote']."</p></div>";
    $limit = '250';
  }
  else{
    $limit = "10";
  }
  
  $_SESSION['show'] = $limit;
  
  
  $numofpages = ceil($totalrows/$limit);
  if($page > $numofpages){
    $page = $numofpages;
  }
  if($page == '0'){ $page = '1';} // Keeps errors from occuring in the following SQL query if no rows have been added yet.
  $lowerlimit = $page * $limit - $limit;
  $sql .= " LIMIT $lowerlimit, $limit";
  
  echo "<form action=\"".$_SERVER['SCRIPT_NAME']."\" method=\"get\"><p>\n $hiddenformvars";
  
  if($page != '1'){
    $previous_page = $page - 1;
	echo "<a href=\"{$url}page=$previous_page&amp;show=$limit\">
	      <img src=\"images/prev.png\" alt=\" &gt;- \" /></a> ";
  }
  
  echo $COLLATE['languages']['selected']['Page']."<select onchange=\"this.form.submit();\" name=\"page\">";
  
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
  $outofpages = str_replace("%numofpages%", "$numofpages", $COLLATE['languages']['selected']['outofpages']);
  echo "</select> $outofpages";
  
  if($page != $numofpages){
    $next_page = $page + 1;
    echo " <a href=\"{$url}page=$next_page&amp;show=$limit\"><img src=\"images/next.png\" alt=\" &lt;- \" /></a>";
	
  }
  $showcount = str_replace("%count%", "<input name=\"show\" type=\"text\" size=\"3\" value=\"$limit\" />", $COLLATE['languages']['selected']['showcount']);
  echo " &nbsp; $showcount <input type=\"submit\" value=\" ".$COLLATE['languages']['selected']['Go']." \" /></p></form>\n";
  return $sql;
}

function get_formatted_subnet_util($subnet_id,$subnet_size,$in_color){
  $sql = "SELECT COUNT(*) FROM statics WHERE subnet_id='$subnet_id'";
  $result = mysql_query($sql);
  $static_count = mysql_result($result, 0, 0);
  
  $sql = "SELECT start_ip, end_ip FROM acl WHERE subnet_id='$subnet_id'";
  $result = mysql_query($sql);
  if ($result != false) {
    while(list($long_acl_start,$long_acl_end) = mysql_fetch_row($result)){
      $subnet_size = $subnet_size - ($long_acl_end - $long_acl_start);
    }
  }
  
  if ($subnet_size == '0'){
    $percent_subnet_used = '100'; // short cut to bypass cases where a whole subnet
                                  // is ACL'd out for DHCP resulting in a subnet_size of 0
  }
  else {
    $percent_subnet_used = round('100' * ($static_count / $subnet_size));
  }
  
  if($percent_subnet_used > '90'){
    $font_color = 'red';
  }
  elseif($percent_subnet_used > '70'){
    $font_color = 'orange';
  }
  else{
    $font_color = 'green';
  }
  
  if($in_color){
    $percent_subnet_used = "<td style=\"color: $font_color;\"><b>~$percent_subnet_used%</b></td>";
  }
  else{
    $percent_subnet_used = "<td>$percent_subnet_used%</td>";
  }
  return $percent_subnet_used;
}

?>