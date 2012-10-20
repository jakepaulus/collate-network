<?php
/**
 * Please see /include/common.php for documentation on common.php, the $COLLATE global array used by this program, and the AccessControl function used widely.
 */
require_once('./include/common.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){

	case "add";
	AccessControl("3", null);
	add_subnet();
	break;
	
	case "submit";
	submit_subnet();
	break;
	
	case "modify";	
	AccessControl("3", null);
	modify_subnet();
	break;
	
	case "submitmove";
	submit_move_subnet();
	break;

	case "resize";
	AccessControl("3", null);
	resize_subnet();
	break;
	
	default:
	AccessControl("1", null);
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
  $acl_name = (empty($_GET['acl_name'])) ? 'DHCP' : $_GET['acl_name'];
  $acl_start = (empty($_GET['acl_start'])) ? '' : $_GET['acl_start'];
  $acl_end = (empty($_GET['acl_end'])) ? '' : $_GET['acl_end'];
  $note = (empty($_GET['note'])) ? '' : $_GET['note'];
  $guidance = (empty($_GET['guidance'])) ? '' : $_GET['guidance'];
  $block_id = $_GET['block_id'];

  echo "<div id=\"nametip\" style=\"display: none;\" class=\"tip\">Enter a unique name for the subnet here. The name should be 
	descriptive of what the subnet will be used for. The name should be short and should not contain spaces.<br /><br/></div>\n".
	"<div id=\"iptip\" style=\"display: none;\" class=\"tip\">Enter a subnet in CIDR notation such as 
	\"192.168.1.0/24\" or using a subnet mask such as in \"192.168.1.0/255.255.255.0.\"<br /><br /></div>\n".
	"<div id=\"guidance\" style=\"display: none;\" class=\"tip\">You may type a message that will be viewable to any user 
	adding a static IP in this subnet. The message should help the user understand what IP to use for the type of device they
	wish to reserve an IP address for. Some formatting will be maintained, HTML is not allowed.<br /><br /></div>\n".
	"<h1>Allocate a Subnet</h1>\n".
	"<br />\n".
	"<form action=\"subnets.php?op=submit\" method=\"post\">\n".
	"<div style=\"float: left; width: 45%; \">\n".
	"  <p>Name:<br /><input type=\"text\" name=\"name\" value=\"$name\" />\n".
	"    <a href=\"#\" onclick=\"new Effect.toggle($('nametip'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
	"  </p>\n".
	"  <p>Subnet:<br /><input type=\"text\" name=\"ip\" value=\"$ip\"/>\n".
	"    <a href=\"#\" onclick=\"new Effect.toggle($('iptip'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
	"  </p>\n".
	"  <p>Default Gateway:<br /><input type=\"text\" value=\"$gateway\" name=\"gateway\" /></p>\n".
	"  <p>ACL Name:<br /><input type=\"text\" name=\"acl_name\" value=\"$acl_name\" />\n".
	"  <p>ACL Range:<br /><input type=\"text\" name=\"acl_start\" value=\"$acl_start\" size=\"15\" />\n".
	"  to <input type=\"text\" name=\"acl_end\" value=\"$acl_end\" size=\"15\" />\n".
	"  </p>\n".
	"  <p>Note: (Optional)<br /><input type=\"text\" name=\"note\" value=\"$note\" /></p>\n".
	" </div>";
	
	
	// Here we'll figure out what available space is left in the IP Block and list it out for the user

	$ipspace = array();
	
	$sql = "SELECT name, start_ip, end_ip FROM blocks WHERE id = '$block_id'";
	$results = mysql_query($sql);
	list($block_name, $block_long_start_ip,$block_long_end_ip) = mysql_fetch_row($results);

	array_push($ipspace, $block_long_start_ip);
	
	// We need to consider that some subnets in the block are not in the IP range the block specifies, so we compare ranges as well as block_id.
	$sql = "SELECT start_ip, end_ip FROM subnets WHERE CAST((start_ip & 0xFFFFFFFF) AS UNSIGNED) >= CAST(('$block_long_start_ip' & 0xFFFFFFFF) AS UNSIGNED) AND CAST((end_ip & 0xFFFFFFFF) AS UNSIGNED) <= CAST(('$block_long_end_ip' & 0xFFFFFFFF) AS UNSIGNED) ORDER BY start_ip ASC";
	$subnet_rows = mysql_query($sql);
	
	while(list($subnet_long_start_ip,$subnet_long_end_ip) = mysql_fetch_row($subnet_rows)){
	  array_push($ipspace, $subnet_long_start_ip, $subnet_long_end_ip);
	}
	array_push($ipspace, $block_long_end_ip);
	$ipspace = array_reverse($ipspace);
    echo "<div style=\"float: left; width: 45%; padding-left: 10px; border-left: 1px solid #000;\">\n".
	     "<h3>Available IP Space in \"$block_name\" block:</h3><br />\n";
		 

    $ipspace_count = count($ipspace);	 

    echo "<table width=\"100%\"><tr><th>Starting IP</th><th>Ending IP</th></tr>";

    while(!empty($ipspace)){
	  $long_start = array_pop($ipspace);
	  if(count($ipspace) != $ipspace_count - '1'){ // Don't subtract 1 from the very first start IP
	    $start = long2ip($long_start + 1);
	  }
	  else{
	    $start = long2ip($long_start);
	  }
		
	  $long_end = array_pop($ipspace);
	  if(count($ipspace) > '1'){
	    $end = long2ip($long_end - 1);
	  }
	  else{
	    $end = long2ip($long_end);
	  }
		
	  if($long_start + 1 != $long_end && $long_start != $long_end){
	    echo "<tr><td>$start</td><td>$end</td></tr>";
	  }
	}
 
	echo "</table>";
	
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
  $acl_name = clean($_POST['acl_name']);
  $acl_start = clean($_POST['acl_start']);
  $acl_end = clean($_POST['acl_end']);
  $note = clean($_POST['note']);
  $guidance = nl2br(clean($_POST['guidance']));

  if(empty($name) || empty($ip)){
    $notice = "Please verify that required fields have been completed.";
	$guidance = urlencode($guidance);
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
	exit();
  }
  
  if(strlen($name) < '3' OR strlen($name) > '60'){
    $notice = "The subnet name must be between 3 and 60 characters long.";
    $guidance = urlencode($guidance);
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
    exit();
  }
  
  if(!strstr($ip, '/')){
    $notice = "You must supply the number of mask bits or a mask.";
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
	exit();
  }
  
  list($start_ip,$mask) = explode('/', $ip);
  
  if(ip2decimal($start_ip) == FALSE){
    $notice = "The IP you have entered is not valid.";
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
	exit();
  }
  
  $start_ip = long2ip(ip2decimal($start_ip));  
  $long_ip = ip2decimal($start_ip);
  if(!strstr($mask, '.') && ($mask <= '0' || $mask >= '32')){
    $notice = "The IP you have specified is not valid. The mask cannot be 0 or 32 bits long.";
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
	exit();
  }
  elseif(!strstr($mask, '.')){
    $bin = str_pad('', $mask, '1');
	$bin = str_pad($bin, '32', '0');
	$mask = bindec(substr($bin,0,8)).".".bindec(substr($bin,8,8)).".".bindec(substr($bin,16,8)).".".bindec(substr($bin,24,8));
    $mask = long2ip(ip2decimal($mask));
  }
  elseif(!checkNetmask($mask)){
    $notice = "The mask you have specified is not valid.";
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
	exit();
  }
  
  if(!empty($acl_start) && (ip2decimal($acl_start) == FALSE || ip2decimal($acl_end) == FALSE)){
    $notice = "The ACL Range you specified is not valid.";
	header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
	exit();
  }
  
  $long_mask = ip2decimal($mask);
  $long_ip = $long_ip & $long_mask; // This makes sure they entered the network address and not an IP inside the network
  $long_end_ip = $long_ip | (~$long_mask);
  
  $long_acl_start = ip2decimal($acl_start);
  $long_acl_end = ip2decimal($acl_end);
  
  if(!empty($acl_start) && ($long_acl_start < $long_ip || $long_acl_start > $long_end_ip || $long_acl_end < $long_acl_start
    || $long_acl_end > $long_end_ip)){
	 $notice = "The ACL Range you specified is not valid.";
	 header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
	exit();
  }
  
  $long_gateway = ip2decimal($gateway);
  
  if(!empty($gateway) && (ip2decimal($gateway) == FALSE || $long_gateway < $long_ip || $long_gateway > $long_end_ip)){
    $notice = "The Default Gateway you specified is not valid.";
	header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
	exit();
  }
  
  // We need to make sure this new subnet doesn't overlap an existing subnet
  $sql = "SELECT COUNT(*) FROM subnets WHERE CAST('$long_ip' AS UNSIGNED) & CAST(mask AS UNSIGNED) = CAST(start_ip AS UNSIGNED) OR CAST(start_ip AS UNSIGNED) & CAST('$long_mask' AS UNSIGNED) = CAST('$long_ip' AS UNSIGNED)";
  
  $result = mysql_query($sql);
  if(mysql_result($result, 0 ,0) != '0'){
    $notice = "The IP you entered overlaps with a subnet in the database.";
	header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
	exit();
  }
  
  $cidr=subnet2cidr($long_ip,$long_mask);
  $accesslevel = "3";
  $message = "Subnet $name ($cidr) has been created";
  AccessControl($accesslevel, $message); // No need to generate logs when nothing is really happening. This goes down here just before we know stuff is actually going to be written.
  
  $username = (!isset($COLLATE['user']['username'])) ? 'system' : $COLLATE['user']['username'];
  $sql = "INSERT INTO subnets (name, start_ip, end_ip, mask, note, block_id, modified_by, modified_at, guidance) 
		VALUES('$name', '$long_ip', '$long_end_ip', '$long_mask', '$note', '$block_id', '$username', now(), '$guidance')";
  
	
  mysql_query($sql);
  
  // Add an ACL for the acl range so users don't assign a static IP inside a acl scope.
  if(!empty($acl_start)){
    $sql = "INSERT INTO acl (name, start_ip, end_ip, subnet_id) VALUES('$acl_name', '$long_acl_start', '$long_acl_end', 
	       (SELECT id FROM subnets WHERE start_ip = '$long_ip'))";
	
    mysql_query($sql);
  }
  // Add static IP for the Default Gateway
  if(!empty($gateway)){
    $sql = "INSERT INTO statics (ip, name, contact, note, subnet_id, modified_by, modified_at) 
	       VALUES('$long_gateway', 'Gateway', 'Network Admin', 'Default Gateway', 
	       (SELECT id FROM subnets WHERE start_ip = '$long_ip'), '$username', now())";
    mysql_query($sql);
  }
  
  $notice = "The subnet you entered has been added.";
  header("Location: subnets.php?block_id=$block_id&notice=$notice");
  exit();
} // ends submit_subnet function

function list_subnets(){
  global $COLLATE;
  require_once('./include/header.php');
  
  if(!isset($_GET['block_id']) || empty($_GET['block_id'])){
    $notice = "Please select an IP block within which to view subnets.";
	header("Location: blocks.php?notice=$notice");
	exit();
  }
  $block_id = $_GET['block_id'];
  $sort = (!isset($_GET['sort'])) ? '' : $_GET['sort'];
  if ($sort == 'network') { 
    $sort = 'start_ip';
  }
  else {
    $sort = 'name';
  }
  
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
	  WHERE `block_id` = '$block_id' ORDER BY `$sort` ASC";

    
   
  echo "<table width=\"100%\">\n". 
	     "<tr><th align=\"left\"><a href=\"subnets.php?block_id=$block_id&amp;sort=name\">Subnet Name</a></th>".
	     "<th align=\"left\"><a href=\"subnets.php?block_id=$block_id&amp;sort=network\">Network Address</a></th>".
	     "<th align=\"left\">Subnet Mask</th>".
		 "<th align=\"left\">Statics Used</th></tr>\n".
	     "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
		 
  $results = mysql_query($sql);  
  $javascript = ''; # This gets concatenated to below.
  while(list($subnet_id,$name,$long_start_ip,$long_end_ip,$long_mask,$note) = mysql_fetch_row($results)){
    $start_ip = long2ip($long_start_ip);
	$mask = long2ip($long_mask);
	
	$subnet_size = $long_end_ip - $long_start_ip;
	
	$sql = "SELECT COUNT(*) FROM statics WHERE subnet_id='$subnet_id'";
	$result = mysql_query($sql);
	$static_count = mysql_result($result, 0, 0);
	
	$sql = "SELECT start_ip, end_ip FROM acl WHERE subnet_id='$subnet_id'";
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
		 
	if($COLLATE['user']['accesslevel'] >= '3' || $COLLATE['settings']['perms'] > '3'){
	  echo "<a href=\"subnets.php?op=modify&amp;subnet_id=$subnet_id\"><img alt=\"modify subnet\" title=\"modify subnet\" src=\"images/modify.gif\" /></a> &nbsp; ".
           "<a href=\"#\" onclick=\"if (confirm('Are you sure you want to delete this object?')) { new Element.update('notice', ''); new Ajax.Updater('notice', '_subnets.php?op=delete&subnet_id=$subnet_id', {onSuccess:function(){ new Effect.Parallel( [new Effect.Fade('subnet_".$subnet_id."_row_1'), new Effect.Fade('subnet_".$subnet_id."_row_2'), new Effect.Fade('subnet_".$subnet_id."_row_3')]); }}); };\"><img src=\"./images/remove.gif\" alt=\"X\" title=\"delete subnet\" /></a>";
	}
    echo "</td>
		 </tr>\n";
		 
	echo "<tr id=\"subnet_".$subnet_id."_row_2\"><td colspan=\"4\"><span id=\"edit_note_".$subnet_id."\">$note</span></td></tr>\n";
    echo "<tr id=\"subnet_".$subnet_id."_row_3\"><td colspan=\"5\"><hr class=\"division\" /></td></tr>\n";
	
	if($COLLATE['user']['accesslevel'] >= '3' || $COLLATE['settings']['perms'] > '3'){
	       
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
  
  echo "</table>";
  
  echo $javascript;
  
} // Ends list_subnets function

function modify_subnet (){
  require_once('./include/header.php');
  
  if(!isset($_GET['subnet_id'])){
    $notice = "Please select a subnet to attempt to modify.";
	header("Location: blocks.php?notice=$notice");
  }
  
  $subnet_id = clean($_GET['subnet_id']);
  
  $sql = "SELECT name, start_ip, mask FROM subnets WHERE id='$subnet_id'";
  list($subnet_name,$long_start_ip,$long_mask) = mysql_fetch_row(mysql_query($sql)); 
  $start_ip = long2ip($long_start_ip);
  $mask = long2ip($long_mask);

  echo "<h1>Modify the $subnet_name subnet: $start_ip/$mask </h1><br />\n";

  echo "<h3>Move $subnet_name to a new block</h3><hr />\n".
	   "<form action=\"subnets.php?op=submitmove\" method=\"post\">\n".
	   "<input type=\"hidden\" name=\"subnet_id\" value=\"$subnet_id\" />".
	   "<p>Select the block you'd like to move this subnet into</p>".
	   "<select name=\"block_id\">";
  $sql = "SELECT id, name FROM blocks";
  $result = mysql_query($sql);
  while(list($block_id,$block_name) = mysql_fetch_row($result)){
    echo "<option value=\"$block_id\">$block_name</option\">";
  }
  echo "</select><br /><br />".
       "<p><input type=\"submit\" value=\" Go \" /></p></form><br /><br />";

  echo "<h3>Resize $subnet_name</h3><hr />";
  echo "<form action=\"subnets.php?op=resize\" method=\"post\">\n".
       "<input type=\"hidden\" name=\"subnet_id\" value=\"$subnet_id\" />\n".
       "<p>Please specify the new subnet in the form \"Network/Mask\" or \"Network/Mask bits\"</p>\n".
       "<p><input type=\"text\" name=\"new_subnet\" /></p>\n".
       "<p>You will be prompted on the next page with more information about how this will\n".
       "affect other subnets and static IP reservations.</p>\n".
       "<p><input type=\"submit\" value=\" Go \" /></p></form><br />";
  
} // Ends move_subnet function

function submit_move_subnet (){
  if(!isset($_POST['subnet_id']) || !isset($_POST['block_id'])){
    $notice = "Please select a subnet to attempt to move.";
    header("Location: blocks.php?notice=$notice");
    exit();
  }
  
  $subnet_id=clean($_POST['subnet_id']);
  $block_id=clean($_POST['block_id']);
  
  $result = mysql_query("SELECT name,start_ip, mask, block_id FROM subnets WHERE id='$subnet_id'");
  if(mysql_num_rows($result) != '1') {
    $notice = "The subnet you tried to modify is not valid.";
    header("Location: blocks.php?notice=$notice");
    exit();
  }
  list($subnet_name,$ip,$mask,$old_block_id)=mysql_fetch_row($result);
  $old_block_name=mysql_result(mysql_query("SELECT name FROM blocks WHERE id='$old_block_id'"), 0, 0);
  $new_block_name=mysql_result(mysql_query("SELECT name FROM blocks WHERE id='$block_id'"), 0, 0);
  $cidr=subnet2cidr($ip,$mask);
  AccessControl("3", "Subnet $subnet_name ($cidr) moved from $old_block_name block to $new_block_name block");
  
  $sql = "UPDATE subnets set block_id='$block_id' WHERE id='$subnet_id'";
  $result = mysql_query($sql);
  
  $notice = "Subnet $subnet_name moved from $old_block_name block to $new_block_name block";
  header("Location: subnets.php?block_id=$old_block_id&notice=$notice");
} // Ends submit_move_subnet function

function resize_subnet() {
  # Validate input
  $subnet_id = clean($_POST['subnet_id']);
  $new_subnet = clean($_POST['new_subnet']);
  $confirm = (empty($_POST['confirm'])) ? '' : $_POST['confirm'];

  $sql = "SELECT name, start_ip, end_ip, mask, block_id FROM subnets WHERE id='$subnet_id'";
  $result = mysql_query($sql);
  if ( mysql_num_rows($result) != '1' ){
    $notice = "Please choose a valid subnet to attempt to modify.";
    header("Location: blocks.php?notice=$notice");
    exit();
  }

  list($original_subnet_name,$original_long_start_ip,$original_long_end_ip,$original_long_mask,$original_block_id) = mysql_fetch_row($result);
  
  $original_cidr=subnet2cidr($original_long_start_ip,$original_long_mask);

  if(!strstr($new_subnet, '/')){
    $notice = "You must supply the number of mask bits or a mask.";
    header("Location: subnets.php?op=modify&subnet_id=$subnet_id&notice=$notice");
    exit();
  }
  
  list($new_start_ip,$new_mask) = explode('/', $new_subnet);
  
  if(ip2decimal($new_start_ip) == FALSE){
    $notice = "The IP you have entered is not valid.";
    header("Location: subnets.php?op=modify&subnet_id=$subnet_id&notice=$notice");
    exit();
  }
  
  $new_long_ip = ip2decimal($new_start_ip);
  if(!strstr($new_mask, '.') && ($new_mask <= '0' || $new_mask >= '32')){
    $notice = "The IP you have specified is not valid. The mask cannot be 0 or 32 bits long.";
    header("Location: subnets.php?op=modify&subnet_id=$subnet_id&notice=$notice");
    exit();
  }
  elseif(!strstr($new_mask, '.')){
    $bin = str_pad('', $new_mask, '1');
	$bin = str_pad($bin, '32', '0');
	$new_mask = bindec(substr($bin,0,8)).".".bindec(substr($bin,8,8)).".".bindec(substr($bin,16,8)).".".bindec(substr($bin,24,8));
    $new_mask = long2ip(ip2decimal($new_mask));
  }
  elseif(!checkNetmask($new_mask)){
    $notice = "The mask you have specified is not valid.";
    header("Location: subnets.php?op=modify&subnet_id=$subnet_id&notice=$notice");
    exit();
  }

  if ($original_long_mask == ip2decimal($new_mask)){
    $notice = "Modifying just the subnet number is not supported. This form only allows you to shrink, grow, or merge subnets.";
    header("Location: subnets.php?op=modify&subnet_id=$subnet_id&notice=$notice");
    exit();
  }
  
  # Make sure we have the actual network address
  $new_long_start_ip = ip2decimal($new_start_ip);
  $new_long_mask = ip2decimal($new_mask);
  $new_long_end_ip = $new_long_start_ip | (~$new_long_mask);
  $new_end_ip = long2ip($new_long_end_ip);
  
  $new_cidr=subnet2cidr($new_long_start_ip, $new_long_mask);
 
  if($confirm != 'true'){
    require_once('./include/header.php');
  }
  else {
    AccessControl('3', "Subnet $original_subnet_name resized from $original_cidr to $new_cidr");
  }
  
  # is new subnet larger or smaller?
  $original_binary_mask = sprintf("%032b", $original_long_mask);
  $new_binary_mask = sprintf("%032b", $new_long_mask);
  if (substr_count($original_binary_mask, '1') < substr_count($new_binary_mask, '1')){
    # if smaller:
    #  * validate new network falls within the old one
    if ($new_long_start_ip & $original_long_mask != $original_long_start_ip) {
      $notice = "The new subnet you specified is smaller than the old one, but not part of the old one. ".
                "Please choose a proper subnet to shrink this one to.";
      header("Location: subnets.php?op=modify&subnet_id=$subnet_id&notice=$notice");
      exit();
    }

    #  * list static IP addresses that would be lost
    if($confirm != 'true') {
	  $sql = "SELECT id, ip, name, contact, note, failed_scans FROM statics WHERE ". 
             "CAST(ip AS UNSIGNED) & CAST('$original_long_mask' AS UNSIGNED) = CAST('$original_long_start_ip' AS UNSIGNED) ".
             "AND CAST(ip AS UNSIGNED) & CAST('$new_long_mask' AS UNSIGNED) != CAST('$new_long_start_ip' AS UNSIGNED) ".
             "ORDER BY `ip` ASC";
	}
	else {
      $sql = "DELETE FROM statics WHERE ". 
             "CAST(ip AS UNSIGNED) & CAST('$original_long_mask' AS UNSIGNED) = CAST('$original_long_start_ip' AS UNSIGNED) ".
             "AND CAST(ip AS UNSIGNED) & CAST('$new_long_mask' AS UNSIGNED) != CAST('$new_long_start_ip' AS UNSIGNED)";
    }
	
    $result = mysql_query($sql);
    $totalrows = mysql_num_rows($result);
    
    if ($totalrows != '0' && $confirm != 'true'){
      echo "<h1>Static IPs in $original_subnet_name that will be deleted:</h1><br />\n".
           "<table width=\"100%\"><tr><th>IP Address</th><th>Name</th><th>Contact</th><th>Failed Scans</th></tr>".
  	   "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
      while(list($static_id,$ip,$name,$contact,$note,$failed_scans) = mysql_fetch_row($result)){
          $ip = long2ip($ip);
          echo "<tr><td>$ip</td><td>$name</td><td>$contact</td><td>$failed_scans</td><td></td></tr>\n";
          echo "<tr><td colspan=\"5\">$note</td></tr>\n";
          echo "<tr><td colspan=\"5\"><hr class=\"division\" /></td></tr>\n";
        }
      echo "</table><br /><br />";
    }
    elseif($confirm != 'true'){
      echo "<h1>Static IPs in $original_subnet_name that will be deleted:</h1><br />\n".
	       "<p>No static IP address reservations will be purged by this subnet shrink.</p><br /><br />";
    }  

    #  * show how ACLs would be adjusted
    # Find acls matching original subnet_id and see if start and end fall within new subnet
    $sql = "SELECT id, name, start_ip, end_ip FROM acl WHERE subnet_id='$subnet_id' AND ( ".
           "CAST(start_ip AS UNSIGNED) & CAST('$new_long_mask' AS UNSIGNED) != CAST('$new_long_start_ip' AS UNSIGNED) ".
           "OR CAST(end_ip AS UNSIGNED) & CAST('$new_long_mask' AS UNSIGNED) != CAST('$new_long_start_ip' AS UNSIGNED))";
    $result = mysql_query($sql);
    if (mysql_num_rows($result) == '0'){
      if($confirm != 'true'){ 
	    echo "<h1>ACLs in $original_subnet_name will be modified as follows:</h1><br />\n".
		     "<p>No ACL entries would be affected.</p><br /><br />"; 
	  }
    }
    else {
      if($confirm != 'true'){ 
	    echo "<h1>ACLs in $original_subnet_name will be modified as follows:</h1><br />\n".
           "<table width=\"100%\">\n".
           "<tr><th>Name</th><th>Starting IP Address</th><th>Ending IP Address</th><th>Modification</th></tr>\n".
           "<tr><td colspan=\"4\"><hr class=\"head\" /></td></tr>";
      }
		   
      while(list($acl_id,$acl_name,$acl_long_start_ip,$acl_long_end_ip) = mysql_fetch_row($result)){
        $note = ""; # this might not get set below.
		$sql = "";

	    if(($acl_long_start_ip & $new_long_mask) == $new_long_start_ip) {
          $new_acl_start_ip = long2ip($acl_long_start_ip);
        }
        else {
          $new_acl_start_ip = $new_start_ip;
          $note = "<b>Starting IP modified</b>";
		  $sql = "UPDATE acl SET start_ip='$new_long_start_ip' WHERE id='$acl_id'";
        }

	    if(($acl_long_end_ip & $new_long_mask) == $new_long_start_ip) {
          $new_acl_end_ip = long2ip($acl_long_end_ip);
        }
        else {
          $new_acl_end_ip = $new_end_ip;
          $note = "<b>Ending IP modified</b>";
		  $sql = "UPDATE acl SET end_ip='$new_long_end_ip' WHERE id='$acl_id'";
        }

        if (($new_acl_start_ip == $new_start_ip) && ($new_acl_end_ip == $new_end_ip)){
          $new_acl_start_ip = long2ip($acl_long_start_ip);
          $new_acl_end_ip = long2ip($acl_long_end_ip);
          $note = "<b>TO BE DELETED</b>";
		  $sql = "DELETE FROM acl WHERE id='$acl_id'";
        }
		if ($confirm != 'true') {
		  echo "<tr><td>$acl_name</td><td>$new_acl_start_ip</td><td>$new_acl_end_ip</td><td>$note</td></tr>\n";
		}
		elseif(strlen($sql) > '2') {
		  $result = mysql_query($sql);
		}
      }
      if ($confirm != 'true') { echo "</table>\n"; }
    }
  }
  else{
    # if larger:
	if(($original_long_start_ip & $new_long_mask) != $new_long_start_ip) {
      $notice = "The subnet you're expanding must be included in the new, larger subnet specified.";
	  header("Location: subnets.php?op=modify&subnet_id=$subnet_id&notice=$notice");
	  exit();
    }
    #  * list all subnets that new network overlaps
    $sql = "SELECT `id`, `name`, `start_ip`, `end_ip`, `mask`, `note` FROM `subnets` WHERE ". 
         "CAST(start_ip AS UNSIGNED) & CAST('$new_long_mask' AS UNSIGNED) = CAST('$new_long_start_ip' AS UNSIGNED) ORDER BY `start_ip` ASC";
    $results = mysql_query($sql);
    if(mysql_num_rows($results) < '1' && $confirm != 'true'){
      echo "<h1>Subnets that must be merged to cleanly expand $original_subnet_name based on your input:</h1><br />\n".
	       "<p>No reserved subnets are overlapped by growing this subnet.</p>";
    }
    else{
	  if($confirm != 'true'){
      echo "<h1>Subnets that must be merged to cleanly expand $original_subnet_name based on your input:</h1><br />\n".
           "<table width=\"100%\">".
           "<tr><th align=\"left\">Subnet Name</th>".
	       "<th align=\"left\">Network Address</th>".
	       "<th align=\"left\">Subnet Mask</th>".
	       "<tr><td colspan=\"4\"><hr class=\"head\" /></td></tr>\n";
      }		   
       
      while(list($affected_subnet_id,$name,$long_start_ip,$long_end_ip,$long_mask,$note) = mysql_fetch_row($results)){
	    if($confirm != 'true'){
          $start_ip = long2ip($long_start_ip);
	      $mask = long2ip($long_mask);
          echo "<tr><td><b>$name</b></td><td>$start_ip</td><td>$mask</td></tr>\n";
	      echo "<tr><td colspan=\"4\">$note</td></tr>\n";
          echo "<tr><td colspan=\"5\"><hr class=\"division\" /></td></tr>\n";
		}
		else {
		  $sql = "UPDATE acl SET subnet_id='$subnet_id' WHERE subnet_id='$affected_subnet_id'";
		  $result = mysql_query($sql);
		}
      }
	  if($confirm != 'true'){ echo "</table>"; }
	  else {
	    $sql = "DELETE FROM `subnets` WHERE CAST(start_ip AS UNSIGNED) & CAST('$new_long_mask' AS UNSIGNED) = CAST('$new_long_start_ip' AS UNSIGNED) ".
		       "AND id != '$subnet_id'";
		$result = mysql_query($sql);
	  
	    $sql = "UPDATE statics SET subnet_id='$subnet_id' WHERE ip & $new_long_mask = $new_long_start_ip";
		$result = mysql_query($sql);
	  }
    }
  }
  
  if ($confirm != 'true') {
    echo "<br /><br /><h3>Would you like  to proceed?</h3><hr /><br />\n".
         "<form action=\"subnets.php?op=resize\" method=\"post\">\n".
	     "<input type=\"hidden\" name=\"subnet_id\" value=\"$subnet_id\" />".
	     "<input type=\"hidden\" name=\"confirm\" value=\"true\" />".
	     "<input type=\"hidden\" name=\"new_subnet\" value=\"$new_subnet\" />".
	     "<p><input type=\"submit\" value=\" Go \" /> | <a href=\"subnets.php?block_id=$original_block_id\">Cancel</a></p>".
	     "</form>";
  }
  else {
    $sql = "UPDATE subnets set start_ip='$new_long_start_ip', end_ip='$new_long_end_ip', mask='$new_long_mask' WHERE id='$subnet_id'";
	$result = mysql_query($sql);
	$notice = "The subnet has been resized.";
	header("Location: subnets.php?block_id=$original_block_id&notice=$notice");
	exit();
  }
} // Ends resize_subnet()
?>