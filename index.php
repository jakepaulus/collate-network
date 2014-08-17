<?php 
require_once('./include/common.php');
if(!isset($COLLATE['settings']['version']) || $COLLATE['settings']['version'] != '2.2.1'){
  header("Location: install.php");
  exit();
}
require_once('./include/header.php');

  echo $COLLATE['languages']['selected']['indextext'];

require_once('./include/footer.php'); 
?>

     
