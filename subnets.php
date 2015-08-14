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
    AccessControl("3", null);
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
  global $COLLATE;
  global $dbo;
  require_once('./include/header.php');
  
  if(!isset($_GET['block_id'])){
    $notice = "invalidrequest";
    header("Location: blocks.php?notice=$notice");
    exit();
  }
  
  $name = (isset($_GET['name'])) ? $_GET['name'] : '';
  $ip = (isset($_GET['ip'])) ? $_GET['ip'] : '';
  $gateway = (isset($_GET['gateway'])) ? $_GET['gateway'] : '';
  $acl_name = (isset($_GET['acl_name'])) ? $_GET['acl_name'] : 'DHCP';
  $acl_start = (isset($_GET['acl_start'])) ? $_GET['acl_start'] : '';
  $acl_end = (isset($_GET['acl_end'])) ? $_GET['acl_end'] : '';
  $note = (isset($_GET['note'])) ? $_GET['note'] : '';
  $guidance = (isset($_GET['guidance'])) ? $_GET['guidance'] : '';
  $block_id = (isset($_GET['block_id'])) ? $_GET['block_id'] : '';

  echo "<div id=\"nametip\" style=\"display: none;\" class=\"tip\">".
       $COLLATE['languages']['selected']['subnetname-tip']."<br /><br/></div>\n".
       "<div id=\"iptip\" style=\"display: none;\" class=\"tip\">".
       $COLLATE['languages']['selected']['subnetaddress-tip']."\"<br /><br /></div>\n".
       "<div id=\"guidance\" style=\"display: none;\" class=\"tip\">".
       $COLLATE['languages']['selected']['guidance-tip']."<br /><br /></div>\n".
       "<h1>".$COLLATE['languages']['selected']['AllocateaSubnet']."</h1>\n".
       "<br />\n".
       "<form action=\"subnets.php?op=submit\" method=\"post\">\n".
       "<div style=\"float: left; width: 45%; \">\n".
       "<p><b>".$COLLATE['languages']['selected']['Name'].":</b><br />\n".
       "<input type=\"text\" name=\"name\" value=\"$name\" required minlength=\"3\" maxlength=\"25\"/>\n".
       "<a href=\"#\" onclick=\"new Effect.toggle($('nametip'),'appear'); return false;\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
       "</p>\n".
       "<p><b>".$COLLATE['languages']['selected']['Subnet'].":</b><br /><input type=\"text\" name=\"ip\" value=\"$ip\" required />\n".
       "<a href=\"#\" onclick=\"new Effect.toggle($('iptip'),'appear'); return false;\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
       "</p>\n".
       "<p><b>".$COLLATE['languages']['selected']['Gateway'].":</b><br /><input type=\"text\" value=\"$gateway\" name=\"gateway\" /></p>\n".
       "<p><b>".$COLLATE['languages']['selected']['ACLName'].":</b><br /><input type=\"text\" name=\"acl_name\" value=\"$acl_name\" minlength=\"3\" maxlength=\"25\" />\n".
       "<p><b>".$COLLATE['languages']['selected']['ACLRange'].":</b><br /><input type=\"text\" name=\"acl_start\" value=\"$acl_start\" size=\"15\" />\n".
       "- <input type=\"text\" name=\"acl_end\" value=\"$acl_end\" size=\"15\" />\n".
       "</p>\n".
       "<p><b>".$COLLATE['languages']['selected']['Note'].":</b> ".$COLLATE['languages']['selected']['Optional']."<br />\n".
       "<input type=\"text\" name=\"note\" value=\"$note\" /></p>\n".
       "<p><b>".$COLLATE['languages']['selected']['IPGuidance'].":</b> ".$COLLATE['languages']['selected']['Optional']. 
       "<a href=\"#\" onclick=\"new Effect.toggle($('guidance'),'appear'); return false;\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
       "<br /><textarea name=\"guidance\" rows=\"10\" cols=\"33\" maxlength=\"255\">$guidance</textarea></p>\n".
       "<input type=\"hidden\" name=\"block_id\" value=\"$block_id\" />\n".
       "<input type=\"submit\" value=\" ".$COLLATE['languages']['selected']['Go']." \" />\n".
       "</div>\n".
       "</form>";
    
    echo "<div style=\"float: left; width: 45%; padding-left: 10px; border-left: 1px solid #000;\">\n";
    
    // Here we'll figure out what available space is left in the IP Block and list it out for the user
    $ipspace = array();
    
    $sql = "SELECT name, start_ip, end_ip FROM blocks WHERE id = '$block_id'";
    $results = $dbo -> query($sql);
    list($block_name,$block_long_start_ip,$block_long_end_ip) = $results -> fetch(PDO::FETCH_NUM);
    if(!empty($block_long_start_ip)){

      array_push($ipspace, $block_long_start_ip);
      
      // We need to consider that some subnets in the block are not in the IP range the block specifies, so we compare ranges as well as block_id.
      $sql = "SELECT start_ip, end_ip FROM subnets WHERE CAST((start_ip & 0xFFFFFFFF) AS UNSIGNED) >= CAST(('$block_long_start_ip' & 0xFFFFFFFF) AS UNSIGNED) AND CAST((end_ip & 0xFFFFFFFF) AS UNSIGNED) <= CAST(('$block_long_end_ip' & 0xFFFFFFFF) AS UNSIGNED) ORDER BY start_ip ASC";
      $subnet_rows = $dbo -> query($sql);
      
      while(list($subnet_long_start_ip,$subnet_long_end_ip) = $subnet_rows -> fetch(PDO::FETCH_NUM)){
        array_push($ipspace, $subnet_long_start_ip, $subnet_long_end_ip);
      }
      array_push($ipspace, $block_long_end_ip);
      $ipspace = array_reverse($ipspace);
      
      $ipspace_count = count($ipspace);
      
      $availableipspaceinblock = str_replace("%block_name%", "$block_name", $COLLATE['languages']['selected']['AvailableIPinBlock']);
      
      echo "<div id=\"blockspace\">\n".
           "<p><a href=\"#\" onclick=\"new Effect.toggle('blockspace', 'blind', { delay: 0.1 }); ".
           "   new Effect.toggle('spacesearch', 'blind', { delay: 0.1 }); return false;\">".
           $COLLATE['languages']['selected']['Showsearchinstead']."</a></p>\n".
           "<h3>$availableipspaceinblock:</h3><br />\n".
           "<table style=\"width: 100%\"><tr><th>".$COLLATE['languages']['selected']['StartingIP'].
           "</th><th>".$COLLATE['languages']['selected']['EndIP']."</th></tr>";
      
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
      echo "</table></div>\n".
           "<div id=\"spacesearch\" style=\"display: none;\">\n".
           "<p><a href=\"#\" onclick=\"new Effect.toggle('blockspace', 'blind', { delay: 0.1 }); \n".
           "   new Effect.toggle('spacesearch', 'blind', { delay: 0.1 }); return false;\">".$COLLATE['languages']['selected']['showblockspace']."</a></p>\n";
    }
    else{
      echo "<div id=\"spacesearch\">";
      $searchonlyparam = '&amp;searchonly=true';
    }

    echo "<h3>".$COLLATE['languages']['selected']['SearchIPSpace']."</h3><br />\n".
         "<p><b>".$COLLATE['languages']['selected']['Subnet'].":</b> <input id=\"subnetsearch\" type=\"text\"><br />".
         "<button onclick=\"new Ajax.Updater('spacesearch', '_subnets.php?op=search$searchonlyparam&amp;search=' + $('subnetsearch').value); return false;\"> ".
         $COLLATE['languages']['selected']['Go']." </button></p></div>";

    echo "</div><p style=\"clear: left;\">\n";
    require_once("include/footer.php");
    exit();
} // Ends add_subnet function

function submit_subnet(){
  global $dbo;
  include 'include/validation_functions.php';
  
  $block_id = (isset($_POST['block_id']) && is_numeric($_POST['block_id'])) ? $_POST['block_id'] : '';
  $name = (isset($_POST['name'])) ? $_POST['name'] : '';
  $ip = (isset($_POST['ip'])) ? $_POST['ip'] : '';
  $gateway = (isset($_POST['gateway'])) ? $_POST['gateway'] : '';
  $acl_name = (isset($_POST['acl_name'])) ? $_POST['acl_name'] : '';
  $acl_start = (isset($_POST['acl_start'])) ? $_POST['acl_start'] : '';
  $acl_end = (isset($_POST['acl_end'])) ? $_POST['acl_end'] : '';
  $note = (isset($_POST['note'])) ? $_POST['note'] : '';
  $guidance = (isset($_POST['guidance'])) ? $_POST['guidance'] : '';
  
  if(empty($block_id)){
    $notice = 'invalidrequest';
    header("Location: blocks.php?notice=$notice");
    exit();
  }
  
  if(empty($name) || empty($ip)){
    $notice = "blankfield-notice";
    $guidance = urlencode($guidance);
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
    exit();
  }
  
  $result = validate_text($name,'subnetname');
  if($result['0'] === false){
    $notice = $result['error'];
    $guidance = urlencode($guidance);
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
    exit();
  }
  else{
    $name = $result['1'];
  }
  
  $result = validate_network($ip);
  if($result['0'] === false){
    $notice = $result['error'];
    $guidance = urlencode($guidance);
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
    exit();
  }
  else{
    $start_ip = $result['start_ip'];
    $end_ip = $result['end_ip'];
    $mask = $result['mask'];
    $long_start_ip = $result['long_start_ip'];
    $long_end_ip = $result['long_end_ip'];
    $long_mask = $result['long_mask'];
  } 
  
  $dbo -> beginTransaction();
  
  $username = (!isset($COLLATE['user']['username'])) ? 'system' : $COLLATE['user']['username'];
  $sql = "INSERT INTO subnets (name, start_ip, end_ip, mask, note, block_id, modified_by, modified_at, guidance) 
        VALUES('$name', '$long_start_ip', '$long_end_ip', '$long_mask', '$note', '$block_id', '$username', now(), '$guidance')";
  
    
  $dbo -> query($sql);
  $subnet_id = $dbo -> lastInsertId();
  
  if(!empty($acl_start) && !empty($acl_end)){
    $result = validate_ip_range($acl_start,$acl_end,'acl');
    if($result['0'] === false){
      $dbo -> rollBack();
      $notice = $result['error'];
      $guidance = urlencode($guidance);
      header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
      exit();
    }
    else{
      $long_acl_start = $result['long_start_ip'];
      $long_acl_end = $result['long_end_ip'];
    }
    
    // Add an ACL for the acl range so users don't assign a static IP inside a acl scope.
    $sql = "INSERT INTO acl (name, start_ip, end_ip, subnet_id) VALUES('$acl_name', '$long_acl_start', '$long_acl_end', '$subnet_id')";
    $dbo -> query($sql);
  }
   
  // Add static IP for the Default Gateway  
  if(!empty($gateway)){
    $long_gateway = ip2decimal($gateway);
    $subnet_test = $long_gateway & $long_mask;
    if($subnet_test !== $long_start_ip){
      $dbo -> rollBack();
      $notice = 'invalidip';
      $guidance = urlencode($guidance);
      header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
      exit();
    }
    
    $validate_gateway = validate_static_ip($gateway);
    if($validate_gateway['0'] === false){
      $dbo -> rollBack();
      $notice = $validate_gateway['error'];
      $guidance = urlencode($guidance);
      header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
      exit();
    }
    
    $sql = "INSERT INTO statics (ip, name, contact, note, subnet_id, modified_by, modified_at) 
           VALUES('$long_gateway', 'Gateway', 'Network Admin', 'Default Gateway', '$subnet_id', '$username', now())";
    $dbo -> query($sql);
  }
  
  $dbo -> commit();
  
  $cidr=subnet2cidr($long_start_ip,$long_mask);
  $accesslevel = "3";
  $message = "Subnet $name ($cidr) has been created";
  AccessControl($accesslevel, $message); // No need to generate logs when nothing is really happening. This 
                                         // goes down here where we know stuff has actually been written. Access
                                         // Control actually happened before submit_subnet() was called.
                                         
 
  $notice = "subnetadded-notice";
  header("Location: subnets.php?block_id=$block_id&notice=$notice");
  exit();
} // ends submit_subnet function

function list_subnets(){
  global $COLLATE;
  global $dbo;
  
  $block_id = (isset($_GET['block_id']) && is_numeric($_GET['block_id'])) ? $_GET['block_id'] : '';
  if(!isset($_GET['block_id']) || empty($_GET['block_id'])){
    $notice = "invalidrequest";
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
  $result = $dbo -> query($sql);
  if($result -> rowCount() !== 1){
    $notice = "invalidrequest";
    header("Location: blocks.php?notice=$notice");
    exit();
  }
  require_once('./include/header.php');
  $block_name = $result -> fetchColumn();
  
  $blocknamesubnets = str_replace("%block_name%", $block_name, $COLLATE['languages']['selected']['BlockSubnets']);
  echo "<h1>$blocknamesubnets</h1>\n".
       "<div style=\"float: left; width: 70%;\">";

  $sql = "SELECT `id`, `name`, `start_ip`, `end_ip`, `mask`, `note` FROM `subnets` 
      WHERE `block_id` = '$block_id' ORDER BY `$sort` ASC";
  $hiddenformvars="<input type=\"hidden\" name=\"block_id\" value=\"$block_id\" />".
                  "<input type=\"hidden\" name=\"sort\" value=\"$sort\" />";

  # page selector here
  $updatedsql = pageselector($sql,$hiddenformvars);
  $row = $dbo -> query($updatedsql);
  $rows = $row -> rowCount();
  
  echo "</div>\n".
       "<a href=\"subnets.php?op=add&amp;block_id=$block_id\">\n".
	   "<div style=\"float: left; width: 25%; text-align:right; padding:5px;\">".
       "<img src=\"./images/add.gif\" alt=\"\" /> ".
       $COLLATE['languages']['selected']['AllocateaSubnet']." </a></div><p style=\"clear: left; display: done;\">\n";
    
   
  echo "<table style=\"width: 100%\">\n". 
         "<tr><th style=\"text-align: left\"><a href=\"subnets.php?block_id=$block_id&amp;sort=name\">".
         $COLLATE['languages']['selected']['SubnetName']."</a></th>".
         "<th style=\"text-align: left\"><a href=\"subnets.php?block_id=$block_id&amp;sort=network\">".
         $COLLATE['languages']['selected']['NetworkAddress']."</a></th>".
         "<th style=\"text-align: left\">".$COLLATE['languages']['selected']['SubnetMask']."</th>".
         "<th style=\"text-align: left\">".$COLLATE['languages']['selected']['StaticsUsed']."</th><th></th></tr>\n".
         "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
         
  $javascript = ''; # This gets concatenated to below.
  while(list($subnet_id,$name,$long_start_ip,$long_end_ip,$long_mask,$note) = $row -> fetch(PDO::FETCH_NUM)){
    $start_ip = long2ip($long_start_ip);
    $mask = long2ip($long_mask);
    
    $subnet_size = $long_end_ip - $long_start_ip;
    $in_color=true;
    $percent_subnet_used = get_formatted_subnet_util($subnet_id,$subnet_size,$in_color);
    
    echo "<tr id=\"subnet_".$subnet_id."_row_1\">
         <td><a href=\"statics.php?subnet_id=$subnet_id\"><img src=\"images/subnet.png\" alt=\"\"></a> &nbsp; 
		 <b><span id=\"edit_name_".$subnet_id."\">$name</span></b></td><td><a href=\"statics.php?subnet_id=$subnet_id\">$start_ip</a></td>
         <td>$mask</td>$percent_subnet_used
         <td style=\"text-align: right;\">";
         
    if($COLLATE['user']['accesslevel'] >= '3' || $COLLATE['settings']['perms'] > '3'){
      echo "<a href=\"subnets.php?op=modify&amp;subnet_id=$subnet_id\"><img alt=\"modify subnet\" title=\"".
           $COLLATE['languages']['selected']['modifysubnet']."\" src=\"images/modify.gif\" /></a> &nbsp; ".
           "<a href=\"#\" onclick=\"
           if (confirm('".$COLLATE['languages']['selected']['confirmdelete']."')) { 
             new Element.update('subnet_".$subnet_id."_notice', ''); 
             new Ajax.Updater('subnet_".$subnet_id."_notice', '_subnets.php?op=delete&subnet_id=$subnet_id', {onSuccess:function(){ 
               new Effect.Parallel( [
                 new Effect.Fade('subnet_".$subnet_id."_row_1'), 
                 new Effect.Fade('subnet_".$subnet_id."_row_2'), 
                 new Effect.Fade('subnet_".$subnet_id."_row_3'), 
                 new Effect.Fade('subnet_".$subnet_id."_row_4')
               ]); 
             }}); 
           };
           return false;\"><img src=\"./images/remove.gif\" alt=\"X\" title=\"".
           $COLLATE['languages']['selected']['deletesubnet']."\" /></a>";
    }
    echo "</td>
         </tr>\n";
         
    echo "<tr id=\"subnet_".$subnet_id."_row_2\"><td colspan=\"4\"><span id=\"edit_note_".$subnet_id."\">$note</span></td><td></td></tr>\n";
    echo "<tr id=\"subnet_".$subnet_id."_row_3\"><td colspan=\"5\"><span id=\"subnet_".$subnet_id."_notice\" class=\"tip\"></span></td></tr>\n";
    echo "<tr id=\"subnet_".$subnet_id."_row_4\"><td colspan=\"5\"><hr class=\"division\" /></td></tr>\n";
    
    if($COLLATE['user']['accesslevel'] >= '3' || $COLLATE['settings']['perms'] > '3'){
           
      $javascript .=
           "<script type=\"text/javascript\"><!--\n".
           "  new Ajax.InPlaceEditorWithEmptyText('edit_name_".$subnet_id."', '_subnets.php?op=edit&subnet_id=$subnet_id&edit=name',
              {
               clickToEditText: '".$COLLATE['languages']['selected']['ClicktoEdit']."',
               highlightcolor: '#a5ddf8', 
               callback:
                function(form) {
                  new Element.update('subnet_".$subnet_id."_notice', '');
                  return Form.serialize(form);
                },
               onFailure: 
                function(transport, ipe) {
                  new Element.update('subnet_".$subnet_id."_notice', ipe.responseText.stripTags());
                }
              }
              );\n".
           "  new Ajax.InPlaceEditorWithEmptyText('edit_note_".$subnet_id."', '_subnets.php?op=edit&subnet_id=$subnet_id&edit=note',
              {
               clickToEditText: '".$COLLATE['languages']['selected']['ClicktoEdit']."',
               highlightcolor: '#a5ddf8',  
               callback:
                function(form) {
                  new Element.update('subnet_".$subnet_id."_notice', '');
                  return Form.serialize(form);
                },
               onFailure: 
                function(transport, ipe) {
                  new Element.update('subnet_".$subnet_id."_notice', ipe.responseText.stripTags());
                }
              }
              );\n".
           "--></script>\n";
    }
  }
  
  echo "</table>";
  
  pageselector($sql,$hiddenformvars);
  
  echo $javascript;
  require_once("include/footer.php");
  exit();
  
} // Ends list_subnets function

function modify_subnet (){
  global $COLLATE;
  global $dbo;
  
  $subnet_id = (isset($_GET['subnet_id']) && is_numeric($_GET['subnet_id'])) ? $_GET['subnet_id'] : '';
  if(empty($subnet_id)){
    $notice = "invalidrequest";
    header("Location: blocks.php?notice=$notice");
    exit();
  }
  
  $sql = "SELECT id, name, start_ip, mask, block_id, stalescan_enabled FROM subnets WHERE id='$subnet_id'";
  $query_result = $dbo -> query($sql);
  if($query_result -> rowCount() !== 1){
    $notice = "invalidrequest";
    header("Location: blocks.php?notice=$notice");
    exit();
  }
  require_once('./include/header.php'); 
  
  list($subnet_id,$subnet_name,$long_start_ip,$long_mask,$current_block_id,$stalescan_enabled) = $query_result -> fetch(PDO::FETCH_NUM); 
  $start_ip = long2ip($long_start_ip);
  $mask = long2ip($long_mask);
  
  $modifythesubnet = str_replace("%subnet_name%", $subnet_name, $COLLATE['languages']['selected']['ModifySubnet']);
  $modifythesubnet = str_replace("%start_ip%", $start_ip, $modifythesubnet);
  $modifythesubnet = str_replace("%mask%", $mask, $modifythesubnet);
  echo "<h1>$modifythesubnet</h1><br />\n";
  
  echo "<h3>".$COLLATE['languages']['selected']['StaleScan'];
    if($stalescan_enabled == false){
      echo " <a href=\"_subnets.php?op=toggle_stale-scan&amp;subnet_id=$subnet_id&amp;toggle=on\">".
           "<img src=\"./images/skipping.png\" alt=\"\" title=\"".$COLLATE['languages']['selected']['enablestalescan']."\" /></a>";
    }
    else{
      echo " <a href=\"_subnets.php?op=toggle_stale-scan&amp;subnet_id=$subnet_id&amp;toggle=off\">".
           "<img src=\"./images/scanning.png\" alt=\"\" title=\"".$COLLATE['languages']['selected']['disablestalescan']."\" /></a>";
    }
    echo "</h3><br /><br />";
    
  $movesubnet = str_replace("%subnet_name%", $subnet_name, $COLLATE['languages']['selected']['Movesubnet']);
  echo "<h3>$movesubnet</h3><hr />\n".
       "<form action=\"subnets.php?op=submitmove\" method=\"post\">\n".
       "<input type=\"hidden\" name=\"subnet_id\" value=\"$subnet_id\" />".
       "<p>".$COLLATE['languages']['selected']['selectblock']."</p>".
       "<p><select name=\"block_id\">";

  $sql = "SELECT id, name, parent_id FROM blocks WHERE type='ipv4'";
  $result = $dbo -> query($sql);
  while(list($select_block_id,$select_block_name,$select_block_parent) = $result -> fetch(PDO::FETCH_NUM)){
    $block_paths[$select_block_id]="$select_block_name";
    while($select_block_parent !== null){ #this has the potential to be really slow and awful...
	  $sql = "SELECT name, parent_id FROM blocks WHERE id='$select_block_parent'";
      $recursive_result = $dbo -> query($sql);
      list($recursive_parent_name,$recursive_parent_parent) = $recursive_result -> fetch(PDO::FETCH_NUM);
      $block_paths[$select_block_id] = "$recursive_parent_name/".$block_paths[$select_block_id];
      $select_block_parent = $recursive_parent_parent;
    }
    $block_paths[$select_block_id]='[root]/'.$block_paths[$select_block_id];
  }
  natcasesort($block_paths);
  foreach ($block_paths as $select_id => $select_text){
    if($current_block_id == $select_id){
      echo "<option selected=\"selected\" value=\"$select_id\">$select_text</option>\n";
    }
    else{
      echo "<option value=\"$select_id\">$select_text</option>\n";
    }
  }
       
  echo "</select></p>\n".
       "<p><input type=\"submit\" value=\" ".$COLLATE['languages']['selected']['Go']." \" /></p></form><br /><br />";

  $resizesubnet = str_replace("%subnet_name%", $subnet_name, $COLLATE['languages']['selected']['Resizesubnet']);
  echo "<h3>$resizesubnet</h3><hr />";
  echo "<form action=\"subnets.php?op=resize\" method=\"post\">\n".
       "<input type=\"hidden\" name=\"subnet_id\" value=\"$subnet_id\" />\n".
       "<p>".$COLLATE['languages']['selected']['subnetaddress-tip']."</p>\n".
       "<p><input type=\"text\" name=\"new_subnet\" /></p>\n".
       "<p>".$COLLATE['languages']['selected']['furtherpromptsahead']."</p>\n".
       "<p><input type=\"submit\" value=\" ".$COLLATE['languages']['selected']['Go']." \" /></p></form><br />";
  
} // Ends move_subnet function

function submit_move_subnet (){
  global $dbo;
  
  $subnet_id = (isset($_POST['subnet_id']) && preg_match("/[0-9]*/", $_POST['subnet_id'])) ? $_POST['subnet_id'] : '';
  $block_id = (isset($_POST['block_id']) && preg_match("/[0-9]*/", $_POST['block_id'])) ? $_POST['block_id'] : '';
  
  if(empty($subnet_id) || empty($block_id)){
    $notice = "invalidrequest";
    header("Location: blocks.php?notice=$notice");
    exit();
  }
    
  $sql = "SELECT name,start_ip, mask, block_id FROM subnets WHERE id='$subnet_id'";
  $result = $dbo -> query($sql);
  if($result -> rowCount() != '1') {
    $notice = "invalidrequest";
    header("Location: blocks.php?notice=$notice");
    exit();
  }
  list($subnet_name,$ip,$mask,$old_block_id)=$result -> fetch(PDO::FETCH_NUM);
  $sql = "SELECT name FROM blocks WHERE id='$old_block_id'";
  $result = $dbo -> query($sql);
  $old_block_name = $result -> fetchColumn();
  
  $sql = "SELECT name FROM blocks WHERE id='$block_id'";
  $result = $dbo -> query($sql);
  $new_block_name = $result -> fetchColumn();
  $cidr=subnet2cidr($ip,$mask);
  AccessControl("3", "Subnet $subnet_name ($cidr) moved from $old_block_name block to $new_block_name block");
  
  $sql = "UPDATE subnets set block_id='$block_id' WHERE id='$subnet_id'";
  $result = $dbo -> query($sql);
  
  $notice = "subnetmoved-notice";
  header("Location: subnets.php?block_id=$old_block_id&notice=$notice");
  exit();
} // Ends submit_move_subnet function

function resize_subnet() {
  global $COLLATE;
  global $dbo;
  include 'include/validation_functions.php';

  $subnet_id = (isset($_POST['subnet_id']) && is_numeric($_POST['subnet_id'])) ? $_POST['subnet_id'] : '';
  $new_subnet = (isset($_POST['new_subnet'])) ? $_POST['new_subnet'] : '';
  $confirm = (isset($_POST['confirm'])) ? true : false;

  $sql = "SELECT name, start_ip, end_ip, mask, block_id FROM subnets WHERE id='$subnet_id'";
  $result = $dbo -> query($sql);
  if ( $result -> rowCount() != '1' ){
    $notice = "invalidrequest";
    header("Location: blocks.php?notice=$notice");
    exit();
  }

  list($original_subnet_name,$original_long_start_ip,$original_long_end_ip,$original_long_mask,$original_block_id) = $result -> fetch(PDO::FETCH_NUM);
  
  $original_cidr=subnet2cidr($original_long_start_ip,$original_long_mask);

  $return = validate_network($new_subnet,'subnet',null,true); #last parameter is saying it's ok if the subnet overlaps another
  if($return['0'] === false){
    $notice = "invalidrequest";
    header("Location: blocks.php?notice=$notice");
    exit();
  }
   
  $new_start_ip = $return['start_ip'];
  $new_long_start_ip = $return['long_start_ip'];
  $new_end_ip = $return['end_ip'];
  $new_long_end_ip = $return['long_end_ip'];
  $new_long_mask = $return['long_mask'];

  $new_cidr=subnet2cidr($new_long_start_ip, $new_long_mask);

  if($confirm === false){
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
	$test = $new_long_start_ip & $original_long_mask;
    if ($test != $original_long_start_ip) {
      $notice = "invalidshrink-notice";
      header("Location: subnets.php?op=modify&subnet_id=$subnet_id&notice=$notice");
      exit();
    }

    #  * list static IP addresses that would be lost
    if($confirm === false) {
      $sql_action = "SELECT id, ip, name, contact, note, failed_scans FROM statics WHERE ";
	  $sql_sort = ' ORDER BY `ip` ASC';
    }
    else {
      $sql_action = "DELETE FROM statics WHERE ";
    }
	
	# in old subnet, but not in new one
	$sql_selection = " CAST(ip & 0xFFFFFFFF AS UNSIGNED) & CAST('$original_long_mask' & 0xFFFFFFFF AS UNSIGNED) = 
	                  CAST('$original_long_start_ip' & 0xFFFFFFFF AS UNSIGNED)
                      AND CAST(ip & 0xFFFFFFFF AS UNSIGNED) & CAST('$new_long_mask' & 0xFFFFFFFF AS UNSIGNED) != 
                      CAST('$new_long_start_ip' & 0xFFFFFFFF AS UNSIGNED) ";
					  
	$sql = $sql_action.$sql_selection;
	$sql = (isset($sql_sort)) ? $sql.$sql_sort : $sql;	
    
    $result = $dbo -> query($sql);
    if($confirm === false){
      $staticstobedeleted = str_replace("%original_subnet_name%", $original_subnet_name, $COLLATE['languages']['selected']['staticstodelete']);
      echo "<h1>$staticstobedeleted:</h1><br />\n";

      if($result -> rowCount() != '0'){
        echo "<table style=\"width: 100%\"><tr><th>".$COLLATE['languages']['selected']['IPAddress'].
             "</th><th>".$COLLATE['languages']['selected']['Name']."</th><th>".
             $COLLATE['languages']['selected']['Contact']."</th><th>".$COLLATE['languages']['selected']['FailedScans']."</th></tr>".
             "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
        while(list($static_id,$ip,$name,$contact,$note,$failed_scans) = $result -> fetch(PDO::FETCH_NUM)){
          $ip = long2ip($ip);
          echo "<tr><td>$ip</td><td>$name</td><td>$contact</td><td>$failed_scans</td><td></td></tr>\n";
          echo "<tr><td colspan=\"5\">$note</td></tr>\n";
          echo "<tr><td colspan=\"5\"><hr class=\"division\" /></td></tr>\n";
        }
        echo "</table><br /><br />";
      }
      else{
        echo "<p>".$COLLATE['languages']['selected']['nostaticsdeleted']."</p><br /><br />";
      }
	}

    #  * show how ACLs would be adjusted
    # Find acls matching original subnet_id and see if start and end fall within new subnet
    $sql = "SELECT id, name, start_ip, end_ip FROM acl WHERE subnet_id='$subnet_id' AND (
           CAST(start_ip & 0xFFFFFFFF AS UNSIGNED) & CAST('$new_long_mask' & 0xFFFFFFFF AS UNSIGNED) != 
           CAST('$new_long_start_ip' & 0xFFFFFFFF AS UNSIGNED)
           OR CAST(end_ip & 0xFFFFFFFF AS UNSIGNED) & CAST('$new_long_mask' & 0xFFFFFFFF AS UNSIGNED) != 
           CAST('$new_long_start_ip' & 0xFFFFFFFF AS UNSIGNED))";
    $result = $dbo -> query($sql);
    if($confirm === false){
      $aclstobechanged = str_replace("%original_subnet_name%", $original_subnet_name, $COLLATE['languages']['selected']['aclstobechanged']);
      echo "<h1>$aclstobechanged:</h1><br />\n";

      if($result -> rowCount() == '0'){
        echo "<p>".$COLLATE['languages']['selected']['noaclschanged']."</p><br /><br />"; 
      }
      else{
        echo "<table style=\"width: 100%\">\n".
             "<tr><th>".$COLLATE['languages']['selected']['Name']."
             </th><th>".$COLLATE['languages']['selected']['StartingIP']."</th><th>".
             $COLLATE['languages']['selected']['EndIP']."</th><th>".$COLLATE['languages']['selected']['Modification']."</th></tr>\n".
             "<tr><td colspan=\"4\"><hr class=\"head\" /></td></tr>";
      }
	}
        
    while(list($acl_id,$acl_name,$acl_long_start_ip,$acl_long_end_ip) = $result -> fetch(PDO::FETCH_NUM)){
      $note = ""; # this might not get set below.
      $sql = "";
    
      if(($acl_long_start_ip & $new_long_mask) == $new_long_start_ip) {
        $new_acl_start_ip = long2ip($acl_long_start_ip);
      }
      else {
        $new_acl_start_ip = $new_start_ip;
        $note = "<b>".$COLLATE['languages']['selected']['StartingIPmodified']."</b>";
        $sql = "UPDATE acl SET start_ip='$new_long_start_ip' WHERE id='$acl_id'";
      }
    
      if(($acl_long_end_ip & $new_long_mask) == $new_long_start_ip) {
        $new_acl_end_ip = long2ip($acl_long_end_ip);
      }
      else {
        $new_acl_end_ip = $new_end_ip;
        $note = "<b>".$COLLATE['languages']['selected']['EndIPmodified']."</b>";
        $sql = "UPDATE acl SET end_ip='$new_long_end_ip' WHERE id='$acl_id'";
      }
    
      if (($new_acl_start_ip == $new_start_ip) && ($new_acl_end_ip == $new_end_ip)){
	    # we wouldn't generally have an ACL reserve a whole subnet. We'll just ditch the ACL
		# and let the user make something new 
        $new_acl_start_ip = long2ip($acl_long_start_ip);
        $new_acl_end_ip = long2ip($acl_long_end_ip);
        $note = "<b>".$COLLATE['languages']['selected']['ToBeDeleted']."</b>";
        $sql = "DELETE FROM acl WHERE id='$acl_id'";
      }
      
      if ($confirm === false) {
        echo "<tr><td>$acl_name</td><td>$new_acl_start_ip</td><td>$new_acl_end_ip</td><td>$note</td></tr>\n";
      }
      elseif(!empty($sql)) {
        $dbo -> query($sql);
      }
    }
    if($confirm === false){
	  echo "</table>\n";
	}
  }
  else{
    # if larger:
    if(($original_long_start_ip & $new_long_mask) != $new_long_start_ip) {
      $notice = "invalidgrow-notice";
      header("Location: subnets.php?op=modify&subnet_id=$subnet_id&notice=$notice");
      exit();
    }
    #  * list all subnets that new network overlaps
    $sql = "SELECT `id`, `name`, `start_ip`, `end_ip`, `mask`, `note` FROM `subnets` WHERE
            CAST(start_ip & 0xFFFFFFFF AS UNSIGNED) & CAST('$new_long_mask' & 0xFFFFFFFF AS UNSIGNED) = 
            CAST('$new_long_start_ip' & 0xFFFFFFFF AS UNSIGNED) ORDER BY `start_ip` ASC";
    $results = $dbo -> query($sql);
    
    $subnetstomerge = str_replace("%original_subnet_name%", $original_subnet_name, $COLLATE['languages']['selected']['subnetstomerge']);
    if($confirm === false){
      echo "<h1>$subnetstomerge:</h1><br />\n";
    }
    
    if($results -> rowCount() < '1' && $confirm === false){
      echo "<p>".$COLLATE['languages']['selected']['nosubnetsoverlap']."</p>";
    }
    else{
      if($confirm === false){
      echo "<table style=\"width: 100%\">".
           "<tr><th style=\"text-align: left\">".$COLLATE['languages']['selected']['SubnetName']."</th>".
           "<th style=\"text-align: left\">".$COLLATE['languages']['selected']['NetworkAddress']."</th>".
           "<th style=\"text-align: left\">".$COLLATE['languages']['selected']['SubnetMask']."</th>".
           "<tr><td colspan=\"4\"><hr class=\"head\" /></td></tr>\n";
      }           
       
      while(list($affected_subnet_id,$name,$long_start_ip,$long_end_ip,$long_mask,$note) = $results -> fetch(PDO::FETCH_NUM)){
        if($confirm === false){
          $start_ip = long2ip($long_start_ip);
          $mask = long2ip($long_mask);
          echo "<tr><td><b>$name</b></td><td>$start_ip</td><td>$mask</td></tr>\n";
          echo "<tr><td colspan=\"4\">$note</td></tr>\n";
          echo "<tr><td colspan=\"5\"><hr class=\"division\" /></td></tr>\n";
        }
        else {
          $sql = "UPDATE acl SET subnet_id='$subnet_id' WHERE subnet_id='$affected_subnet_id'";
          $result = $dbo -> query($sql);
        }
      }
      if($confirm === false){
	    echo "</table>";
      }
      else{
        $sql = "DELETE FROM `subnets` WHERE CAST(start_ip & 0xFFFFFFFF AS UNSIGNED) & CAST('$new_long_mask' & 0xFFFFFFFF AS UNSIGNED) = 
                CAST('$new_long_start_ip' & 0xFFFFFFFF AS UNSIGNED)
                AND id != '$subnet_id'";
        $result = $dbo -> query($sql);
      
        $sql = "UPDATE statics SET subnet_id='$subnet_id' WHERE 
		       CAST(ip & 0xFFFFFFFF AS UNSIGNED) & CAST('$new_long_mask' & 0xFFFFFFFF AS UNSIGNED) = 
			   CAST('$new_long_start_ip' & 0xFFFFFFFF AS UNSIGNED)";
        $result = $dbo -> query($sql);
      }
    }
  }
  
  if ($confirm === false) {
    echo "<br /><br /><h3>".$COLLATE['languages']['selected']['confirmproceed']."</h3><hr /><br />\n".
         "<form action=\"subnets.php?op=resize\" method=\"post\">\n".
         "<input type=\"hidden\" name=\"subnet_id\" value=\"$subnet_id\" />".
         "<input type=\"hidden\" name=\"confirm\" value=\"true\" />".
         "<input type=\"hidden\" name=\"new_subnet\" value=\"$new_subnet\" />".
         "<p><input type=\"submit\" value=\" ".$COLLATE['languages']['selected']['Go'].
         " \" /> | <a href=\"subnets.php?block_id=$original_block_id\">".$COLLATE['languages']['selected']['altcancel']."</a></p>".
         "</form>";
  }
  else {
    $sql = "UPDATE subnets set start_ip='$new_long_start_ip', end_ip='$new_long_end_ip', mask='$new_long_mask' WHERE id='$subnet_id'";
    $result = $dbo -> query($sql);
    $notice = "resized-notice";
    header("Location: subnets.php?block_id=$original_block_id&notice=$notice");
    exit();
  }
} // Ends resize_subnet()
?>