<!DOCTYPE html>
<meta charset="utf-8">
<title>Collate:Network</title>
   
<link rel="stylesheet" type="text/css" href="css/bluesky.css" />
<script src="javascripts/prototype.js" type="text/javascript"></script>
<script src="javascripts/scriptaculous.js" type="text/javascript"></script>
<script src="javascripts/InPlaceEditorWithEmptyText.js" type="text/javascript"></script>

<body id="collate-network">

<div id="page">
    
    <div id="header">
        <a href="index.php">Collate:Network</a>&nbsp;
    </div>
        <div id="content">
<div id="path" class="path">
  <table style="width: 100%">
    <tr><td style="text-align: left">
      <?php 
	// Here we construct the current path links.
	if(stristr($_SERVER['REQUEST_URI'], "blocks") ||
	   stristr($_SERVER['REQUEST_URI'], "subnets") ||
	   stristr($_SERVER['REQUEST_URI'], "statics")){
	   
	  echo "<a href=\"blocks.php\">[root]</a> ";
	  if(stristr($_SERVER['REQUEST_URI'], "subnet_id")){ # let's get the block_id
	    $subnet_id = (preg_match("/[0-9]*/", $_GET['subnet_id'])) ? $_GET['subnet_id'] : null;
		
		$dbo = getdbo();
		
		$sql = "SELECT name, block_id FROM subnets WHERE id='$subnet_id'";
		$subnet_result = $dbo->query($sql);
	    if($subnet_result->rowCount() == '1'){
		  list($subnet_name,$block_id) = $subnet_result->fetch(PDO::FETCH_NUM);
		}		
	  }
	  
	  if((isset($_GET['block_id']) && preg_match("/[0-9]*/", $_GET['block_id'])) || !empty($block_id)){
	    $block_id = (!empty($block_id)) ? $block_id : $_GET['block_id'];
	    $sql = "SELECT name, parent_id, type FROM blocks WHERE id='$block_id'";
	    $block_result = $dbo->query($sql);
	    if($block_result->rowCount() == '1'){
		  list($block_name,$parent_id,$block_type) = $block_result->fetch(PDO::FETCH_NUM);
		  if($block_type == 'container'){
		    $block_path = "/ <a href=\"blocks.php?block_id=$block_id\">$block_name</a>";
		  }
		  else{
	        $block_path = "/ <a href=\"subnets.php?block_id=$block_id\">$block_name</a>";
		  }
		  $toomanylookups=1;
		  while($parent_id !== null && $toomanylookups < 5){
		    $sql = "SELECT `name`, `parent_id`, `type` FROM blocks WHERE id='$parent_id'";
			$recursive_result = $dbo->query($sql);
			list($recursive_parent_name,$recursive_parent_id,$recursive_block_type) = $recursive_result->fetch(PDO::FETCH_NUM);
			if($recursive_block_type == 'container'){
		      $block_path = "/ <a href=\"blocks.php?block_id=$parent_id\">$recursive_parent_name</a>".$block_path;
		    }
		    else{
	          $block_path = "/ <a href=\"subnets.php?block_id=$parent_id\">$recursive_parent_name</a>".$block_path;
		    }
			$parent_id = $recursive_parent_id;
		    $toomanylookups++;
		  }
		  $block_path = ($toomanylookups === 5) ? '....'.$block_path : $block_path;
	    }
		echo $block_path;
		if(!empty($subnet_id)){
	  	  echo "/ <a href=\"statics.php?subnet_id=$subnet_id\">$subnet_name</a>";
	    }
	  }
	}
	
    echo "</td>\n<td style=\"text-align: right\"><a href=\"search.php\">".$COLLATE['languages']['selected']['AdvancedSearch']."</a></td></tr>";
	?>
</table>
    </div>
<div id="main">
<div id="notice" class="tip">
<?php
  if(isset($_GET['notice'])){
    if(isset($COLLATE['languages']['selected'][$_GET['notice']])){
	  echo "<p>".$COLLATE['languages']['selected'][$_GET['notice']]."</p>";
	}
  }
?>
</div>
