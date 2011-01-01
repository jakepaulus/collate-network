<?php

require_once('include/common.php');
$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){
	
	case "add_ldap_server";
	add_ldap_server();
	break;
	
	case "delete_ldap_server";
	delete_ldap_server();
	break;
}


function add_ldap_server(){
  
  global $COLLATE;

// EditInPlace POSTS form value as: $_POST['value']

  $block_id = (empty($_GET['block_id'])) ? '' : clean($_GET['block_id']);
  $edit = (empty($_GET['edit'])) ? '' : clean($_GET['edit']);
  $value = (empty($_POST['value'])) ? '' : clean($_POST['value']);
  $username = (isset($COLLATE['user']['username'])) ? $COLLATE['user']['username'] : 'unknown';
  
  if(empty($block_id) || empty($edit)){ 
    header("HTTP/1.1 500 Internal Error");
	echo "Please select a block to edit.";
	exit();
  }
  elseif($edit == 'name' && strlen($value) < '3'){
    header("HTTP/1.1 500 Internal Error");
	echo "Block names must be three characters or longer.";
	exit();
  }
  
  $result = mysql_query("SELECT name FROM blocks WHERE name='$value'");
  
  if($edit == 'name'){
    if(mysql_num_rows($result) != '0'){
	  header("HTTP/1.1 500 Internal Error"); // Tells Ajax.InPlaceEditor that an error has occured.
	  echo "That name already exists in the database.";
	  exit;
	}
	$result = mysql_query("SELECT name FROM blocks WHERE id='$block_id'");
	$name = mysql_result($result, 0);
    AccessControl('4', "Block #$block_id name changed from $name to $value");
	$sql = "UPDATE blocks SET name='$value', modified_by='$username', modified_at=NOW() WHERE id='$block_id'";
  }
  elseif($edit == 'note'){
    $result = mysql_query("SELECT name FROM blocks WHERE id='$block_id'");
    $name = mysql_result($result, 0);
    AccessControl('4', "Block #$block_id ($name) note edited");
	$sql = "UPDATE blocks SET note='$value', modified_by='$username', modified_at=NOW() WHERE id='$block_id'";
  }
  else{
    return;
  }
 
  mysql_query($sql);
  
  echo $value;
} // Ends edit_block function


function delete_ldap_server(){

  global $COLLATE;
  
  $server_id = (empty($_GET['ldap_server_id'])) ? '' : $_GET['ldap_server_id'];
  
  if(empty($server_id)){
    header("HTTP/1.1 500 Internal Error");
    echo "Please select a valid ldap server to delete.";
	exit();
  }
  
  $sql = "SELECT domain, server FROM `ldap-servers` WHERE id='$server_id'";
  $result = mysql_query($sql);
	
  if(mysql_num_rows($result) != '1'){
    header("HTTP/1.1 500 Internal Error");
	echo "That server entry was not found. Please try again.";
	exit();
  }
  
  list($domain,$server) = mysql_fetch_row($result);
  
  $accesslevel = "5";
  $message = "The ldap server entry \"$server\" for the \"$domain\" domain has been deleted!";
  AccessControl($accesslevel, $message);
    
  // First delete all static IPs
  $sql = "DELETE FROM `ldap-servers` WHERE id='$server_id' LIMIT 1";
  mysql_query($sql);
  
  echo "The ldap server entry \"$server\" for the \"$domain\" domain has been deleted.";
  
} // Ends delete_block function

?>