<?php
require_once('./include/common.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op) { 
  case "changepasswd";
  change_password();
  break;
  
  case "logout";
  cn_logout();
  break;
  
  default:
  cn_login();
  break;
}

function change_password(){
  global $COLLATE;
  include 'include/validation_functions.php';
  
  if(isset($_GET['returnto'])){
    $returnto = urldecode($_GET['returnto']);
  }
  else{
    $returnto = "";
  }
  if(isset($_GET['action'])){
    $action = $_GET['action'];
  }
  else{
    $action = "show form";
  }
  
  if($action != "change"){
    require_once('./include/header.php');
    
    echo "<h1>".$COLLATE['languages']['selected']['changeyourpassword']."</h1>\n".
         "<br />";
    ?>
    <div style="float: left; width: 28%;">
    <form action="login.php?op=changepasswd&amp;action=change&amp;returnto=<?php echo urlencode($returnto); ?>" method="post">
    <p><b><?php echo $COLLATE['languages']['selected']['newpassword'] ?></b><br />
    <input name="password" type="password" size="15" /></p>
    <p><b><?php echo $COLLATE['languages']['selected']['confirmpassword'] ?></b><br />
    <input name="confirm" type="password" size="15" /></p>  
    <p><input type="submit" value=" <?php echo $COLLATE['languages']['selected']['Go'] ?> " /></p>
    </form>
    </div>
    <script type="text/javascript">
    window.onload = function() {
      setTimeout("document.forms[0].passwd.focus()",1);
    }
    </script>
    <?php
    
    
    echo "<p style=\"clear: left;\">";
    require_once('./include/footer.php');
    exit();
  }

  $username = $COLLATE['user']['username'];
  $password = $_POST['password'];
  $confirm = $_POST['confirm'];
  
  if($confirm != $password){
    $notice = "passwdmatch-notice";
    $returnto = urlencode($returnto);
	header("Location: login.php?op=changepasswd&notice=$notice&returnto=$returnto");
	exit();
  }
  
  if(strlen($password) < $COLLATE['settings']['passwdlength']){
    $notice = "shortpasswd-notice";
    $returnto = urlencode($returnto);
	header("Location: login.php?op=changepasswd&notice=$notice&returnto=$returnto");
	exit();
  }
 
  $auth = auth($username, $password);
  
  if($auth == 'ldap'){
    $notice = "ldappasswd-notice";
    $returnto = urlencode($returnto);
	header("Location: index.php?notice=$notice");
	exit();
  }
  
  $password = sha1(clean($password));
  
  if($auth != FALSE){
    $notice = "oldpasswd-notice";
    $returnto = urlencode($returnto);
	header("Location: login.php?op=changepasswd&notice=$notice&returnto=$returnto");
	exit();
  }
  
  if($COLLATE['settings']['accountexpire'] != "0"){
    $then = $COLLATE['settings']['accountexpire']; // Get number of days from settings
    $expireat = strtotime("+$then days"); // strtotime is awesome!
	$expireat = date("Y-m-d H:i:s", $expireat); // Format the result to match MySQL's datetime format. 
  }
  else{
    $expireat = "0000-00-00 00:00:00";
  }
  $sql = "UPDATE users SET passwd='$password', tmppasswd=NULL, loginattempts='0', passwdexpire='$expireat' WHERE username='$username'";
  mysql_query($sql);
  
  $level = "5";
  $message = "Password changed: $username";
  collate_log($level, $message);
  
  $notice = "passwdchange-notice";
  if(stristr($returnto, "?") == TRUE){ 
      $sep= "&"; 
    } 
    else {
      $sep = "?";
    }
  
  $returnto .= $sep."notice=".$notice;
  
  if(stristr($returnto, ".php") == TRUE){
    header("Location: $returnto");
	exit();
  }
  else{
    header("Location: index.php?notice=$notice");
	exit();
  }
  
  

} // Ends change_password function

function cn_logout(){
 // Taken straight from php.net/session_destroy in Example 1.
   // Unset all of the session variables.
  $_SESSION = array();

  // If it's desired to kill the session, also delete the session cookie.
  // Note: This will destroy the session, and not just the session data!
  if (isset($_COOKIE[session_name()])) {
     setcookie(session_name(), '', time()-42000, '/');
  }

  // Finally, destroy the session.
  session_destroy();
  $notice = "logout-notice";
  header("Location: index.php?notice=$notice");
  exit();
} // Ends cn_logout function

function cn_login() {
  global $COLLATE;
  include 'include/validation_functions.php';
  
  $action = (empty($_GET['action'])) ? 'show form' : $_GET['action'];
  
  $returnto = (empty($_GET['returnto'])) ? '' : $_GET['returnto'];
    
  if(isset($COLLATE['user']['username'])) { // The user is already logged in
    $notice = "alreadyloggedin-notice";
	header("Location: index.php?notice=$notice");
	exit();
  }
  
  if($action != "login") {
    require_once('./include/header.php');
    
	echo "<h1>".$COLLATE['languages']['selected']['Login']."</h1>\n".
         "<br />";
	?>
    <div style="float: left; width: 28%;">
    <form action="login.php?op=login&amp;action=login&amp;returnto=<?php echo urlencode($returnto); ?>" method="post">
    <p><b><?php echo $COLLATE['languages']['selected']['Username']; ?>:</b><br />
    <input name="username" type="text" size="15" /></p>
    <p><b><?php echo $COLLATE['languages']['selected']['Password']; ?>:</b><br />
    <input name="password" type="password" size="15" /></p>  
    <p><input type="submit" value=" <?php echo $COLLATE['languages']['selected']['Go']; ?> " /></p>
    </form>
    </div>
    <script type="text/javascript">
	    window.onload = function() {
	  	setTimeout("document.forms[0].username.focus()",1);
	    }
    </script>
    <?php
  if($COLLATE['settings']['auth_type'] != 'db'){
      echo "<div id=\"helper\" style=\"float: left; width: 70%; padding-left: 10px; border-left: 1px solid #000;\">\n".
           "<p><b>".$COLLATE['languages']['selected']['Note'].":</b><br />\n".
           $COLLATE['languages']['selected']['ldapformatnote']."</p>\n";
           
      if(!empty($COLLATE['settings']['domain'])){
        echo "<p>".$COLLATE['languages']['selected']['domainnote']."</p>";
      }
      else{
        echo "<p>".$COLLATE['languages']['selected']['nodomainnote']."</p>\n";
      }
      
      echo "</div>";
  }
    
    echo "<p style=\"clear: left;\">";
    
    require_once('./include/footer.php');
    exit();
  }
  
  $username = clean($_POST['username']);
  $password = clean($_POST['password']);
  
  if(strlen($username) < "4"){
    $notice = "shortusername-notice";
	$returnto = urlencode($returnto);
    header("Location: login.php?notice=$notice&returnto=$returnto");
	exit();
  }
  
  $auth = auth($username, $password);
     
  if($auth == 'ldap'){
    $auth = ldap_auth($username,$password);
	$authtype = 'ldap';
  }
     
  if($auth == FALSE){
    $level = "5";
	$message = "authentication failed: $username";
	collate_log($level, $message);
    $sql = "UPDATE users SET loginattempts=loginattempts+1 WHERE username='$username'";
	mysql_query($sql);
    $notice = "failedlogin-notice";
    $returnto = urlencode($returnto);
	header("Location: login.php?notice=$notice&returnto=$returnto");
	exit();
  }
  
  if($auth == "locked"){
    $level = "5";
	$message = "user account locked: $username";
	collate_log($level, $message);
    $notice = "lockedaccount-notice";
	header("Location: login.php?notice=$notice");
	exit();
  }

  // If they have gotten this far, they entered a correct pair of username and password.
  $now = date('Y-m-d H:i:s');
  $_SESSION['username'] = $username;
  $_SESSION['accesslevel'] = $auth['accesslevel'];
  $_SESSION['language'] = $auth['language'];
  $_SESSION['ldapexempt'] = $auth['ldapexempt'];
  
  $sql = "UPDATE users SET loginattempts='0' WHERE username='$username'";
  mysql_query($sql);
  $sql = "UPDATE users SET last_login_at=NOW() WHERE username='$username'";
  mysql_query($sql);
  
  if($auth['passwdexpire'] < $now && $auth['passwdexpire'] != '0000-00-00 00:00:00' || isset($auth['tmppasswd'])){
    $returnto = urlencode($returnto);
	$notice = "passwdexpired-notice";
    header("Location: login.php?op=changepasswd&returnto=$returnto&notice=$notice");
	exit();
  }
  
  if($authtype == 'ldap'){
    $_SESSION['auth_type'] = 'ldap';
  }
  
  $notice = "loginsuccess-notice";
  if(stristr($returnto, "?") == TRUE){ 
      $sep= "&"; 
    } 
    else {
      $sep = "?";
    }
  
  $returnto .= $sep."notice=".$notice;
  
  if(stristr($returnto, ".php") == TRUE){
    header("Location: $returnto");
	exit();
  }
  else{
    header("Location: index.php?notice=$notice");
	exit();
  }
} // Ends cn_login function

function auth($username, $password){
  global $COLLATE;
  $password = sha1($password);
  
  $sql = "SELECT passwd, tmppasswd, accesslevel, loginattempts, passwdexpire,ldapexempt, language FROM users WHERE username='$username'";
  $row = mysql_query($sql);
  if(mysql_num_rows($row) != "1"){
    return FALSE;    
  }
  
  list($passwd,$tmppasswd,$accesslevel,$loginattempts,$passwdexpire,$ldapexempt,$language) = mysql_fetch_row($row);
  
  if($COLLATE['settings']['auth_type'] == 'ldap' && $ldapexempt == false){
    return "ldap";
  }
  
  if($loginattempts >= $COLLATE['settings']['loginattempts']){
    return "locked";
  }
  
  if($password != $passwd && $password != $tmppasswd) {
    return FALSE;
  }
  
  // If we've gotten this far, a good username, password combination has been supplied.
  if($password === $tmppasswd){
    $auth['tmppasswd'] = $password;
  }
  
  $auth['accesslevel'] = $accesslevel;
  $auth['loginattempts'] = $loginattempts;
  $auth['passwdexpire'] = $passwdexpire;
  $auth['language'] = $language;
  $auth['ldapexempt'] = $ldapexempt;
  
  return $auth;
} // Ends auth function

function ldap_auth($username, $password){
  global $COLLATE;  
  // First make sure that they are a valid application user, then check their password in the Directory.
  $sql = "SELECT accesslevel, loginattempts FROM users WHERE username='$username'";
  $row = mysql_query($sql);
  if(mysql_num_rows($row) != "1"){
    return FALSE;    
  }
  
  list($accesslevel,$loginattempts) = mysql_fetch_row($row);
  
  if($loginattempts >= $COLLATE['settings']['loginattempts']){
    return "locked";
  }
  
  $username = utf8_encode($username);
  $password = utf8_encode($password);
 
  // Find domain if there is one -- If no domain exists, return error
  if(!strstr($username, "@")){
    $username .= "@".$COLLATE['settings']['domain'];
  }
  
  // Tokenize based on @ sign. Second token is domain name. Look up domain name in database to find ldap server
  $nothing = strtok($username, "@"); // Dump first token...sloppy don't care right now.
  $domain = strtok("@");
  
  if(empty($domain)){
    $domain = $COLLATE['settings']['domain'];
  }
  
  $sql = "SELECT domain, server FROM `ldap-servers` WHERE domain='$domain'";
  $result = mysql_query($sql);
  if(mysql_num_rows($result) != '1'){
    return FALSE;
  }
  list($domain,$ldap_server) = mysql_fetch_row($result);
  	
  // connect to ldap server
  $ldapconn = ldap_connect($ldap_server)
    or die("Could not connect to LDAP server.");
	
  if ($ldapconn) {
    // binding to ldap server
    $ldapbind = ldap_bind($ldapconn, $username, $password);
	
    // verify binding
    if (!$ldapbind) {
      $auth = false;
    }
	else{
	  $auth = array();
	  $auth['accesslevel'] = $accesslevel;
	  $auth['passwdexpire'] = '0000-00-00 00:00:00';
	}
    return $auth;
  }
  
} // Ends ad_auth function
?>
