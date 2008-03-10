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
  session_destroy();
  session_start();
  global $COLLATE;
  
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
  ?>
  <h1>Change Your Password</h1>
  <br />
  <form action="login.php?op=changepasswd&amp;action=change&amp;returnto=<?php echo urlencode($returnto); ?>" method="post">
  <p><b>Username:</b><br />
  <input name="username" type="text" size="15" /></p>
  <p><b>Current Password:</b><br />
  <input name="passwd" type="password" size="15" /></p>
  <p><b>New Password:</b><br />
  <input name="password" type="password" size="15" /></p>
  <p><b>Confirm Password:</b><br />
  <input name="confirm" type="password" size="15" /></p>  
  <p><input type="submit" value=" Go " /></p>
  </form>
  
  <?php
  require_once('./include/footer.php');
  exit();
  }
  
  $username = $_POST['username'];
  $passwd = $_POST['passwd'];
  $password = $_POST['password'];
  $confirm = $_POST['confirm'];
  
  if($confirm != $password){
    $notice = "The new password and confirmation password you have entered do not match. Please try again.";
    $returnto = urlencode($returnto);
	header("Location: login.php?op=changepasswd&notice=$notice&returnto=$returnto");
	exit();
  }
  
  if(strlen($password) < $COLLATE['settings']['passwdlength']){
    $notice = "The new password you have entered is less than the minimum password length required by your administrator.".
	          "Please try again.";
    $returnto = urlencode($returnto);
	header("Location: login.php?op=changepasswd&notice=$notice&returnto=$returnto");
	exit();
  }
  
  $auth = auth($username, $passwd);
  
  if($auth == 'ldap'){
    $notice = "Your administrator has configured your account for LDAP authentication. You cannot change your password using the Change Password form. Please contact your administrator if you need assistance changing your password.";
    $returnto = urlencode($returnto);
	header("Location: index.php?notice=$notice");
	exit();
  }
  
  $password = sha1(clean($password));
  
  if($auth == FALSE){
    $level = "5";
	$message = "authentication failed: $username";
	collate_log($level, $message);
    $notice = "The username and/or password you have entered are invalid. Please note ".
              "that failed login attempts are logged.";
    $returnto = urlencode($returnto);
	header("Location: login.php?op=changepasswd&notice=$notice&returnto=$returnto");
	exit();
  
  }elseif($auth == "locked"){
    $level = "5";
	$message = "user account locked: $username";
	collate_log($level, $message);
    $notice = "This username has been locked because there have been too many failed attempts to login. You must contact your administrator to have a new temporary password set.";
	header("Location: login.php?notice=$notice");
	exit();
  
  }elseif($password == $auth['passwd']){
    $notice = "You have not supplied a new password. Please try again.";
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
  $message = "password changed: $username";
  collate_log($level, $message);
  
  // Password Change Successful:
  $_SESSION['username'] = $username;
  $_SESSION['accesslevel'] = $auth['accesslevel'];
  session_write_close();

  $notice = "You have successfully changed your password.";
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
  $notice = "You have successfully been logged out.";
  header("Location: index.php?notice=$notice");
  exit();
} // Ends cn_logout function

function cn_login() {
  global $COLLATE;
  
  $action = (empty($_GET['action'])) ? 'show form' : $_GET['action'];
  
  $returnto = (empty($_GET['returnto'])) ? '' : $_GET['returnto'];
    
  if(isset($_SESSION['username'])) { // The user is already logged in
    $notice = "You are already logged in as ".$COLLATE['user']['username'].".";
	header("Location: index.php?notice=$notice");
	exit();
  }
  
  if($action != "login") {
  require_once('./include/header.php');
  ?>
  <h1>Login</h1>
  <br />
  <form action="login.php?op=login&amp;action=login&amp;returnto=<?php echo urlencode($returnto); ?>" method="post">
  <p><b>Username:</b><br />
  <input name="username" type="text" size="15" /></p>
  <p><b>Password:</b><br />
  <input name="password" type="password" size="15" /></p>  
  <p><input type="submit" value=" Go " /></p>
  </form>
  <?php
  require_once('./include/footer.php');
  exit();
  }
  
  $username = clean($_POST['username']);
  $password = clean($_POST['password']);
  
  if(strlen($username) < "4" || strlen($password) < "3"){
    $notice = "The username and/or password you have entered is not long enough to be valid.";
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
    $notice = "The username and/or password you have entered are invalid. Please note that failed login attempts are logged.";
    $returnto = urlencode($returnto);
	header("Location: login.php?notice=$notice&returnto=$returnto");
	exit();
  }
  
  if($auth == "locked"){
    $level = "5";
	$message = "user account locked: $username";
	collate_log($level, $message);
    $notice = "This username has been locked because there have been too many failed attempts to login. You must contact your administrator to have a new temporary password set.";
	header("Location: login.php?notice=$notice");
	exit();
  }

  // If they have gotten this far, they entered a correct pair of username and password.
  $now = date('Y-m-d H:i:s');
  
  if($auth['passwdexpire'] < $now && $auth['passwdexpire'] != '0000-00-00 00:00:00' || isset($auth['tmppasswd'])){
    $returnto = urlencode($returnto);
	$notice = "Your password has expired. You are required by your administrator to change your password before continuing.";
    header("Location: login.php?op=changepasswd&username=$username&returnto=$returnto&notice=$notice");
	exit();
  }
  else{ // Normal successful login.
    $sql = "UPDATE users SET loginattempts='0' WHERE username='$username'";
	mysql_query($sql);
  }
  
  if($authtype == 'ldap'){
    $_SESSION['auth_type'] = 'ldap';
  }
 
  $_SESSION['username'] = $username;
  $_SESSION['accesslevel'] = $auth['accesslevel'];
  session_write_close();

  $notice = "You have successfully been logged in.";
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
  
  $sql = "SELECT passwd, tmppasswd, accesslevel, loginattempts, passwdexpire,ldapexempt FROM users WHERE username='$username'";
  $row = mysql_query($sql);
  if(mysql_num_rows($row) != "1"){
    return FALSE;    
  }
  
  list($passwd,$tmppasswd,$accesslevel,$loginattempts,$passwdexpire,$ldapexempt) = mysql_fetch_row($row);
  
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