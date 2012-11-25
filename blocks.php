<?php
/**
 * Please see /include/common.php for documentation on common.php, the $COLLATE global array used by this program, and the AccessControl function used widely.
 */
require_once('./include/common.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){

	case "add";
	AccessControl("4", "Add IP Block form accessed");
	add_block();
	break;
	
	case "submit";
	submit_block();
	break;
	
	default:
	AccessControl("1", null);
	list_blocks();
	break;
}

require_once('./include/footer.php');

function add_block(){
  global $COLLATE;
  require_once('./include/header.php');
  
  $name = (empty($_GET['name'])) ? '' : $_GET['name'];
  $ip = (empty($_GET['ip'])) ? '' : $_GET['ip'];
  $end_ip = (empty($_GET['end_ip'])) ? '' : $_GET['end_ip'];
  $note = (empty($_GET['note'])) ? '' : $_GET['note'];

  echo "<h1>".$COLLATE['languages']['selected']['AddaBlock']."</h1>\n".
	   "<br />\n".
	   "<div style=\"float: left\">\n".
	   "<form action=\"blocks.php?op=submit\" method=\"POST\">\n".
	   "  <p><b>".$COLLATE['languages']['selected']['Name'].":</b><br /><input type=\"text\" name=\"name\" value=\"$name\" />\n".
	   "    <a href=\"#\" onclick=\"new Effect.toggle($('nametip'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
	   "  </p>\n".
	   "  <p><b>".$COLLATE['languages']['selected']['IP'].":</b><br /><input type=\"text\" name=\"ip\" value=\"$ip\"/>\n".
	   "    <a href=\"#\" onclick=\"new Effect.toggle($('iptip'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
	   "  </p>\n".
	   "  <p><b>".$COLLATE['languages']['selected']['EndIP'].":</b> ".$COLLATE['languages']['selected']['Optional'].
	   "    <br /><input type=\"text\" name=\"end_ip\" value=\"$end_ip\" />\n".
	   "    <a href=\"#\" onclick=\"new Effect.toggle($('endiptip'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
	   "  </p>\n".
	   "  <p><b>".$COLLATE['languages']['selected']['Note'].":</b> ".$COLLATE['languages']['selected']['Optional'].
	   "    <br /><input type=\"text\" name=\"note\" value=\"$note\" />\n".
	   "    <a href=\"#\" onclick=\"new Effect.toggle($('notetip'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
	   "  </p>\n".
	   "  <p><input type=\"submit\" value=\" ".$COLLATE['languages']['selected']['Go']." \" /></p>\n".
	   "</form></div>\n".
	   "<div id=\"nametip\" style=\"display: none;\" class=\"tip\">".$COLLATE['languages']['selected']['blocknamehelp']."<br /><br /></div>\n".
	   "<div id=\"iptip\" style=\"display: none;\" class=\"tip\">".$COLLATE['languages']['selected']['blockiphelp']."<br /><br /></div>\n".
	   "<div id=\"endiptip\" style=\"display: none;\" class=\"tip\">".$COLLATE['languages']['selected']['blockendiphelp']."<br /><br /></div>\n".
	   "<div id=\"notetip\" style=\"display: none;\" class=\"tip\">".$COLLATE['languages']['selected']['blocknotehelp']."<br /><br /></div>\n".
	   "<p style=\"clear: both\" />\n";

} // Ends add_block function

function submit_block() {
  $name = clean($_POST['name']);
  $ip = clean($_POST['ip']);
  $end_ip = clean($_POST['end_ip']);
  $note = clean($_POST['note']);
 
  if(empty($name) || empty($ip)){
    $notice = "missingfield-notice";
    header("Location: blocks.php?op=add&name=$name&ip=$ip&end_ip=$end_ip&note=$note&notice=$notice");
	exit();
  }
  
  // Make sure that the block name isn't already in use
  $sql = "SELECT name FROM blocks WHERE name='$name'";
  $result = mysql_query($sql);
  if(mysql_num_rows($result) >= '1'){
    $notice = "blocknameconflict";
    header("Location: blocks.php?op=add&name=$name&ip=$ip&end_ip=$end_ip&note=$note&notice=$notice");
	exit();
  }
  
  if(!strstr($ip, '/') && empty($end_ip)){
    $notice = "blockbounds-notice";
    header("Location: blocks.php?op=add&name=$name&ip=$ip&end_ip=$end_ip&note=$note&notice=$notice");
	exit();
  }
  
  if(strstr($ip, '/')){
    list($ip,$mask) = explode('/', $ip);
  
    if(ip2decimal($ip) == FALSE){
      $notice = "invalidip";
      header("Location: blocks.php?op=add&name=$name&ip=$ip&end_ip=$end_ip&note=$note&notice=$notice");
	  exit();
    }
  
    $ip = long2ip(ip2decimal($ip));  
    $long_ip = ip2decimal($ip);
    if(!strstr($mask, '.') && ($mask <= '0' || $mask >= '32')){
      $notice = "invalidmask";
      header("Location: blocks.php?op=add&name=$name&ip=$ip&end_ip=$end_ip&note=$note&notice=$notice");
	  exit();
    }
    elseif(!strstr($mask, '.')){
      $bin = str_pad('', $mask, '1');
	  $bin = str_pad($bin, '32', '0');
	  $mask = bindec(substr($bin,0,8)).".".bindec(substr($bin,8,8)).".".bindec(substr($bin,16,8)).".".bindec(substr($bin,24,8));
      $mask = long2ip(ip2decimal($mask));
    }
    elseif(!checkNetmask($mask)){
      $notice = "invalidmask";
      header("Location: blocks.php?op=add&name=$name&ip=$ip&end_ip=$end_ip&note=$note&notice=$notice");
	  exit();
    }
    
	$long_mask = ip2decimal($mask);
    $long_ip = ($long_ip & $long_mask); // This makes sure they entered the network address and not an IP inside the network
    $long_end_ip = $long_ip | (~$long_mask);
  }
  else{
    if(!long2ip($end_ip)){
	  $notice = "blockbounds-notice";
	  header("Location: blocks.php?op=add&name=$name&ip=$ip&end_ip=$end_ip&note=$note&notice=$notice");
	}
    $long_ip = ip2decimal($ip);
    $long_end_ip = ip2decimal($end_ip);
  }
  
  // We need to make sure this new block doesn't overlap an existing block
  $sql = "SELECT id FROM blocks WHERE (CAST(start_ip AS UNSIGNED) <= CAST('$long_ip' AS UNSIGNED) AND CAST(end_ip AS UNSIGNED) >= CAST('$long_ip' AS UNSIGNED)) OR 
          (CAST(start_ip AS UNSIGNED) <= CAST('$long_end_ip' AS UNSIGNED) AND CAST(end_ip AS UNSIGNED) >= CAST('$long_end_ip' AS UNSIGNED)) OR
		  (CAST(start_ip AS UNSIGNED) >= CAST('$long_ip' AS UNSIGNED) AND CAST(end_ip AS UNSIGNED) <= CAST('$long_end_ip' AS UNSIGNED))";

  $search = mysql_query($sql);
  if(mysql_num_rows($search) != '0'){
    $notice = "blockoverlap-notice";
	header("Location: blocks.php?op=add&name=$name&ip=$ip&end_ip=$end_ip&note=$note&notice=$notice");
	exit();
  }
  $username = (empty($_SESSION['username'])) ? 'system' : $_SESSION['username'];
  $sql = "INSERT INTO blocks (name, start_ip, end_ip, note, modified_by, modified_at) VALUES('$name', '$long_ip', '$long_end_ip', '$note', '$username', now())";
  
  $accesslevel = "4";
  $message = "IP Block added: $name";
  AccessControl($accesslevel, $message); // We don't want to generate logs when nothing is really happening, so this goes down here.
  
  
  mysql_query($sql);
  $notice=str_replace("%name%", "$name", $COLLATE['languages']['selected']['blockadded-notice']);
  header("Location: blocks.php?notice=$notice");
  exit();

} // Ends submit_blocks function

function list_blocks(){
  global $COLLATE;
  require_once('./include/header.php');
   
  $sort = (empty($_GET['sort'])) ? "" : $_GET['sort'];
  if ($sort === 'network') { 
    $sort = 'start_ip';
  }
  else {
    $sort = 'name';
  }
 
  echo "<h1>".$COLLATE['languages']['selected']['AllIPBlocks']."</h1>\n".
       "<p style=\"text-align: right;\"><a href=\"blocks.php?op=add\">
	   <img src=\"./images/add.gif\" alt=\"Add\" /> ".$COLLATE['languages']['selected']['AddaBlock']." </a></p>";
	   
  echo "<table width=\"100%\">\n". // Here we actually build the HTML table
	     "<tr><th align=\"left\"><a href=\"blocks.php\">".$COLLATE['languages']['selected']['BlockName']."</a></th>".
	     "<th align=\"left\"><a href=\"blocks.php?sort=network\">".$COLLATE['languages']['selected']['StartingIP']."</a></th>".
	     "<th align=\"left\">".$COLLATE['languages']['selected']['EndIP']."</th>".
	     "</tr>\n".
	     "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
		 
  $sql = "SELECT `id`, `name`, `start_ip`, `end_ip`, `note` FROM `blocks` ORDER BY `$sort` ASC";
  $results = mysql_query($sql);
  $javascript = ''; # this gets concatenated to below
  while(list($block_id,$name,$long_start_ip,$long_end_ip,$note) = mysql_fetch_row($results)){
    $start_ip = long2ip($long_start_ip);
	$end_ip = long2ip($long_end_ip);
	
    echo "<tr id=\"block_".$block_id."_row_1\">
	     <td><b><span id=\"edit_name_".$block_id."\">$name</span></b></td>
		 <td><a href=\"subnets.php?block_id=$block_id\">$start_ip</a></td>
		 <td>$end_ip</td>
		 <td>";
		 
	if($COLLATE['user']['accesslevel'] >= '4' || $COLLATE['settings']['perms'] > '4'){
	  echo " <a href=\"#\" onclick=\"
	         if (confirm('".$COLLATE['languages']['selected']['confirmdelete']."')) { 
			   new Element.update('notice', ''); 
			   new Ajax.Updater('notice', '_blocks.php?op=delete&block_id=$block_id', {onSuccess:function(){ 
			     new Effect.Parallel( [new Effect.Fade('block_".$block_id."_row_1'), 
				 new Effect.Fade('block_".$block_id."_row_2'), 
				 new Effect.Fade('block_".$block_id."_row_3')]); 
               }}); 
			 };\">
			 <img src=\"./images/remove.gif\" alt=\"X\" /></a>";
	}
    echo "</td>
		 </tr>\n";
	echo "<tr id=\"block_".$block_id."_row_2\"><td colspan=\"3\"><span id=\"edit_note_".$block_id."\">$note</span></td></tr>\n";
    echo "<tr id=\"block_".$block_id."_row_3\"><td colspan=\"4\"><hr class=\"division\" /></td></tr>\n";
	
	if($COLLATE['user']['accesslevel'] >= '4' || $COLLATE['settings']['perms'] > '4'){
	  $javascript .=
		   "<script type=\"text/javascript\"><!--\n".
	       "  new Ajax.InPlaceEditor('edit_name_".$block_id."', '_blocks.php?op=edit&block_id=$block_id&edit=name',
		      {
			   clickToEditText: '".$COLLATE['languages']['selected']['ClicktoEdit']."',
			   highlightcolor: '#a5ddf8', 
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
		   "  new Ajax.InPlaceEditor('edit_note_".$block_id."', '_blocks.php?op=edit&block_id=$block_id&edit=note',
		      {
			   clickToEditText: '".$COLLATE['languages']['selected']['ClicktoEdit']."',
			   highlightcolor: '#a5ddf8',  
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
  
  echo (empty($javascript) ? "" : $javascript);
  
} // Ends list_blocks function
?>