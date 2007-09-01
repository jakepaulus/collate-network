<div class="left">
  <div class="left_nav">
    <h3>Navigation</h3>
    <div class="inner_box">
      <ul>
        <li><a href="blocks.php">Browse</a></li>
        <li>&nbsp;</li>
	    <li><a href="panel.php">Control Panel</a></li>
		<?php 
	    if(isset($_SESSION['username'])){
		  echo "<li><a href=\"login.php?op=logout\">Logout</a></li>";
		}
		else{
		  echo "<li><a href=\"login.php\"> Login </a></li>";
		}
		?>
	  </ul>
    </div>
  </div>
</div>

