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
  global $COLLATE;
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
       "<input type=\"text\" name=\"name\" value=\"$name\" />\n".
       "<a href=\"#\" onclick=\"new Effect.toggle($('nametip'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
       "</p>\n".
       "<p><b>".$COLLATE['languages']['selected']['Subnet'].":</b><br /><input type=\"text\" name=\"ip\" value=\"$ip\"/>\n".
       "<a href=\"#\" onclick=\"new Effect.toggle($('iptip'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
       "</p>\n".
       "<p><b>".$COLLATE['languages']['selected']['Gateway'].":</b><br /><input type=\"text\" value=\"$gateway\" name=\"gateway\" /></p>\n".
       "<p><b>".$COLLATE['languages']['selected']['ACLName'].":</b><br /><input type=\"text\" name=\"acl_name\" value=\"$acl_name\" />\n".
       "<p><b>".$COLLATE['languages']['selected']['ACLRange'].":</b><br /><input type=\"text\" name=\"acl_start\" value=\"$acl_start\" size=\"15\" />\n".
       "- <input type=\"text\" name=\"acl_end\" value=\"$acl_end\" size=\"15\" />\n".
       "</p>\n".
       "<p><b>".$COLLATE['languages']['selected']['Note'].":</b> ".$COLLATE['languages']['selected']['Optional']."<br />\n".
       "<input type=\"text\" name=\"note\" value=\"$note\" /></p>\n".
       "<p><b>".$COLLATE['languages']['selected']['IPGuidance'].":</b> ".$COLLATE['languages']['selected']['Optional']. 
       "<a href=\"#\" onclick=\"new Effect.toggle($('guidance'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a>\n".
       "<br /><textarea name=\"guidance\" rows=\"10\" cols=\"33\">$guidance</textarea></p>\n".
       "<input type=\"hidden\" name=\"block_id\" value=\"$block_id\" />\n".
       "<input type=\"submit\" value=\" ".$COLLATE['languages']['selected']['Go']." \" /></p>\n".
       "</div>\n".
       "</form>";
    
    
    // Here we'll figure out what available space is left in the IP Block and list it out for the user
    $ipspace = array();
    
    $sql = "SELECT name, start_ip, end_ip FROM blocks WHERE id = '$block_id'";
    $results = mysql_query($sql);
    list($block_name,$block_long_start_ip,$block_long_end_ip) = mysql_fetch_row($results);

    array_push($ipspace, $block_long_start_ip);
    
    // We need to consider that some subnets in the block are not in the IP range the block specifies, so we compare ranges as well as block_id.
    $sql = "SELECT start_ip, end_ip FROM subnets WHERE CAST((start_ip & 0xFFFFFFFF) AS UNSIGNED) >= CAST(('$block_long_start_ip' & 0xFFFFFFFF) AS UNSIGNED) AND CAST((end_ip & 0xFFFFFFFF) AS UNSIGNED) <= CAST(('$block_long_end_ip' & 0xFFFFFFFF) AS UNSIGNED) ORDER BY start_ip ASC";
    $subnet_rows = mysql_query($sql);
    
    while(list($subnet_long_start_ip,$subnet_long_end_ip) = mysql_fetch_row($subnet_rows)){
      array_push($ipspace, $subnet_long_start_ip, $subnet_long_end_ip);
    }
    array_push($ipspace, $block_long_end_ip);
    $ipspace = array_reverse($ipspace);
    
    $ipspace_count = count($ipspace);
    
    $availableipspaceinblock = str_replace("%block_name%", "$block_name", $COLLATE['languages']['selected']['AvailableIPinBlock']);
    
    echo "<div style=\"float: left; width: 45%; padding-left: 10px; border-left: 1px solid #000;\">\n".
         "<div id=\"blockspace\">\n".
         "<p><a href=\"#\" onclick=\"new Effect.toggle('blockspace', 'blind', { delay: 0.1 }); ".
         "   new Effect.toggle('spacesearch', 'blind', { delay: 0.1 })\">".
         $COLLATE['languages']['selected']['Showsearchinstead']."</a></p>\n".
         "<h3>$availableipspaceinblock:</h3><br />\n".
         "<table width=\"100%\"><tr><th>".$COLLATE['languages']['selected']['StartingIP'].
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
    echo "</table></div>";

    echo "<div id=\"spacesearch\" style=\"display: none;\">\n".
         "<p><a href=\"#\" onclick=\"new Effect.toggle('blockspace', 'blind', { delay: 0.1 }); ".
         "   new Effect.toggle('spacesearch', 'blind', { delay: 0.1 })\">".$COLLATE['languages']['selected']['showblockspace']."</a></p>\n".
         "<h3>".$COLLATE['languages']['selected']['SearchIPSpace']."</h3><br />\n".
         "<p><b>".$COLLATE['languages']['selected']['Subnet'].":</b> <input id=\"subnetsearch\" type=\"text\"><br />".
         "<button onclick=\"new Ajax.Updater('spacesearch', '_subnets.php?op=search&amp;search=' + $('subnetsearch').value);\")\"> ".
         $COLLATE['languages']['selected']['Go']." </button></p></div>";

    echo "</div><p style=\"clear: left;\">\n";
    require_once("include/footer.php");
    exit();
} // Ends add_subnet function

function submit_subnet(){
  include 'include/validation_functions.php';
  
  $block_id = (isset($_POST['block_id']) && is_numeric($_GET['block_id'])) ? $_POST['block_id'] : '';
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
    $notice = $return['error'];
    $guidance = urlencode($guidance);
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
    exit();
  }
  else{
    $name = $return['1'];
  }
  
  $return = validate_network($ip);
  if($result['0'] === false){
    $notice = $return['error'];
    $guidance = urlencode($guidance);
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
    exit();
  }
  else{
    $start_ip = $return['start_ip'];
    $end_ip = $return['end_ip'];
    $mask = $return['mask'];
    $long_start_ip = $return['long_start_ip'];
    $long_end_ip = $return['long_end_ip'];
    $long_mask = $return['long_mask'];
  }
  
  $return = validate_ip_range($acl_start,$acl_end);
  if($result['0'] === false){
    $notice = $return['error'];
    $guidance = urlencode($guidance);
    header("Location: subnets.php?op=add&block_id=$block_id&name=$name&ip=$ip&gateway=$gateway&acl_start=$acl_start&acl_end=$acl_end&note=$note&guidance=$guidance&notice=$notice");
    exit();
  }
  else{
    $long_acl_start = $return['long_start_ip'];
    $long_acl_end = $return['long_end_ip'];
  }
  
  $long_gateway = ip2decimal($gateway);
  
  if(!empty($gateway) && (long_gateway === false || $long_gateway < $long_ip || $long_gateway > $long_end_ip)){
    $notice = "invalidgateway-notice";
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
  
  $notice = "subnetadded-notice";
  header("Location: subnets.php?block_id=$block_id&notice=$notice");
  exit();
} // ends submit_subnet function

function list_subnets(){
  global $COLLATE;  
  
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
  $result = mysql_query($sql);
  if(mysql_num_rows($result) != '1'){
    $notice = "invalidrequest";
    header("Location: blocks.php?notice=$notice");
    exit();
  }
  require_once('./include/header.php');
  $block_name = mysql_result($result, 0, 0);
  
  $blocknamesubnets = str_replace("%block_name%", $block_name, $COLLATE['languages']['selected']['BlockSubnets']);
  echo "<h1>$blocknamesubnets</h1>\n".
       "<p style=\"text-align: right;\"><a href=\"subnets.php?op=add&amp;block_id=$block_id\">
       <img src=\"./images/add.gif\" alt=\"\" /> ".
       $COLLATE['languages']['selected']['AllocateaSubnet']." </a></p>";

  $sql = "SELECT `id`, `name`, `start_ip`, `end_ip`, `mask`, `note` FROM `subnets` 
      WHERE `block_id` = '$block_id' ORDER BY `$sort` ASC";

    
   
  echo "<table width=\"100%\">\n". 
         "<tr><th align=\"left\"><a href=\"subnets.php?block_id=$block_id&amp;sort=name\">".
         $COLLATE['languages']['selected']['SubnetName']."</a></th>".
         "<th align=\"left\"><a href=\"subnets.php?block_id=$block_id&amp;sort=network\">".
         $COLLATE['languages']['selected']['NetworkAddress']."</a></th>".
         "<th align=\"left\">".$COLLATE['languages']['selected']['SubnetMask']."</th>".
         "<th align=\"left\">".$COLLATE['languages']['selected']['StaticsUsed']."</th></tr>\n".
         "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
         
  $results = mysql_query($sql);  
  $javascript = ''; # This gets concatenated to below.
  while(list($subnet_id,$name,$long_start_ip,$long_end_ip,$long_mask,$note) = mysql_fetch_row($results)){
    $start_ip = long2ip($long_start_ip);
    $mask = long2ip($long_mask);
    
    $subnet_size = $long_end_ip - $long_start_ip;
    $in_color=true;
    $percent_subnet_used = get_formatted_subnet_util($subnet_id,$subnet_size,$in_color);
    
    echo "<tr id=\"subnet_".$subnet_id."_row_1\">
         <td><b><span id=\"edit_name_".$subnet_id."\">$name</span></b></td><td><a href=\"statics.php?subnet_id=$subnet_id\">$start_ip</a></td>
         <td>$mask</td>$percent_subnet_used
         <td>";
         
    if($COLLATE['user']['accesslevel'] >= '3' || $COLLATE['settings']['perms'] > '3'){
      echo "<a href=\"subnets.php?op=modify&amp;subnet_id=$subnet_id\"><img alt=\"modify subnet\" title=\"".
           $COLLATE['languages']['selected']['modifysubnet']."\" src=\"images/modify.gif\" /></a> &nbsp; ".
           "<a href=\"#\" onclick=\"
           if (confirm('".$COLLATE['languages']['selected']['confirmdelete']."')) { 
             new Element.update('notice', ''); 
             new Ajax.Updater('notice', '_subnets.php?op=delete&subnet_id=$subnet_id', {onSuccess:function(){ 
               new Effect.Parallel( [
                 new Effect.Fade('subnet_".$subnet_id."_row_1'), 
                 new Effect.Fade('subnet_".$subnet_id."_row_2'), 
                 new Effect.Fade('subnet_".$subnet_id."_row_3')
               ]); 
             }}); 
           };\"><img src=\"./images/remove.gif\" alt=\"X\" title=\"".
           $COLLATE['languages']['selected']['deletesubnet']."\" /></a>";
    }
    echo "</td>
         </tr>\n";
         
    echo "<tr id=\"subnet_".$subnet_id."_row_2\"><td colspan=\"4\"><span id=\"edit_note_".$subnet_id."\">$note</span></td></tr>\n";
    echo "<tr id=\"subnet_".$subnet_id."_row_3\"><td colspan=\"5\"><hr class=\"division\" /></td></tr>\n";
    
    if($COLLATE['user']['accesslevel'] >= '3' || $COLLATE['settings']['perms'] > '3'){
           
      $javascript .=
           "<script type=\"text/javascript\"><!--\n".
           "  new Ajax.InPlaceEditor('edit_name_".$subnet_id."', '_subnets.php?op=edit&subnet_id=$subnet_id&edit=name',
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
           "  new Ajax.InPlaceEditor('edit_note_".$subnet_id."', '_subnets.php?op=edit&subnet_id=$subnet_id&edit=note',
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
  
  echo $javascript;
  require_once("include/footer.php");
  exit();
  
} // Ends list_subnets function

function modify_subnet (){
  global $COLLATE;
  $subnet_id = (isset($_GET['subnet_id']) && is_numeric($_GET['subnet_id'])) ? $_GET['subnet_id'] : '';
  if(empty($subnet_id)){
    $notice = "invalidrequest";
    header("Location: blocks.php?notice=$notice");
    exit();
  }
  require_once('./include/header.php');  
  
  $sql = "SELECT name, start_ip, mask FROM subnets WHERE id='$subnet_id'";
  list($subnet_name,$long_start_ip,$long_mask) = mysql_fetch_row(mysql_query($sql)); 
  $start_ip = long2ip($long_start_ip);
  $mask = long2ip($long_mask);

  $modifythesubnet = str_replace("%subnet_name%", $subnet_name, $COLLATE['languages']['selected']['ModifySubnet']);
  $modifythesubnet = str_replace("%start_ip%", $start_ip, $modifythesubnet);
  $modifythesubnet = str_replace("%mask%", $mask, $modifythesubnet);
  echo "<h1>$modifythesubnet</h1><br />\n";

  $movesubnet = str_replace("%subnet_name%", $subnet_name, $COLLATE['languages']['selected']['Movesubnet']);
  echo "<h3>$movesubnet</h3><hr />\n".
       "<form action=\"subnets.php?op=submitmove\" method=\"post\">\n".
       "<input type=\"hidden\" name=\"subnet_id\" value=\"$subnet_id\" />".
       "<p>".$COLLATE['languages']['selected']['selectblock']."</p>".
       "<select name=\"block_id\">";
  $sql = "SELECT id, name FROM blocks";
  $result = mysql_query($sql);
  while(list($block_id,$block_name) = mysql_fetch_row($result)){
    echo "<option value=\"$block_id\">$block_name</option\">";
  }
  echo "</select><br /><br />".
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
  
  $subnet_id = (isset($_POST['subnet_id']) && is_numeric($_POST['subnet_id'])) ? $_POST['subnet_id'] : '';
  $block_id = (isset($POST['block_id']) && is_numeric($_POST['block_id'])) ? $_POST['block_id'] : '';
  
  if(empty($subnet_id) || empty($block_id)){
    $notice = "invalidrequest";
    header("Location: blocks.php?notice=$notice");
    exit();
  }
    
  $result = mysql_query("SELECT name,start_ip, mask, block_id FROM subnets WHERE id='$subnet_id'");
  if(mysql_num_rows($result) != '1') {
    $notice = "invalidrequest";
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
  
  $notice = "subnetmoved-notice";
  header("Location: subnets.php?block_id=$old_block_id&notice=$notice");
  exit();
} // Ends submit_move_subnet function

function resize_subnet() {

  $subnet_id = (isset($_POST['subnet_id']) && is_numeric($_POST['subnet_id'])) ? $_POST['subnet_id'] : '';
  $new_subnet = (isset($_POST['new_subnet'])) ? $_POST['new_subnet'] : '';
  $confirm = (empty($_POST['confirm'])) ? '' : $_POST['confirm'];

  $sql = "SELECT name, start_ip, end_ip, mask, block_id FROM subnets WHERE id='$subnet_id'";
  $result = mysql_query($sql);
  if ( mysql_num_rows($result) != '1' ){
    $notice = "invalidrequest";
    header("Location: blocks.php?notice=$notice");
    exit();
  }

  list($original_subnet_name,$original_long_start_ip,$original_long_end_ip,$original_long_mask,$original_block_id) = mysql_fetch_row($result);
  
  $original_cidr=subnet2cidr($original_long_start_ip,$original_long_mask);

  $result = validate_network($new_subnet);
  if($result['0'] === false){
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
      $notice = "invalidshrink-notice";
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
    if($confirm != 'true'){
      $staticstobedeleted = str_replace("%original_subnet_name%", $original_subnet_name, $COLLATE['languages']['selected']['staticstodelete']);
      echo "<h1>$staticstobedeleted:</h1><br />\n";
    }
    
    if ($totalrows != '0' && $confirm != 'true'){
      echo "<table width=\"100%\"><tr><th>".$COLLATE['languages']['selected']['IPAddress'].
           "</th><th>".$COLLATE['languages']['selected']['Name']."</th><th>".
           $COLLATE['languages']['selected']['Contact']."</th><th>".$COLLATE['languages']['selected']['FailedScans']."</th></tr>".
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
      echo "<p>".$COLLATE['languages']['selected']['nostaticsdeleted']."</p><br /><br />";
    }  

    #  * show how ACLs would be adjusted
    # Find acls matching original subnet_id and see if start and end fall within new subnet
    $sql = "SELECT id, name, start_ip, end_ip FROM acl WHERE subnet_id='$subnet_id' AND ( ".
           "CAST(start_ip AS UNSIGNED) & CAST('$new_long_mask' AS UNSIGNED) != CAST('$new_long_start_ip' AS UNSIGNED) ".
           "OR CAST(end_ip AS UNSIGNED) & CAST('$new_long_mask' AS UNSIGNED) != CAST('$new_long_start_ip' AS UNSIGNED))";
    $result = mysql_query($sql);
    if($confirm != 'true'){
      $aclstobechanged = str_replace("%original_subnet_name%", $original_subnet_name, $COLLATE['languages']['selected']['aclstobechanged']);
      echo "<h1>$aclstobechanged:</h1><br />\n";
    }
    if (mysql_num_rows($result) == '0' && $confirm != 'true'){
      echo "<p>".$COLLATE['languages']['selected']['noaclschanged']."</p><br /><br />"; 
    }
    elseif ($confirm != 'true'){
      if($confirm != 'true'){ 
        echo "<table width=\"100%\">\n".
             "<tr><th>".$COLLATE['languages']['selected']['Name']."
             </th><th>".$COLLATE['languages']['selected']['StartingIP']."</th><th>".
             $COLLATE['languages']['selected']['EndIP']."</th><th>".$COLLATE['languages']['selected']['Modification']."</th></tr>\n".
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
          $new_acl_start_ip = long2ip($acl_long_start_ip);
          $new_acl_end_ip = long2ip($acl_long_end_ip);
          $note = "<b>".$COLLATE['languages']['selected']['ToBeDeleted']."</b>";
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
      $notice = "invalidgrow-notice";
      header("Location: subnets.php?op=modify&subnet_id=$subnet_id&notice=$notice");
      exit();
    }
    #  * list all subnets that new network overlaps
    $sql = "SELECT `id`, `name`, `start_ip`, `end_ip`, `mask`, `note` FROM `subnets` WHERE ". 
         "CAST(start_ip AS UNSIGNED) & CAST('$new_long_mask' AS UNSIGNED) = CAST('$new_long_start_ip' AS UNSIGNED) ORDER BY `start_ip` ASC";
    $results = mysql_query($sql);
    
    $subnetstomerge = str_replace("%original_subnet_name%", $original_subnet_name, $COLLATE['languages']['selected']['subnetstomerge']);
    if($confirm != 'true'){
      echo "<h1>$subnetstomerge:</h1><br />\n";
    }
    
    if(mysql_num_rows($results) < '1' && $confirm != 'true'){
      echo "<p>".$COLLATE['languages']['selected']['nosubnetsoverlap']."</p>";
    }
    else{
      if($confirm != 'true'){
      echo "<table width=\"100%\">".
           "<tr><th align=\"left\">".$COLLATE['languages']['selected']['SubnetName']."</th>".
           "<th align=\"left\">".$COLLATE['languages']['selected']['NetworkAddress']."</th>".
           "<th align=\"left\">".$COLLATE['languages']['selected']['SubnetMask']."</th>".
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
    echo "<br /><br /><h3>".$COLLATE['languages']['selected']['confirmproceed']."</h3><hr /><br />\n".
         "<form action=\"subnets.php?op=resize\" method=\"post\">\n".
         "<input type=\"hidden\" name=\"subnet_id\" value=\"$subnet_id\" />".
         "<input type=\"hidden\" name=\"confirm\" value=\"true\" />".
         "<input type=\"hidden\" name=\"new_subnet\" value=\"$new_subnet\" />".
         "<p><input type=\"submit\" value=\" ".$COLLATE['languages']['selected']['Go'].
         " \" /> | <a href=\"subnets.php?block_id=$original_block_id\">".$COLLATE['languages']['selected']['Cancel']."</a></p>".
         "</form>";
  }
  else {
    $sql = "UPDATE subnets set start_ip='$new_long_start_ip', end_ip='$new_long_end_ip', mask='$new_long_mask' WHERE id='$subnet_id'";
    $result = mysql_query($sql);
    $notice = "resized-notice";
    header("Location: subnets.php?block_id=$original_block_id&notice=$notice");
    exit();
  }
} // Ends resize_subnet()
?>