<?php
/**
 * Please see /include/common.php for documentation on common.php, the $COLLATE global array used by this program, and the AccessControl function used widely.
 */
require_once('./include/common.php');
require_once('./include/header.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){

	case "add";
	add_static();
	break;
	
	case "submit";
	submit_static();
	break;
	
	default:
	list_statics();
	break;
}

require_once('./include/footer.php');

function add_static(){
  if(!isset($_GET['subnet_id']) || empty($_GET['subnet_id'])){
    $notice = "Please select an IP block and a subnet to reserve an IP address from.";
	header("Location: blocks.php?notice=$notice");
  }
  $subnet_id = clean($_GET['subnet_id']);
  
  // Create an array with all of the IPs in the subnet, then remove all the IPs that are ACL'd out, then remove all the IPs that are already in use
  $sql = "SELECT start_ip, end_ip FROM subnets WHERE id='$subnet_id'";
  $results = mysql_query($sql);
  
  if(mysql_num_rows($results) != '1'){
    $notice = "The subnet you provided is not valid. Please select an IP block and a subnet to reserve an IP address from.";
	header("Location: blocks.php?notice=$notice");
  }
  
  list($long_subnet_start_ip,$long_subnet_end_ip) = mysql_fetch_row($results);
  $first_usable = $long_subnet_start_ip + '1';
  $last_usable = $long_subnet_end_ip - '1';
  $whole_subnet = range($first_usable, $last_usable);
  $ipspace = $whole_subnet;
  
  $sql = "SELECT start_ip, end_ip FROM acl WHERE start_ip >= '$long_subnet_start_ip' AND end_ip <= '$long_subnet_end_ip'";
  $results = mysql_query($sql);
  
  while(list($start_ip, $end_ip) = mysql_fetch_row($results)){
    $acl = range($start_ip, $end_ip);
	$ipsace = array_diff($ipspace, $acl);
  }
  $ipspace = array_intersect($ipspace, $whole_subnet); // We don't want any IPs that might have ended up in an ACL that aren't in the subnet
  
  $sql = "SELECT ip FROM statics WHERE ip >= '$long_subnet_start_ip' AND ip <= '$long_subnet_end_ip'";
  $results = mysql_query($sql);
  
  if(mysql_num_rows($results) > '0'){
    $static_ips = array();
    while($static_ip = mysql_result($results, 0, 0)){
      array_push($static_ips, $static_ip);
    }
    $ipspace = array_diff($ipspace, $static_ips); // Now we have an array containing only usable IPs
  }
  $ipspace = array_reverse($ipspace);
  $suggested_ip = long2ip(array_pop($ipspace));
  
  $name = (empty($_GET['name'])) ? '' : $_GET['name'];
  $ip = (empty($_GET['ip'])) ? $suggested_ip : $_GET['ip'];
  $note = (empty($_GET['note'])) ? '' : $_GET['note'];
  $contact = (empty($_GET['contact'])) ? '' : $_GET['note'];

  echo "<div id=\"iptip\" style=\"display: none;\" class=\"tip\">Enter the static IP you'd like to reserve. This field is \n".
       "pre-filled with the first available usable IP in the subnet.<br /><br/></div>\n".
       "<h1>Reserve a static IP:</h1>\n".
       "<br />\n".
       "<form action=\"statics.php?op=submit\" method=\"post\">\n".
       "  <p>Name:<br /><input type=\"text\" name=\"name\" value=\"$name\" /></p>\n".
       "  <p>IP Address:<br /><input type=\"text\" name=\"ip\" value=\"$ip\"/>\n".
	   "    <a href=\"#\" onclick=\"new Effect.toggle($('iptip'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
	   "  </p> \n".
       "  <p>Contact Person:<br /><input type=\"text\" name=\"contact\" value=\"$contact\"/></p>\n".
       "  <p>Note: (Optional)<br /><input type=\"text\" name=\"note\" value=\"$note\" /></p>\n".
       "  <p><input type=\"hidden\" name=\"subnet_id\" value=\"$subnet_id\" /><input type=\"submit\" value=\" Go \" /></p>\n".
       "</form>";
} // Ends add_static function

function submit_static(){
} // Ends submit_static function

function list_statics(){
  if(!isset($_GET['subnet_id']) || empty($_GET['subnet_id'])){
    $notice = "Please select the IP Block and Subnet you would like to reserve an IP address from.";
	header("Location: blocks.php?notice=$notice");
  }
  
  $subnet_id = clean($_GET['subnet_id']);
  
  $sql = "SELECT name FROM subnets WHERE id='$subnet_id'";
  $results = mysql_query($sql);
  if(mysql_num_rows($results) != '1') {
    $notice = "You have not selected a valid subnet. Please select the IP Block and Subnet you would like to reserve an IP address from.";
	header("Location: blocks.php?notice=$notice");
  }
  
  $subnet_name = mysql_result($results, 0, 0);
  
  echo "<h1>Static IPs in \"$subnet_name:\"</h1>\n".
       "<p style=\"text-align: right;\"><a href=\"statics.php?op=add&amp;subnet_id=$subnet_id\">
	   <img src=\"./images/add.gif\" alt=\"Add\" /> Reserve an IP </a></p>\n".
	   "<table width=\"100%\"><tr><th>IP Address</th><th>Name</th><th>Contact</th><th>Delete?</th></tr>".
	   "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
  

  $sql = "SELECT ip, name, contact, note FROM statics WHERE subnet_id='$subnet_id'";
  $results = mysql_query($sql);
  
  while(list($ip,$name,$contact,$note) = mysql_fetch_row($results)){
    $ip = long2ip($ip);
    echo "<tr>
	     <td>$ip</td><td>$name</td><td>$contact</td>
		 <td><a href=\"subnets.php?op=delete&amp;subnet_id=$subnet_id\"><img src=\"./images/remove.gif\" alt=\"X\" /></a></td>
		 </tr>\n";
	echo "<tr><td>$note<td></tr>\n";
    echo "<tr><td colspan=\"5\"><hr class=\"division\" /></td></tr>\n";
  }
  echo "</table>";
} // Ends list_statics function