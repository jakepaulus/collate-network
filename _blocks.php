<?php

require_once('include/common.php');
$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

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

// EditInPlace POSTS form value as: $_POST['value']

  $block_id = (empty($_GET['block_id'])) ? '' : clean($_GET['block_id']);
  $edit = (empty($_GET['edit'])) ? '' : clean($_GET['edit']);
  $value = (empty($_POST['value'])) ? '' : clean($_POST['value']);
  
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


function delete_block(){

  global $COLLATE;
  
  $block_id = (empty($_GET['block_id'])) ? '' : $_GET['block_id'];
  
  if(empty($block_id)){
    header("HTTP/1.1 500 Internal Error");
    echo "Please select a block in order to delete it.";
	exit();
  }
  
  $sql = "SELECT name FROM blocks WHERE id='$block_id'";
  $result = mysql_query($sql);
	
  if(mysql_num_rows($result) != '1'){
    header("HTTP/1.1 500 Internal Error");
	echo "That block was not found. Please try again.";
	exit();
  }
  
  $name = mysql_result($result, 0, 0);
  
  $accesslevel = "4";
  $message = "IP Block #$block_id ($name) deleted!";
  AccessControl($accesslevel, $message);
    
  // First delete all static IPs
  $sql = "DELETE FROM statics WHERE subnet_id IN (SELECT id FROM subnets WHERE block_id='$block_id')";
  mysql_query($sql);
  
  // Next, remove the DHCP ACLs
  $sql = "DELETE FROM acl WHERE apply IN (SELECT id FROM subnets WHERE block_id='$block_id')";
  mysql_query($sql);
  
  // Next, remove the subnets
  $sql = "DELETE FROM subnets WHERE block_id='$block_id'";
  mysql_query($sql);
  
  // Lastly, delete the IP block
  $sql = "DELETE FROM blocks WHERE id='$block_id'";
  mysql_query($sql);
  
  echo "The $name block has been deleted";
  
} // Ends delete_block function

?>