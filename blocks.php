<?php
/**
 * Please see /include/common.php for documentation on common.php, the $COLLATE global array used by this program, and the AccessControl function used widely.
 */
require_once('./include/common.php');
require_once('./include/header.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){

	case "add";
	add_block();
	break;
	
	case "submit";
	submit_block();
	break;
	
	default:
	list_blocks();
	break;
}

require_once('./include/footer.php');

function add_block(){
  $name = (empty($_GET['name'])) ? '' : $_GET['name'];
  $ip = (empty($_GET['ip'])) ? '' : $_GET['ip'];
  $end_ip = (empty($_GET['end_ip'])) ? '' : $_GET['end_ip'];
  $note = (empty($_GET['note'])) ? '' : $_GET['note'];

  echo "<div id=\"nametip\" style=\"display: none;\" class=\"tip\">Enter the name of the IP block here. The name should be 
	descriptive of what the subnets inside the block will be used for. The name should be short and should not contain 
	spaces.<br /><br /></div>\n".
	"<div id=\"iptip\" style=\"display: none;\" class=\"tip\">Enter a block of IP addresses in CIDR notation such as 
	\"10.10.0.0/23\" or using a subnet mask such as in \"10.10.0.0/255.255.254.0.\"<br /><br /></div>\n".
	"<div id=\"notetip\" style=\"display: none;\" class=\"tip\">Enter a very brief description of what the subnets inside the 
	block will\n".
	"  be used for. An example would be \"Point to Point subnets.\"<br /><br /></div>\n".
	"<h1>Add Block:</h1>\n".
	"<br />\n".
	"<form action=\"blocks.php?op=submit\" method=\"POST\">\n".
	"  <p>Name:<br /><input type=\"text\" name=\"name\" value=\"$name\" />\n".
	"    <a href=\"#\" onclick=\"new Effect.toggle($('nametip'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
	"  </p>\n".
	"  <p>IP:<br /><input type=\"text\" name=\"ip\" value=\"$ip\"/>\n".
	"    <a href=\"#\" onclick=\"new Effect.toggle($('iptip'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
	"  </p>\n".
	"  <p>Note: (Optional)<br /><input type=\"text\" name=\"note\" value=\"$note\" />\n".
	"    <a href=\"#\" onclick=\"new Effect.toggle($('notetip'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
	"  </p>\n".
	"  <p><input type=\"submit\" value=\" Go \" /></p>\n".
	"</form>\n";

} // Ends add_block function

function submit_block() {
  $name = clean($_POST['name']);
  $ip = clean($_POST['ip']);
  $note = clean($_POST['note']);
  
  if(empty($name) || empty($ip)){
    $notice = "Please verify that required fields have been completed.";
    header("Location: blocks.php?op=add&name=$name&ip=$ip&note=$note&notice=$notice");
	exit();
  }
  
  // Make sure that the block name isn't already in use
  $sql = "SELECT name FROM blocks WHERE name='$name'";
  $result = mysql_query($sql);
  if(mysql_num_rows($result) >= '1'){
    $notice = "The block name you have chosen is already in use. Please use another name.";
    header("Location: blocks.php?op=add&name=$name&ip=$ip&note=$note&notice=$notice");
	exit();
  }
  
  if(!strstr($ip, '/')){
    $notice = "You must supply the number of mask bits or a mask to add an IP block.";
    header("Location: blocks.php?op=add&name=$name&ip=$ip&note=$note&notice=$notice");
	exit();
  }
  
  list($ip,$mask) = explode('/', $ip);
  
  if(ip2long($ip) == FALSE){
    $notice = "The IP you have entered is not valid.";
    header("Location: blocks.php?op=add&name=$name&ip=$ip&note=$note&notice=$notice");
	exit();
  }
  
  $ip = long2ip(ip2long($ip));  
  $long_ip = ip2long($ip);
  if(!strstr($mask, '.') && ($mask <= '0' || $mask >= '32')){
    $notice = "The IP block you have specified is not valid. The mask cannot be 0 or 32 bits long.";
    header("Location: blocks.php?op=add&name=$name&ip=$ip&note=$note&notice=$notice");
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
  
  $long_mask = ip2long($mask);
  $long_ip = ($long_ip & $long_mask); // This makes sure they entered the network address and not an IP inside the network
  $long_end_ip = $long_ip | (~$long_mask);
  
  // We need to make sure this new block doesn't overlap an existing block
  $sql = "SELECT id FROM blocks WHERE (start_ip <= '$long_ip' AND end_ip >= '$long_ip') OR 
          (start_ip <= '$long_end_ip' AND end_ip >= '$long_end_ip') OR
		  (start_ip >= '$long_ip' AND end_ip <= '$long_end_ip')";
  
  $search = mysql_query($sql);
  if(mysql_num_rows($search) != '0'){
    $notice = "The IP block you entered overlaps with an existing IP block in the database.";
	header("Location: blocks.php?op=add&name=$name&ip=$ip&note=$note&notice=$notice");
	exit();
  }
  
  $sql = "INSERT INTO blocks (name, start_ip, end_ip, note) VALUES('$name', '$long_ip', '$long_end_ip', '$note')";
  mysql_query($sql);
  $notice = "The IP block you entered has been added.";
  header("Location: blocks.php?notice=$notice");
  exit();

} // Ends submit_blocks function

function list_blocks(){
 
  echo "<h1>All IP Blocks</h1>\n".
       "<p style=\"text-align: right;\"><a href=\"blocks.php?op=add\">
	   <img src=\"./images/add.gif\" alt=\"Add\" /> Add a Block </a></p>";
	   
  echo "<table width=\"100%\">\n". // Here we actually build the HTML table
	     "<tr><th align=\"left\">Block Name</th>".
	     "<th align=\"left\">Starting IP</th>".
	     "<th align=\"left\">Ending IP</th>".
	     "<th align=\"left\">Actions</th></tr>\n".
	     "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
		 
  $sql = "SELECT `id`, `name`, `start_ip`, `end_ip`, `note` FROM `blocks` ORDER BY `name` ASC";
  $results = mysql_query($sql);
  
  while(list($block_id,$name,$long_start_ip,$long_end_ip,$note) = mysql_fetch_row($results)){
    $start_ip = long2ip($long_start_ip);
	$end_ip = long2ip($long_end_ip);
	
    echo "<tr>
	     <td><b><a href=\"subnets.php?block_id=$block_id\">$name</a></b></td><td>$start_ip</td>
		 <td>$end_ip</td>
		 <td><a href=\"blocks.php?op=delete&amp;block_id=$block_id\"><img src=\"./images/remove.gif\" alt=\"X\" /></a> &nbsp;
		 &nbsp;<a href=\"blocks.php?op=edit&amp;block_id=$block_id\"><img src=\"./images/edit.gif\" alt=\"edit\" /></td></td>
		 </tr>\n";
	echo "<tr><td>$note<td></tr>\n";
    echo "<tr><td colspan=\"5\"><hr class=\"division\" /></td></tr>\n";
  }
  
  echo "</table>";
} // Ends list_blocks function


?>