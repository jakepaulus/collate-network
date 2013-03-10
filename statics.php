<?php
/**
 * Please see /include/common.php for documentation on common.php, the $COLLATE global array used by this program, and the AccessControl function used widely.
 */
require_once('./include/common.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){

    case "add";
    AccessControl("2", "Static IP Reservation form accessed");
    add_static();
    break;
    
    case "submit";
    submit_static();
    break;
    
    case "submit_acl";
    submit_acl();
    break;
    
    default:
    AccessControl("1", "Static IP list viewed");
    list_statics();
    break;
}

require_once('./include/footer.php');

function add_static(){
  global $COLLATE;

  $subnet_id = (isset($_GET['subnet_id']) && is_numeric($_GET['subnet_id'])) ? $_GET['subnet_id'] : '';
  $name = (isset($_GET['name'])) ? $_GET['name'] : '';
  $ip_addr = (isset($_GET['ip_addr'])) ? $_GET['ip_addr'] : '';
  $note = (isset($_GET['note'])) ? $_GET['note'] : '';
  $contact = (isset($COLLATE['user']['username'])) ? $COLLATE['user']['username'] : 'system'; # set a default
  $contact = (isset($_GET['contact'])) ? $_GET['contact'] : $contact; # let user input override the default
  
  if(empty($subnet_id)){
    $notice = "invalidrequest";
    header("Location: blocks.php?notice=$notice");
  }  
  
  $return = find_free_statics($subnet_id);
  $ipspace = $return['ipspace'];
  
  if($ipspace['0'] === false){
    $notice = "invalidrequest";
    header("Location: blocks.php?notice=$notice");
	exit();
  }
  
  require_once('./include/header.php');
 
  echo "<h1>".$COLLATE['languages']['selected']['ReserveaStaticIP']."</h1>\n".
       "<p style=\"text-align: right;\"><a href=\"#\" 
       onclick=\"new Ajax.Updater('helper', '_statics.php?op=guidance&amp;subnet_id=$subnet_id'); \">".
       $COLLATE['languages']['selected']['IPGuidance']."</a></p>\n".
       "<form action=\"statics.php?op=submit\" method=\"post\">\n".
       "<div style=\"float: left; width: 28%;\">\n".
       "  <p><b>".$COLLATE['languages']['selected']['Name'].":</b><br /><input type=\"text\" name=\"name\" value=\"$name\" /></p>\n".
       "  <p><b>".$COLLATE['languages']['selected']['IPAddress'].":</b><br /><select id=\"ip\" name=\"ip_addr\">\n";
    
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
       " <a href=\"#\" onclick=\"
       new Element.update('helper', '&lt;p&gt;&lt;img src=&quot;images/loading.gif&quot; alt=&quot;&quot; /&gt;&lt;/p&gt;'); 
       new Ajax.Updater('helper', '_statics.php?op=ping&amp;ip=' + document.forms[0].ip.value);\">[".$COLLATE['languages']['selected']['Ping']."]</a>\n".
       "  </p> \n".
       "  <p><b>".$COLLATE['languages']['selected']['ContactPerson'].":</b><br /><input type=\"text\" name=\"contact\" value=\"$contact\"/></p>\n".
       "  <p><b>".$COLLATE['languages']['selected']['Note'].":</b> ".$COLLATE['languages']['selected']['Optional']."<br />".
       "  <input type=\"text\" name=\"note\" value=\"$note\" /></p>\n".
       "  </div>\n".
       "  <div id=\"helper\" style=\"float: left; width: 70%; padding-left: 10px; border-left: 1px solid #000;\">\n";
  
  $sql = "SELECT guidance FROM subnets WHERE id='$subnet_id'";
  $result = mysql_query($sql);
  
  $guidance = mysql_result($result, 0, 0);
  
  if(empty($guidance) && empty($COLLATE['settings']['guidance'])){
    $help =  '';
  }
  elseif(!empty($guidance)){
    $help = $guidance;
  }
  else{ 
    $help = $COLLATE['settings']['guidance'];
  }
  
  echo "<pre><span id=\"guidance\">$help</span></pre>";
  
  if($COLLATE['user']['accesslevel'] >= '3' || $COLLATE['settings']['perms'] > '3'){
    echo "<script type=\"text/javascript\"><!--\n".
           "  new Ajax.InPlaceEditor('guidance', '_statics.php?op=edit_guidance&subnet_id=$subnet_id',
              {
              clickToEditText: '".$COLLATE['languages']['selected']['ClicktoEdit']."',
              highlightcolor: '#a5ddf8', rows: '7', cols: '49',
              callback:
                function(form) {
                  new Element.update('notice', '');
                  return Form.serialize(form);
                },
               onFailure: 
                function(transport) {
                  new Element.update('notice', transport.responseText.stripTags());
                }}
              );
          --></script>";
  }

       
  echo "</div>  <p style=\"clear: left;\"><input type=\"hidden\" name=\"subnet_id\" value=\"$subnet_id\" />\n".
       "<input type=\"submit\" value=\" ".$COLLATE['languages']['selected']['Go']." \" /></p>\n".
       "</form>";
      
} // Ends add_static function


function submit_static(){
  global $COLLATE;
  include 'include/validation_functions.php';
  
  $name = (empty($_POST['name'])) ? '' : clean($_POST['name']);
  $ip_addr = (empty($_POST['ip_addr'])) ? '' : clean($_POST['ip_addr']);
  $long_ip_addr = ip2decimal($ip_addr); 
  $note = (empty($_POST['note'])) ? '' : clean($_POST['note']);
  $contact = (empty($_POST['contact'])) ? '' : clean($_POST['contact']);
  $subnet_id = (empty($_POST['subnet_id'])) ? '' : clean($_POST['subnet_id']);
  $username = (!isset($COLLATE['user']['username'])) ? 'system' : $COLLATE['user']['username'];
    
  if(empty($name) || empty($ip_addr) || empty($contact) || empty($subnet_id)){
    $notice = "blankfield-notice";
    header("Location: statics.php?op=add&subnet_id=$subnet_id&name=$name&ip_addr=$ip_addr&contact=$contact&note=$note&notice=$notice");
    exit();
  }
    
  $validate_ip = validate_static_ip($ip_addr);
  if ($validate_ip['0'] === false){
    $notice = $validate_ip['error'];
	header("Location: statics.php?op=add&subnet_id=$subnet_id&name=$name&ip_addr=$ip_addr&contact=$contact&note=$note&notice=$notice");
    exit();
  }
  else{
    $long_mask = $validate_ip['long_mask'];
	$mask = long2ip($long_mask);
  }
     
  $sql = "INSERT INTO statics (ip, name, contact, note, subnet_id, modified_by, modified_at) 
         VALUES('$long_ip_addr', '$name', '$contact', '$note', '$subnet_id', '$username', now())";

  $accesslevel = "2";
  $message = "Static IP Reserved: $ip_addr ($name)";
  AccessControl($accesslevel, $message); // No need to generate logs if nothing is happening. Here, we know data is about to be written to the db.         

  mysql_query($sql);
    
  // Everything looks good so here's a success page with all of the information.
  require_once('./include/header.php');
 
  $sql = "SELECT ip FROM statics WHERE subnet_id = '$subnet_id' AND note = 'Default Gateway'";
  $result = mysql_query($sql);
  if(mysql_num_rows($result) == '1'){
    $gateway = long2ip(mysql_result($result, '0'));
    $error = ''; #none    
  }
  else{
    $gateway = "*";
    $error = "<p><b>*</b>".$COLLATE['languages']['selected']['nogateway']."</p><br />";
  }
  
  
  echo "<h1>".$COLLATE['languages']['selected']['IPReserved']."</h1><br />\n".
       "<p><b>".$COLLATE['languages']['selected']['Name'].":</b> $name</p>\n".
       "<p><b>".$COLLATE['languages']['selected']['IPAddress'].":</b> $ip_addr</p>\n".
       "<p><b>".$COLLATE['languages']['selected']['SubnetMask'].":</b> $mask</p>\n".
       "<p><b>".$COLLATE['languages']['selected']['Gateway'].":</b> $gateway</p>\n".
       "<p><b>".$COLLATE['languages']['selected']['DNSServers'].":</b> ".$COLLATE['settings']['dns']."</p><br />\n".
       "$error".
       "<br />\n".
       "<p><b><a href=\"statics.php?subnet_id=$subnet_id\">".$COLLATE['languages']['selected']['continuetostatics']."</a></b></p>\n";
  
} // Ends submit_static function


function list_statics(){
  global $COLLATE;
  
  $subnet_id = (isset($_GET['subnet_id']) && is_numeric($_GET['subnet_id'])) ? $_GET['subnet_id'] : '';
  if(empty($_GET['subnet_id'])){
    $notice = "invalidrequest";
    header("Location: blocks.php?notice=$notice");
    exit();
  }
   
  
  $sort = (!isset($_GET['sort'])) ? '' : $_GET['sort'];
  if ($sort != 'name' && $sort != 'contact') { 
    $sort = 'ip';
  }
    
  $sql = "SELECT name, start_ip, mask FROM subnets WHERE id='$subnet_id'";
  $results = mysql_query($sql);
  if(mysql_num_rows($results) != '1') {
    $notice = "invalidrequest";
    header("Location: blocks.php?notice=$notice");
    exit();
  }
  
  require_once('./include/header.php');
  
  list($subnet_name, $subnet_number, $subnet_mask) = mysql_fetch_row($results);
  $subnet_number = long2ip($subnet_number);
  $subnet_mask = long2ip($subnet_mask);
     
  $page = (!isset($_GET['page'])) ? "1" : $_GET['page'];
  
  echo "<h1>Static IPs in $subnet_name: $subnet_number / $subnet_mask</h1>\n".
       "<div style=\"float: left; width: 70%;\">";
  
  $sql = "SELECT id, ip, name, contact, note, failed_scans FROM statics WHERE subnet_id='$subnet_id' ORDER BY `$sort` ASC";
  $hiddenformvars="<input type=\"hidden\" name=\"subnet_id\" value=\"$subnet_id\" />".
                  "<input type=\"hidden\" name=\"sort\" value=\"$sort\" />";
  
  $updatedsql = pageselector($sql, $hiddenformvars);
  $row = mysql_query($updatedsql);
  $rows = mysql_num_rows($row);
  
  echo "</div>";   

  echo "<div style=\"float: left; width: 25%; text-align:right; padding:5px;\">".
       "<a href=\"statics.php?op=add&amp;subnet_id=$subnet_id\">".
       "<img src=\"./images/add.gif\" alt=\"\" /> ".$COLLATE['languages']['selected']['ReserveIP'].
       "</a></div><p style=\"clear: left; display: done;\">\n";

  echo "<table width=\"100%\">".
       "<tr><th><a href=\"statics.php?subnet_id=$subnet_id\">".$COLLATE['languages']['selected']['IPAddress']."</a></th>".
       "<th><a href=\"statics.php?subnet_id=$subnet_id&amp;sort=name\">".$COLLATE['languages']['selected']['Name']."</a></th>".
       "<th><a href=\"statics.php?subnet_id=$subnet_id&amp;sort=contact\">".$COLLATE['languages']['selected']['Contact']."</a></th>".
       "<th><a href=\"statics.php?subnet_id=$subnet_id&amp;sort=failed_scans\">".$COLLATE['languages']['selected']['FailedScans']."</a></th></tr>".
       "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
   
  $javascript = ''; # this gets concatenated to below 
  while(list($static_id,$ip,$name,$contact,$note,$failed_scans) = mysql_fetch_row($row)){
      $ip = long2ip($ip);
      echo "<tr id=\"static_".$static_id."_row_1\">".
           "<td>$ip</td><td><span id=\"edit_name_".$static_id."\">$name</span></td>".
           "<td><span id=\"edit_contact_".$static_id."\">$contact</span></td>".
           "<td>$failed_scans</td>".
           "<td>";
       
      if($COLLATE['user']['accesslevel'] >= '2' || $COLLATE['settings']['perms'] > '2'){
        echo "<a href=\"#\" onclick=\"
               if (confirm('".$COLLATE['languages']['selected']['confirmdelete']."')) { 
                 new Element.update('notice', ''); 
                 new Ajax.Updater('notice', '_statics.php?op=delete&static_ip=$ip', {onSuccess:function(){ 
                   new Effect.Parallel( [
                     new Effect.Fade('static_".$static_id."_row_1'), 
                     new Effect.Fade('static_".$static_id."_row_2'), 
                     new Effect.Fade('static_".$static_id."_row_3')
                   ]); 
                 }}); 
               };
              \"><img src=\"./images/remove.gif\" alt=\"X\" title=\"".$COLLATE['languages']['selected']['deletestatic']."\" /></a>";
      }
      echo "</td></tr>\n";
      echo "<tr id=\"static_".$static_id."_row_2\">".
           "  <td colspan=\"3\"><span id=\"edit_note_".$static_id."\">$note</span></td>";

      if($failed_scans == '-1'){
        echo "  <td><a href=\"_statics.php?op=toggle_stale-scan&amp;static_ip=$ip&amp;toggle=on\" \">".
             "<img src=\"./images/skipping.png\" alt=\"\" title=\"".$COLLATE['languages']['selected']['enablestalescan']."\" /></a></td>";
      }
      else{
        echo "  <td><a href=\"_statics.php?op=toggle_stale-scan&amp;static_ip=$ip&amp;toggle=off\" \">".
             "<img src=\"./images/scanning.png\" alt=\"\" title=\"".$COLLATE['languages']['selected']['disablestalescan']."\" /></a></td>";
      }
      
      echo "</tr>\n";
      echo "<tr id=\"static_".$static_id."_row_3\"><td colspan=\"5\"><hr class=\"division\" /></td></tr>\n";
    
      if($COLLATE['user']['accesslevel'] >= '2' || $COLLATE['settings']['perms'] > '2'){
          $javascript .=      
             "<script type=\"text/javascript\"><!--\n".
             "  new Ajax.InPlaceEditor('edit_name_".$static_id."', '_statics.php?op=edit&static_id=$static_id&edit=name',
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
           "  new Ajax.InPlaceEditor('edit_contact_".$static_id."', '_statics.php?op=edit&static_id=$static_id&edit=contact',
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
            "  new Ajax.InPlaceEditor('edit_note_".$static_id."', '_statics.php?op=edit&static_id=$static_id&edit=note',
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
  echo "</table><br />";
  
  
  
  if($rows < 1){
    echo "<p>".$COLLATE['languages']['selected']['nostatics']."</p>";
  }
  
  pageselector($sql,$hiddenformvars);
  
  $sql = "SELECT id, name, start_ip, end_ip FROM acl WHERE subnet_id='$subnet_id' ORDER BY name ASC";
  $result = mysql_query($sql);
  
  echo "<h1>".$COLLATE['languages']['selected']['ACL']."</h1>\n";
  
  if($COLLATE['user']['accesslevel'] >= '3' || $COLLATE['settings']['perms'] > '3'){
    echo  "<p style=\"text-align: right;\"><a href=\"javascript:Effect.toggle($('add_acl'),'appear',{duration:0})\">\n".
          "<img src=\"./images/add.gif\" alt=\"Add\" /> ".$COLLATE['languages']['selected']['AddACL']." </a></p>\n";
  }
  else{
    echo "<p></p>";
  }
  
  echo "<form action=\"statics.php?op=submit_acl&amp;subnet_id=$subnet_id\" method=\"post\"><table width=\"100%\">".
        "<tr><th>".$COLLATE['languages']['selected']['Name'].
        "</th><th>".$COLLATE['languages']['selected']['StartingIPAddress']."</th>".
        "<th>".$COLLATE['languages']['selected']['EndingIPAddress']."</th></tr>".
        "<tr><td colspan=\"4\"><hr class=\"head\" /></td></tr>";
    
  while(list($acl_id,$acl_name, $long_acl_start, $long_acl_end) = mysql_fetch_row($result)){
    $acl_start = long2ip($long_acl_start);
    $acl_end = long2ip($long_acl_end);    
         
    echo "<tr id=\"acl_".$acl_id."\">
           <td><span id=\"edit_acl_name_".$acl_id."\">$acl_name</span></td>
           <td>$acl_start</td>
           <td>$acl_end</td>
               <td>";
         
    if($COLLATE['user']['accesslevel'] >= '3' || $COLLATE['settings']['perms'] > '3'){
      echo " <a href=\"#\" onclick=\"
            if (confirm('Are you sure you want to delete this object?')) { 
              new Element.update('notice', ''); 
              new Ajax.Updater('notice', '_statics.php?op=delete_acl&acl_id=$acl_id', {onSuccess:function(){ 
                new Effect.Fade('acl_".$acl_id."'); 
              }});
            };\"><img src=\"./images/remove.gif\" alt=\"X\" /></a>";
    }
    echo "</td>
         </tr>\n";
    
    if($COLLATE['user']['accesslevel'] >= '3' || $COLLATE['settings']['perms'] > '3'){
      $javascript .= 
           "<script type=\"text/javascript\"><!-- \n".
           "  new Ajax.InPlaceEditor('edit_acl_name_".$acl_id."', '_statics.php?op=edit_acl&acl_id=$acl_id',
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
  echo "<tr style=\"display: none;\" id=\"add_acl\">
         <td>
           <input type=\"text\" name=\"acl_name\" /><br />
           <input type=\"submit\" value=\" ".$COLLATE['languages']['selected']['Go']." \" />
           <a href=\"javascript:Effect.toggle($('add_acl'),'appear',{duration:0})\">".$COLLATE['languages']['selected']['altcancel']."</a>
         </td>
         <td style=\"vertical-align: top\"><input type=\"text\" name=\"acl_start\" /></td>
         <td style=\"vertical-align: top\"><input type=\"text\" name=\"acl_end\" /></td>
       </tr>     
       ";
  echo "</table></form>\n";
       
  echo $javascript;

  
} // Ends list_statics function


function submit_acl(){
  include 'include/validation_functions.php';

  $subnet_id = (isset($_GET['subnet_id']) && is_numeric($_GET['subnet_id'])) ? $_GET['subnet_id'] : '';
  $acl_name = (isset($_POST['acl_name'])) ? $_POST['acl_name'] : '';
  $acl_start = (isset($_POST['acl_start'])) ? $_POST['acl_start'] : '';
  $acl_end = (isset($_POST['acl_end'])) ? $_POST['acl_end'] : '';  
  
  
  if(empty($subnet_id)){
    $notice = "invalidrequest";
    header("Location: blocks.php?notice=$notice");
    exit();
  }
  
  if(empty($acl_name) || empty($acl_start) || empty($acl_end)){
    $notice = "blankfield-notice";
    header("Location: statics.php?subnet_id=$subnet_id&notice=$notice");
    exit();
  }
  
  $result = validate_text($acl_name,'aclname');
  if($result['0'] === false){
    $notice = $result['error'];
	header("Location: statics.php?subnet_id=$subnet_id&notice=$notice");
    exit();
  }
  else{
    $acl_name = $result['1'];
  }
  
  $result = validate_ip_range($acl_start,$acl_end,'acl',$subnet_id);
  if($result['0'] === false){
    $notice = $result['error'];
	header("Location: statics.php?subnet_id=$subnet_id&notice=$notice");
    exit();
  }
  else{
    $long_acl_start = $result['long_start_ip'];
    $long_acl_end = $result['long_end_ip'];
	$subnet_name = $result['subnet_name'];
  }
  
  AccessControl('3', "$acl_name ACL for $subnet_name subnet edited");
  
  $sql = "INSERT INTO acl (name, start_ip, end_ip, subnet_id) VALUES ('$acl_name', '$long_acl_start', '$long_acl_end', '$subnet_id')";
  mysql_query($sql);
  $notice = "acladded-notice";
  header("Location: statics.php?subnet_id=$subnet_id&notice=$notice");
  exit();
}
?>