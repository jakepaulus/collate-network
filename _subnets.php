<?php

require_once('include/common.php');
$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){
	
	case "edit";
	edit_subnet();
	break;
	
	case "delete";
	delete_subnet();
	break;
}


function edit_subnet(){

  global $COLLATE;

// EditInPlace POSTS form value as: $_POST['value']

  $subnet_id = (empty($_GET['subnet_id'])) ? '' : clean($_GET['subnet_id']);
  $edit = (empty($_GET['edit'])) ? '' : clean($_GET['edit']);
  $value = (empty($_POST['value'])) ? '' : clean($_POST['value']);
  $username = (isset($COLLATE['user']['username'])) ? $COLLATE['user']['username'] : 'unknown';
  
  if(empty($subnet_id) || empty($edit)){ 
    header("HTTP/1.1 500 Internal Error");
	echo "Please select a subnet to edit.";
	exit();
  }
  elseif($edit == 'name' && (strlen($value) < '3' OR strlen($value) > '60')){
    header("HTTP/1.1 500 Internal Error");
	echo "Subnet names must be between 3 and 60 characters long.";
	exit();
  }
 
  if($edit == 'name'){
	$result = mysql_query("SELECT name FROM subnets WHERE id='$subnet_id'");
	$name = mysql_result($result, 0, 0);
    AccessControl('3', "subnet #$subnet_id name changed from $name to $value");
	$sql = "UPDATE subnets SET name='$value', modified_by='$username', modified_at=NOW() WHERE id='$subnet_id'";
  }
  elseif($edit == 'note'){
    $result = mysql_query("SELECT name FROM subnets WHERE id='$subnet_id'");
    $name = mysql_result($result, 0, 0);
    AccessControl('3', "subnet #$subnet_id ($name) note edited");
	$sql = "UPDATE subnets SET note='$value', modified_by='$username', modified_at=NOW() WHERE id='$subnet_id'";
  }
  else{
    return;
  }
 
  mysql_query($sql);
  
  echo $value;
} // Ends edit_subnet function


function delete_subnet(){

  global $COLLATE;

  $subnet_id = (empty($_GET['subnet_id'])) ? '' : $_GET['subnet_id'];
  
  if(empty($subnet_id)){
    header("HTTP/1.1 500 Internal Error");
    echo "Please select a subnet to delete.";
	exit();
  }
  
  $sql = "SELECT name FROM subnets WHERE id='$subnet_id'";
  $result = mysql_query($sql);
	
  if(mysql_num_rows($result) != '1'){
    header("HTTP/1.1 500 Internal Error");
	echo "That subnet was not found. Please try again.";
	exit();
  }
  
  $name = mysql_result($result, 0, 0);
  
  $accesslevel = "3";
  $message = "Subnet #$subnet_id ($name) has been deleted";
  AccessControl($accesslevel, $message); 
  
  // First delete all static IPs
  $sql = "DELETE FROM statics WHERE subnet_id='$subnet_id'";
  mysql_query($sql);
  
  // Next, remove the acl ACL
  $sql = "DELETE FROM acl WHERE subnet_id='$subnet_id'";
  mysql_query($sql);
  
  // Lastly, remove the subnet
  $sql = "DELETE FROM subnets WHERE id='$subnet_id'";
  mysql_query($sql);
  
  echo "The subnet $name has been deleted";
  
} // Ends delete_subnet function

?>
