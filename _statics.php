<?php

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){
	
	case "ping";
	ping_host();
	break;
	
}

function ping_host(){
  $ip = escapeshellcmd($_GET['ip']);
  if(empty($ip)){ return; }
  
  echo "<pre>";
  if (!strstr($_SERVER['DOCUMENT_ROOT'], ":")){ // *nix system
    system ("ping -c 4 -i .3 -n $ip");
  }
  else{ // Windows Server
    system("ping -n 4 -w 100 $ip");
  }
  echo "</pre>";
} // Ends ping_host function
  
?>