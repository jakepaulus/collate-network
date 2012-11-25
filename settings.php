<?php

require_once('./include/common.php');
AccessControl('5', null);
require_once('./include/header.php');
form();
require_once('./include/footer.php');
exit();


function form() {
global $COLLATE;

  ?>
  <h1><?php echo $COLLATE['languages']['selected']['Settings']; ?></h1>
  <br />
  <h3><?php echo $COLLATE['languages']['selected']['General']; ?></h3>
  <hr />
  <div id="generalnotice" class="tip"></div>
  <div style="margin-left: 25px;">
    <?php
    foreach (glob("languages/*.php") as $filename){
      include $filename;
    }
	?>
	<p><b><?php echo $COLLATE['languages']['selected']['DefaultLanguage']; ?></b> <select name="languages" onchange="new Ajax.Updater('generalnotice', '_settings.php?op=updatelanguage&amp;language=' + this.value);">
	<?php
	foreach ($languages as $language){
	  if($COLLATE['settings']['language'] == $language['isocode']){
	    $selected = "selected=\"selected\"";
	  }
	  else {
	    $selected = "";
	  }
	  echo "<option value=\"".$language['isocode']."\" $selected /> ".$language['languagename']." </option>\n";
	}
    ?>
	</select></p>
  </div>
  <br />
  <h3><?php echo $COLLATE['languages']['selected']['Authorization']; ?></h3>
  <hr />
  <div id="authorizationnotice" class="tip"></div>
  <div style="margin-left: 25px;">
  <p><b><?php echo $COLLATE['languages']['selected']['checkperms']; ?></b></p>
  
  <?php
  
    if($COLLATE['settings']['perms'] == "5"){
	  $checked5 = "checked=\"checked\"";
	}
	elseif($COLLATE['settings']['perms'] == "4"){
	  $checked4 = "checked=\"checked\"";
	}
	elseif($COLLATE['settings']['perms'] == "3"){
	  $checked3 = "checked=\"checked\"";
	}
	elseif($COLLATE['settings']['perms'] == "2"){
	  $checked2 = "checked=\"checked\"";
	}
	elseif($COLLATE['settings']['perms'] == "0"){
	  $checked1 = "checked=\"checked\"";
	}
	else{
	  $checked0 = "checked=\"checked\"";
	}

  ?>
  <ul class="plain">
    <li>
	  <input type="radio" name="perms" onchange="new Ajax.Updater('authorizationnotice', '_settings.php?op=updateauthorization&amp;perms=0');" <?php 
	  echo (empty($checked1) ? "" : $checked1);
	  echo "/>".$COLLATE['languages']['selected']['readonly']; ?></li>
	<li>
	  <input type="radio" name="perms" onchange="new Ajax.Updater('authorizationnotice', '_settings.php?op=updateauthorization&amp;perms=2');" <?php 
	  echo (empty($checked2) ? "" : $checked2);
	  echo "/>".$COLLATE['languages']['selected']['ReserveIPs']; ?></li>
    <li>
	  <input type="radio" name="perms" onchange="new Ajax.Updater('authorizationnotice', '_settings.php?op=updateauthorization&amp;perms=3');" <?php 
	  echo (empty($checked3) ? "" : $checked3);
	  echo "/>".$COLLATE['languages']['selected']['AllocateSubnets']; ?></li>
	<li>
	  <input type="radio" name="perms" onchange="new Ajax.Updater('authorizationnotice', '_settings.php?op=updateauthorization&amp;perms=4');" <?php 
	  echo (empty($checked4) ? "" : $checked4);
	  echo "/>".$COLLATE['languages']['selected']['AllocateBlocks']; ?></li>
	<li>
	  <input type="radio" name="perms" onchange="new Ajax.Updater('authorizationnotice', '_settings.php?op=updateauthorization&amp;perms=5');" <?php 
	  echo (empty($checked5) ? "" : $checked5);
	  echo "/>".$COLLATE['languages']['selected']['Admin']; ?></li>
	<li>
	  <input type="radio" name="perms" onchange="new Ajax.Updater('authorizationnotice', '_settings.php?op=updateauthorization&amp;perms=6');" <?php 
	  echo (empty($checked0) ? "" : $checked0);
	  echo "/>".$COLLATE['languages']['selected']['noauthentication']; ?></li>
  </ul>
  </div>
  <br />
  <h3><?php echo $COLLATE['languages']['selected']['Authentication']; ?></h3>
  <hr />
  <div id="authenticationnotice" class="tip"></div>
  <div style="margin-left: 20px;">
    <p><b><?php echo $COLLATE['languages']['selected']['DefaultAuthMethod']; ?></b></p>
    <ul class="plain">
      <li>
	    <input type="radio" name="auth_type" onchange="new Ajax.Updater('authenticationnotice', '_settings.php?op=updateauthentication&amp;auth_type=db');" <?php 
	    echo ($COLLATE['settings']['auth_type'] == 'db') ? "checked=\"checked\"": ""; 
	    echo "/>".$COLLATE['languages']['selected']['Database']; ?></li>
      <li>
	    <input type="radio" name="auth_type" onchange="new Ajax.Updater('authenticationnotice', '_settings.php?op=updateauthentication&amp;auth_type=ldap');" <?php 
	    echo ($COLLATE['settings']['auth_type'] == 'ldap') ? "checked=\"checked\"": ""; 
	    echo "/>".$COLLATE['languages']['selected']['LDAP']; ?></li>
    </ul>
  
	<table width="90%">
	<tr>
	  <th width="33%"><?php echo $COLLATE['languages']['selected']['Domain']; ?></th>
	  <th width="33%"><?php echo $COLLATE['languages']['selected']['LDAPServer']; ?></th>
	  <td width="33%"><a href="#" onclick="
	    new Element.update('authenticationnotice', '');
	    new Ajax.Updater({ success: 'ldap_servers', failure: 'authenticationnotice' }, '_settings.php?op=addldapserver', {onSuccess:function(){
		  new Ajax.Request('_settings.php?op=addldapserver&javascript=true', {evalJS: 'force'});
		}});"
	    <img src="./images/add.gif" alt="" /> <?php echo $COLLATE['languages']['selected']['AddLDAPServer']; ?> </a></td>
	</tr>
	</table>
	<div id="ldap_servers">
	
	<?php
	$sql = "select id,domain, server from `ldap-servers` order by domain ASC";
	$result = mysql_query($sql);
	if(mysql_num_rows($result) == '0'){
	  echo $COLLATE['languages']['selected']['noserversdefined'];
	}
	else{
	    echo "<table width=\"90%\">";
	$javascript='';
	while(list($id,$domain,$server) = mysql_fetch_row($result)){
	  echo "<tr id=\"ldap_server_$id\"><td width=\"33%\"><span id=\"edit_domain_$id\">$domain</span></td><td width=\"33%\"><span id=\"edit_server_$id\">$server</span></td><td width=\"33%\"><a href=\"#\" onclick=\"
      if (confirm('".$COLLATE['languages']['selected']['confirmdelete']."')) { 
        new Element.update('authenticationnotice', ''); 
        new Ajax.Updater('authenticationnotice', '_settings.php?op=delete_ldap_server&ldap_server_id=$id', {onSuccess:function(){ 
          new Effect.Fade('ldap_server_".$id."') 
        }}); 
      };\"
      ><img src=\"./images/remove.gif\" alt=\"X\" /></a></td></tr>\n";
      
      $javascript .=	  

         "  new Ajax.InPlaceEditor('edit_domain_$id', '_settings.php?op=editldap&object=domain&id=$id',
              {
			    clickToEditText: '".$COLLATE['languages']['selected']['ClicktoEdit']."',
			    highlightcolor: '#a5ddf8', 
                callback:
                  function(form) {
                    new Element.update('authenticationnotice', '');
                    return Form.serialize(form);
                  },
                onFailure: 
                  function(transport) {
                    new Element.update('authenticationnotice', transport.responseText.stripTags());
                  }
              }
            );\n".
         "  new Ajax.InPlaceEditor('edit_server_$id', '_settings.php?op=editldap&object=server&id=$id',
              {
			    clickToEditText: '".$COLLATE['languages']['selected']['ClicktoEdit']."',
			    highlightcolor: '#a5ddf8',  
                callback:
                  function(form) {
                    new Element.update('authenticationnotice', '');
                    return Form.serialize(form);
                  },
                onFailure: 
                  function(transport) {
                    new Element.update('authenticationnotice', transport.responseText.stripTags());
                  }
              }
            );\n";      
	  }
    echo "</table>";
    echo "<script type=\"text/javascript\"><!--\n";
    echo $javascript;
    echo "--></script>\n";
	}
	
	?>
	
	</div>
	<br />
	<p><b><?php echo $COLLATE['languages']['selected']['DefaultDomainName']; ?></b>
	<a href="#" onclick="new Effect.toggle($('defaultdomaintip'),'appear');"><img src="images/help.gif" alt="[?]" /></a><br />
	<div style="margin-left: 25px;">
	  <span id="defaultdomain"><?php echo $COLLATE['settings']['domain']; ?></span>
	</div>
    </p>
    <div style="display: none;" class="tip" id="defaultdomaintip"><p><?php echo $COLLATE['languages']['selected']['defaultdomaintip']; ?></p></div>
    <script type="text/javascript"><!--
      new Ajax.InPlaceEditor('defaultdomain', '_settings.php?op=editdomain',
              {
			  clickToEditText: ' <?php echo $COLLATE['languages']['selected']['ClicktoEdit']; ?>',
    	      highlightcolor: '#a5ddf8', 
              callback:
                function(form) {
                  new Element.update('authenticationnotice', '');
                  return Form.serialize(form);
                },
              onFailure: 
                function(transport) {
                  new Element.update('authenticationnotice', transport.responseText.stripTags());
                }
              }
            );
    --></script>
      
	<p><b><?php echo $COLLATE['languages']['selected']['PasswordExpire']; ?></b>
	<select name="accountexpire" onchange="new Ajax.Updater('authenticationnotice', '_settings.php?op=updateaccountexpire&accountexpire=' + this.value);">
	<option value="0" <?php if ($COLLATE['settings']['accountexpire'] == "0") { echo "selected=\"selected\""; } ?>> 0 </option>
	<option value="30" <?php if ($COLLATE['settings']['accountexpire'] == "30") { echo "selected=\"selected\""; } ?>> 30 </option>
	<option value="45" <?php if ($COLLATE['settings']['accountexpire'] == "45") { echo "selected=\"selected\""; } ?>> 45 </option>
	<option value="60" <?php if ($COLLATE['settings']['accountexpire'] != "0" && 
	                             $COLLATE['settings']['accountexpire'] != "30" &&
								 $COLLATE['settings']['accountexpire'] != "45" &&
								 $COLLATE['settings']['accountexpire'] != "90" &&
								 $COLLATE['settings']['accountexpire'] != "120" &&
								 $COLLATE['settings']['accountexpire'] != "180") { echo "selected=\"selected\""; } ?>> 60 </option>	
	<option value="90" <?php if ($COLLATE['settings']['accountexpire'] == "90") { echo "selected=\"selected\""; } ?>> 90 </option>
	<option value="120" <?php if ($COLLATE['settings']['accountexpire'] == "120") { echo "selected=\"selected\""; } ?>> 120 </option>
	<option value="180" <?php if ($COLLATE['settings']['accountexpire'] == "180") { echo "selected=\"selected\""; } ?>> 180 </option>
	</select>
    <a href="#" onclick="new Effect.toggle($('passwdexpiretip'),'appear');"><img src="images/help.gif" alt="[?]" /></a></p>
    <div style="display: none;" class="tip" id="passwdexpiretip">
	  <p><?php echo $COLLATE['languages']['selected']['passwdexpiretip']; ?></p>
	</div>
      
	<p><b><?php echo $COLLATE['languages']['selected']['MinPasswdLength']; ?></b>
	<select name="passwdlength" onchange="new Ajax.Updater('authenticationnotice', '_settings.php?op=updatepasswdlength&passwdlength=' + this.value);">
	<option value="5" <?php if($COLLATE['settings']['passwdlength'] == "5") { echo "selected=\"selected\""; } ?>> 5 </option>
	<option value="6" <?php if($COLLATE['settings']['passwdlength'] == "6") { echo "selected=\"selected\""; } ?>> 6 </option>
	<option value="7" <?php if($COLLATE['settings']['passwdlength'] == "7") { echo "selected=\"selected\""; } ?>> 7 </option>
	<option value="8" <?php if($COLLATE['settings']['passwdlength'] == "8") { echo "selected=\"selected\""; } ?>> 8 </option>
	<option value="9" <?php if($COLLATE['settings']['passwdlength'] == "9") { echo "selected=\"selected\""; } ?>> 9 </option>
	<option value="10" <?php if($COLLATE['settings']['passwdlength'] == "10") { echo "selected=\"selected\""; } ?>> 10 </option>
	</select>
    <a href="#" onclick="new Effect.toggle($('passwdlengthtip'),'appear');"><img src="images/help.gif" alt="[?]" /></a></p>
    <div style="display: none;" class="tip" id="passwdlengthtip">
	  <p><?php echo $COLLATE['languages']['selected']['passwdlengthtip']; ?></p>
	</div>
      
	<p><b><?php echo $COLLATE['languages']['selected']['MaxLoginAttempts']; ?></b>
	<select name="loginattempts" onchange="new Ajax.Updater('authenticationnotice', '_settings.php?op=updateloginattempts&loginattempts=' + this.value);">
	<option value="0" <?php if($COLLATE['settings']['loginattempts'] == "0") { echo "selected=\"selected\""; } ?>> 0 </option>
	<option value="1" <?php if($COLLATE['settings']['loginattempts'] == "1") { echo "selected=\"selected\""; } ?>> 1 </option>
	<option value="2" <?php if($COLLATE['settings']['loginattempts'] == "2") { echo "selected=\"selected\""; } ?>> 2 </option>
	<option value="3" <?php if($COLLATE['settings']['loginattempts'] == "3") { echo "selected=\"selected\""; } ?>> 3 </option>
	<option value="4" <?php if($COLLATE['settings']['loginattempts'] == "4") { echo "selected=\"selected\""; } ?>> 4 </option>
	<option value="5" <?php if($COLLATE['settings']['loginattempts'] == "5") { echo "selected=\"selected\""; } ?>> 5 </option>
	<option value="6" <?php if($COLLATE['settings']['loginattempts'] == "6") { echo "selected=\"selected\""; } ?>> 6 </option>
	<option value="7" <?php if($COLLATE['settings']['loginattempts'] == "7") { echo "selected=\"selected\""; } ?>> 7 </option>
	<option value="8" <?php if($COLLATE['settings']['loginattempts'] == "8") { echo "selected=\"selected\""; } ?>> 8 </option>
	<option value="9" <?php if($COLLATE['settings']['loginattempts'] == "9") { echo "selected=\"selected\""; } ?>> 9 </option>
	</select>
    <a href="#" onclick="new Effect.toggle($('maxlogintip'),'appear');"><img src="images/help.gif" alt="[?]" /></a></p>
	<div style="display: none;" class="tip" id="maxlogintip">
	  <p><?php echo $COLLATE['languages']['selected']['maxlogintip']; ?></p>
	</div>
  </div>
  <h3><?php echo $COLLATE['languages']['selected']['UserGuidance']; ?></h3>
  <hr />
  <div id="guidancenotice" class="tip"></div>
  <div style="margin-left: 25px;">
  <p><?php echo $COLLATE['languages']['selected']['DefaultIPGuidance']; ?></p>
  <div style="margin-left: 25px;">
    <pre><span id="guidance"><?php echo $COLLATE['settings']['guidance']; ?></span></pre>
  </div>
  <script type="text/javascript"><!--
      new Ajax.InPlaceEditor('guidance', '_settings.php?op=editguidance',
              {
			  clickToEditText: '<?php echo $COLLATE['languages']['selected']['ClicktoEdit']; ?>',
    	      highlightcolor: '#a5ddf8', rows: '7', cols: '49',
              callback:
                function(form) {
                  new Element.update('guidancenotice', '');
                  return Form.serialize(form);
                },
              onFailure: 
                function(transport) {
                  new Element.update('guidancenotice', transport.responseText.stripTags());
                }
              }
            );
    --></script>
  <br />
  <p><?php echo $COLLATE['languages']['selected']['DNSGuidance']; ?><br />
  <div style="margin-left: 25px;">
    <span id="dns"><?php echo $COLLATE['settings']['dns']; ?></span>
  </div>
  <br />
  </p>
  <script type="text/javascript"><!--
    new Ajax.InPlaceEditor('dns', '_settings.php?op=editdns',
            {
			clickToEditText: '<?php echo $COLLATE['languages']['selected']['ClicktoEdit']; ?>',
  	        highlightcolor: '#a5ddf8', 
            callback:
              function(form) {
                new Element.update('guidancenotice', '');
                return Form.serialize(form);
              },
            onFailure: 
              function(transport) {
                new Element.update('guidancenotice', transport.responseText.stripTags());
              }
            }
          );
  --></script>
  </div>

<?php
} // Ends form function

?>