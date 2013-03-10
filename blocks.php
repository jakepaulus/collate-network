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
  global $COLLATE;
  include 'include/validation_functions.php';
  
  $name = (isset($_POST['name'])) ? $_POST['name'] : '';
  $note = (isset($_POST['note'])) ? $_POST['note'] : ''; # this input is optional
  $ip = (isset($_POST['ip'])) ? $_POST['ip'] : '';
  $end_ip = (isset($_POST['end_ip'])) ? $_POST['end_ip'] : '';
  $username = (empty($_SESSION['username'])) ? 'system' : $_SESSION['username'];
 
  if(empty($name) || empty($ip)){
    $notice = "missingfield-notice";
    header("Location: blocks.php?op=add&name=$name&ip=$ip&end_ip=$end_ip&note=$note&notice=$notice");
	exit();
  }
  
  $return = validate_text($name,'blockname');
  if($return['0'] === false){
    $notice = $return['error'];
	header("Location: blocks.php?op=add&name=$name&ip=$ip&end_ip=$end_ip&note=$note&notice=$notice");
	exit();
  }
  else{
    $name = $return['1'];
  }
  $result = mysql_query("SELECT id from blocks where name='$name'");
  if(mysql_num_rows($result) != '0'){
    header("HTTP/1.1 500 Internal Error");
    $notice = 'duplicatename';
	header("Location: blocks.php?op=add&name=$name&ip=$ip&end_ip=$end_ip&note=$note&notice=$notice");
    exit();
  }
  
  $return = validate_text($note,'note');
  if($return['0'] === false){
    $notice = $return['error'];
	header("Location: blocks.php?op=add&name=$name&ip=$ip&end_ip=$end_ip&note=$note&notice=$notice");
	exit();
  }
  else{
    $note = $return['1'];
  }
  
  if(empty($end_ip)){ # subnet supplied
    $return = validate_network($ip,'block');
  }
  else{ # range supplied
    $return = validate_ip_range($ip,$end_ip,'block');
  }
  
  if($return['0'] === false){
    $notice = $return['error'];
	header("Location: blocks.php?op=add&name=$name&ip=$ip&end_ip=$end_ip&note=$note&notice=$notice");
	exit();
  }
  else{
    $start_ip = $return['start_ip'];
	$end_ip = $return['end_ip'];
	$long_start_ip = $return['long_start_ip'];
	$long_end_ip = $return['long_end_ip'];
  }
    
  $sql = "INSERT INTO blocks (name, start_ip, end_ip, note, modified_by, modified_at) VALUES('$name', '$long_start_ip', '$long_end_ip', '$note', '$username', now())";
  
  $accesslevel = "4";
  $message = "IP Block added: $name";
  AccessControl($accesslevel, $message); // We don't want to generate logs when nothing is really happening, so this goes down here.
  
  
  mysql_query($sql);
  $notice = 'blockadded-notice';
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