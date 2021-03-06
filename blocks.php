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
	
	case "modify":
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
  global $dbo;
  
  $block_id = (empty($_GET['block_id'])) ? '' : $_GET['block_id'];
  $name = (empty($_GET['name'])) ? '' : $_GET['name'];
  $ip = (empty($_GET['ip'])) ? '' : $_GET['ip'];
  $end_ip = (empty($_GET['end_ip'])) ? '' : $_GET['end_ip'];
  $note = (empty($_GET['note'])) ? '' : $_GET['note'];
  $parent_block = (isset($_GET['parent_block'])) ? $_GET['parent_block'] : null;
  $block_type = (isset($_GET['block_type'])) ? $_GET['block_type'] : '';
  
  if(!empty($block_id)){ #this is an edit, not an add
    if(!preg_match("/[0-9]*/", $block_id)){ #this better be a block id and not some sort of trick
	  $notice = 'invalidrequest';
	  header("Location: blocks.php?notice=$notice");
	  exit();
	}
	$sql = "SELECT name, start_ip, end_ip, note, parent_id, type FROM blocks WHERE id='$block_id'";
	$result = $dbo -> query($sql);
	if($result -> rowCount()!= '1'){
	  $notice = 'invalidrequest';
	  header("Location: blocks.php?notice=$notice");
	  exit();
	}
	list($name,$long_start_ip,$long_end_ip,$note,$old_parent_block,$block_type) = $result->fetch(PDO::FETCH_NUM);
	$ip = (empty($long_start_ip)) ? '' : long2ip($long_start_ip);
	$end_ip = (empty($long_end_ip)) ? '' : long2ip($long_end_ip);
	$hidden_form_inputs = '<input type="hidden" name="update_block" value="true">'.
	                      "<input type=\"hidden\" name=\"block_id\" value=\"$block_id\">";
	$block_action_text = str_replace("%block_name%", $name, $COLLATE['languages']['selected']['ModifyBlock']);
	$parent_block = (empty($parent_block)) ? $old_parent_block : $parent_block;
  }
  else{
    $block_action_text = $COLLATE['languages']['selected']['AddaBlock'];
	$hidden_form_inputs = '';
  }
  
  if(empty($block_type) || $block_type == 'ipv4'){
    $ipv4block_html = 'checked="checked"';
	$containerblock_html = '';
	$ip_range_style = '';
  }
  else{
    $ipv4block_html = '';
    $containerblock_html = 'checked="checked"';
	$ip_range_style = 'style="display: none;"';
  }
  
  require_once('./include/header.php');

  echo "<h1>$block_action_text</h1>\n".
	   "<br />\n".
	   "<div style=\"float: left\">\n".
	   "<form action=\"blocks.php?op=submit\" method=\"POST\">\n".
	   "  <p><label for=\"container_block\"><img src=\"images/container_block.png\" alt=\"\"></label>
	         <input type=\"radio\" name=\"block_type\" id=\"container_block\" $containerblock_html value=\"container\" 
	          onchange=\"new Effect.Fade('iprangefields', {duration: 0.2}); return false;\"> ".$COLLATE['languages']['selected']['iscontainerblock'].
       "<br />\n".
	   "     <label for=\"ip_block\"><img src=\"images/ip_block.png\" alt=\"\"></label>
	         <input type=\"radio\" name=\"block_type\" id=\"ip_block\" $ipv4block_html value=\"ipv4\" 
	          onchange=\"new Effect.Appear('iprangefields'); return false;\"> ".
	      $COLLATE['languages']['selected']['isipv4block'].
	   "  </p>\n".
	   "  <p><b>".$COLLATE['languages']['selected']['Name'].":</b><br /><input type=\"text\" name=\"name\" value=\"$name\" required minlength=\"3\" maxlength=\"25\" />\n".
	   "    <a href=\"#\" onclick=\"new Effect.toggle($('nametip'),'appear'); return false;\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
	   "  </p>\n".
	   "  <p id=\"nametip\" style=\"display: none;\" class=\"tip\">".$COLLATE['languages']['selected']['blocknamehelp']."</p>\n".
	   "  <div id=\"iprangefields\" $ip_range_style>\n".
	   "    <p><b>".$COLLATE['languages']['selected']['IP'].":</b> ".$COLLATE['languages']['selected']['Optional'].
	   "      <br /><input type=\"text\" name=\"ip\" value=\"$ip\"/>\n".
	   "      <a href=\"#\" onclick=\"new Effect.toggle($('iptip'),'appear'); return false;\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
	   "    </p>\n".
	   "    <p id=\"iptip\" style=\"display: none;\" class=\"tip\">".$COLLATE['languages']['selected']['blockiphelp']."</p>\n".
	   "    <p><b>".$COLLATE['languages']['selected']['EndIP'].":</b> ".$COLLATE['languages']['selected']['Optional'].
	   "      <br /><input type=\"text\" name=\"end_ip\" value=\"$end_ip\" />\n".
	   "      <a href=\"#\" onclick=\"new Effect.toggle($('endiptip'),'appear'); return false;\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
	   "    </p>\n".
	   "    <p id=\"endiptip\" style=\"display: none;\" class=\"tip\">".$COLLATE['languages']['selected']['blockendiphelp']."</p>\n".
	   "  </div>\n".
	   "  <p><b>".$COLLATE['languages']['selected']['Note'].":</b> ".$COLLATE['languages']['selected']['Optional'].
	   "    <br /><input type=\"text\" name=\"note\" value=\"$note\" maxlength=\"80\" />\n".
	   "    <a href=\"#\" onclick=\"new Effect.toggle($('notetip'),'appear'); return false;\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
	   "  $hidden_form_inputs</p>\n".
	   "  <p id=\"notetip\" style=\"display: none;\" class=\"tip\">".$COLLATE['languages']['selected']['blocknotehelp']."</p>\n".
       "  <p><b>".$COLLATE['languages']['selected']['ParentBlock'].":</b><select name=\"parent_block\">";
	   
  if($parent_block !== null){
    echo "<option value=\"null\">[root]</option>\n";
  }
  else{
     echo "<option selected=\"selected\" value=\"null\">[root]</option>\n";
  }
  
  $sql = "SELECT id, name, parent_id FROM blocks WHERE type='container'";
  $result = $dbo -> query($sql);
  while(list($select_block_id,$select_block_name,$select_block_parent) = $result->fetch(PDO::FETCH_NUM)){
	$block_paths[$select_block_id]="$select_block_name";
	while($select_block_parent !== null){ #this has the potential to be really slow and awful...
	  $sql = "SELECT name, parent_id FROM blocks WHERE id='$select_block_parent'";
	  $recursive_result = $dbo -> query($sql);
	  list($recursive_parent_name,$recursive_parent_parent) = $recursive_result->fetch(PDO::FETCH_NUM);
	  $block_paths[$select_block_id] = "$recursive_parent_name/".$block_paths[$select_block_id];
	  $select_block_parent = $recursive_parent_parent;
	}
	$block_paths[$select_block_id]='[root]/'.$block_paths[$select_block_id];
  }
  if(!empty($block_paths)){
    natcasesort($block_paths);
    foreach ($block_paths as $select_id => $select_text){
      if($parent_block == $select_id){
	    echo "<option selected=\"selected\" value=\"$select_id\">$select_text</option>\n";
	  }
	  else{
	    echo "<option value=\"$select_id\">$select_text</option>\n";
      }
    }
  }
	   
  echo "</select></p>\n".
       "  <p><input type=\"submit\" value=\" ".$COLLATE['languages']['selected']['Go']." \" /></p>\n".
	   "</form></div>\n".
       "<p style=\"clear: both\">\n";

} // Ends add_block function

function submit_block() {
  #validation here might look messy, but it's essentially in order of parameters listed below by
  # 1. all checks that don't require db lookups
  # 2. all other checks
  
  global $COLLATE;
  global $dbo;
  include 'include/validation_functions.php';
    
  $block_id = (isset($_POST['block_id'])) ? $_POST['block_id'] : '';
  $name = (isset($_POST['name'])) ? $_POST['name'] : '';
  $note = (isset($_POST['note'])) ? $_POST['note'] : ''; # this input is optional
  $ip = (isset($_POST['ip'])) ? $_POST['ip'] : '';
  $end_ip = (isset($_POST['end_ip'])) ? $_POST['end_ip'] : '';
  $username = (empty($_SESSION['username'])) ? 'system' : $_SESSION['username'];
  $update_block = (isset($_POST['update_block'])) ? $_POST['update_block'] : false;
  $submit_op = ($update_block == 'true') ? "modify&block_id=$block_id" : 'add';
  $parent_block = (isset($_POST['parent_block'])) ? $_POST['parent_block'] : '';
  $block_type = (isset($_POST['block_type'])) ? $_POST['block_type'] : '';
  
  if($block_type == 'container'){ #containers don't have IP ranges associated with them
    $ip = '';
	$end_ip = '';
  }
   
  if(empty($name) || (!empty($end_ip) && empty($ip)) || empty($block_type)){
    $notice = "missingfield-notice";
    header("Location: blocks.php?op=$submit_op&name=$name&ip=$ip&end_ip=$end_ip&note=$note&block_type=$block_type&parent_block=$parent_block&notice=$notice");
	exit();
  }
  
  if(empty($parent_block) || (!preg_match("/[0-9]*/", $parent_block) && $parent_block != 'null')){
    $notice = "invalidrequest";
    header("Location: blocks.php?notice=$notice");
	exit();
  }
  
  $return = validate_text($name,'blockname');
  if($return['0'] === false){
    $notice = $return['error'];
	header("Location: blocks.php?op=$submit_op&name=$name&ip=$ip&end_ip=$end_ip&note=$note&block_type=$block_type&parent_block=$parent_block&notice=$notice");
	exit();
  }
  else{
    $name = $return['1'];
  }
  unset($return);
  
  if(!preg_match('/^container$|^ipv4$/', $block_type)){
    $notice = 'invalidrequest';
	header("Location: blocks.php?op=$submit_op&name=$name&ip=$ip&end_ip=$end_ip&note=$note&parent_block=$parent_block&notice=$notice");
	exit();
  }

  if($update_block === false){ # checking for duplicate block name
    $sql = "SELECT id from blocks where name='$name'";
    $result = $dbo -> query($sql);
	if($result -> rowCount()!= '0'){
  	  header("HTTP/1.1 400 Bad Request");
   	  $notice = 'duplicatename';
   	  header("Location: blocks.php?op=$submit_op&name=$name&ip=$ip&end_ip=$end_ip&note=$note&block_type=$block_type&parent_block=$parent_block&notice=$notice");
   	  exit();
	}
  }
  else{ # checking that we're updating a block that actually exists
    $sql = "SELECT name FROM blocks WHERE id='$block_id'";
    $result = $dbo -> query($sql);
	if($result -> rowCount()!= '1'){
      header("HTTP/1.1 400 Bad Request");
      $notice = 'selectblock';
	  header("Location: blocks.php?notice=$notice");
   	  exit();
	}
	$old_block_name = $result -> fetchColumn();
  }
  
  $return = validate_text($note,'note');
  if($return['0'] === false){
    $notice = $return['error'];
	header("Location: blocks.php?op=$submit_op&name=$name&ip=$ip&end_ip=$end_ip&note=$note&block_type=$block_type&parent_block=$parent_block&notice=$notice");
	exit();
  }
  else{
    $note = $return['1'];
  }
  unset($return);
  
  if(empty($end_ip) && !empty($ip)){ # subnet supplied
    $return = validate_network($ip,'block',$block_id);
  }
  elseif(!empty($ip)){ # range supplied
    $return = validate_ip_range($ip,$end_ip,'block',$block_id);
  }
  
  if(isset($return) && $return['0'] === false){
    $notice = $return['error'];
	header("Location: blocks.php?op=$submit_op&name=$name&ip=$ip&end_ip=$end_ip&note=$note&block_type=$block_type&parent_block=$parent_block&notice=$notice");
	exit();
  }
  elseif(isset($return)){
	$long_start_ip = $return['long_start_ip'];
	$long_end_ip = $return['long_end_ip'];
  }
  unset($return);
  
  $result = '';
  if($parent_block != 'null'){
    $sql = "SELECT id FROM blocks WHERE id='$parent_block'";
    $result = $dbo -> query($sql);
	if($result -> rowCount()!= '1'){
	  $notice = "invalidrequest";
      header("Location: blocks.php?notice=$notice");
	  exit();
	}
	$parent_id = "'$parent_block'";
  }
  else{
    $parent_id = 'null';
  }
  
  if($update_block === false){ # new block
    $old_parent_block = $parent_block; #we're going to redirect the user to the block they put this block into
  }
  else{ 
    $sql = "SELECT parent_id FROM blocks WHERE id='$block_id'";
	$result = $dbo -> query($sql);
    $old_parent_block = $result -> fetchColumn();
  }
  
  # If we're changing an existing block, we must make sure we don't orphan a child object
  if($update_block !== false){
    if($block_type == 'ipv4' && find_child_blocks($block_id) !== false){
	  $notice = 'wouldorphanblocks';
	  header("Location: blocks.php?op=$submit_op&name=$name&ip=$ip&end_ip=$end_ip&note=$note&notice=$notice");
	  exit();	  
	}
	elseif($block_type == 'container'){ # just check this block for subnets
	  $sql = "SELECT count(*) FROM subnets where block_id='$block_id'";
	  $result = $dbo -> query($sql);
	  if($result -> fetchColumn() != '0'){
	    $notice = 'wouldorphansubnets';
	    header("Location: blocks.php?op=$submit_op&name=$name&ip=$ip&end_ip=$end_ip&note=$note&parent_block=$parent_block&notice=$notice");
	    exit();
	  }
	}
  }
  
  if($update_block){
    $sql = "UPDATE blocks SET name='$name', start_ip='$long_start_ip', end_ip='$long_end_ip', note='$note', modified_by='$username', modified_at=now(),
           parent_id=$parent_id, type='$block_type' WHERE id='$block_id'";
  }
  else{
    $sql = "INSERT INTO blocks (name, start_ip, end_ip, note, modified_by, modified_at, parent_id, type) 
	       VALUES('$name', '$long_start_ip', '$long_end_ip', '$note', '$username', now(), $parent_id, '$block_type')";
  }
  
  $accesslevel = "4";
  $message = ($update_block) ? "IP Block updated: $name" : "IP Block added: $name";
  $message .= ($name != $old_block_name) ? "(previously $old_block_name)" : '';
  AccessControl($accesslevel, $message); // We don't want to generate logs when nothing is really happening, so this goes down here.
  
  $dbo -> query($sql);
  $notice = ($update_block) ? 'blockupdated-notice' : 'blockadded-notice';
  if($old_parent_block == 'null'){
    header("Location: blocks.php?notice=$notice");
  }
  else{
    header("Location: blocks.php?block_id=$old_parent_block&notice=$notice");
  }
  exit();

} // Ends submit_blocks function

function list_blocks(){
  global $COLLATE;
  global $dbo;
  require_once('./include/header.php');  
  
  $block_id = (isset($_GET['block_id']) && preg_match("/[0-9]*/", $_GET['block_id'])) ? $_GET['block_id'] : '';
  
  if(!empty($block_id)){
    $parent_name_check = "SELECT name FROM blocks WHERE id = '$block_id'";
    $result = $dbo -> query($parent_name_check);
    if($result -> rowCount()!= '1'){
      $block_id = ''; # Instead of an error, we'll just show them the root-level blocks list
    }
    else{
      $parent_block_name = $result -> fetchColumn();
	  $block_limit_sql = "WHERE parent_id='$block_id'";
    }
  }
  
  if(empty($block_id)){
    $heading = $COLLATE['languages']['selected']['AllIPBlocks'];
	$block_limit_sql = "WHERE parent_id is NULL";
  }
  else{
	$heading = str_replace("%block_name%", $parent_block_name, $COLLATE['languages']['selected']['SomeIPBlocks']);
  }
   
  $sort = (empty($_GET['sort'])) ? "" : $_GET['sort'];
  if ($sort === 'network') { 
    $sort = 'start_ip';
  }
  else {
    $sort = 'name';
  }
 
  echo "<h1>$heading</h1>";
  if(!empty($block_id)){
    echo "<p style=\"text-align: right;\"><a href=\"blocks.php?op=add&parent_block=$block_id\">";
  }
  else{
    echo "<p style=\"text-align: right;\"><a href=\"blocks.php?op=add\">";
  }
       
 echo "<img src=\"./images/add.gif\" alt=\"Add\" /> ".$COLLATE['languages']['selected']['AddaBlock']." </a></p>";
	   
  echo "<table style=\"width: 100%\">\n". // Here we actually build the HTML table
	     "<tr><th style=\"text-align: left\"><a href=\"blocks.php\">".$COLLATE['languages']['selected']['BlockName']."</a></th>".
	     "<th style=\"text-align: left\"><a href=\"blocks.php?sort=network\">".$COLLATE['languages']['selected']['StartingIP']."</a></th>".
	     "<th style=\"text-align: left\">".$COLLATE['languages']['selected']['EndIP']."</th><th></th>".
	     "</tr>\n".
	     "<tr><td colspan=\"4\"><hr class=\"head\" /></td></tr>\n";
		 
  $sql = "SELECT `id`, `name`, `start_ip`, `end_ip`, `note`,`type` FROM `blocks` $block_limit_sql ORDER BY `$sort` ASC";
  $results = $dbo -> query($sql);
  $javascript = ''; # this gets concatenated to below
  while(list($block_id,$name,$long_start_ip,$long_end_ip,$note,$block_type) = $results -> fetch(PDO::FETCH_NUM)){
    $link_target = ($block_type == 'container') ? "blocks.php?block_id=$block_id" : "subnets.php?block_id=$block_id";
	if(empty($long_start_ip)){
	  $start_ip = $COLLATE['languages']['selected']['Browse'];
	  $end_ip = '';
	}
	else{
	  $start_ip = long2ip($long_start_ip);
	  $end_ip = long2ip($long_end_ip);
	}
    
	
    echo "<tr id=\"block_".$block_id."_row_1\"><td><a href=\"$link_target\">";
	echo ($block_type == 'container') ? "<img src=\"images/container_block.png\" alt=\"\">" :  "<img src=\"images/ip_block.png\" alt=\"\">";	
	echo "</a> &nbsp; <b><span id=\"edit_name_".$block_id."\">$name</span></b></td>
		 <td><a href=\"$link_target\">$start_ip</a></td>
		 <td>$end_ip</td>
		 <td style=\"text-align: right;\">";
		 
	if($COLLATE['user']['accesslevel'] >= '4' || $COLLATE['settings']['perms'] > '4'){
	  echo "<a href=\"blocks.php?op=modify&amp;block_id=$block_id\"><img alt=\"modify block\" title=\"".
           $COLLATE['languages']['selected']['modifyblock']."\" src=\"images/modify.gif\" /></a> &nbsp; ".
		   " <a href=\"#\" onclick=\"
	         if (confirm('".$COLLATE['languages']['selected']['confirmdelete']."')) { 
			   new Element.update('block_".$block_id."_notice', ''); 
			   new Ajax.Updater('block_".$block_id."_notice', '_blocks.php?op=delete&block_id=$block_id', {onSuccess:function(){ 
			     new Effect.Parallel( [new Effect.Fade('block_".$block_id."_row_1'), 
				 new Effect.Fade('block_".$block_id."_row_2'), 
				 new Effect.Fade('block_".$block_id."_row_3'), 
				 new Effect.Fade('block_".$block_id."_row_4')]); 
               }}); 
			 };
			 return false;\">
			 <img src=\"./images/remove.gif\" alt=\"X\" /></a>";
	}
    echo "</td>
		 </tr>\n";
	echo "<tr id=\"block_".$block_id."_row_2\"><td colspan=\"3\"><span id=\"edit_note_".$block_id."\">$note</span></td><td></td></tr>\n";
	echo "<tr id=\"block_".$block_id."_row_3\"><td colspan=\"4\"><span id=\"block_".$block_id."_notice\" class=\"tip\"></span>\n";
    echo "<tr id=\"block_".$block_id."_row_4\"><td colspan=\"4\"><hr class=\"division\" /></td></tr>\n";
	
	if($COLLATE['user']['accesslevel'] >= '4' || $COLLATE['settings']['perms'] > '4'){
	  $javascript .=
		   "<script type=\"text/javascript\"><!--\n".
	       "  new Ajax.InPlaceEditorWithEmptyText('edit_name_".$block_id."', '_blocks.php?op=edit&block_id=$block_id&edit=name',
		      {
			   clickToEditText: '".$COLLATE['languages']['selected']['ClicktoEdit']."',
			   highlightcolor: '#a5ddf8', 
			   callback:
			    function(form) {
			      new Element.update('block_".$block_id."_notice', '');			  
				  return Form.serialize(form);
			    },
			   onFailure: 
			    function(transport, ipe) {
			        new Element.update('block_".$block_id."_notice', ipe.responseText);
			    }
			  }
			  );\n".
		   "  new Ajax.InPlaceEditorWithEmptyText('edit_note_".$block_id."', '_blocks.php?op=edit&block_id=$block_id&edit=note',
		      {
			   clickToEditText: '".$COLLATE['languages']['selected']['ClicktoEdit']."',
			   highlightcolor: '#a5ddf8',  
			   callback:
			    function(form) {
			      new Element.update('block_".$block_id."_notice', '');
				  return Form.serialize(form);
			    },
			   onFailure: 
			    function(transport, ipe) {
			        new Element.update('block_".$block_id."_notice', ipe.responseText);
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