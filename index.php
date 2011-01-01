<?php 
require_once('./include/common.php');
require_once('./include/header.php');
 
?>

  <h1>Welcome <?php if(isset($COLLATE['user']['username'])){ echo $COLLATE['user']['username']; } ?>!</h1>
    <br />
    <h3>About Collate</h3>
      <p> 
      Collate is a collection of applications that will help people manage IT information. 
      </p>
	  
    <h3>About Collate:Network</h3>
      <p> 
      With this application you can easily track your IP space allocations and static IP address assignments. 
      </p>
      
    <h3>Documentation</h3>
      <p>
      Documentation for this application can be found in the docs directory that came with this distribution. You can read the 
	  documenation online at 
	  <a href="http://collate.info/">Collate.info</a>.
      </p>

<?php require_once('./include/footer.php'); ?>

     
