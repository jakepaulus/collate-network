<?php
/**
 * Please see /include/common.php for documentation on common.php, the $COLLATE global array used by this program, and the AccessControl function used widely.
 */
require_once('./include/common.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){

	case "add";
	AccessControl("3", "Subnet Allocation form accessed");
	add_subnet();
	break;
	
	case "submit";
	submit_subnet();
	break;
	
	case "edit";
	edit_subnet();
	break;
	
	case "update";
	update_subnet();
	break;
	
	case "delete";
	delete_subnet();
	break;
	
	default:
	AccessControl("1", "Subnet list viewed");
	list_subnets();
	break;
}

require_once('./include/footer.php');

function add_subnet (){
  require_once('./include/header.php');
  
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
  $guidance = (empty($_GET['guidance'])) ? '' : $_GET['guidance'];
  $block_id = $_GET['block_id'];

  echo "<div id=\"nametip\" style=\"display: none;\" class=\"tip\">Enter the name of the subnet here. The name should be 
	descriptive of what the subnet will be used for. The name should be short and should not contain spaces.<br /><br/></div>\n".
	"<div id=\"iptip\" style=\"display: none;\" class=\"tip\">Enter a subnet in CIDR notation such as 
	\"192.168.1.0/24\" or using a subnet mask such as in \"192.168.1.0/255.255.255.0.\"<br /><br /></div>\n".
	"<div id=\"guidance\" style=\"display: none;\" class=\"tip\">You may type a message that will be viewable to any user 
	adding a static IP in this subnet. The message should help the user understand what IP to use for the type of device they
	wish to reserve an IP address for. Some formatting will be maintained, HTML is not allowed.<br /><br /></div>\n".
	"<h1>Allocate a Subnet</h1>\n".
	"<br />\n".
	"<form action=\"subnets.php?op=submit\" method=\"post\">\n".
	"<div style=\"float: left; width: 45%; border-right: 1px solid #000;\">\n".
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
	" </div>";
	
	
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
	     "<h3>Available IP Space in \"$block_name\" block:</h3><br />\n";
		 
    if($ipspace[0] == $ipspace[1]){
	  echo "<p>The IP Block is exhausted.</p>";
	}
	else{
	  echo "<table width=\"100%\"><tr><th>Starting IP</th><th>Ending IP</th></tr>";
    	
      $ipspace_count = count($ipspace);
      if(count($ipspace) > '2'){
	    while(!empty($ipspace)){
	      $long_start = array_pop($ipspace);
		  if(count($ipspace) != $ipspace_count - '1'){
		    $start = long2ip($long_start + '1');
		  }
		  else{
	        $start = long2ip($long_start);
		  }
	      $long_end = array_pop($ipspace);
		  if(count($ipspace) > '1'){
		    $end = long2ip($long_end - '1');
		  }
		  else{
	        $end = long2ip($long_end);
	      }
	      if($long_start + 1 != $long_end){
	        echo "<tr><td>$start</td><td>$end</td></tr>";
	      }
	    }
	  }
	  else{
	    while(!empty($ipspace)){
	      $long_start = array_pop($ipspace);
	      $start = long2ip($long_start);
	      $long_end = array_pop($ipspace);
	      $end = long2ip($long_end);
	      if($long_start + 1 != $long_end){
	        echo "<tr><td>$start</td><td>$end</td></tr>";
	      }
	    }
	  }
	  echo "</table>";
	}
	echo "</div>\n".
	     "<p style=\"clear: left;\">\n".
		 "<p>IP Guidance: (Optional) 
		 <a href=\"#\" onclick=\"new Effect.toggle($('guidance'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
		 "<br /><textarea name=\"guidance\" rows=\"10\" cols=\"45\">$guidance</textarea></p>\n".
		 "<input type=\"hidden\" name=\"block_id\" value=\"$block_id\" />\n".
		 "<input type=\"submit\" value=\" Go \" /></p>\n".
	     "</form>\n";
	
} // Ends add_subnet function

function submit_subnet(){
  $block_id = clean($_POST['block_id']);
  $name = clean($_POST['name']);
  $ip = clean($_POST['ip']);
  $gateway = clean($_POST['gateway']);
  $dhcp_start = clean($_POST['dhcp_start']);
  $dhcp_end = clean($_POST['dhcp_end']);
  $note = clean($_POST['note']);
  $guidance = clean($_POST['guidance']);
  
  $accesslevel = "3";
  $message = "Subnet Allocation form submitted: $name";
  AccessControl($accesslevel, $message); 
  
  if(empty($name) || empty($ip)){
    $notice = "Please verify that required fields have been completed.";
	$guidance = urlencode($guidance);
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&guidance=$guidance&notice=$notice");
	exit();
  }
  
  // Make sure that the subnet name isn't already in use
  $sql = "SELECT name FROM subnets WHERE name='$name' AND block_id = '$block_id'";
  $result = mysql_query($sql);
  if(mysql_num_rows($result) != '0'){
    $notice = "The subnet name you have chosen is already in use in this IP block. Please use another name.";
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&guidance=$guidance&notice=$notice");
	exit();
  }
  
  if(!strstr($ip, '/')){
    $notice = "You must supply the number of mask bits or a mask.";
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&guidance=$guidance&notice=$notice");
	exit();
  }
  
  list($start_ip,$mask) = explode('/', $ip);
  
  if(ip2long($start_ip) == FALSE){
    $notice = "The IP you have entered is not valid.";
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&guidance=$guidance&notice=$notice");
	exit();
  }
  
  $start_ip = long2ip(ip2long($start_ip));  
  $long_ip = ip2long($start_ip);
  if(!strstr($mask, '.') && ($mask <= '0' || $mask >= '32')){
    $notice = "The IP you have specified is not valid. The mask cannot be 0 or 32 bits long.";
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&guidance=$guidance&notice=$notice");
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
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&guidance=$guidance&notice=$notice");
	exit();
  }
  
  if(!empty($dhcp_start) && (ip2long($dhcp_start) == FALSE || ip2long($dhcp_end) == FALSE)){
    $notice = "The DHCP Range you specified is not valid.";
	header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&guidance=$guidance&notice=$notice");
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
	 header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&guidance=$guidance&notice=$notice");
	exit();
  }
  
  $long_gateway = ip2long($gateway);
  
  if(!empty($gateway) && (ip2long($gateway) == FALSE || $long_gateway < $long_ip || $long_gateway > $long_end_ip)){
    $notice = "The Default Gateway you specified is not valid.";
	header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&guidance=$guidance&notice=$notice");
	exit();
  }
  
  // We need to make sure the subnet falls entirely within the IP block
  $sql = "SELECT id FROM blocks where id='$block_id' AND start_ip <= '$long_ip' AND end_ip >= '$long_end_ip'";
  $search = mysql_query($sql);
  if(mysql_num_rows($search) != '1'){
    $notice = "The IP you entered does not match the block you have selected.";
	header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&guidance=$guidance&notice=$notice");
	exit();
	}
  
  // We need to make sure this new subnet doesn't overlap an existing subnet
  $sql = "SELECT id FROM subnets WHERE block_id='$block_id' AND ( 
		(start_ip <= '$long_ip' AND end_ip >= '$long_ip') OR 
        (start_ip <= '$long_end_ip' AND end_ip >= '$long_end_ip') OR
		(start_ip >= '$long_ip' AND end_ip <= '$long_end_ip')
		)";
  
  $result = mysql_query($sql);
  if(mysql_num_rows($result) != '0'){
    $notice = "The IP you entered overlaps with an subnet in the database.";
	header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&dhcp_start=$dhcp_start&dhcp_end=$dhcp_end&note=$note&guidance=$guidance&notice=$notice");
	exit();
  }
  $username = (empty($_SESSION['username'])) ? 'system' : $_SESSION['username'];
  $sql = "INSERT INTO subnets (name, start_ip, end_ip, mask, note, block_id, modified_by, modified_at, guidance) 
		VALUES('$name', '$long_ip', '$long_end_ip', '$long_mask', '$note', '$block_id', '$username', now(), '$guidance')";
  mysql_query($sql);
  
  // Add an ACL for the DHCP range so users don't assign a static IP inside a DHCP scope.
  if(!empty($dhcp_start)){
    $sql = "INSERT INTO acl (name, start_ip, end_ip, apply) VALUES('DHCP', '$long_dhcp_start', '$long_dhcp_end', 
	       (SELECT id FROM subnets WHERE start_ip = '$long_ip'))";
	
    mysql_query($sql);
  }
  // Add static IP for the Default Gateway
  if(!empty($gateway)){
    $username = (empty($_SESSION['username'])) ? 'system' : $_SESSION['username'];
    $sql = "INSERT INTO statics (ip, name, contact, note, subnet_id, modified_by, modified_at) 
	       VALUES('$long_gateway', 'Gateway', 'Network Admin', 'Default Gateway', 
	       (SELECT id FROM subnets WHERE start_ip = '$long_ip'), '$username', now())";
    mysql_query($sql);
  }
  
  $notice = "The subnet you entered has been added.";
  header("Location: subnets.php?block_id=$block_id&notice=$notice");
  exit();
} // ends submit_subnet function

function edit_subnet(){

  $subnet_id = (empty($_GET['subnet_id'])) ? '' : $_GET['subnet_id'];
  $guidance = (empty($_GET['guidance'])) ? '' : $_GET['guidance'];
  
  if(empty($subnet_id)){
    $notice = "Please select a block, then a subnet to edit.";
	header("Location: blocks.php?notice=$notice");
	exit();
  }
  
  $sql = "SELECT name, note, guidance FROM subnets WHERE id='$subnet_id'";
  $result = mysql_query($sql);
  
  if(mysql_num_rows($result) != '1'){
    $notice = "Please select a block, then a subnet to edit.";
	header("Location: blocks.php?notice=$notice");
	exit();
  }
  
  list($name,$note,$guidance) = mysql_fetch_row($result);
  
  $accesslevel = "3";
  $message = "Subnet edit form accessed: $name";
  AccessControl($accesslevel, $message); 

  require_once('./include/header.php');
    
  $sql = "SELECT start_ip, end_ip FROM acl WHERE apply='$subnet_id'";
  $result = mysql_query($sql);
  
  if(mysql_num_rows($result) == '1'){
    list($long_dhcp_start,$long_dhcp_end) = mysql_fetch_row($result);
	$dhcp_start = long2ip($long_dhcp_start);
	$dhcp_end = long2ip($long_dhcp_end);
  }
  
  echo "<div id=\"guidance\" style=\"display: none;\" class=\"tip\">You may type a message that will be viewable to any user 
	   adding a static IP in this subnet. The message should help the user understand what IP to use for the type of device they
	   wish to reserve an IP address for. Some formatting will be maintained, HTML is not allowed.<br /><br /></div>\n".
       "<h1>Update Subnet: $name</h1>\n".
	   "<br />\n".
	   "<form action=\"subnets.php?op=update\" method=\"POST\">\n".
	   "  <p>Name:<br /><input type=\"text\" name=\"name\" value=\"$name\" /></p>\n".
	   "  <p>Note: (Optional)<br /><input type=\"text\" name=\"note\" value=\"$note\" /></p>\n".
	   "  <p>DHCP Range:<br /><input type=\"text\" name=\"dhcp_start\" value=\"$dhcp_start\" size=\"15\" />\n".
	   "  to <input type=\"text\" name=\"dhcp_end\" value=\"$dhcp_end\" size=\"15\" />\n";
	   
  echo "<p>IP Guidance: (Optional) 
	   <a href=\"#\" onclick=\"new Effect.toggle($('guidance'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
	   "<br /><textarea name=\"guidance\" rows=\"10\" cols=\"45\" />$guidance</textarea></p>\n".
       "  <p><input type=\"hidden\" name=\"subnet_id\" value=\"$subnet_id\" /><input type=\"submit\" value=\" Go \" /></p>\n".
	   "</form>\n";

} // Ends edit_subnet function

function update_subnet(){

  $subnet_id = (empty($_POST['subnet_id'])) ? '' : $_POST['subnet_id'];
  $name = (empty($_POST['name'])) ? '' : clean($_POST['name']);
  $note = (empty($_POST['note'])) ? '' : clean($_POST['note']);
  $dhcp_start = (empty($_POST['dhcp_start'])) ? '' : $_POST['dhcp_start'];
  $long_dhcp_start = ip2long($dhcp_start);
  $dhcp_end = (empty($_POST['dhcp_end'])) ? '' : $_POST['dhcp_end'];
  $long_dhcp_end = ip2long($dhcp_end);
  $guidance = (empty($_POST['guidance'])) ? '' : clean($_POST['guidance']);
  
  $accesslevel = "3";
  $message = "Subnet edit form submitted: $name";
  AccessControl($accesslevel, $message); 
  
  if(empty($subnet_id)){
    $notice = "Please select an IP block, then a subnet to edit.";
	header("Location: blocks.php?notice=$notice");
	exit();
  }
  elseif(empty($name)){
    $notice = "The name field cannot be blank.";
	header("Location: subnets.php?op=edit&subnet_id=$subnet_id&guidance=$guidance&notice=$notice");
	exit();
  }
  
  if(!empty($dhcp_start) && (ip2long($dhcp_start) == FALSE || ip2long($dhcp_end) == FALSE)){
    $notice = "The DHCP Range you specified is not valid.";
	header("Location: subnets.php?op=edit&subnet_id=$subnet_id&guidance=$guidance&notice=$notice");
	exit();
  }
  
  $sql = "SELECT start_ip, end_ip, block_id FROM subnets WHERE id='$subnet_id'";
  $result = mysql_query($sql);
  
  if(mysql_num_rows($result) != '1'){
    $notice = "Please select an IP block, then a subnet to edit.";
	header("Location: blocks.php?notice=$notice");
	exit();
  }
  
  list($long_ip,$long_end_ip,$block_id) = mysql_fetch_row($result);
  
  if(!empty($dhcp_start) && ($long_dhcp_start < $long_ip || $long_dhcp_start > $long_end_ip || $long_dhcp_end < $long_dhcp_start
    || $long_dhcp_end > $long_end_ip)){
	 $notice = "The DHCP Range you specified is not valid.";
	 header("Location: subnets.php?op=edit&subnet_id=$subnet_id&guidance=$guidance&notice=$notice");
	exit();
  }
  
  $sql = "SELECT id FROM statics WHERE subnet_id='$subnet_id' AND (ip > '$long_dhcp_start' AND ip < '$long_dhcp_end')";
  $result = mysql_query($sql);
  if(mysql_num_rows($result) != '0'){
    $notice = "There are static IPs reserved in the DHCP range you selected. Please delete them first.";
	header("Location: statics.php?subnet_id=$subnet_id&notice=$notice");
	exit();
  }
  $username = (empty($_SESSION['username'])) ? 'system' : $_SESSION['username'];
  $sql = "UPDATE subnets SET name='$name', note='$note', modified_by='$username', modified_at=now(), guidance='$guidance' WHERE id='$subnet_id'";
  mysql_query($sql);
  
  $sql = "SELECT id FROM acl WHERE name='DHCP' AND apply='$subnet_id'";
  $result = mysql_query($sql);
  if(mysql_num_rows($result) == 0){
    $sql = "INSERT INTO acl (name, start_ip, end_ip, apply) VALUES('DHCP', '$long_dhcp_start', '$long_dhcp_end', '$subnet_id')";
  }
  else{
    $sql = "UPDATE acl SET start_ip='$long_dhcp_start', end_ip='$long_dhcp_end' WHERE name='DHCP' AND apply='$subnet_id'";
  }
  mysql_query($sql);
  
  $notice = "The subnet has been updated.";
  header("Location: subnets.php?block_id=$block_id&notice=$notice");
  exit();

} // Ends update_subnet function

function list_subnets(){
  require_once('./include/header.php');
 
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

  $sql = "SELECT `id`, `name`, `start_ip`, `end_ip`, `mask`, `note` FROM `subnets` 
	  WHERE `block_id` = '$block_id' ORDER BY `name` ASC";

    
   
  echo "<table width=\"100%\">\n". 
	     "<tr><th align=\"left\">Subnet Name</th>".
	     "<th align=\"left\">Network Address</th>".
	     "<th align=\"left\">Subnet Mask</th>".
		 "<th align=\"left\">Statics Used</th>".
	     "<th align=\"left\">Actions</th></tr>\n".
	     "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
		 
  $results = mysql_query($sql);  
  while(list($subnet_id,$name,$long_start_ip,$long_end_ip,$long_mask,$note) = mysql_fetch_row($results)){
    $start_ip = long2ip($long_start_ip);
	$mask = long2ip($long_mask);
	
	$subnet_size = $long_end_ip - $long_start_ip;
	
	$sql = "SELECT COUNT(*) FROM statics WHERE subnet_id='$subnet_id'";
	$result = mysql_query($sql);
	$static_count = mysql_result($result, 0, 0);
	
	$sql = "SELECT start_ip, end_ip FROM acl WHERE apply='$subnet_id'";
	$result = mysql_query($sql);
	while(list($long_dhcp_start,$long_dhcp_end) = mysql_fetch_row($result)){
	  $subnet_size = $subnet_size - ($long_dhcp_end - $long_dhcp_start);
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
	
    echo "<tr>
	     <td><b><a href=\"statics.php?subnet_id=$subnet_id\">$name</a></b></td><td>$start_ip</td>
		 <td>$mask</td><td style=\"color: $font_color;\">$percent_subnet_used</td>
		 <td><a href=\"subnets.php?op=delete&amp;block_id=$block_id&amp;subnet_id=$subnet_id\"><img src=\"./images/remove.gif\" alt=\"X\" /></a> &nbsp;
		 &nbsp;<a href=\"subnets.php?op=edit&amp;subnet_id=$subnet_id\"><img src=\"./images/edit.gif\" alt=\"edit\" /></td>
		 
		 </tr>\n";
	echo "<tr><td>$note</td></tr>\n";
    echo "<tr><td colspan=\"5\"><hr class=\"division\" /></td></tr>\n";
  }
  
  echo "</table>";
} // Ends list_subnets function

function delete_subnet(){

  $subnet_id = (empty($_GET['subnet_id'])) ? '' : $_GET['subnet_id'];
  $block_id = (empty($_GET['block_id'])) ? '' : $_GET['block_id'];
  $confirm = (empty($_GET['confirm'])) ? 'no' : $_GET['confirm'];
  
  if(empty($block_id)){
    $notice = "Please select a block in order to delete a subnet from it.";
	header("Location: blocks.php?notice=$notice");
	exit();
  }
  elseif(empty($subnet_id)){
    $notice = "Please select a subnet to delete.";
	header("Location: subnets.php?block_id=$block_id&notice=$notice");
	exit();
  }
  
  $sql = "SELECT name FROM subnets WHERE id='$subnet_id' AND block_id='$block_id'";
  $result = mysql_query($sql);
	
  if(mysql_num_rows($result) != '1'){
	$notice = "That subnet was not found. Please try again.";
	header("Location: subnets.php?block_id=$block_id&notice=$notice");
	exit();
  }
  
  $name = mysql_result($result, 0, 0);
  
  $accesslevel = "3";
  $message = "Subnet deletion attempt: $name";
  AccessControl($accesslevel, $message); 
  
  if($confirm != "yes"){
    require_once('./include/header.php');
    
	echo "Are you sure you'd like to delete the subnet \"$name\" and everything in it? There is no undo for this action!
	      <br />\n".
         "<br />".
		 "<a href=\"subnets.php?op=delete&amp;block_id=$block_id&amp;subnet_id=$subnet_id&amp;confirm=yes\">
		 <img src=\"./images/apply.gif\" alt=\"confirm\" /></a>".
		 " &nbsp; <a href=\"subnets.php?block_id=$block_id\"><img src=\"./images/cancel.gif\" alt=\"cancel\" /></a>";
    require_once('include/footer.php');
	exit();
  }
  
  // First delete all static IPs
  $sql = "DELETE FROM statics WHERE subnet_id='$subnet_id'";
  mysql_query($sql);
  
  // Next, remove the DHCP ACL
  $sql = "DELETE FROM acl WHERE apply='$subnet_id'";
  mysql_query($sql);
  
  // Lastly, remove the subnet
  $sql = "DELETE FROM subnets WHERE id='$subnet_id' AND block_id='$block_id'";
  mysql_query($sql);
  
  $notice = "The subnet $name has been deleted";
  header("Location: subnets.php?block_id=$block_id&notice=$notice");
  exit();
  
} // Ends delete_subnet function
    
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