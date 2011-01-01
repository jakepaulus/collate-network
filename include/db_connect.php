<?php
  /* This is borrowed from Justin Guagliata's db connect script from the forum code he wrote
    * a long, long time ago. Thanks Justin, it still works. I've left it in the form of a function
    * so that the variables are strictly localized inside of the function.
    */

connectToDB();

function connectToDB() {

  //database host (IP Address or Hostname)
  $db_host = "localhost";

  //database username
  $db_user = "root";

  //database password
  $db_pass = "root";

  //database
  $db_name = "collate";
  
  ($link = mysql_pconnect("$db_host", "$db_user", "$db_pass")) || die("Couldn't connect to MySQL");

  // select db:
  mysql_select_db($db_name, $link) || die("Couldn't open db: $db_name. Error if any was: ".mysql_error() );
} 
?>
