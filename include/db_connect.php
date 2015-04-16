<?php
  /* This is borrowed from Justin Guagliata's db connect script from the forum code he wrote
    * a long, long time ago. Thanks Justin, it still works. I've left it in the form of a function
    * so that the variables are strictly localized inside of the function.
    */

function connectToDB() {
  global $COLLATE;

  //database host (IP Address or Hostname)
  $db_host = "localhost";

  //database username
  $db_user = "root";

  //database password
  $db_pass = "";

  //database
  $db_name = "ipam-latest";
  
  $link = mysql_connect("$db_host", "$db_user", "$db_pass");
  
  if(!$link){
    if(!strstr($_SERVER['REQUEST_URI'], 'install.php')){
      # this file is normally includeded before header.php, but not on install.php
      header("Location: install.php");
      exit();
    }    
    return "Couldn't connect to MySQL";
  }

  // select db:
  $select_result = mysql_select_db($db_name, $link);
  if(!$select_result){
    if(!strstr($_SERVER['REQUEST_URI'], 'install.php')){
      header("Location: install.php");
      exit();
    }
    return "Couldn't open db: $db_name. Caught error: ".mysql_error();
  }
  return true;
} 
?>
