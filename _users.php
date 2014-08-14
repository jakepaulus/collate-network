<?php

require_once('include/common.php');

AccessControl('5', null, false); # null means no log, false means don't redirect

include 'include/validation_functions.php';

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

$username = (isset($_GET['username'])) ? $_GET['username'] : '';
$result = validate_text($username,'username');
if($result['0'] === false){
  header("HTTP/1.1 400 Bad Request");
  echo $COLLATE['languages']['selected'][$result['error']];
  exit();
}
else{
  $username = $result['1'];
}

$sql = "select count(*) from users where username='$username'";
$count = mysql_result(mysql_query($sql), 0);
if($count != '1'){ 
  header("HTTP/1.1 400 Bad Request");  
  echo $COLLATE['languages']['selected']['invalidrequest'];
  exit();
}

switch($op){
	
	case "deleteuser";
	delete_user();
	break;
		
	default:
	exit();
}

function delete_user() {
  global $COLLATE;
  global $username;
  
  collate_log('5', "User deleted: $username");
  
  $sql = "DELETE FROM users WHERE username='$username'";
  $result = mysql_query($sql);
  
  $message = str_replace("%username%", "$username", $COLLATE['languages']['selected']['userdeleted']);
  echo $message;
  
  exit();
} // Ends delete_user function

?>