<?php
session_start();
// Let the sessions expire when the user closes the browser window or logs out. Hopefully no timeouts
ini_set("session.gc_maxlifetime", "86400");

//------------- Build CI array var and put version number in it -----------------------------

$COLLATE = array();

if(isset($_SESSION['accesslevel'])){
  $COLLATE['user']['accesslevel'] = $_SESSION['accesslevel'];
  $COLLATE['user']['username'] = $_SESSION['username'];
}
else{
  $COLLATE['user']['accesslevel'] = "0";
}



//---------- Populate $COLLATE['settings'] with settings from db ----------------------------------

// First get CI settings to see if we even need to check user's permissions
require_once('./include/db_connect.php'); 

$sql = "SELECT name, value FROM settings";
$result = mysql_query($sql);
  
while ($column = mysql_fetch_assoc($result)) {
  // $COLLATE['settings']['setting_name'] will be set to the seting's value.
  $COLLATE['settings'][$column['name']] = $column['value'];
}  


// --------------- Prevent Unwanted Access ---------------------------------------------------

/**
 * The goal of this section is to compare $_SESSION['accesslevel'] with the $accesslevel
 * parameter and allow or deny access. Each function has a hard-coded value 
 * to check for to allow the function to run. When AccessControl has determined the
 * user has enough access for the function, it will stop further checks.
 * 
 * Access Level 0 = Access denied completely: User can see index.php and login.php (this is the default for a new user)
 * Access Level 1 = Read-Only access, no changes can be made
 * Access Level 2 = Can make changes to statics table
 * Access Level 3 = Can make changes to subnets table + level 2
 * Access Level 4 = Can make changes to blocks table + level 3
 * Access Level 5 = Full control of the application including setting changes, user's access level modifications, and user password resets.
 */

 function AccessControl($accesslevel, $message) {
   global $COLLATE;
   
  if($COLLATE['settings']['perms'] >= $accesslevel) {
    return;
  }
  elseif(!isset($_SESSION['username'])) { // the user isn't logged in.
    $returnto = urlencode($_SERVER['REQUEST_URI']); // return the user to where they came from with this var
    $notice = "The administrator of this application requires you to login to use this feature.";
    header("Location: login.php?notice=$notice&returnto=$returnto");
    exit();
  }
  elseif($_SESSION['accesslevel'] >= $accesslevel){
    if($accesslevel > "1"){
      collate_log($accesslevel, $message);
	}
    return; // Access is allowed
  }
  // if we've gotten this far in the function, we've not met any condition to allow access so access is denied.
  $notice = "You do not have sufficient access to use this resource. Please contact your administrator if you believe you have reached this page in error.";
  header("Location: index.php?notice=$notice");
  exit();  
  
} // Ends AccessControl function


function clean($variable){ 

  $invalid = array();
  $invalid['0'] = '"'; // removes single quotes
  $invalid['1'] = '\\"'; // removes single quotes (escaped quotes would leave slashes, which look ugly where they dont belong)
  $invalid['2'] = '\''; // removes double quotes
  $invalid['3'] = '\\\''; // removes double quotes (escaped quotes would leave slashes, which look ugly where they dont belong)

  $variable = str_replace($invalid, '', $variable);
  $variable = strip_tags(trim($variable)); 
  return $variable;
}

//------------Logging Function------------------------------------------------------
function collate_log($accesslevel, $message){
  $ipaddress = $_SERVER['REMOTE_ADDR'];
  
  if($accesslevel <= "2"){ $level = "low"; }
  if($accesslevel == "3"){ $level = "normal"; }
  if($accesslevel >= "4"){ $level = "high"; }
  
  $username = (!isset($COLLATE['user']['username'])) ? 'system' : $COLLATE['user']['username'];
  
  $sql = "INSERT INTO logs (occuredat, username, ipaddress, level, message) VALUES(NOW(), '$username', '$ipaddress', '$level', '$message')";
  mysql_query($sql);
 
} // Ends collate_log function

?>