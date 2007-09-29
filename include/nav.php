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
  <br />
  <div class="left_nav">
    <h3>Quick Search</h3>
	<div class="inner_box">
	  <form action="search.php" method="get">
	  <ul>
	    <li style="font-size: .8em;">Hostname:</li>
		<li>
		  <input type="hidden" name="op" value="search" />
		  <input type="hidden" name="first" value="1" />
		  <input type="hidden" name="second" value="name" />
		  <input type="text" name="search" />
		</li>
		<li><input type="submit" value=" Go " /></li>
	  </ul>
	  </form>
	  <form action="search.php" method="get">  
	  <ul>
	    <li style="font-size: .8em;">IP:</li>
		<li>
		  <input type="hidden" name="op" value="search" />
		  <input type="hidden" name="first" value="1" />
		  <input type="hidden" name="second" value="ip" />
		  <input type="text" name="search" />		  
		</li>
		<li><input type="submit" value=" Go " /></li>
	  </ul>
	  </form>
	</div>
  </div>
  
  
</div>

