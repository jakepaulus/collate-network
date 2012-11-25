<?php
/*
  * Please see /include/common.php for documentation on common.php and the $COLLATE global array used by this application as well as the AccessControl function used widely.
 */
require_once('./include/common.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){

  case "download";
  AccessControl('1', null); 
  download();
  break;
  
  case "search";
  AccessControl('1', null);
  search();
  break;
  
  default: 
  AccessControl('1', null);
  require_once('./include/header.php');
  show_form();
  break;
}

/*
 * The download function takes the same GET inputs as the search function but outputs an XML file that the user can download.
 * The download function has to be a separate page because we've already produced output 
 * to the browser in the search function that we don't want in the spreadsheet by the time we get to the actual search results.
 */

function download() {
  global $COLLATE;
  $first_input = (!isset($_GET['first'])) ? '' : clean($_GET['first']);
  $second_input = (!isset($_GET['second'])) ? '' : clean($_GET['second']);
  $search_input = (!isset($_GET['search'])) ? '' : clean($_GET['search']);
  $fromdate_input = (!isset($_GET['fromdate'])) ? '' : clean($_GET['fromdate']);
  $todate_input = (!isset($_GET['todate'])) ? '' : clean($_GET['todate']);
  $when_input = (!isset($_GET['when'])) ? '' : clean($_GET['when']);
  
  if($export == "on"){ // The download function is a separate page
    $uri = $_SERVER['REQUEST_URI'];
	$uri = str_replace("op=search", "op=download", $uri);
	header("Location: $uri");
	exit();
  }
  
  $tmparray=build_search_sql();
  $sql=$tmparray["sql"];
  $searchdescription=$tmparray["searchdescription"];
  $first = $tmparray["first"];
  $First = $tmparray["First"];
  $second = $tmparray["second"];
  $Second = $tmparray["Second"];
  $search = $tmparray["search"];
  $todate = $tmparray["todate"];
  $fromdate = $tmparray["fromdate"];

  $row = mysql_query($sql);
  $totalrows = mysql_num_rows($row);
 
  if($totalrows < "1"){
    require_once('./include/header.php');
	
	echo $COLLATE['languages']['selected']['SearchResults'];
	
	if($second == 'failed_scans'){
	  $SearchResultsText=str_replace("%search%", '$search', $COLLATE['languages']['selected']['failedscansearch']);
    }
    else{
      $SearchResultsText=str_replace("%first%", "$first", $COLLATE['languages']['selected']['generalsearchterm']);
      $SearchResultsText=str_replace("%second%", "$second", $SearchResultsText);
      $SearchResultsText=str_replace("%search%", "$search", $SearchResultsText);
      $SearchResultsText=str_replace("%searchdescription%", "$searchdescription", $SearchResultsText);
    }
	echo "$SearchResultsText</p>\n<hr class=\"head\" />".
         "<p><b>".$COLLATE['languages']['selected']['nosearchresults']."</b></p>";
    require_once('./include/footer.php');
    exit();
  }
  
  ob_start();

  echo "<table>\n";
  
  if($first == "subnets"){
    echo "<tr><th>".$COLLATE['languages']['selected']['SubnetName']."</th>".
         "<th>".$COLLATE['languages']['selected']['NetworkAddress']."</th>".
         "<th>".$COLLATE['languages']['selected']['SubnetMask']."</th>".
         "<th>".$COLLATE['languages']['selected']['StaticsUsed']."</th>".
         "<th>".$COLLATE['languages']['selected']['Note']."</th></tr>\n";
 
    while(list($subnet_id,$name,$long_start_ip,$long_end_ip,$long_mask,$note) = mysql_fetch_row($row)){
      $start_ip = long2ip($long_start_ip);
      $mask = long2ip($long_mask);
	  
      if(!isset($block_name[$block_id])){ // Only look up the block name if we haven't seen the block_id yet on this page
	    $blocknamesql = "SELECT `name` FROM `blocks` WHERE `id` = '$block_id'";
        $result = mysql_query($blocknamesql);
        $block_name[$block_id] = mysql_result($result, 0, 0);
      }
	  
      $subnet_size = $long_end_ip - $long_start_ip;
      $in_color=false;
      $percent_subnet_used = get_formatted_subnet_util($subnet_id,$subnet_size,$in_color);
    
      echo "<tr><td><b>$name</b></td><td>".$block_name[$block_id]."</td><td>$start_ip</td>".
           "<td>$mask</td>$percent_subnet_used<td>$note</td></tr>";
    }
  }
  elseif($first == "static IPs"){
    echo "<tr><th>".$COLLATE['languages']['selected']['IPAddress']."</th>".
	     "<th>".$COLLATE['languages']['selected']['Name']."</th>".
		 "<th>".$COLLATE['languages']['selected']['Contact']."</th>".
		 "<th>".$COLLATE['languages']['selected']['Note']."</th>".
		 "<th>".$COLLATE['languages']['selected']['FailedScans']."</th></tr>";
  
    while(list($static_id,$ip,$name,$contact,$note,$failed_scans) = mysql_fetch_row($row)){
      $ip = long2ip($ip);
      echo "<tr><td>$ip</td><td>$name</td><td>$contact</td><td>$note</td><td>$failed_scans</td></tr>\n";
    }
  }
  elseif($first == "logs"){
    echo "<tr><td><b>".$COLLATE['languages']['selected']['Timestamp']."</b></td><td><b>".
	     $COLLATE['languages']['selected']['Username']."</b></td><td><b>".
		 $COLLATE['languages']['selected']['IPAddress']."</b></td>".
         "<td><b>".$COLLATE['languages']['selected']['Severity']."</b></td><td><b>".
		 $COLLATE['languages']['selected']['Message']."</b></td></tr>\n";
    while(list($occuredat,$username,$ipaddress, $level,$message) = mysql_fetch_row($row)){
      echo "<tr><td>$occuredat</td><td>$username</td><td>$ipaddress</td><td>$level</td><td>$message</td></tr>";
    }
  }
  
  echo "</table>\n";

  $fileout = ob_get_contents();
  ob_end_clean();

  $size = strlen($fileout);
  header("Cache-Control: "); //keeps ie happy
  header("Pragma: "); //keeps ie happy
  header("Content-type: text/xml"); // conent type
  header("Content-Length: $size");
  header("Content-Disposition: attachment; filename=\"search_result.xml\"");
  
  echo $fileout;
}

function search() {
  global $COLLATE;
  $export = (!isset($_GET['export'])) ? 'off' : $_GET['export'];
  
  $first_input = (!isset($_GET['first'])) ? '' : clean($_GET['first']);
  $second_input = (!isset($_GET['second'])) ? '' : clean($_GET['second']);
  $search_input = (!isset($_GET['search'])) ? '' : clean($_GET['search']);
  $fromdate_input = (!isset($_GET['fromdate'])) ? '' : clean($_GET['fromdate']);
  $todate_input = (!isset($_GET['todate'])) ? '' : clean($_GET['todate']);
  $when_input = (!isset($_GET['when'])) ? '' : clean($_GET['when']);
  
  if($export == "on"){ // The download function is a separate page
    $uri = $_SERVER['REQUEST_URI'];
	$uri = str_replace("op=search", "op=download", $uri);
	header("Location: $uri");
	exit();
  }
  
  $tmparray=build_search_sql();
  $sql=$tmparray["sql"];
  $searchdescription=$tmparray["searchdescription"];
  $first = $tmparray["first"];
  $First = $tmparray["First"];
  $second = $tmparray["second"];
  $Second = $tmparray["Second"];
  $search = $tmparray["search"];
  $todate = $tmparray["todate"];
  $fromdate = $tmparray["fromdate"];
  $sort=$tmparray["sort"];
  
  require_once('include/header.php');
  
  $hiddenformvars = "<input type=\"hidden\" name=\"op\" value=\"search\" />
	                 <input type=\"hidden\" name=\"first\" value=\"$first_input\" />
	                 <input type=\"hidden\" name=\"second\" value=\"$second_input\" />
		             <input type=\"hidden\" name=\"search\" value=\"$search_input\" />
		             <input type=\"hidden\" name=\"when\" value=\"$when_input\" />
		             <input type=\"hidden\" name=\"fromdate\" value=\"$fromdate_input\" />
		             <input type=\"hidden\" name=\"todate\" value=\"$todate_input\" />
					 <input type=\"hidden\" name=\"sort\" value=\"$sort\" />";
  $updatedsql = pageselector($sql,$hiddenformvars);
  $row = mysql_query($updatedsql);
  $rows = mysql_num_rows($row);
     

  echo $COLLATE['languages']['selected']['SearchResults'];
  if($second == 'failed_scans'){
	  $SearchResultsText=str_replace("%search%", '$search', $COLLATE['languages']['selected']['failedscansearch']);
    }
    else{
      $SearchResultsText=str_replace("%first%", "$first", $COLLATE['languages']['selected']['generalsearchterm']);
      $SearchResultsText=str_replace("%second%", "$second", $SearchResultsText);
      $SearchResultsText=str_replace("%search%", "$search", $SearchResultsText);
      $SearchResultsText=str_replace("%searchdescription%", "$searchdescription", $SearchResultsText);
    }
	echo "$SearchResultsText</p>\n<hr class=\"head\" />\n";
  if($rows < "1"){
    echo "<p><b>".$COLLATE['languages']['selected']['nosearchresults']."</b></p>";
    require_once('./include/footer.php');
    exit();
  }
  
  if($first == "subnets"){
    echo "<table width=\"100%\">\n". 
	     "<tr><th align=\"left\"><a href=\"search.php?op=search&amp;first=$first_input&amp;second=$second_input&amp;search=$search_input&amp;when=$when_input&amp;fromdate=$fromdate_input&amp;todate=$todate_input&amp;page=1&amp;sort=name\">".$COLLATE['languages']['selected']['SubnetName']."</th>".
		 "<th align=\"left\">".$COLLATE['languages']['selected']['Block']."</th>".
	     "<th align=\"left\"><a href=\"search.php?op=search&amp;first=$first_input&amp;second=$second_input&amp;search=$search_input&amp;when=$when_input&amp;fromdate=$fromdate_input&amp;todate=$todate_input&amp;page=1&amp;sort=network\">".$COLLATE['languages']['selected']['NetworkAddress']."</th>".
	     "<th align=\"left\">".$COLLATE['languages']['selected']['SubnetMask']."</th>".
	     "<th align=\"left\">".$COLLATE['languages']['selected']['StaticsUsed']."</th></tr>\n".
	     "<tr><td colspan=\"6\"><hr class=\"head\" /></td></tr>\n";
		 
    $javascript=''; # this gets appended to in the following while loop
    while(list($subnet_id,$name,$long_start_ip,$long_end_ip,$long_mask,$note,$block_id) = mysql_fetch_row($row)){
      $start_ip = long2ip($long_start_ip);
      $mask = long2ip($long_mask);
	  if(!isset($block_name[$block_id])){ // Only look up the block name if we haven't seen the block_id yet on this page
	    $blocknamesql = "SELECT `name` FROM `blocks` WHERE `id` = '$block_id'";
        $result = mysql_query($blocknamesql);
        $block_name[$block_id] = mysql_result($result, 0, 0);
      }
      
      $subnet_size = $long_end_ip - $long_start_ip;
      $in_color=true;
	  $percent_subnet_used = get_formatted_subnet_util($subnet_id,$subnet_size,$in_color);
      
      echo "<tr id=\"subnet_".$subnet_id."_row_1\">
           <td><b><span id=\"edit_name_".$subnet_id."\">$name</span></b></td><td>".$block_name[$block_id]."</td><td><a href=\"statics.php?subnet_id=$subnet_id\">$start_ip</a></td>
           <td>$mask</td>$percent_subnet_used
           <td>";
         
      if($COLLATE['user']['accesslevel'] >= '3' || $COLLATE['settings']['perms'] > '3'){
        echo "<a href=\"subnets.php?op=modify&amp;subnet_id=$subnet_id\"><img title=\"".
		     $COLLATE['languages']['selected']['modifysubnet']."\"".
             "src=\"images/modify.gif\" /></a> &nbsp; ".
             "<a href=\"#\" onclick=\"
			 if (confirm('".$COLLATE['languages']['selected']['confirmdelete']."')) {
               new Element.update('notice', ''); 
			   new Ajax.Updater('notice', '_subnets.php?op=delete&subnet_id=$subnet_id', 
			   {onSuccess:function(){ 
			     new Effect.Parallel( [
				   new Effect.Fade('subnet_".$subnet_id."_row_1'), 
				   new Effect.Fade('subnet_".$subnet_id."_row_2'), 
                   new Effect.Fade('subnet_".$subnet_id."_row_3')]);
               }}); 
			 };\"><img src=\"./images/remove.gif\" alt=\"X\" title=\"".
		     $COLLATE['languages']['selected']['deletesubnet']."\" /></a>";
      }
      echo "</td></tr>\n";
         
      echo "<tr id=\"subnet_".$subnet_id."_row_2\"><td colspan=\"5\"><span id=\"edit_note_".$subnet_id."\">$note</span></td></tr>\n";
      echo "<tr id=\"subnet_".$subnet_id."_row_3\"><td colspan=\"6\"><hr class=\"division\" /></td></tr>\n";
      
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
    echo "</table>\n";
  }
  elseif($first == "static IPs"){
    echo "<table width=\"100%\"><tr>".
         "<th><a href=\"search.php?op=search&amp;first=$first_input&amp;second=$second_input&amp;search=$search_input&amp;when=$when_input&amp;fromdate=$fromdate_input&amp;todate=$todate_input&amp;page=1&amp;sort=ip\">".$COLLATE['languages']['selected']['IPAddress']."</a></th>".
		 "<th>".$COLLATE['languages']['selected']['Path']."</th>".
         "<th><a href=\"search.php?op=search&amp;first=$first_input&amp;second=$second_input&amp;search=$search_input&amp;when=$when_input&amp;fromdate=$fromdate_input&amp;todate=$todate_input&amp;page=1&amp;sort=name\">".$COLLATE['languages']['selected']['Name']."</a></th>".
         "<th><a href=\"search.php?op=search&amp;first=$first_input&amp;second=$second_input&amp;search=$search_input&amp;when=$when_input&amp;fromdate=$fromdate_input&amp;todate=$todate_input&amp;page=1&amp;sort=contact\">".$COLLATE['languages']['selected']['Contact']."</a></th>".
         "<th><a href=\"search.php?op=search&amp;first=$first_input&amp;second=$second_input&amp;search=$search_input&amp;when=$when_input&amp;fromdate=$fromdate_input&amp;todate=$todate_input&amp;page=1&amp;sort=failed_scans\">".$COLLATE['languages']['selected']['FailedScans']."</a></th>".
         "</tr><tr><td colspan=\"6\"><hr class=\"head\" /></td></tr>\n";

	$javascript = ''; # this gets appended to in the following while loop
    while(list($static_id,$ip,$name,$contact,$note,$subnet_id,$failed_scans) = mysql_fetch_row($row)){
	
		# Build path information for IP - use an array to avoid accessive db calls
		if(!isset($path[$subnet_id])) {
			$pathsql = "SELECT blocks.name, subnets.name, subnets.block_id FROM blocks, subnets 
                        WHERE subnets.id ='$subnet_id' AND subnets.block_id = blocks.id";
			$result = mysql_query($pathsql);
			if(mysql_num_rows($result) == '1'){
				list($block_name, $subnet_name, $block_id) = mysql_fetch_row($result);
				$path[$subnet_id] = "<a href=\"blocks.php\">All</a> / <a href=\"subnets.php?block_id=$block_id\">$block_name</a> / 
				 <a href=\"statics.php?subnet_id=$subnet_id\">$subnet_name</a>";
			}
		}
	
      $ip = long2ip($ip);
      echo "<tr id=\"static_".$static_id."_row_1\">".
           "<td>$ip</td><td>".$path[$subnet_id]." </td><td><span id=\"edit_name_".$static_id."\">$name</span></td>".
           "<td><span id=\"edit_contact_".$static_id."\">$contact</span></td>".
           "<td>$failed_scans</td>".
           "<td>";
       
      if($COLLATE['user']['accesslevel'] >= '2' || $COLLATE['settings']['perms'] > '2'){
        echo " <a href=\"#\" onclick=\"
		      if (confirm('".$COLLATE['languages']['selected']['confirmdelete']."')) { 
			    new Element.update('notice', ''); 
				new Ajax.Updater('notice', '_statics.php?op=delete&static_ip=$ip', {onSuccess:function(){ 
				  new Effect.Parallel( [
				    new Effect.Fade('static_".$static_id."_row_1'), 
				    new Effect.Fade('static_".$static_id."_row_2'), 
					new Effect.Fade('static_".$static_id."_row_3')]); 
				}}); 
               };\"><img src=\"./images/remove.gif\" alt=\"X\" title=\"".$COLLATE['languages']['selected']['deletestatic']."\" /></a>";
      }
      echo "</td></tr>\n";
      echo "<tr id=\"static_".$static_id."_row_2\">".
           "  <td colspan=\"4\"><span id=\"edit_note_".$static_id."\">$note</span></td>";

      if($failed_scans == '-1'){
        echo "  <td><a href=\"_statics.php?op=toggle_stale-scan&amp;static_ip=$ip&amp;toggle=on\">".
             "<img src=\"./images/skipping.png\" alt=\"\" title=\"".$COLLATE['languages']['selected']['enablestalescan']."\" /></a></td>";
      }
      else{
        echo "  <td><a href=\"_statics.php?op=toggle_stale-scan&amp;static_ip=$ip&amp;toggle=off\">".
             "<img src=\"./images/scanning.png\" alt=\"\" title=\"".$COLLATE['languages']['selected']['disablestalescan']."\" /></a></td>";
      }
      
      echo "</tr>\n";
      echo "<tr id=\"static_".$static_id."_row_3\"><td colspan=\"6\"><hr class=\"division\" /></td></tr>\n";
    
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
    echo "</table>\n";
  }
  elseif($first == "logs"){
    echo "<table width=\"100%\"><tr><td><b>".$COLLATE['languages']['selected']['Timestamp'].
         "</b></td><td><b>".$COLLATE['languages']['selected']['Username'].
		 "</b></td><td><b>".$COLLATE['languages']['selected']['IPAddress']."</b></td>".
         "<td><b>".$COLLATE['languages']['selected']['Severity'].
		 "</b></td><td><b>".$COLLATE['languages']['selected']['Message']."</b></td></tr>\n".
         "<tr><td colspan=\"5\"><hr class=\"head\" /></td></tr>\n";
		 
    while(list($occuredat,$username,$ipaddress, $level,$message) = mysql_fetch_row($row)){
      if($level == "high"){
	    $level = "<b>$level</b>";
      }
	  echo "<tr><td>$occuredat</td><td>$username</td><td>$ipaddress</td><td>$level</td><td>$message</td></tr>".
	       "<tr><td colspan=\"5\"><hr class=\"division\" /></td></tr>";
    }
    echo "</table>\n";
  }
  
  pageselector($sql,$hiddenformvars);
  
  $javascript= (!isset($javascript)) ? '' : $javascript;
  echo $javascript;

  require_once('./include/footer.php');
  
} // Ends search function

/*
 * The search form uses the script.aculo.us javascript library as well as options.js which is taken from
 * http://www.quirksmode.org/js/options.html. I have modified options.js to call scriptaculous functions that
 * enable actions on changes of drop down lists. Options.js enables dynamic drop-down list contents based on the
 * selection in a previous drop-down list.
 */

function show_form()  {
  global $COLLATE;
  ?>
  <script type="text/javascript">
  <!--
  var store = new Array();
  
  store[0] = new Array(
  	'<?php echo $COLLATE['languages']['selected']['IP']; ?>', 'ip',
  	'<?php echo $COLLATE['languages']['selected']['name']; ?>', 'name',
  	'<?php echo $COLLATE['languages']['selected']['note']; ?>', 'note',
  	'<?php echo $COLLATE['languages']['selected']['lastmodifiedby']; ?>', 'modified_by');
  
  store[1] = new Array(
  	'<?php echo $COLLATE['languages']['selected']['IP']; ?>', 'ip',
  	'<?php echo $COLLATE['languages']['selected']['name']; ?>', 'name',
  	'<?php echo $COLLATE['languages']['selected']['contact']; ?>', 'contact',
  	'<?php echo $COLLATE['languages']['selected']['note']; ?>', 'note',
  	'<?php echo $COLLATE['languages']['selected']['lastmodifiedby']; ?>', 'modified_by',
    '<?php echo $COLLATE['languages']['selected']['failedscanscount']; ?>', 'failed_scans');
  	
  store[2] = new Array(
  	'<?php echo $COLLATE['languages']['selected']['username']; ?>', 'username',
  	'<?php echo $COLLATE['languages']['selected']['level']; ?>', 'level',
  	'<?php echo $COLLATE['languages']['selected']['message']; ?>', 'message');
  
  function init()
  {
  	optionTest = true;
  	if(document.forms[0])
  	{
  	lgth = document.forms[0].second.options.length - 1;
  	document.forms[0].second.options[lgth] = null;
  	if (document.forms[0].second.options[lgth]) optionTest = false;
  	}
  }
  
  
  function populate()
  {
  	if (!optionTest) return;
  	var box = document.forms[0].first;
  	var number = box.options[box.selectedIndex].value;
  	if (!number) return;
  	var list = store[number];
  	var box2 = document.forms[0].second;
  	box2.options.length = 0;
  	for(i=0;i<list.length;i+=2)
  	{
  		box2.options[i/2] = new Option(list[i],list[i+1]);
  	}
  }
  // -->
  </script>
  <script type="text/javascript" src="javascripts/calendarDateInput.php">
  /***********************************************
  * Jason's Date Input Calendar- By Jason Moon http://calendar.moonscript.com/dateinput.cfm
  * Script featured on and available at http://www.dynamicdrive.com
  * Keep this notice intact for use.
  ***********************************************/
  </script>
  <script type="text/javascript">
    window.onload = init();
  </script>
  <h1><?php echo $COLLATE['languages']['selected']['AdvancedSearch']; ?></h1>
  <br />
  <form onload="init();" id="test" action="search.php" method="get">
  <p><?php echo $COLLATE['languages']['selected']['Lookfor']; ?> <input type="hidden" name="op" value="search" />
  <select name="first" onchange="populate();">
    <option value="0"><?php echo $COLLATE['languages']['selected']['subnets']; ?></option>
	<option value="1"><?php echo $COLLATE['languages']['selected']['staticIPs']; ?></option>
	<option value="2"><?php echo $COLLATE['languages']['selected']['logs']; ?></option>
  </select>
  <?php echo $COLLATE['languages']['selected']['withaan']; ?>
  <select name="second">
	<option value="ip"><?php echo $COLLATE['languages']['selected']['IP']; ?></option>
	<option value="name"><?php echo $COLLATE['languages']['selected']['name']; ?></option>
	<option value="note"><?php echo $COLLATE['languages']['selected']['note']; ?></option>
	<option value="modified_by"><?php echo $COLLATE['languages']['selected']['lastmodifiedby']; ?></option>
  </select> matching: <input name="search" type="text" /> &nbsp;
  <br />
  <br />
  <input type="radio" name="when" value="all" checked="checked" onclick="new Effect.Fade('extraforms', {duration: 0.2})" /> 
  <?php echo $COLLATE['languages']['selected']['Searchallrecords']; ?> <br />
  <input type="radio" name="when" value="dates" onclick="new Effect.Appear('extraforms', {duration: 0.2})" /> 
  <?php echo $COLLATE['languages']['selected']['Specifydaterange']; ?><br />
  <div id="extraforms" style="display: none;">
    <br />
    <b><?php echo $COLLATE['languages']['selected']['dateFrom']; ?></b><br />
      <script type="text/javascript">DateInput('fromdate', 'false', 'YYYY-MM-DD')</script>
	<br />
    <b><?php echo $COLLATE['languages']['selected']['dateTo']; ?></b><br />
      <script type="text/javascript">DateInput('todate', 'false', 'YYYY-MM-DD')</script>
  </div>
  <br />
  <br />
  <input type="checkbox" name="export" /> <?php echo $COLLATE['languages']['selected']['Exportresults']; ?><br />
  <br />
  <input type="submit" value=" <?php echo $COLLATE['languages']['selected']['Go']; ?> " /></p>
  </form>
  <br />
  <?php
  require_once('./include/footer.php');
} // Ends list_searches function

function build_search_sql(){
  global $COLLATE;
  
  $first = $first_input = (!isset($_GET['first'])) ? '' : clean($_GET['first']);
  $second = $second_input = (!isset($_GET['second'])) ? '' : clean($_GET['second']);
  $search = $search_input = (!isset($_GET['search'])) ? '' : clean($_GET['search']);
  $fromdate = $fromdate_input = (!isset($_GET['fromdate'])) ? '' : clean($_GET['fromdate']);
  $todate = $todate_input = (!isset($_GET['todate'])) ? '' : clean($_GET['todate']);
  $when = $when_input = (!isset($_GET['when'])) ? '' : clean($_GET['when']);
  if($fromdate == $todate){ // The user forgot to move the button back to "all" without selecting specific dates
    $when = "all";
  }
    
  if(strlen($search) < "3" && $second != 'failed_scans'){
    $notice = "shortsearch";
    header("Location: search.php?notice=$notice");
    exit();
  }
  elseif($second == 'failed_scans' && !is_numeric($search)){
    $notice = "numericfailedscans";
    header("Location: search.php?notice=$notice");
    exit();
  }
  
  // -----------------------------------------------Build our sort variable---------------------------------------------
  if($first == '0'){ // subnet search
    // use what they ask for or default to what they searched by
    // $sort is what the URI uses, $order and $full_order go into the SQL query - $full_order includes ASC or DESC
    if(!empty($_GET['sort']) && ( $_GET['sort'] == 'network' || $_GET['sort'] == 'name' )){
      $sort = $_GET['sort'];
    }
    else{
      $sort = $second;
    }
    $order = $sort;
    if($sort == 'network' || $sort == 'ip'){ $order = 'start_ip'; }
  }
  else{ // static IP search or logs (logs are always sorted by ID Desc. because they're logs and i'm lazy)
    if(!empty($_GET['sort']) && ( $_GET['sort'] == 'ip' || $_GET['sort'] == 'name' || $_GET['sort'] == 'contact' || $_GET['sort'] == 'failed_scans')){
      $sort = $_GET['sort'];
    }
    else{
      $sort = $second;
    }
    $order = $sort;
  }
  //-----------------------------------------------------------------------------------------------------------------------------
  
  if(($first == '0' || $first == '1') && $second == "ip"){
  
    if(!strstr($search, '/')){
      $ip = $search;
      $mask = '32';
	  }
	  else{
      list($ip,$mask) = explode('/', $search);
	  }
  
    if(ip2decimal($ip) == FALSE){
      $notice = "invalidip";
      header("Location: search.php?notice=$notice");
      exit();
    }
  
    $ip = long2ip(ip2decimal($ip));  
    if(!strstr($mask, '.') && ($mask <= '0' || $mask > '32')){
      $notice = "invalidmask";
      header("Location: search.php?notice=$notice");
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
      header("Location: search.php?notice=$notice");
	  exit();
    }
  }
  $long_ip = (isset($ip)) ? ip2decimal($ip) : '';
  $long_mask = (isset($mask)) ? ip2decimal($mask) : '';
  
  if($when == "dates"){
    $searchdescription = str_replace("%fromdate%", "$fromdate", $COLLATE['languages']['selected']['searchdatedesc']);
	$searchdescription = str_replace("%todate%", "$todate", $COLLATE['languages']['selected']['searchdatedesc']);
  }
  
  if($first == "0") { // Subnet search
    $first = "subnets";
    $First = "Subnets";

    if($when == "dates"){
      if($second == "ip"){
        $sql = "SELECT id, name, start_ip, end_ip, mask, note, block_id FROM subnets WHERE CAST(start_ip AS UNSIGNED) & CAST('$long_mask' AS UNSIGNED) = CAST('$long_ip' AS UNSIGNED) AND
        modified_at > '$fromdate 00:00:00' AND modified_at < '$todate 23:59:59' ORDER BY `$order` ASC";
        }
      else{
        $sql = "SELECT id, name, start_ip, end_ip, mask, note, block_id FROM subnets WHERE $second LIKE '%$search%' AND
        modified_at > '$fromdate 00:00:00' AND modified_at < '$todate 23:59:59' ORDER BY `$order` ASC";
      }
    }
    else{
      if($second == "ip"){
        $sql = "SELECT id, name, start_ip, end_ip, mask, note, block_id FROM subnets WHERE CAST(start_ip AS UNSIGNED) & CAST('$long_mask' AS UNSIGNED) = CAST('$long_ip' AS UNSIGNED) ORDER BY `$order` ASC";
        }
      else{
        $sql = "SELECT id, name, start_ip, end_ip, mask, note, block_id FROM subnets WHERE $second LIKE '%$search%' ORDER BY `$order` ASC";
      }
    }
  }
  elseif($first == "1"){ // Statics earch
    $first = "static IPs";
    if($sort == 'failed_scans'){
      $full_order = "`failed_scans` DESC";
    }
    else {
      $full_order = "`$sort` ASC";
    }
	
    if($when == "dates"){
      if($second == "ip"){
        $sql = "SELECT id, ip, name, contact, note, subnet_id, failed_scans FROM statics WHERE CAST(ip AS UNSIGNED) & CAST('$long_mask' AS UNSIGNED) = CAST('$long_ip' AS UNSIGNED) AND
        modified_at > '$fromdate 00:00:00' AND modified_at < '$todate 23:59:59' ORDER BY $full_order";
      }
      elseif($second == 'failed_scans'){
        $sql = "SELECT id, ip, name, contact, note, subnet_id, failed_scans FROM statics WHERE 
              (failed_scans >= '$search' OR failed_scans = '-1') AND modified_at > '$fromdate 00:00:00' 
              AND modified_at < '$todate 23:59:59' ORDER BY $full_order";
      }
      else{
        $sql = "SELECT id, ip, name, contact, note, subnet_id, failed_scans FROM statics WHERE $second LIKE '%$search%' AND
        modified_at > '$fromdate 00:00:00' AND modified_at < '$todate 23:59:59' ORDER BY $full_order";
      }
    }
    else{
      if($second == "ip"){
        $sql = "SELECT id, ip, name, contact, note, subnet_id, failed_scans FROM statics WHERE CAST(ip AS UNSIGNED) & CAST('$long_mask' AS UNSIGNED) = CAST('$long_ip' AS UNSIGNED) 
        ORDER BY $full_order";
      }
      elseif($second == 'failed_scans') {
        $sql = "SELECT id, ip, name, contact, note, subnet_id, failed_scans FROM statics WHERE (failed_scans >= '$search' 
        OR failed_scans = '-1') ORDER BY $full_order";
      }
      else{
        $sql = "SELECT id, ip, name, contact, note, subnet_id, failed_scans FROM statics WHERE $second LIKE '%$search%' 
        ORDER BY $full_order";
      }
    }
  }
  elseif($first == "2"){ // They're trying to search logs
    $first = "logs";
    $First = "Logs";
    $Second = ucfirst($second);
    if($when == "dates"){
      $sql = "SELECT occuredat, username, ipaddress, level, message FROM logs WHERE $second LIKE '%$search%' AND ".
             "occuredat>='$fromdate 00:00:00' AND occuredat<='$todate 23:59:59' ORDER BY `id` DESC";
    }
    else{
      $sql = "SELECT occuredat, username, ipaddress, level, message FROM logs WHERE $second LIKE '%$search%' ORDER BY `id` DESC";
    }
  }
  if($second == "username"){
    $Second = "User";
  }
  
  $searchdescription =(!isset($searchdescription)) ? '' : $searchdescription;
  $First = (!isset($First)) ? '' : $First;
  $Second =(!isset($Second)) ? '' : $Second;
  
  $resultarray=array(
	"sql"=>$sql, 
	"searchdescription"=>$searchdescription,
	"first"=>$first,
	"First"=>$First,
	"second"=>$second,
	"Second"=>$Second,
	"search"=>$search,
	"todate"=>$todate,
	"fromdate"=>$fromdate,
	"sort"=>$sort
	);
  return $resultarray;
}
?>
