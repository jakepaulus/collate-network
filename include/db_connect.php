<?php
function getdbo() {
  #########################################
  # Begin Configuration
  #########################################

  //database host (IP Address or Hostname)
  $db_host = "localhost";

  //database username
  $db_user = "root";

  //database password
  $db_pass = "";

  //database
  $db_name = "ipam-latest";
  
  #########################################
  # End Configuration
  #########################################

  global $dbo;
  if(is_object($dbo)){ return $dbo; }
  
  try {
    $dbo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", "$db_user", "$db_pass", 
      array(PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT));
  }
  catch (PDOException $exception) {
    if(!strstr($_SERVER['REQUEST_URI'], 'install.php')){
      # this file is normally includeded before header.php, but not on install.php
      header("Location: install.php");
      exit();
    }    
    return "Database connection failed. Exception was: " . $exception->getMessage();
  }
  return $dbo;
} 
?>
