<?php

require_once('include/common.php');
$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

AccessControl('5', null, false); # null means no log, false means don't redirect

switch($op){

    case "updatelanguage";
	update_language();
	break;

    case "updateauthorization";
	update_authorization();
	break;

	case "updateauthentication";
	update_authentication();
	break;
	
	case "addldapserver";
	add_ldap_server();
	break;
	
	case "delete_ldap_server";
	delete_ldap_server();
	break;
	
	case "editldap";
	edit_ldap();
	break;
	
	case "editdomain";
	edit_domain();
	break;
	
	case "updateaccountexpire";
	update_accountexpire();
	break;
	
	case "updatepasswdlength";
	update_passwdlength();
	break;
	
	case "updateloginattempts";
	update_loginattempts();
	break;
	
	case "editguidance";
	edit_guidance();
	break;
	
	case "editdns";
	edit_dns();
	break;
	
	case "addapikey";
	add_api_key();
	break;
	
	case "delete_api_key";
	delete_api_key();
	break;
	
	case "editapidescript";
	edit_api_key_description();
	break;
	
	case "changeapikeystatus";
	change_api_key_status();
	break;
	
	default:
	exit();
}

function update_language(){
  global $COLLATE;
  $dbo = getdbo();
  $language = (isset($_GET['value'])) ? $_GET['value'] : '';
  
  foreach (glob("languages/*.php") as $filename){
    include $filename;
  }
  if(!isset($languages[$language]['isocode']) || $language != $languages[$language]['isocode'] || empty($language)){
    header("HTTP/1.1 400 Bad Request");
	exit(); 
  } 

  if($language == $COLLATE['settings']['language']){ 
    echo $languages[$language]['languagename'];
	exit();
  }
  
  $message = "Settings Updated: default language changed from ".$COLLATE['languages']['selected']['languagename']." to ".$languages[$language]['languagename'];
  collate_log('5', $message);
  
  $sql = "update settings set value='$language' where name='language'";
  $dbo -> query($sql);
  echo $languages[$language]['languagename'];
  exit();

}

function update_authorization(){
  global $COLLATE;
  $dbo = getdbo();
  
  if (preg_match('/^[023456]{1}$/', $_GET['perms'])){
    $perms = $_GET['perms'];
  }
  else {
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected']['invalidrequest'];
	exit();
  }
  if($COLLATE['settings']['perms'] == $perms){ exit(); }
  
  // We need to make sure there is at least one administrator user 
  // so we know they don't lock themselves out of the application
  $sql = "SELECT id FROM users WHERE accesslevel='5'";
  $result = $dbo -> query($sql);
  if($result -> rowCount() < '1'){
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected']['needadmin'];
    exit();
  }
  $sql = "UPDATE settings SET value='$perms' WHERE name='perms'";
  $dbo -> query($sql);
  echo ""; # this clears the notification div
  collate_log('5', "Settings Updated: authorization level changed");
  exit();
}

function update_authentication(){
  global $COLLATE;
  $dbo = getdbo();
  
  if (preg_match('/db|ldap/', $_GET['auth_type'])){
    $auth_type = $_GET['auth_type'];
  }
  else {
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected']['invalidrequest'];
	exit();
  }
  
  if($COLLATE['settings']['auth_type'] != $auth_type) {
    if($auth_type == 'ldap' && !function_exists('ldap_connect')){
	  header("HTTP/1.1 400 Bad Request");
      echo $COLLATE['languages']['selected']['noldapsupport'];
      exit();
	}
	else{
      $sql = "UPDATE settings SET value='$auth_type' WHERE name='auth_type'";
	  $dbo -> query($sql);
      exit();
	}
  echo $COLLATE['languages']['selected']['nosettingupdated'];
  collate_log('5', "Settings Updated: default authentication method set to $auth_type");
  exit();
  }
}

function add_ldap_server(){
  global $COLLATE;
  $dbo = getdbo();
  
  # We either output the HTML table or the javascript for in place editing depending on this GET variable
  $outputjavascript = (isset($_GET['javascript']) && $_GET['javascript'] == 'true') ? true : false;
  
  if(!$outputjavascript) {
    $sql = "INSERT INTO `ldap-servers` (domain, server) VALUES ('example.com', '127.0.0.1')";
    $dbo -> query($sql);
	collate_log('5', "Settings Updated: LDAP server added");
  }
  
  $sql = "select id,domain,server from `ldap-servers` order by domain ASC";
 
  $result = $dbo -> query($sql);
  if($result -> rowCount() != '0'){
    if(!$outputjavascript){ echo "<table width=\"90%\">"; }
	$javascript='';
	while(list($id,$domain,$server) = $result -> fetch(PDO::FETCH_NUM)){
	  if(!$outputjavascript){
	    echo "<tr id=\"ldap_server_$id\"><td width=\"33%\"><span id=\"edit_domain_$id\">$domain</span></td><td width=\"33%\"><span id=\"edit_server_$id\">$server</span></td><td width=\"33%\"><a href=\"#\" onclick=\"
             if (confirm('".$COLLATE['languages']['selected']['confirmdelete']."')) { 
               new Element.update('authenticationnotice', ''); 
               new Ajax.Updater('authenticationnotice', '_settings.php?op=delete_ldap_server&ldap_server_id=$id', {onSuccess:function(){ 
                 new Effect.Fade('ldap_server_".$id."') 
               }}); 
             }; return false;\"
              ><img src=\"./images/remove.gif\" alt=\"X\" /></a></td></tr>\n";
	  }
	  else {
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
	}
	if(!$outputjavascript){ 
	  echo "</table>";
      exit();
	}
	else {
	  header("Content-type: text/javascript");
	  echo $javascript;
      exit();
	}
  }
  exit();
}

function delete_ldap_server(){
  global $COLLATE;
  $dbo = getdbo();
  
  $server_id = (isset($_GET['ldap_server_id']) && is_numeric($_GET['ldap_server_id'])) ? $_GET['ldap_server_id'] : '';  
  if(empty($server_id)){
    header("HTTP/1.1 400 Bad Request");
	exit();
  }
  
  $sql = "SELECT domain, server FROM `ldap-servers` WHERE id='$server_id'";
  $result = $dbo -> query($sql);
	
  if($result -> rowCount() != '1'){
    header("HTTP/1.1 400 Bad Request");
	exit();
  }
  
  list($domain,$server) = $result -> fetch(PDO::FETCH_NUM);

  $sql = "DELETE FROM `ldap-servers` WHERE id='$server_id' LIMIT 1";
  $dbo -> query($sql);
  
  $message=str_replace("%server%", "$server", $COLLATE['languages']['selected']['ldapdeleted']);
  $message=str_replace("%domain%", "$domain", $message);
  collate_log('5', "$message");
  exit();
  
} // Ends delete_block function

function edit_ldap(){
  global $COLLATE;
  $dbo = getdbo();
  include 'include/validation_functions.php';
  
  $id = (isset($_GET['id']) && is_numeric($_GET['id'])) ? $_GET['id'] : '';
  $object = (isset($_GET['object']) && ($_GET['object'] === 'domain' || $_GET['object'] === 'server')) ? $_GET['object'] : '';
  $value = (isset($_POST['value'])) ? $_POST['value'] : '';
  
  if(empty($id) || empty($object) || empty($value)){
    header("HTTP/1.1 400 Bad Request");
	echo "$id, $object, $value";
	exit();
    echo $COLLATE['languages']['selected']['invalidrequest'];
	exit();
  }
    
  if($object == 'server' && ip2decimal($value) === false){
    echo $COLLATE['languages']['selected']['invalidip'];
	exit();
  }
  
  if($object == 'domain'){
    $return = validate_text($value,'domain');
    if($return['0'] === false){
      header("HTTP/1.1 400 Bad Request");
      echo $COLLATE['languages']['selected'][$return['error']];
    exit();
    }
  }

  $sql = "select count(*) from `ldap-servers` where id='$id'";
  $result = $dbo -> query($sql);
  if($result -> fetchColumn() != '1'){
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected']['invalidrequest'];
	exit();
  }
    
  $sql = "update `ldap-servers` set $object='$value' where id='$id'";
  $result = $dbo -> query($sql);
  echo $value;
  collate_log('5', "Settings Updated: LDAP server entry modified");
  exit();  
}

function edit_domain(){
  global $COLLATE;
  $dbo = getdbo();
  include 'include/validation_functions.php';
  
  $value = (isset($_POST['value'])) ? $_POST['value'] : '';
  
  $return = validate_text($value,'domain');
  if($return['0'] === false){
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected'][$return['error']];
	exit();
  }
  
  if($COLLATE['settings']['domain'] == $value){ 
    echo $value;
	exit(); 
  }

  $message = "Settings Updated: default domain changed from ".$COLLATE['settings']['domain']." to $value";
  collate_log('5', $message);
  
  $sql = "update settings set value='$value' where name='domain'";
  $dbo -> query($sql);
  echo $value;  
  exit();
}

function update_accountexpire(){
  global $COLLATE;
  $dbo = getdbo();
  $accountexpire = (isset($_GET['value'])) ? $_GET['value'] : '';
  if($accountexpire != "0" && 
     $accountexpire != "30" &&
     $accountexpire != "45" &&
     $accountexpire != "60" &&
     $accountexpire != "90" &&
     $accountexpire != "120" &&
	 $accountexpire != "180"){
	header("HTTP/1.1 400 Bad Request");
	exit(); 
  }
  
  if($accountexpire == $COLLATE['settings']['accountexpire']){ 
    echo $accountexpire;
	exit();
  }
  
  $message = "Settings Updated: password expiration changed from ".$COLLATE['settings']['accountexpire']." to $accountexpire days";
  collate_log('5', $message);
  
  $sql = "update settings set value='$accountexpire' where name='accountexpire'";
  $dbo -> query($sql);
  echo $accountexpire;
  exit();
}

function update_passwdlength(){
  global $COLLATE;
  $dbo = getdbo();
  $passwdlength = (isset($_GET['value'])) ? $_GET['value'] : '';
  if($passwdlength != "5" && 
     $passwdlength != "6" &&
     $passwdlength != "7" &&
     $passwdlength != "8" &&
     $passwdlength != "9" &&
     $passwdlength != "10"){
	header("HTTP/1.1 400 Bad Request");
	exit();
  }
  
  if($passwdlength == $COLLATE['settings']['passwdlength']){
    echo $passwdlength;
	exit();
  }
  
  $message = "Settings Updated: minimum password length changed from ".
             $COLLATE['settings']['passwdlength']." to $passwdlength characters";
  collate_log('5', $message);
  
  $sql = "update settings set value='$passwdlength' where name='passwdlength'";
  $dbo -> query($sql);
  echo $passwdlength;
  exit();
}

function update_loginattempts(){
  global $COLLATE;
  $dbo = getdbo();
  $loginattempts = (isset($_GET['value'])) ? $_GET['value'] : '';
  if($loginattempts != "0" && 
     $loginattempts != "1" &&
     $loginattempts != "2" &&
     $loginattempts != "3" &&
     $loginattempts != "4" &&
     $loginattempts != "5" &&
     $loginattempts != "6" &&
     $loginattempts != "7" &&
     $loginattempts != "8" &&
	 $loginattempts != "9"){
	header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected']['invalidrequest'];
	exit(); 
  }
  
  if($loginattempts == $COLLATE['settings']['loginattempts']){
    echo $loginattempts;
	exit();
  }
  
  $message = "Settings Updated: maximum login attempts changed from ".$COLLATE['settings']['loginattempts']." to $loginattempts";
  collate_log('5', $message);
  
  $sql = "update settings set value='$loginattempts' where name='loginattempts'";
  $dbo -> query($sql);
  echo $loginattempts;
  exit();
}

function edit_guidance(){
  global $COLLATE;
  $dbo = getdbo();
  include 'include/validation_functions.php';
  
  $value = (isset($_POST['value'])) ? $_POST['value'] : '';
  
  $return = validate_text($value,'guidance');
  if($return['0'] === false){
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected'][$return['error']];
	exit();
  }
  else{
    $value = $return['1'];
  }
    
  $sql = "update `settings` set value='$value' where name='guidance'";
  $result = $dbo -> query($sql);
  echo $value;
  collate_log('5', "Settings Updated: default IP guidance modified");
  exit();
}

function edit_dns(){
  global $COLLATE;
  $dbo = getdbo();
  include 'include/validation_functions.php';
  
  $value = (isset($_POST['value'])) ? $_POST['value'] : '';
  
  $return = validate_text($value,'dnshelper');
  if($return['0'] === false){
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected'][$return['error']];
	exit();
  }
  else{
    $value = $return['1'];
  }
    
  $sql = "update `settings` set value='$value' where name='dns'";
  $result = $dbo -> query($sql);
  echo $value;
  collate_log('5', "Settings Updated: DNS server guidance modified");
  exit();
}

function add_api_key(){
  global $COLLATE;
  $dbo = getdbo();
  
  # We either output the HTML table or the javascript for in place editing depending on this GET variable
  $outputjavascript = (isset($_GET['javascript']) && $_GET['javascript'] == 'true') ? true : false;
  
  
  if(!$outputjavascript) {
    $keygenerated = false;
	$tries='0';
	while($keygenerated === false && $tries < '3'){
	  $newapikey = substr(str_shuffle('abcefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'), '0', '21');
      $sql = "INSERT INTO `api-keys` (description, apikey) VALUES ('example description', '$newapikey')";
      $result = $dbo -> query($sql);
	  if($result -> rowCount() == '1'){ // This should fail and allow retries if we somehow generate a duplicate key
	    $keygenerated = true;
	  }
	  $tries++;
	}
	collate_log('5', "Settings Updated: A new API key has been generated!");
  }
  $sql = "select description,active,apikey from `api-keys` order by description ASC";
  $result = $dbo -> query($sql);
  if(!$outputjavascript){	
	if($result -> rowCount() == '0'){
	  echo $COLLATE['languages']['selected']['nokeysdefined'];
	}
	else{
	    echo "<table width=\"90%\">";
    }
  }
  $javascript='';
  while(list($apidescription,$apikeystatus,$apikey) = $result -> fetch(PDO::FETCH_NUM)){
	if(!$outputjavascript){
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
    }
    else {	
      $javascript .=	  

         "  new Ajax.InPlaceEditor('edit_key_$apikey', '_settings.php?op=editapidescript&apikey=$apikey',
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
  }
  if(!$outputjavascript){ 
    echo "</table>";
       exit();
  }
  else {
    header("Content-type: text/javascript");
    echo $javascript;
       exit();
  }
  exit();
}

function delete_api_key(){
  global $COLLATE;
  $dbo = getdbo();
  include 'include/validation_functions.php';
  
  $apikey = (isset($_GET['apikey'])) ? $_GET['apikey'] : '';

  $return = validate_api_key($apikey);
  if($return['0'] === false){
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected'][$return['error']];
	exit();
  }
  else{
    $keydescription = $return['description'];
  }

  $sql = "DELETE FROM `api-keys` WHERE apikey='$apikey' LIMIT 1";
  $dbo -> query($sql);
  
  echo $COLLATE['languages']['selected']['APIkeydeleted'];
  collate_log('5', "Settings Updated: API key with description \"$keydescription\" deleted!");
  exit();
  
} // Ends delete_block function

function edit_api_key_description(){
  global $COLLATE;
  $dbo = getdbo();
  include 'include/validation_functions.php';
  
  $apikey = (isset($_GET['apikey'])) ? $_GET['apikey'] : '';
  $value = (isset($_POST['value'])) ? $_POST['value'] : '';
  
  $return = validate_api_key($apikey);
  if($return['0'] === false){
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected'][$return['error']];
	exit();
  }
  else{
    $old_description = $return['description'];
  }
  
  $return = validate_text($value,'apidescription');
  if($return['0'] === false){
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected'][$return['error']];
	exit();
  }
  else{
    $value = $return['1'];
  }
       
  $sql = "update `api-keys` set description='$value' where apikey='$apikey'";
  $result = $dbo -> query($sql);
  echo $value;
  collate_log('5', "Settings Updated: API key description changed from \"$old_description\" to \"$value\"");
  exit();  
}

function change_api_key_status(){
  global $COLLATE;
  $dbo = getdbo();
  include 'include/validation_functions.php';
  
  $apikey = (isset($_GET['apikey'])) ? $_GET['apikey'] : '';
  $status = (isset($_GET['status'])) ? $_GET['status'] : '';
  
  if(empty($apikey) || empty($status) || !preg_match("/active|revoked/", $status)){
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected']['invalidrequest'];
	exit();
  }
  
  $return = validate_api_key($apikey);
  if($return['0'] === false){
    header("HTTP/1.1 400 Bad Request");
    echo $COLLATE['languages']['selected'][$return['error']];
	exit();
  }
  else{
    $description = $return['description'];
	$old_status = $return['active'];
  }
  
  $status = ($status == 'active') ? true : false;
  $status_action = ($status === true) ? "activated" : "revoked";
  $message = ($status === true) ? $COLLATE['languages']['selected']['keyactivated'] : $COLLATE['languages']['selected']['keyrevoked'];
    
  if($status === $old_status){ exit(); }
  
  $sql = "update `api-keys` set active='$status' where apikey='$apikey'";
  $dbo -> query($sql);
  
  collate_log('5', "Settings Updated: API key with description \"$description\" has been $status_action");
  echo $message;
  exit();
  
  
}
?>