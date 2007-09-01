<?php
/**
 * Please see /include/common.php for documentation on common.php, the $COLLATE global array used by this program, and the AccessControl function used widely.
 */
require_once('./include/common.php');
require_once('./include/header.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){

	case "add";
	AccessControl("2", "Static IP Reservation form accessed");
	add_static();
	break;
	
	case "submit";
	submit_static();
	break;
	
	case "edit";
	edit_static();
	break;
	
	case "update";
	update_static();
	break;
	
	case "delete";
	delete_static();
	break;
	
	default:
	AccessControl("1", "Static IP list viewed");
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
  $sql = "SELECT name, start_ip, end_ip FROM subnets WHERE id='$subnet_id'";
  $results = mysql_query($sql);
  
  if(mysql_num_rows($results) != '1'){
    $notice = "The subnet you provided is not valid. Please select an IP block and a subnet to reserve an IP address from.";
	header("Location: blocks.php?notice=$notice");
  }
  
  list($subnet_name,$long_subnet_start_ip,$long_subnet_end_ip) = mysql_fetch_row($results);
  $first_usable = $long_subnet_start_ip;
  $last_usable = $long_subnet_end_ip - '1';
  $whole_subnet = range($first_usable, $last_usable);
  $ipspace = $whole_subnet;
  
  $sql = "SELECT start_ip, end_ip FROM acl WHERE apply='ALL' OR apply='$subnet_id'";
  $results = mysql_query($sql);
  
  while(list($start_ip, $end_ip) = mysql_fetch_row($results)){
    $acl = range($start_ip, $end_ip);
	$ipspace = array_diff($ipspace, $acl);
  }
  
  $sql = "SELECT ip FROM statics WHERE subnet_id='$subnet_id'";
  $results = mysql_query($sql);
  
  
  if(mysql_num_rows($results) > '0'){
    while($static_ip = mysql_fetch_row($results)){
	  $ipspace = array_diff($ipspace, $static_ip); 
	}    
  }
  $ipspace = array_reverse($ipspace);
  $suggested_ip = long2ip(array_pop($ipspace));
  
  $name = (empty($_GET['name'])) ? '' : $_GET['name'];
  $ip_addr = (empty($_GET['ip_addr'])) ? '' : $_GET['ip_addr'];
  $note = (empty($_GET['note'])) ? '' : $_GET['note'];
  $contact = (empty($_GET['contact'])) ? '' : $_GET['contact'];
  

  echo "<div id=\"iptip\" style=\"display: none;\" class=\"tip\">If the IP Address you're looking for is not
		listed, the address is already in use or is not available because it was part of the range given for 
		the DHCP scope in the subnet.<br /><br/></div>\n".
       "<h1>Reserve a static IP:</h1>\n".
       "<br />\n".
       "<form action=\"statics.php?op=submit\" method=\"post\">\n".
       "  <p>Name:<br /><input type=\"text\" name=\"name\" value=\"$name\" /></p>\n".
       "  <p>IP Address:<br /><select name=\"ip_addr\">\n";
	
  while(!empty($ipspace)){
    $ip = long2ip(array_pop($ipspace));
	if($ip === $ip_addr){
	  echo "<option selected=\"selected\" value=\"$ip\">$ip</option>\n";
	}
	else{
	  echo "<option value=\"$ip\">$ip</option>\n";
	}
  }   
	   
  echo "</select>".
	   "    <a href=\"#\" onclick=\"new Effect.toggle($('iptip'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
	   "  </p> \n".
       "  <p>Contact Person:<br /><input type=\"text\" name=\"contact\" value=\"$contact\"/></p>\n".
       "  <p>Note: (Optional)<br /><input type=\"text\" name=\"note\" value=\"$note\" /></p>\n".
       "  <p><input type=\"hidden\" name=\"subnet_id\" value=\"$subnet_id\" /><input type=\"submit\" value=\" Go \" /></p>\n".
       "</form>";

   
} // Ends add_static function

function submit_static(){
  $name = (empty($_POST['name'])) ? '' : clean($_POST['name']);
  $ip_addr = (empty($_POST['ip_addr'])) ? '' : clean($_POST['ip_addr']);
  $note = (empty($_POST['note'])) ? '' : clean($_POST['note']);
  $contact = (empty($_POST['contact'])) ? '' : clean($_POST['contact']);
  $subnet_id = (empty($_POST['subnet_id'])) ? '' : clean($_POST['subnet_id']);
  
  $accesslevel = "2";
  $message = "Static IP Reservation form submitted: $name";
  AccessControl($accesslevel, $message); 
  
  if(empty($name) || empty($ip_addr) || empty($contact) || empty($subnet_id)){
    $notice = "You have left a required field blank.";
	header("Location: statics.php?op=add&subnet_id=$subnet_id&name=$name&ip_addr=$ip_addr&contact=$contact&note=$note&notice=$notice");
    exit();
  }
  
  $sql = "SELECT name, start_ip, end_ip FROM subnets WHERE id='$subnet_id'";
  $results = mysql_query($sql);
  
  if(mysql_num_rows($results) != '1'){
    $notice = "The subnet you provided is not valid. Please select an IP block and a subnet to reserve an IP address from.";
	header("Location: blocks.php?notice=$notice");
  }
  
  list($subnet_name,$long_subnet_start_ip,$long_subnet_end_ip) = mysql_fetch_row($results);
  $first_usable = $long_subnet_start_ip;
  $last_usable = $long_subnet_end_ip - '1';
  $whole_subnet = range($first_usable, $last_usable);
  $ipspace = $whole_subnet;
  
  $sql = "SELECT start_ip, end_ip FROM acl WHERE apply='ALL' OR apply='$subnet_id'";
  $results = mysql_query($sql);
  
  while(list($start_ip, $end_ip) = mysql_fetch_row($results)){
    $acl = range($start_ip, $end_ip);
	$ipspace = array_diff($ipspace, $acl);
  }
  
  $sql = "SELECT ip FROM statics WHERE subnet_id='$subnet_id'";
  $results = mysql_query($sql);
  
  if(mysql_num_rows($results) > '0'){
    while($static_ip = mysql_fetch_row($results)){
	  $ipspace = array_diff($ipspace, $static_ip); 
	}    
  }
  
  $long_ip_addr = ip2long($ip_addr);	
  
  if(array_search($long_ip_addr, $ipspace) == FALSE){
    $notice = "The IP Address supplied is not valid. Please choose another.";
	header("Location: statics.php?op=add&subnet_id=$subnet_id&name=$name&contact=$contact&note=$note&notice=$notice");
	exit();
  }
  
  $sql = "INSERT INTO statics (ip, name, contact, note, subnet_id) 
		 VALUES('$long_ip_addr', '$name', '$contact', '$note', '$subnet_id')";
		 
  mysql_query($sql);
  
  $notice ="$ip_addr has been reserved for $name.";
  header("Location: statics.php?subnet_id=$subnet_id&notice=$notice");
  exit();
} // Ends submit_static function

function edit_static(){

  $static_id = (empty($_GET['static_id'])) ? '' : $_GET['static_id'];
  
  if(empty($static_id)){
    $notice = "Please select a block, then a subnet, then a static IP to edit.";
	header("Location: blocks.php?notice=$notice");
	exit();
  }
  
  $sql = "SELECT name, contact, note FROM statics WHERE id='$static_id'";
  $result = mysql_query($sql);
  
  if(mysql_num_rows($result) != '1'){
    $notice = "Please select a block, then a subnet, then a static IP to edit.";
	header("Location: blocks.php?notice=$notice");
	exit();
  }
  
  list($name,$contact,$note) = mysql_fetch_row($result);
  
  $accesslevel = "3";
  $message = "Static IP edit form accessed: $name";
  AccessControl($accesslevel, $message); 
  
  echo "<h1>Update Static IP: $name</h1>\n".
	   "<br />\n".
	   "<form action=\"statics.php?op=update\" method=\"POST\">\n".
	   "  <p>Name:<br /><input type=\"text\" name=\"name\" value=\"$name\" /></p>\n".
	   "  <p>Contact:<br /><input type=\"text\" name=\"contact\" value=\"$contact\" /></p>\n".
	   "  <p>Note: (Optional)<br /><input type=\"text\" name=\"note\" value=\"$note\" /></p>\n".
       "  <p><input type=\"hidden\" name=\"static_id\" value=\"$static_id\" /><input type=\"submit\" value=\" Go \" /></p>\n".
	   "</form>\n";

} // Ends edit_static function

function update_static(){

  $static_id = (empty($_POST['static_id'])) ? '' : $_POST['static_id'];
  $name = (empty($_POST['name'])) ? '' : clean($_POST['name']);
  $contact = (empty($_POST['contact'])) ? '' : clean($_POST['contact']);
  $note = (empty($_POST['note'])) ? '' : clean($_POST['note']);
  
  
  $accesslevel = "3";
  $message = "Static IP edit form submitted: $name";
  AccessControl($accesslevel, $message); 
  
  if(empty($static_id)){
    $notice = "Please select an IP block, then a subnet, then a static IP to edit.";
	header("Location: blocks.php?notice=$notice");
	exit();
  }
  elseif(empty($name) || empty($contact) || empty($note)){
    $notice = "The name, contact, and note fields cannot be blank.";
	header("Location: statics.php?op=edit&static_id=$static_id&notice=$notice");
	exit();
  }
  
  $sql = "SELECT subnet_id FROM statics WHERE id='$static_id'";
  $result = mysql_query($sql);
  
  if(mysql_num_rows($result) != '1'){
    $notice = "Please select an IP block, then a subnet, then a static IP to edit.";
	header("Location: blocks.php?notice=$notice");
	exit();
  }
  
  $subnet_id = mysql_result($result, 0, 0);  
  
  $sql = "UPDATE statics SET name='$name', contact='$contact', note='$note' WHERE id='$static_id'";
  mysql_query($sql);
  
  $notice = "The static IP reservation has been updated.";
  header("Location: statics.php?subnet_id=$subnet_id&notice=$notice");
  exit();

} // Ends update_static function

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
	   "<table width=\"100%\"><tr><th>IP Address</th><th>Name</th><th>Contact</th><th>Actions</th></tr>".
	   "<tr><td colspan=\"4\"><hr class=\"head\" /></td></tr>\n";
  

  $sql = "SELECT id, ip, name, contact, note FROM statics WHERE subnet_id='$subnet_id' ORDER BY ip ASC";
  $results = mysql_query($sql);
  
  while(list($static_id,$ip,$name,$contact,$note) = mysql_fetch_row($results)){
    $ip = long2ip($ip);
    echo "<tr>
	     <td>$ip</td><td>$name</td><td>$contact</td>
		 <td><a href=\"statics.php?op=delete&amp;subnet_id=$subnet_id&amp;static_ip=$ip\"><img src=\"./images/remove.gif\" alt=\"X\" /></a>
		  &nbsp;
		 &nbsp;<a href=\"statics.php?op=edit&amp;static_id=$static_id\"><img src=\"./images/edit.gif\" alt=\"edit\" /></td>
		 </tr>\n";
	echo "<tr><td>$note<td></tr>\n";
    echo "<tr><td colspan=\"5\"><hr class=\"division\" /></td></tr>\n";
  }
  echo "</table><br />";
  
  $sql = "SELECT start_ip, end_ip FROM acl WHERE name='DHCP' AND apply='$subnet_id'";
  $result = mysql_query($sql);
  
  if(mysql_num_rows($result) == '1'){
    list($long_dhcp_start,$long_dhcp_end) = mysql_fetch_row($result);
	$dhcp_start = long2ip($long_dhcp_start);
	$dhcp_end = long2ip($long_dhcp_end);
	
	echo "<h1>DHCP Range in \"$subnet_name\"</h1><br />\n".
	     "<table width=\"55%\">
		 <tr><th>Starting IP Address</th><th>Ending IP Address</th></tr>
		 <tr><td>$dhcp_start</td><td>$dhcp_end</td></tr>
		 </table>\n";
  } 
} // Ends list_statics function

function delete_static(){
  $static_ip = (empty($_GET['static_ip'])) ? '' : $_GET['static_ip'];
  $subnet_id = (empty($_GET['subnet_id'])) ? '' : $_GET['subnet_id'];
  $confirm = (empty($_GET['confirm'])) ? 'no' : $_GET['confirm'];
  
  $accesslevel = "2";
  $message = "Static IP delete attempt: $static_ip";
  AccessControl($accesslevel, $message); 

  if(empty($subnet_id)){
    $notice = "The static IP you tried to delete is not in a valid subnet.";
	header("Location: blocks.php?notice=$notice");
	exit();
  }
  elseif(empty($static_ip) || !long2ip($static_ip)){
    $notice = "The static IP you tried to delete is not valid.";
	header("Location: subnets.php?subnet_id=$subnet_id");
	exit();
  }
  
  if($confirm != "yes"){
    echo "Are you sure you'd like to delete $static_ip?<br />\n".
         "<br />".
		 "<a href=\"statics.php?op=delete&amp;subnet_id=$subnet_id&amp;static_ip=$static_ip&amp;confirm=yes\">
		 <img src=\"./images/apply.gif\" alt=\"confirm\" /></a>".
		 " &nbsp; <a href=\"statics.php?subnet_id=$subnet_id\"><img src=\"./images/cancel.gif\" alt=\"cancel\" /></a>";
    return;
  }
  
  $long_ip = ip2long($static_ip);
  
  $sql = "DELETE FROM statics WHERE ip='$long_ip' LIMIT 1";
  mysql_query($sql);
    
  $notice = "The static IP has been successfully deleted.";
  header("Location: statics.php?subnet_id=$subnet_id&notice=$notice");
  
  
} // Ends delete_static function

?>