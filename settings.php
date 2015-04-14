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
	<p><b><?php echo $COLLATE['languages']['selected']['DefaultLanguage']."</b> ";
    echo "<span id='language'>".$COLLATE['languages']['selected']['languagename']."</span>"; ?>
	<?php
	$language_js_array = '';
	foreach ($languages as $language){
	  $language_js_array .= "['".$language['isocode']."','".$language['languagename']."'],";
	}
    ?>
	<script type="text/javascript">
	new Ajax.InPlaceCollectionEditor(
      'language', '_settings.php?op=updatelanguage', { 
	  collection: [<?php echo $language_js_array; ?>],
	  highlightcolor: '#a5ddf8',
	  ajaxOptions: {method: 'get'}
	  }
    );
	</script>
	</p>
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
	  <input type="radio" name="perms" onchange="
		new Ajax.Updater(
			'authorizationnotice', '_settings.php?op=updateauthorization&amp;perms=0', 
			{
				onSuccess:
					function(){
						new Effect.Highlight('authorization0', { startcolor: '#a5ddf8'});
					}
			}
		); return false;" <?php 
	  echo (empty($checked1) ? "" : $checked1);
	  echo "/><span id=\"authorization0\">".$COLLATE['languages']['selected']['readonly']; ?></span></li>
	<li>
	  <input type="radio" name="perms" onchange="
	  new Ajax.Updater(
		'authorizationnotice', '_settings.php?op=updateauthorization&amp;perms=2',
		{
			onSuccess:
				function(response){
					new Effect.Highlight('authorization2', { startcolor: '#a5ddf8'});
				}
		}
	  ); return false;" <?php 
	  echo (empty($checked2) ? "" : $checked2);
	  echo "/><span id=\"authorization2\">".$COLLATE['languages']['selected']['ReserveIPs']; ?></span></li>
    <li>
	  <input type="radio" name="perms" onchange="
	  new Ajax.Updater(
		'authorizationnotice', '_settings.php?op=updateauthorization&amp;perms=3',
		{
			onSuccess:
				function(response){
					new Effect.Highlight('authorization3', { startcolor: '#a5ddf8'});
				}
		}
	  ); return false;" <?php 
	  echo (empty($checked3) ? "" : $checked3);
	  echo "/><span id=\"authorization3\">".$COLLATE['languages']['selected']['AllocateSubnets']; ?></span></li>
	<li>
	  <input type="radio" name="perms" onchange="
	  new Ajax.Updater(
		'authorizationnotice', '_settings.php?op=updateauthorization&amp;perms=4',
		{
			onSuccess:
				function(response){
					new Effect.Highlight('authorization4', { startcolor: '#a5ddf8'});
				}
		}
	  ); return false;" <?php 
	  echo (empty($checked4) ? "" : $checked4);
	  echo "/><span id=\"authorization4\">".$COLLATE['languages']['selected']['AllocateBlocks']; ?></span></li>
	<li>
	  <input type="radio" name="perms" onchange="
	  new Ajax.Updater(
		'authorizationnotice', '_settings.php?op=updateauthorization&amp;perms=5',
		{
			onSuccess:
				function(response){
					new Effect.Highlight('authorization5', { startcolor: '#a5ddf8'});
				}
		}
	  ); return false;" <?php 
	  echo (empty($checked5) ? "" : $checked5);
	  echo "/><span id=\"authorization5\">".$COLLATE['languages']['selected']['Admin']; ?></span></li>
	<li>
	  <input type="radio" name="perms" onchange="
	  new Ajax.Updater(
		'authorizationnotice', '_settings.php?op=updateauthorization&amp;perms=6',
		{
			onSuccess:
				function(response){
					new Effect.Highlight('authorization6', { startcolor: '#a5ddf8'});
				}
		}
	  ); return false;" <?php 
	  echo (empty($checked0) ? "" : $checked0);
	  echo "/><span id=\"authorization6\">".$COLLATE['languages']['selected']['noauthentication']; ?></span></li>
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
	    <input type="radio" name="auth_type" onchange="new Ajax.Updater(
		'authenticationnotice', '_settings.php?op=updateauthentication&amp;auth_type=db',
		{
			onSuccess:
				function(response){
					new Effect.Highlight('dbauth', { startcolor: '#a5ddf8'});
				}
		}
	  ); return false;" <?php 
	    echo ($COLLATE['settings']['auth_type'] == 'db') ? "checked=\"checked\"": ""; 
	    echo "/><span id=\"dbauth\">".$COLLATE['languages']['selected']['Database']; ?></span></li>
      <li>
	    <input type="radio" name="auth_type" onchange="new Ajax.Updater(
		'authenticationnotice', '_settings.php?op=updateauthentication&amp;auth_type=ldap',
		{
			onSuccess:
				function(response){
					new Effect.Highlight('ldapauth', { startcolor: '#a5ddf8'});
				}
		}
	  ); return false;" <?php 
	    echo ($COLLATE['settings']['auth_type'] == 'ldap') ? "checked=\"checked\"": ""; 
	    echo "/><span id=\"ldapauth\">".$COLLATE['languages']['selected']['LDAP']; ?></span></li>
    </ul>
  
	<table style="width: 90%">
	<tr>
	  <th style="width: 33%"><?php echo $COLLATE['languages']['selected']['Domain']; ?></th>
	  <th style="width: 33%"><?php echo $COLLATE['languages']['selected']['LDAPServer']; ?></th>
	  <td style="width: 33%"><a href="#" onclick="
	    new Element.update('authenticationnotice', '');
	    new Ajax.Updater({ success: 'ldap_servers', failure: 'authenticationnotice' }, '_settings.php?op=addldapserver', {onSuccess:function(){
		  new Ajax.Request('_settings.php?op=addldapserver&amp;javascript=true', {evalJS: 'force'});
		}}); return false;">
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
        new Ajax.Request('_settings.php?op=delete_ldap_server&amp;ldap_server_id=$id', {onSuccess:function(){ 
          new Effect.Fade('ldap_server_".$id."') 
        }}); 
      }; return false;\"
      ><img src=\"./images/remove.gif\" alt=\"X\" /></a></td></tr>\n";
      
      $javascript .=	  

         "  new Ajax.InPlaceEditorWithEmptyText('edit_domain_$id', '_settings.php?op=editldap&amp;object=domain&id=$id',
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
         "  new Ajax.InPlaceEditorWithEmptyText('edit_server_$id', '_settings.php?op=editldap&amp;object=server&id=$id',
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
	<p><a href="#" onclick="new Effect.toggle($('defaultdomaintip'),'appear'); return false;"><img src="images/help.gif" alt="[?]" /></a>
	  <b><?php echo $COLLATE['languages']['selected']['DefaultDomainName']; ?>:</b>
	  <span id="defaultdomain"><?php echo $COLLATE['settings']['domain']; ?></span>
    </p>
    <div style="display: none;" class="tip" id="defaultdomaintip"><p><?php echo $COLLATE['languages']['selected']['defaultdomaintip']; ?></p></div>
    <script type="text/javascript"><!--
      new Ajax.InPlaceEditorWithEmptyText('defaultdomain', '_settings.php?op=editdomain',
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
      
	<p><a href="#" onclick="new Effect.toggle($('passwdexpiretip'),'appear'); return false;"><img src="images/help.gif" alt="[?]" /></a>
	<b><?php echo $COLLATE['languages']['selected']['PasswordExpire']; ?></b>
	<span id='accountexpire'><?php echo $COLLATE['settings']['accountexpire']; ?></span>
	<script type="text/javascript">
	new Ajax.InPlaceCollectionEditor(
      'accountexpire', '_settings.php?op=updateaccountexpire', { 
	  collection: [0,30,45,60,90,120,180],
	  highlightcolor: '#a5ddf8',
	  ajaxOptions: {method: 'get'}
	  }
    );
	</script>
    </p>
    <div style="display: none;" class="tip" id="passwdexpiretip">
	  <p><?php echo $COLLATE['languages']['selected']['passwdexpiretip']; ?></p>
	</div>
      
	<p><a href="#" onclick="new Effect.toggle($('passwdlengthtip'),'appear'); return false;"><img src="images/help.gif" alt="[?]" /></a>
	<b><?php echo $COLLATE['languages']['selected']['MinPasswdLength']; ?></b>
	<span id='passwdlength'><?php echo $COLLATE['settings']['passwdlength']; ?></span>
	<script type="text/javascript">
	new Ajax.InPlaceCollectionEditor(
      'passwdlength', '_settings.php?op=updatepasswdlength', { 
	  collection: [5,6,7,8,9,10],
	  highlightcolor: '#a5ddf8',
	  ajaxOptions: {method: 'get'}
	  }
    );
	</script>
    </p>
    <div style="display: none;" class="tip" id="passwdlengthtip">
	  <p><?php echo $COLLATE['languages']['selected']['passwdlengthtip']; ?></p>
	</div>
      
	<p><a href="#" onclick="new Effect.toggle($('maxlogintip'),'appear'); return false;"><img src="images/help.gif" alt="[?]" /></a>
	<b><?php echo $COLLATE['languages']['selected']['MaxLoginAttempts']; ?></b>
	<span id='loginattempts'><?php echo $COLLATE['settings']['loginattempts']; ?></span>
	<script type="text/javascript">
	new Ajax.InPlaceCollectionEditor(
      'loginattempts', '_settings.php?op=updateloginattempts', { 
	  collection: [0,1,2,3,4,5,6,7,8,9],
	  highlightcolor: '#a5ddf8',
	  ajaxOptions: {method: 'get'}
	  }
    );
	</script>
    </p>
	<div style="display: none;" class="tip" id="maxlogintip">
	  <p><?php echo $COLLATE['languages']['selected']['maxlogintip']; ?></p>
	</div>
  </div>
  <br /><br />
  <?php
  /* This section is for later
  <h3><?php echo $COLLATE['languages']['selected']['APIKeys']; ?></h3>
  <hr />
  <div id="apinotice" class="tip"></div>
  <div style="margin-left: 20px;">
	<table style="width: 90%">
	<tr>
	  <th style="width: 30%"><?php echo $COLLATE['languages']['selected']['APIKeyDescript']; ?></th>
	  <th style="width: 15%"><?php echo $COLLATE['languages']['selected']['Status']; ?></th>
	  <th style="width: 30%"><?php echo $COLLATE['languages']['selected']['APIKey']; ?></th>
	  <td style="width: 25%"><a href="#" onclick="
	    new Element.update('apinotice', '');
	    new Ajax.Updater({ success: 'api_keys', failure: 'apinotice' }, '_settings.php?op=addapikey', {onSuccess:function(){
		  new Ajax.Request('_settings.php?op=addapikey&javascript=true', {evalJS: 'force'});
		}}); return false;"
	    <img src="./images/add.gif" alt="" /> <?php echo $COLLATE['languages']['selected']['AddAPIKey']; ?> </a></td>
	</tr>
	</table>
    <div id="api_keys">
	
	<?php
	$sql = "select description,active,apikey from `api-keys` order by description ASC";
	$result = mysql_query($sql);
	if(mysql_num_rows($result) == '0'){
	  echo $COLLATE['languages']['selected']['nokeysdefined'];
	}
	else{
	    echo "<table width=\"90%\">";
	$javascript='';
	while(list($apidescription,$apikeystatus,$apikey) = mysql_fetch_row($result)){
	  if($apikeystatus == '1'){
		$activechecked="selected=\"selected\"";
		$revokedchecked="";
	  }
	  else{
		$activechecked="";
		$revokedchecked="selected=\"selected\"";
	  }
	  echo "<tr id=\"api_key_$apikey\">".
	       "<td width=\"30%\"><span id=\"edit_key_$apikey\">$apidescription</span></td>".
		   "<td width=\"15%\"><select name=\"status\" onchange=\"
		    new Ajax.Updater('apinotice', '_settings.php?op=changeapikeystatus&apikey=$apikey&status=' + this.value); return false;\">".
		   "  <option value=\"active\" $activechecked>".$COLLATE['languages']['selected']['Active']."</option>".
		   "  <option value=\"revoked\" $revokedchecked>".$COLLATE['languages']['selected']['Revoked']."</option></select></td>".
		   "<td width=\"30%\">$apikey</td>".
		   "<td width=\"25%\"><a href=\"#\" onclick=\"
      if (confirm('".$COLLATE['languages']['selected']['confirmdelete']."')) { 
        new Element.update('apinotice', ''); 
        new Ajax.Updater('apinotice', '_settings.php?op=delete_api_key&apikey=$apikey', {onSuccess:function(){ 
          new Effect.Fade('api_key_$apikey') 
        }}); 
      }; return false;\"
      ><img src=\"./images/remove.gif\" alt=\"X\" /></a></td></tr>\n";
      
      $javascript .=	  

         "  new Ajax.InPlaceEditorWithEmptyText('edit_key_$apikey', '_settings.php?op=editapidescript&apikey=$apikey',
              {
			    clickToEditText: '".$COLLATE['languages']['selected']['ClicktoEdit']."',
			    highlightcolor: '#a5ddf8', 
                callback:
                  function(form) {
                    new Element.update('apinotice', '');
                    return Form.serialize(form);
                  },
                onFailure: 
                  function(transport) {
                    new Element.update('apinotice', transport.responseText.stripTags());
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
  </div>
  <br /><br />
  */ ?>
	
  <h3><?php echo $COLLATE['languages']['selected']['UserGuidance']; ?></h3>
  <hr />
  <div id="guidancenotice" class="tip"></div>
  <div style="margin-left: 25px;">
  <p><?php echo $COLLATE['languages']['selected']['DefaultIPGuidance']; ?></p>
  <div style="margin-left: 25px;">
    <pre><span id="guidance"><?php echo $COLLATE['settings']['guidance']; ?></span></pre>
  </div>
  <script type="text/javascript"><!--
      new Ajax.InPlaceEditorWithEmptyText('guidance', '_settings.php?op=editguidance',
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

  <script type="text/javascript"><!--
    new Ajax.InPlaceEditorWithEmptyText('dns', '_settings.php?op=editdns',
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