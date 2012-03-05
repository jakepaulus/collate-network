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
	
	case "delete";
	delete_static();
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

  if(!isset($_GET['subnet_id']) || empty($_GET['subnet_id'])){
    $notice = "Please select an IP block and a subnet to reserve an IP address from.";
	header("Location: blocks.php?notice=$notice");
  }
  $subnet_id = clean($_GET['subnet_id']);
  
  // Create an array with all of the IPs in the subnet, then remove all the IPs that are ACL'd out, then remove all the IPs that are already in use
  $sql = "SELECT name, start_ip, end_ip FROM subnets WHERE id='$subnet_id'";
  $results = mysql_query($sql);
  
  if(mysql_num_rows($results) != '1'){
    $notice = "The subnet you provided is not valid. Please select an IP block and a subnet to reserve an IP address from.";
	header("Location: blocks.php?notice=$notice");
  }
  
  require_once('./include/header.php');
    
  list($subnet_name,$long_subnet_start_ip,$long_subnet_end_ip) = mysql_fetch_row($results);
  $first_usable = $long_subnet_start_ip;
  $last_usable = $long_subnet_end_ip - '1';
  $whole_subnet = range($first_usable, $last_usable);
  $ipspace = $whole_subnet;
  
  $sql = "SELECT start_ip, end_ip FROM acl WHERE subnet_id='$subnet_id'";
  $results = mysql_query($sql);
  
  while(list($start_ip, $end_ip) = mysql_fetch_row($results)){
    $acl = range($start_ip, $end_ip);
	$ipspace = array_diff($ipspace, $acl);
  }
    
  $sql = "SELECT ip FROM statics WHERE subnet_id='$subnet_id'";
  $results = mysql_query($sql);
  
  
  if(mysql_num_rows($results) > '0'){
    $statics = array();
    while($static_ip = mysql_fetch_row($results)){
	  array_push($statics, $static_ip['0']); 
	}
	$ipspace = array_diff($ipspace, $statics);  
  }
  $ipspace = array_reverse($ipspace);
  $dotzeroaddress = array_pop($ipspace);
    
  $name = (empty($_GET['name'])) ? '' : $_GET['name'];
  $ip_addr = (empty($_GET['ip_addr'])) ? '' : $_GET['ip_addr'];
  $note = (empty($_GET['note'])) ? '' : $_GET['note'];
  $contact = (!isset($COLLATE['user']['username'])) ? 'system' : $COLLATE['user']['username'];
  $contact = (!isset($_GET['contact'])) ? $contact : $_GET['contact'];
  

  echo "<h1>Reserve a static IP</h1>\n".
       "<p style=\"text-align: right;\"><a href=\"#\" 
	   onclick=\"new Ajax.Updater('helper', '_statics.php?op=guidance&amp;subnet_id=$subnet_id'); \">IP Guidance</a></p>\n".
	   "<form action=\"statics.php?op=submit\" method=\"post\">\n".
	   "<div style=\"float: left; width: 28%;\">\n".
       "  <p>Name:<br /><input type=\"text\" name=\"name\" value=\"$name\" /></p>\n".
       "  <p>IP Address:<br /><select id=\"ip\" name=\"ip_addr\">\n";
	
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
	   new Ajax.Updater('helper', '_statics.php?op=ping&amp;ip=' + document.forms[0].ip.value);\">[Ping]</a>\n".
	   "  </p> \n".
       "  <p>Contact Person:<br /><input type=\"text\" name=\"contact\" value=\"$contact\"/></p>\n".
       "  <p>Note: (Optional)<br /><input type=\"text\" name=\"note\" value=\"$note\" /></p>\n".
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
		      {highlightcolor: '#a5ddf8', rows: '7', cols: '49',
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
	   "<input type=\"submit\" value=\" Go \" /></p>\n".
       "</form>";
      
} // Ends add_static function


function submit_static(){
  global $COLLATE;
  
  $name = (empty($_POST['name'])) ? '' : clean($_POST['name']);
  $ip_addr = (empty($_POST['ip_addr'])) ? '' : clean($_POST['ip_addr']);
  $note = (empty($_POST['note'])) ? '' : clean($_POST['note']);
  $contact = (empty($_POST['contact'])) ? '' : clean($_POST['contact']);
  $subnet_id = (empty($_POST['subnet_id'])) ? '' : clean($_POST['subnet_id']);
    
  if(empty($name) || empty($ip_addr) || empty($contact) || empty($subnet_id)){
    $notice = "You have left a required field blank.";
    header("Location: statics.php?op=add&subnet_id=$subnet_id&name=$name&ip_addr=$ip_addr&contact=$contact&note=$note&notice=$notice");
    exit();
  }
  
  $sql = "SELECT name, start_ip, end_ip, mask FROM subnets WHERE id='$subnet_id'";
  $results = mysql_query($sql);
  
  if(mysql_num_rows($results) != '1'){
    $notice = "The subnet you provided is not valid. Please select an IP block and a subnet to reserve an IP address from.";
    header("Location: blocks.php?notice=$notice");
  }
  
  list($subnet_name,$long_subnet_start_ip,$long_subnet_end_ip,$long_mask) = mysql_fetch_row($results);
  $first_usable = $long_subnet_start_ip;
  $last_usable = $long_subnet_end_ip - '1';
  $whole_subnet = range($first_usable, $last_usable);
  $ipspace = $whole_subnet;
  
  $sql = "SELECT start_ip, end_ip FROM acl WHERE subnet_id='$subnet_id'";
  $results = mysql_query($sql);
  
  while(list($start_ip, $end_ip) = mysql_fetch_row($results)){
    $acl = range($start_ip, $end_ip);
    $ipspace = array_diff($ipspace, $acl);
  }
  
  $sql = "SELECT ip FROM statics WHERE subnet_id='$subnet_id'";
  $results = mysql_query($sql);
  
  if(mysql_num_rows($results) > '0'){
    $statics = array();
    while($static_ip = mysql_fetch_row($results)){
	  array_push($statics, $static_ip['0']); 
	}
	$ipspace = array_diff($ipspace, $statics);  
  }
  
  $long_ip_addr = ip2decimal($ip_addr);	
  
  if(array_search($long_ip_addr, $ipspace) == FALSE){
    $notice = "The IP Address supplied is not valid. Please choose another.";
    header("Location: statics.php?op=add&subnet_id=$subnet_id&name=$name&contact=$contact&note=$note&notice=$notice");
    exit();
  }
  
  $username = (!isset($COLLATE['user']['username'])) ? 'system' : $COLLATE['user']['username'];
  
  $sql = "INSERT INTO statics (ip, name, contact, note, subnet_id, modified_by, modified_at) 
		 VALUES('$long_ip_addr', '$name', '$contact', '$note', '$subnet_id', '$username', now())";

  $accesslevel = "2";
  $message = "Static IP Reserved: $ip_addr ($name)";
  AccessControl($accesslevel, $message); // No need to generate logs if nothing is happening. Here, we know data is about to be written to the db.		 

  mysql_query($sql);
    
  // Everything looks good so here's a success page with all of the information.
  require_once('./include/header.php');
  
  $mask = long2ip($long_mask);
  $sql = "SELECT ip FROM statics WHERE subnet_id = '$subnet_id' AND note = 'Default Gateway'";
  $result = mysql_query($sql);
  if(mysql_num_rows($result) == '1'){
    $gateway = long2ip(mysql_result($result, '0'));
	$error = ''; #none	
  }
  else{
    $gateway = "*";
    $error = "<p><b>*</b>This field relies on a single static IP having the note \"Default Gateway\" being reserved. ".
	         "This could not be found for this subnet. Please have your administrator correct ".
			 "this in order to see this information properly.</p><br />";
  }
  
  
  echo "<h1>Your IP has been reserved!</h1><br />\n".
       "<p><b>Name:</b> $name</p>\n".
	   "<p><b>IP Address:</b> $ip_addr</p>\n".
	   "<p><b>Subnet Mask:</b> $mask</p>\n".
	   "<p><b>Gateway:</b> $gateway</p>\n".
	   "<p><b>DNS Servers:</b> ".$COLLATE['settings']['dns']."</p><br />\n".
	   "$error".
	   "<br />\n".
	   "<p><b><a href=\"statics.php?subnet_id=$subnet_id\">Continue to Statics List</a></b></p>\n";
  
  
} // Ends submit_static function


function list_statics(){

  global $COLLATE;

  if(!isset($_GET['subnet_id']) || empty($_GET['subnet_id'])){
    $notice = "Please select the IP Block and Subnet you would like to reserve an IP address from.";
	header("Location: blocks.php?notice=$notice");
  }
   
  $subnet_id = clean($_GET['subnet_id']);
  $sort = (!isset($_GET['sort'])) ? '' : $_GET['sort'];
  if ($sort != 'name' && $sort != 'contact') { 
    $sort = 'ip';
  }
    
  $sql = "SELECT name, start_ip, mask FROM subnets WHERE id='$subnet_id'";
  $results = mysql_query($sql);
  if(mysql_num_rows($results) != '1') {
    $notice = "You have not selected a valid subnet. Please select the IP Block and Subnet you would like to reserve an IP address from.";
	header("Location: blocks.php?notice=$notice");
  }
  
  require_once('./include/header.php');
  
  list($subnet_name, $subnet_number, $subnet_mask) = mysql_fetch_row($results);
  $subnet_number = long2ip($subnet_number);
  $subnet_mask = long2ip($subnet_mask);
     
  $page = (!isset($_GET['page'])) ? "1" : $_GET['page'];
  $show = (!isset($_GET['show'])) ? $_SESSION['show'] : $_GET['show'];
  
  $sql = "SELECT id, ip, name, contact, note, failed_scans FROM statics WHERE subnet_id='$subnet_id' ORDER BY `$sort` ASC";
  
  if(is_numeric($show) && $show <= '250' && $show > '5'){
    $limit = $show;
  }
  elseif($show > '250'){
    echo "<div class=\"tip\"><p>You can only ask for up to 250 results per page.</p></div>";
	$limit = '250';
  }
  else{
    $limit = "10";
  }
  
  $_SESSION['show'] = $limit;
  
  $result = mysql_query($sql);
  $totalrows = mysql_num_rows($result);
  $numofpages = ceil($totalrows/$limit);
  if($page > $numofpages){
    $page = $numofpages;
  }
  if($page == '0'){ $page = '1';} // Keeps errors from occuring in the following SQL query if no rows have been added yet.
  $lowerlimit = $page * $limit - $limit;
  $sql .= " LIMIT $lowerlimit, $limit";
  $row = mysql_query($sql);
  $rows = mysql_num_rows($row);
  
  echo "<h1>Static IPs in $subnet_name: $subnet_number / $subnet_mask</h1>\n".
       "<form action=\"statics.php\" method=\"get\"><table width=\"100%\"><tr><td align=\"left\">";

  echo "<p><input type=\"hidden\" name=\"subnet_id\" value=\"$subnet_id\" />".
       "<input type=\"hidden\" name=\"sort\" value=\"$sort\" />";
  
  if($page != '1'){
    $previous_page = $page - 1;
	echo "<a href=\"statics.php?subnet_id=$subnet_id&amp;page=$previous_page&amp;show=$limit&amp;sort=$sort\">
	      <img src=\"images/prev.png\" alt=\" &gt;- \" /></a> ";
  }
  
  echo "Page: <select onchange=\"this.form.submit();\" name=\"page\">";
  
  $listed_page = '1';
  while($listed_page <= $numofpages){
    if($listed_page == $page){
	  echo "<option value=\"$listed_page\" selected=\"selected\"> $listed_page </option>";
	}
	else{
	  echo "<option value=\"$listed_page\"> $listed_page </option>";
	}
	$listed_page++;
  }

  echo "</select> out of $numofpages";
  
  if($page != $numofpages){
    $next_page = $page + 1;
    echo "<a href=\"statics.php?subnet_id=$subnet_id&amp;page=$next_page&amp;show=$limit&amp;sort=$sort\">
	      <img src=\"images/next.png\" alt=\" &lt;- \" /></a>";
	
  }
  
  echo "</p></td>
  <td><p>Showing <input name=\"show\" type=\"text\" size=\"3\" value=\"$limit\" /> results per page 
  <input type=\"submit\" value=\" Go \" /></p></td>";
   

 echo "<td align=\"right\"><a href=\"statics.php?op=add&amp;subnet_id=$subnet_id\">
	   <img src=\"./images/add.gif\" alt=\"Add\" /> Reserve an IP </a></td></tr></table></form>\n".
	   "<table width=\"100%\">".
     "<tr><th><a href=\"statics.php?subnet_id=$subnet_id\">IP Address</a></th>".
     "<th><a href=\"statics.php?subnet_id=$subnet_id&amp;sort=name\">Name</a></th>".
     "<th><a href=\"statics.php?subnet_id=$subnet_id&amp;sort=contact\">Contact</a></th>".
	 "<th><a href=\"statics.php?subnet_id=$subnet_id&amp;sort=failed_scans\">Failed Scans</a></th></tr>".
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
        echo " <a href=\"#\" onclick=\"if (confirm('Are you sure you want to delete this object?')) { new Element.update('notice', ''); new Ajax.Updater('notice', '_statics.php?op=delete&static_ip=$ip', {onSuccess:function(){ new Effect.Parallel( [new Effect.Fade('static_".$static_id."_row_1'), new Effect.Fade('static_".$static_id."_row_2'), new Effect.Fade('static_".$static_id."_row_3')]); }}); };\"><img src=\"./images/remove.gif\" alt=\"X\" title=\"delete static ip\" /></a>";
      }
      echo "</td></tr>\n";
      echo "<tr id=\"static_".$static_id."_row_2\">".
           "  <td colspan=\"3\"><span id=\"edit_note_".$static_id."\">$note</span></td>";

      if($failed_scans == '-1'){
        echo "  <td><a href=\"_statics.php?op=toggle_stale-scan&amp;static_ip=$ip&amp;toggle=on\" \">".
             "<img src=\"./images/skipping.png\" alt=\"Toggle Scanning\" title=\"click to enable stale scan\" /></a></td>";
      }
      else{
        echo "  <td><a href=\"_statics.php?op=toggle_stale-scan&amp;static_ip=$ip&amp;toggle=off\" \">".
             "<img src=\"./images/scanning.png\" alt=\"Toggle Scanning\" title=\"click to disable stale scan\" /></a></td>";
      }
      
      echo "</tr>\n";
      echo "<tr id=\"static_".$static_id."_row_3\"><td colspan=\"5\"><hr class=\"division\" /></td></tr>\n";
    
      if($COLLATE['user']['accesslevel'] >= '2' || $COLLATE['settings']['perms'] > '2'){
          $javascript .=	  
             "<script type=\"text/javascript\"><!--\n".
             "  new Ajax.InPlaceEditor('edit_name_".$static_id."', '_statics.php?op=edit&static_id=$static_id&edit=name',
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
           "  new Ajax.InPlaceEditor('edit_contact_".$static_id."', '_statics.php?op=edit&static_id=$static_id&edit=contact',
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
            "  new Ajax.InPlaceEditor('edit_note_".$static_id."', '_statics.php?op=edit&static_id=$static_id&edit=note',
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
  
  
  
  if($rows < 1){
    echo "<p>No static IPs have been reserved for this subnet yet.</p>";
  }
  echo "<p>&nbsp;</p>";
  echo "<form action=\"statics.php\" method=\"get\"><table width=\"80%\"><tr><td align=\"left\">\n".
       "<p><input type=\"hidden\" name=\"subnet_id\" value=\"$subnet_id\" />".
       "<input type=\"hidden\" name=\"sort\" value=\"$sort\" />";
	   
  if($page != '1'){
    $previous_page = $page - 1;
	echo "<a href=\"statics.php?subnet_id=$subnet_id&amp;page=$previous_page&amp;show=$limit&amp;sort=$sort\">
	      <img src=\"images/prev.png\" alt=\" &gt;- \" /></a> ";
  }
	   
  echo "Page: <select onchange=\"this.form.submit();\" name=\"page\">";
  
  $listed_page = '1';
  
  while($listed_page <= $numofpages){
    if($listed_page == $page){
	  echo "<option value=\"$listed_page\" selected=\"selected\"> $listed_page </option>";
	}
	else{
	  echo "<option value=\"$listed_page\"> $listed_page </option>";
	}
	$listed_page++;
  }

  echo "</select> out of $numofpages";
  
  if($page != $numofpages){
    $next_page = $page + 1;
    echo "<a href=\"statics.php?subnet_id=$subnet_id&amp;page=$next_page&amp;show=$limit&amp;sort=$sort\">
	      <img src=\"images/next.png\" alt=\" &lt;- \" /></a>";
	
  }
  
  echo "</p></td>
  <td><p>Showing <input name=\"show\" type=\"text\" size=\"3\" value=\"$limit\" /> results per page 
  <input type=\"submit\" value=\" Go \" /></p></td></tr></table></form>";
  
  $sql = "SELECT id, name, start_ip, end_ip FROM acl WHERE subnet_id='$subnet_id' ORDER BY name ASC";
  $result = mysql_query($sql);
  
  echo "<h1>ACL for \"$subnet_name\"</h1>\n";
  
  if($COLLATE['user']['accesslevel'] >= '3' || $COLLATE['settings']['perms'] > '3'){
    echo  "<p style=\"text-align: right;\"><a href=\"javascript:Effect.toggle($('add_acl'),'appear',{duration:0})\">\n".
	      "<img src=\"./images/add.gif\" alt=\"Add\" /> Add an ACL Statement </a></p>\n";
  }
  else{
    echo "<p></p>";
  }
  
  echo "<form action=\"statics.php?op=submit_acl&amp;subnet_id=$subnet_id\" method=\"post\"><table width=\"100%\">
		<tr><th>Name</th><th>Starting IP Address</th><th>Ending IP Address</th></tr>
		<tr><td colspan=\"4\"><hr class=\"head\" /></td></tr>";
	
  while(list($acl_id,$acl_name, $long_acl_start, $long_acl_end) = mysql_fetch_row($result)){
	$acl_start = long2ip($long_acl_start);
	$acl_end = long2ip($long_acl_end);	
		 
    echo "<tr id=\"acl_".$acl_id."\">
	       <td><span id=\"edit_acl_name_".$acl_id."\">$acl_name</span></td>
		   <td>$acl_start</td>
		   <td>$acl_end</td>
		   	<td>";
		 
	if($COLLATE['user']['accesslevel'] >= '3' || $COLLATE['settings']['perms'] > '3'){
	  echo " <a href=\"#\" onclick=\"if (confirm('Are you sure you want to delete this object?')) { new Element.update('notice', ''); new Ajax.Updater('notice', '_statics.php?op=delete_acl&acl_id=$acl_id', {onSuccess:function(){ new Effect.Fade('acl_".$acl_id."'); }}); };\"><img src=\"./images/remove.gif\" alt=\"X\" /></a>";
	}
    echo "</td>
		 </tr>\n";
	
    if($COLLATE['user']['accesslevel'] >= '3' || $COLLATE['settings']['perms'] > '3'){
	  $javascript .= 
	       "<script type=\"text/javascript\"><!-- \n".
	       "  new Ajax.InPlaceEditor('edit_acl_name_".$acl_id."', '_statics.php?op=edit_acl&acl_id=$acl_id&edit=name',
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
  echo "<tr style=\"display: none;\" id=\"add_acl\">
	     <td>
		   <input type=\"text\" name=\"acl_name\" /><br />
		   <input type=\"submit\" value=\" Go \" />
		   <a href=\"javascript:Effect.toggle($('add_acl'),'appear',{duration:0})\">cancel</a>
		 </td>
	     <td style=\"vertical-align: top\"><input type=\"text\" name=\"acl_start\" /></td>
		 <td style=\"vertical-align: top\"><input type=\"text\" name=\"acl_end\" /></td>
	   </tr>	 
       ";
  echo "</table></form>\n";
	   
  echo $javascript;

  
} // Ends list_statics function


function submit_acl(){
  $subnet_id = (empty($_GET['subnet_id'])) ? '' : clean($_GET['subnet_id']);
  $acl_name = (empty($_POST['acl_name'])) ? '' : clean($_POST['acl_name']);
  $acl_start = (empty($_POST['acl_start'])) ? '' : clean($_POST['acl_start']);
  $acl_end = (empty($_POST['acl_end'])) ? '' : clean($_POST['acl_end']);
  
  if(empty($subnet_id)){
    $notice = "Please select a block, then a subnet to add a new ACL Statement.";
	header("Location: blocks.php?notice=$notice");
	exit();
  }
  
  if(empty($acl_name) || empty($acl_start) || empty($acl_end)){
  // All fields are required
    $notice = "All fields are required for a new ACL Statement.";
    header("Location: statics.php?subnet_id=$subnet_id&notice=$notice");
    exit();
  }
  
  if(ip2decimal($acl_start) == FALSE || ip2decimal($acl_end) == FALSE){
	$notice = "The ACL Range you specified is not valid.";
	header("Location: statics.php?subnet_id=$subnet_id&notice=$notice");
	exit();
  }
  
  $sql = "SELECT name, start_ip, end_ip FROM subnets WHERE id='$subnet_id'";
  $result = mysql_query($sql);
  
  if(mysql_num_rows($result) != '1'){
    $notice = "A valid subnet was not found while trying to add the ACL Statement you requested.";
	header("Location: blocks.php?notice=$notice");
	exit();
  }
  
  list($subnet_name,$long_start_ip,$long_end_ip) = mysql_fetch_row($result);
  
  AccessControl('3', "ACL for $subnet_name subnet edited");
  
  $long_acl_start = ip2decimal($acl_start);
  $long_acl_end = ip2decimal($acl_end);
  
  if($long_acl_start < $long_start_ip || $long_acl_start > $long_end_ip || $long_acl_end < $long_acl_start || $long_acl_end > $long_end_ip){
	$notice = "The ACL Range you specified is not valid.";
	header("Location: statics.php?subnet_id=$subnet_id&notice=$notice");
	exit();
  }
  
  $sql = "INSERT INTO acl (name, start_ip, end_ip, subnet_id) VALUES ('$acl_name', '$long_acl_start', '$long_acl_end', '$subnet_id')";
  
  mysql_query($sql);
  
  $notice = "The ACL statement was successfully added.";
  header("Location: statics.php?subnet_id=$subnet_id&notice=$notice");
  exit();
}
?>