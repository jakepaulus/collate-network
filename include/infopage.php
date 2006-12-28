<?php
// This page is used to display general error messages.
if(empty($CI)){exit();} // Make sure people don't access this page directly.
?>
  <h1>Notice:</h1>
  <p>
    <?php echo $result; ?>
  </p>	
<?php
require_once('./include/footer.php');
exit();
?>
