<?php

require_once('include/common.php');
$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

if(isset($_GET['block_id'] && is_numeric($_GET['block_id']){
  $block_id = $_GET['block_id'];
}
else{  
  header("HTTP/1.1 500 Internal Error"); // Tells Ajax.InPlaceEditor that an error has occured.
  echo $COLLATE['languages']['selected']['selectblock'];
  exit();
}
  
switch($op){
	
	case "edit";
	edit_block();
	break;
	
	case "delete";
	delete_block();
	break;
}


function edit_block(){
  global $COLLATE;
  global $block_id;
  include 'include/validation_functions.php';

  $edit = (empty($_GET['edit'])) ? '' : clean($_GET['edit']);
  $value = (empty($_POST['value'])) ? '' : clean($_POST['value']);
  $username = (isset($COLLATE['user']['username'])) ? $COLLATE['user']['username'] : 'unknown';
  
  if($edit == 'name'){
    $return = validate_text($value,'blockname');
	if($return['0'] === false){
	  header("HTTP/1.1 500 Internal Error");
	  echo $COLLATE['languages']['selected'][$return['error']];
	  exit();
	}
	else{
	  $value = $return['1'];
	}
	$result = mysql_query("SELECT name FROM blocks WHERE id='$block_id'");
	$name = mysql_result($result, 0);
    AccessControl('4', "Block $name has been updated to $value");
	$sql = "UPDATE blocks SET name='$value', modified_by='$username', modified_at=NOW() WHERE id='$block_id'";
  }
  elseif($edit == 'note'){
    $return = validate_text($value,'note');
	if($return['0'] === false){
	  header("HTTP/1.1 500 Internal Error");
	  echo $COLLATE['languages']['selected'][$return['error']];
	  exit();
	}
	else{
	  $value = $return['1'];
	}
    $result = mysql_query("SELECT name FROM blocks WHERE id='$block_id'");
    $name = mysql_result($result, 0);
    AccessControl('4', "Block $name note edited");
	$sql = "UPDATE blocks SET note='$value', modified_by='$username', modified_at=NOW() WHERE id='$block_id'";
  }
  else{
    header("HTTP/1.1 500 Internal Error");
	echo $COLLATE['languages']['selected']['invalidrequest'];
	exit();
  }
 
  mysql_query($sql);
  
  echo $value;
} // Ends edit_block function


function delete_block(){
  global $COLLATE;
  global $block_id;
  
  $sql = "SELECT name FROM blocks WHERE id='$block_id'";
  $result = mysql_query($sql);
	
  if(mysql_num_rows($result) != '1'){
    header("HTTP/1.1 500 Internal Error");
	echo $COLLATE['languages']['selected']['selectblock'];
	exit();
  }
  
  $name = mysql_result($result, 0, 0);

  AccessControl("4", "Block $name has been deleted!");
    
  // First delete all static IPs
  $sql = "DELETE FROM statics WHERE subnet_id IN (SELECT id FROM subnets WHERE block_id='$block_id')";
  mysql_query($sql);
  
  // Next, remove the DHCP ACLs
  $sql = "DELETE FROM acl WHERE subnet_id IN (SELECT id FROM subnets WHERE block_id='$block_id')";
  mysql_query($sql);
  
  // Next, remove the subnets
  $sql = "DELETE FROM subnets WHERE block_id='$block_id'";
  mysql_query($sql);
  
  // Lastly, delete the IP block
  $sql = "DELETE FROM blocks WHERE id='$block_id'";
  mysql_query($sql);
  
  $message=str_replace("%name%", "$name", $COLLATE['languages']['selected']['blockdeleted']);
  echo $message;
  
} // Ends delete_block function

?>