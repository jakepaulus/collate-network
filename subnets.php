<?php
/**
 * Please see /include/common.php for documentation on common.php, the $COLLATE global array used by this program, and the AccessControl function used widely.
 */
require_once('./include/common.php');
require_once('./include/header.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){

	case "add";
	add_subnet();
	break;
	
	case "submit";
	submit_subnet();
	break;
	
	default:
	list_subnets();
	break;
}

require_once('./include/footer.php');

function add_subnet (){
  if(!isset($_GET['block_id'])){
    $notice = "Please select an IP block to allocate a subnet from.";
	header("Location: blocks.php?notice=$notice");
  }
  
  $name = (empty($_GET['name'])) ? '' : $_GET['name'];
  $ip = (empty($_GET['ip'])) ? '' : $_GET['ip'];
  $gateway = (empty($_GET['gateway'])) ? '' : $_GET['gateway'];
  $dhcp_start = (empty($_GET['dhcp_start'])) ? '' : $_GET['dhcp_start'];
  $dhcp_end = (empty($_GET['dhcp_end'])) ? '' : $_GET['dhcp_end'];
  $note = (empty($_GET['note'])) ? '' : $_GET['note'];
  $block_id = $_GET['block_id'];

  echo "<div id=\"nametip\" style=\"display: none;\" class=\"tip\">Enter the name of the subnet here. The name should be 
	descriptive of what the subnet will be used for. The name should be short and should not contain spaces.<br /><br/></div>\n".
	"<div id=\"iptip\" style=\"display: none;\" class=\"tip\">Enter a subnet in CIDR notation such as 
	\"192.168.1.0/24\" or using a subnet mask such as in \"192.168.1.0/255.255.255.0.\"<br /><br /></div>\n".
	"<h1>Allocate a Subnet:</h1>\n".
	"<br />\n".
	"<div style=\"float: left; width: 45%; border-right: 1px solid #000;\">\n".
	"<form action=\"subnets.php?op=submit\" method=\"post\">\n".
	"  <p>Name:<br /><input type=\"text\" name=\"name\" value=\"$name\" />\n".
	"    <a href=\"#\" onclick=\"new Effect.toggle($('nametip'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
	"  </p>\n".
	"  <p>Subnet:<br /><input type=\"text\" name=\"ip\" value=\"$ip\"/>\n".
	"    <a href=\"#\" onclick=\"new Effect.toggle($('iptip'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
	"  </p>\n".
	"  <p>Default Gateway:<br /><input type=\"text\" value=\"$gateway\" name=\"gateway\" /></p>\n".
	"  <p>DHCP Range:<br /><input type=\"text\" name=\"dhcp_start\" value=\"$dhcp_start\" size=\"15\" />\n".
	"  to <input type=\"text\" name=\"dhcp_end\" value=\"$dhcp_end\" size=\"15\" />\n".
	"  </p>\n".
	"  <p>Note: (Optional)<br /><input type=\"text\" name=\"note\" value=\"$note\" /></p>\n".
	"  <p><input type=\"hidden\" name=\"block_id\" value=\"$block_id\" /><input type=\"submit\" value=\" Go \" /></p>\n".
	"</form></div>\n";
	
	
	// Here we'll figure out what available space is left in the IP Block and list it out for the user

	$ipspace = array();
	
	$sql = "SELECT name, start_ip, end_ip FROM blocks WHERE id = '$block_id'";
	$results = mysql_query($sql);
	list($block_name, $block_long_start_ip,$block_long_end_ip) = mysql_fetch_row($results);

	array_push($ipspace, $block_long_start_ip);
	
	$sql = "SELECT start_ip, end_ip FROM subnets WHERE block_id = '$block_id' ORDER BY start_ip ASC";
	$results = mysql_query($sql);
	
	while(list($subnet_long_start_ip,$subnet_long_end_ip) = mysql_fetch_row($results)){
	  array_push($ipspace, $subnet_long_start_ip, $subnet_long_end_ip);
	}
	array_push($ipspace, $block_long_end_ip);
	$ipspace = array_reverse($ipspace);
    echo "<div style=\"float: left; width: 45%; padding-left: 10px;\">\n".
	     "<h3>Available IP Space in \"$block_name\" block:</h3><br />\n".
	     "<table width=\"100%\"><tr><th>Starting IP</th><th>Ending IP</th></tr>";
	while(!empty($ipspace)){
	  $long_start = array_pop($ipspace);
	  $start = long2ip($long_start);
	  $long_end = array_pop($ipspace);
	  $end = long2ip($long_end);
	  if($long_start + 1 != $long_end){
	    echo "<tr><td>$start</td><td>$end</td></tr>";
	  }
	}
	echo "</table>";
	echo "</div><p style=\"clear: left;\"></p>";
  
} // Ends add_subnet function

function submit_subnet(){
  $block_id = clean($_POST['block_id']);
  $name = clean($_POST['name']);
  $ip = clean($_POST['ip']);
  $gateway = clean($_POST['gateway']);
  $dhcp_start = clean($_POST['dhcp_start']);
  $dhcp_end = clean($_POST['dhcp_end']);
  $note = clean($_POST['note']);
  
  if(empty($name) || empty($ip)){
    $notice = "Please verify that required fields have been completed.";
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&notice=$notice");
	exit();
  }
  
  // Make sure that the subnet name isn't already in use
  $sql = "SELECT name FROM subnets WHERE name='$name' AND block_id = '$block_id'";
  $result = mysql_query($sql);
  if(mysql_num_rows($result) != '0'){
    $notice = "The subnet name you have chosen is already in use in this IP block. Please use another name.";
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&notice=$notice");
	exit();
  }
  
  if(!strstr($ip, '/')){
    $notice = "You must supply the number of mask bits or a mask.";
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&notice=$notice");
	exit();
  }
  
  list($start_ip,$mask) = explode('/', $ip);
  
  if(ip2long($start_ip) == FALSE){
    $notice = "The IP you have entered is not valid.";
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&notice=$notice");
	exit();
  }
  
  $start_ip = long2ip(ip2long($start_ip));  
  $long_ip = ip2long($start_ip);
  if(!strstr($mask, '.') && ($mask <= '0' || $mask >= '32')){
    $notice = "The IP you have specified is not valid. The mask cannot be 0 or 32 bits long.";
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&notice=$notice");
	exit();
  }
  elseif(strstr($mask, '.')){
    $mask = long2ip(ip2long($mask));
  }
  else{
    $bin = str_pad('', $mask, '1');
	$bin = str_pad($bin, '32', '0');
	$mask = bindec(substr($bin,0,8)).".".bindec(substr($bin,8,8)).".".bindec(substr($bin,16,8)).".".bindec(substr($bin,24,8));
    $mask = long2ip(ip2long($mask));
  }
  
  if(!empty($dhcp_start) && (ip2long($dhcp_start) == FALSE || ip2long($dhcp_end) == FALSE)){
    $notice = "The DHCP Range you specified is not valid.";
	header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&notice=$notice");
	exit();
  }
  
  $long_mask = ip2long($mask);
  $long_ip = ($long_ip & $long_mask); // This makes sure they entered the network address and not an IP inside the network
  $long_end_ip = $long_ip | (~$long_mask);
  
  $long_dhcp_start = ip2long($dhcp_start);
  $long_dhcp_end = ip2long($dhcp_end);
  
  if(!empty($dhcp_start) && ($long_dhcp_start < $long_ip || $long_dhcp_start > $long_end_ip || $long_dhcp_end < $long_dhcp_start
    || $long_dhcp_end > $long_end_ip)){
	 $notice = "The DHCP Range you specified is not valid.";
	 header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&notice=$notice");
	exit();
  }
  
  $long_gateway = ip2long($gateway);
  
  if(empty($gateway) || ip2long($gateway) == FALSE || $long_gateway < $long_ip || $long_gateway > $long_end_ip){
    $notice = "The Default Gateway you specified is not valid.";
	header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&notice=$notice");
	exit();
  }
  
  // We need to make sure the subnet falls entirely within the IP block
  $sql = "SELECT id FROM blocks where id='$block_id' AND start_ip <= '$long_ip' AND end_ip >= '$long_end_ip'";
  $search = mysql_query($sql);
  if(mysql_num_rows($search) != '1'){
    $notice = "The IP you entered does not match the block you have selected.";
	header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&notice=$notice");
	exit();
  }
  
  // We need to make sure this new subnet doesn't overlap an existing subnet
  $sql = "SELECT id FROM subnets WHERE block_id='$block_id' AND ( 
		(start_ip <= '$long_ip' AND end_ip >= '$long_ip') OR 
        (start_ip <= '$long_end_ip' AND end_ip >= '$long_end_ip') OR
		(start_ip >= '$long_ip' AND end_ip <= '$long_end_ip')
		)";
  
  $search = mysql_query($sql);
  if(mysql_num_rows($search) != '0'){
    $notice = "The IP you entered overlaps with an subnet in the database.";
	header("Location: subnets.php?op=add&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&notice=$notice");
	exit();
  }
  
  $sql = "INSERT INTO subnets (name, start_ip, end_ip, mask, note, block_id) 
		VALUES('$name', '$long_ip', '$long_end_ip', '$long_mask', '$note', '$block_id')";
  mysql_query($sql);
  
  // Add an ACL for the DHCP range so users don't assign a static IP inside a DHCP scope.
  
  $sql = "INSERT INTO acl (name, start_ip, end_ip) VALUES('DHCP', '$long_dhcp_start', '$long_dhcp_end')";
  mysql_query($sql);
  
  // Add static IP for the Default Gateway
  $sql = "INSERT INTO statics (ip, name, contact, note, subnet_id) 
		 VALUES('$long_gateway', 'Gateway', 'Network Admin', 'Default Gateway', 
		 (SELECT id FROM subnets WHERE start_ip = '$long_ip'))";
  mysql_query($sql);
  
  $notice = "The subnet you entered has been added.";
  header("Location: subnets.php?block_id=$block_id&notice=$notice");
  exit();
} // ends submit_subnet function

function list_subnets(){
 
  if(!isset($_GET['block_id']) || empty($_GET['block_id'])){
    $notice = "Please select an IP block within which to view subnets.";
	header("Location: blocks.php?notice=$notice");
	exit();
  }
  $block_id = $_GET['block_id'];
  
  $sql = "SELECT `name` FROM `blocks` WHERE `id` = '$block_id'";
  $result = mysql_query($sql);
  if(mysql_num_rows($result) != '1'){
    $notice = "The subnet you selected is not valid. Please select an IP block within which to view subnets.";
	header("Location: blocks.php?notice=$notice");
	exit();
  }
  $block_name = mysql_result($result, 0, 0);
  
  
  echo "<h1>\"$block_name\" Subnets</h1>\n".
       "<p style=\"text-align: right;\"><a href=\"subnets.php?op=add&amp;block_id=$block_id\">
	   <img src=\"./images/add.gif\" alt=\"Add\" /> Allocate a Subnet </a></p>";

  $sql = "SELECT `id`, `name`, `start_ip`, `mask`, `note` FROM `subnets` 
	  WHERE `block_id` = '$block_id' ORDER BY `name` ASC";

    
   
  echo "<table width=\"100%\">\n". 
	     "<tr><th align=\"left\">Subnet Name</th>".
	     "<th align=\"left\">Network Address</th>".
	     "<th align=\"left\">Subnet Mask</th>".
	     "<th align=\"left\">Actions</th></tr>\n".
	     "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
		 
  $results = mysql_query($sql);  
  while(list($subnet_id,$name,$long_start_ip,$long_mask,$note) = mysql_fetch_row($results)){
    $start_ip = long2ip($long_start_ip);
	$mask = long2ip($long_mask);
	
    echo "<tr>
	     <td><b><a href=\"statics.php?subnet_id=$subnet_id\">$name</a></b></td><td>$start_ip</td>
		 <td>$mask</td>
		 <td><a href=\"subnets.php?op=delete&amp;subnet_id=$subnet_id\"><img src=\"./images/remove.gif\" alt=\"X\" /></a> &nbsp;
		 &nbsp;<a href=\"subnets.php?op=edit&amp;subnet_id=$subnet_id\"><img src=\"./images/edit.gif\" alt=\"edit\" /></td>
		 </tr>\n";
	echo "<tr><td>$note<td></tr>\n";
    echo "<tr><td colspan=\"5\"><hr class=\"division\" /></td></tr>\n";
  }
  
  echo "</table>";
} // Ends list_subnets function
