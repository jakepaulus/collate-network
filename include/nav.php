<div class="left">
  <div class="left_nav">
    <h3><?php echo $COLLATE['languages']['selected']['Navigation']; ?></h3>
    <div class="inner_box">
      <ul>
        <li><a href="blocks.php"><?php echo $COLLATE['languages']['selected']['Browse']; ?></a></li>
        <li>&nbsp;</li>
	    <li><a href="panel.php"><?php echo $COLLATE['languages']['selected']['ControlPanel']; ?></a></li>
		<?php 
	    if(isset($_SESSION['username'])){
		  echo "<li><a href=\"login.php?op=logout\">".$COLLATE['languages']['selected']['LogOut']."</a></li>";
		}
		else{
		  echo "<li><a href=\"login.php\">".$COLLATE['languages']['selected']['LogIn']."</a></li>";
		}
		?>
	  </ul>
    </div>
  </div>
  <br />
  <div class="left_nav">
    <h3><?php echo $COLLATE['languages']['selected']['QuickSearch']; ?></h3>
	<div class="inner_box">
	  <form action="search.php" method="get">
	  <ul>
	    <li style="font-size: .8em;"><?php echo $COLLATE['languages']['selected']['Hostname']; ?>:</li>
		<li>
		  <input type="hidden" name="op" value="search" />
		  <input type="hidden" name="first" value="2" />
		  <input type="hidden" name="second" value="name" />
		  <input type="search" name="search" />
		</li>
		<li><input type="submit" value=" <?php echo $COLLATE['languages']['selected']['Go']; ?> " /></li>
	  </ul>
	  </form>
	  <form action="search.php" method="get">  
	  <ul>
	    <li style="font-size: .8em;"><?php echo $COLLATE['languages']['selected']['IP']; ?>:</li>
		<li>
		  <input type="hidden" name="op" value="search" />
		  <input type="hidden" name="first" value="2" />
		  <input type="hidden" name="second" value="ip" />
		  <input type="search" name="search" />		  
		</li>
		<li><input type="submit" value=" <?php echo $COLLATE['languages']['selected']['Go']; ?> " /></li>
	  </ul>
	  </form>
	</div>
  </div>
  
  
</div>

