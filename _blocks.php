<?php

require_once('include/common.php');
AccessControl('4', null, false); # null means no log, false means don't redirect
$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

if(isset($_GET['block_id']) && preg_match("/[0-9]*/", $_GET['block_id'])){
  $block_id = $_GET['block_id'];
}
else{
  header("HTTP/1.1 400 Bad Request"); // Tells Ajax.InPlaceEditor that an error has occured.
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
  
  $dbo = getdbo();

  $edit = (empty($_GET['edit'])) ? '' : clean($_GET['edit']);
  $value = (empty($_POST['value'])) ? '' : clean($_POST['value']);
  $username = (isset($COLLATE['user']['username'])) ? $COLLATE['user']['username'] : 'unknown';
  
  if($edit == 'name'){
    $return = validate_text($value,'blockname');
	if($return['0'] === false){
	  header("HTTP/1.1 400 Bad Request");
	  echo $COLLATE['languages']['selected'][$return['error']];
	  exit();
	}
	else{
	  $value = $return['1'];
	}
	
	$sql = "SELECT id FROM blocks WHERE name='$value'";
	$result = $dbo -> query($sql);
	if($result -> rowCount()!= '0'){ 
	  # a block by this name exists already
      $existing_block_id = $result -> fetchColumn();
	  
	  if($existing_block_id !== $block_id){
	    header("HTTP/1.1 400 Bad Request");
	    echo $COLLATE['languages']['selected']['duplicatename'];
	    exit();
	  }
	}
	
	$sql = "SELECT name FROM blocks WHERE id='$block_id'";
	$result = $dbo -> query($sql);
	$name = $result -> fetchColumn();
	
    collate_log('4', "Block $name has been updated to $value");
	$sql = "UPDATE blocks SET name='$value', modified_by='$username', modified_at=NOW() WHERE id='$block_id'";
  }
  elseif($edit == 'note'){
    $return = validate_text($value,'note');
	if($return['0'] === false){
	  header("HTTP/1.1 400 Bad Request");
	  echo $COLLATE['languages']['selected'][$return['error']];
	  exit();
	}
	else{
	  $value = $return['1'];
	}
	
	$sql = "SELECT name FROM blocks WHERE id='$block_id'";
    $result = $dbo -> query($sql);
    $name = $result -> fetchColumn();
    collate_log('4', "Block $name note edited");
	$sql = "UPDATE blocks SET note='$value', modified_by='$username', modified_at=NOW() WHERE id='$block_id'";
  }
  else{
    header("HTTP/1.1 400 Bad Request");
	echo $COLLATE['languages']['selected']['invalidrequest'];
	exit();
  }
 
  $dbo -> query($sql);
  
  echo $value;
} // Ends edit_block function


function delete_block(){
  global $COLLATE;
  global $block_id;
  
  $dbo = getdbo();
  
  $block_ids = array();
  $block_ids[] = $block_id;
  
  
  $sql = "SELECT name FROM blocks WHERE id='$block_id'";
  $result = $dbo -> query($sql);
	
  if($result -> rowCount()!= '1'){
    header("HTTP/1.1 400 Bad Request");
	echo $COLLATE['languages']['selected']['selectblock'];
	exit();
  }
  
  $name = $result -> fetchColumn();

  collate_log("4", "Block $name has been deleted!");
  
  if(find_child_blocks($block_id) !== false){ # this is a recursive function
    $block_ids = array_merge($block_ids, find_child_blocks($block_id));
  }

  foreach($block_ids as $block_id){
    // First delete all static IPs
    $sql = "DELETE FROM statics WHERE subnet_id IN (SELECT id FROM subnets WHERE block_id='$block_id')";
    $dbo -> query($sql);
    
    // Next, remove the DHCP ACLs
    $sql = "DELETE FROM acl WHERE subnet_id IN (SELECT id FROM subnets WHERE block_id='$block_id')";
    $dbo -> query($sql);
    
    // Next, remove the subnets
    $sql = "DELETE FROM subnets WHERE block_id='$block_id'";
    $dbo -> query($sql);
    
    // Lastly, delete the IP block
    $sql = "DELETE FROM blocks WHERE id='$block_id'";
    $dbo -> query($sql);
  }
  
  # we don't output to the user on success. The row fades on the page to provide feedback.
  
} // Ends delete_block function
?>