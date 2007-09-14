<?php

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){
	
	case "ping";
	ping_host();
	break;
	
	case "guidance";
	ip_guidance();
	break;
	
}

function ping_host(){
  $ip = escapeshellcmd($_GET['ip']);
  
  // This prevents someone from passing extra parameters to ping that could be dangerous e.g. DoS the server or use the server to DoS a host....
  if(!ereg("^([1-9][0-9]{0,2})+\.([1-9][0-9]{0,2})+\.([1-9][0-9]{0,2})+\.([1-9][0-9]{0,2})+$", $ip)){ return; }
  
  echo "<pre>";
  if (!strstr($_SERVER['DOCUMENT_ROOT'], ":")){ // *nix system
    system ("ping -c 4 -i .3 -n $ip");
  }
  else{ // Windows Server
    system("ping -n 4 -w 100 $ip");
  }
  echo "</pre>";
} // Ends ping_host function


function ip_guidance(){
  require_once('include/common.php');
  
  $subnet_id = $_GET['subnet_id'];
  
  if(!is_numeric($subnet_id)){ return; }
  
  $sql = "SELECT guidance FROM subnets WHERE id='$subnet_id'";
  $result = mysql_query($sql);
  
  list($guidance) = mysql_fetch_row($result);

  if(empty($guidance) && empty($COLLATE['settings']['guidance'])){
    echo "<p>Sorry, there is no guidance available. This data can be input when allocating or editing a subnet. Default
	     guidance information can be input into the settings page by an administrator.</p>";
  }
  elseif(!empty($guidance)){
	echo "<p>".nl2br($guidance)."</p>";
  }
  else{ 
    echo nl2br($COLLATE['settings']['guidance']);
  }
} // Ends ip_guidance function  
?>