<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title>Collate:Network</title>
    
    <meta http-equiv="content-type" content="application/xhtml+xml; charset=iso-8859-1" />
    
    <meta name="generator" content="Jake Paulus" />
    <meta name="description" content="Organize your hardware and software inventory records" />
    <meta name="keywords" content="hardware,software,inventory,users" />
   
<?php 
if(isset($_GET['view'])){
  $view = $_GET['view'];
}
else {
  $view = "normal";
}
  // Make sure we supply the correct css for the view the user is requesting and we don't load those libraries if we don't have to.
if($view == "printable"){ ?>
<link rel="stylesheet" type="text/css" href="css/print.css" />
<?php } else { ?>
<link rel="stylesheet" type="text/css" href="css/bluesky.css" />
<script src="javascripts/scriptaculous.shrunk.js" type="text/javascript" charset="ISO-8859-1"></script>
<script src="javascripts/scriptaculous.extensions.js" type="text/javascript"></script>
<?php } ?>

</head>
<body id="collate-network">

<div id="page">
    
    <div id="header">
        <a href="index.php">Collate:Network</a>&nbsp;
    </div>
        <div id="content">
<div class="path">
  <table width="100%">
    <tr><td align="left">
      <?php 
	// Here we construct the current path links.
	if(stristr($_SERVER['REQUEST_URI'], "blocks") ||
	   stristr($_SERVER['REQUEST_URI'], "subnets") ||
	   stristr($_SERVER['REQUEST_URI'], "statics")){
	   
	  echo "<a href=\"blocks.php\">All</a> ";
	}
	if(stristr($_SERVER['REQUEST_URI'], "block_id")){
	  $block_id = clean($_GET['block_id']);
	  $sql = "SELECT name FROM blocks WHERE id='$block_id'";
	  $result = mysql_query($sql);
	  if(mysql_num_rows($result) == '1'){
	    $block_name = mysql_result($result, 0, 0);
	    echo "/ <a href=\"subnets.php?block_id=$block_id\">$block_name</a></td>";
	  }
	}
	elseif(stristr($_SERVER['REQUEST_URI'], "subnet_id")){
	  $subnet_id = clean($_GET['subnet_id']);
	  $sql = "SELECT blocks.name, subnets.name, subnets.block_id FROM blocks, subnets 
			WHERE subnets.id ='$subnet_id' AND subnets.block_id = blocks.id";
	  $result = mysql_query($sql);
	  if(mysql_num_rows($result) == '1'){
	    list($block_name, $subnet_name, $block_id) = mysql_fetch_row($result);
		echo "/ <a href=\"subnets.php?block_id=$block_id\">$block_name</a> / 
			 <a href=\"statics.php?subnet_id=$subnet_id\">$subnet_name</a></td>";
	  }
	}
	else{
	  echo "</td>";
	}

     // This little mess here makes sure that the print URL is formed properly.
    echo "<td align=\"right\">
	        <a href=\"search.php\">Search</a> | 
			<a href=\"http://".$_SERVER['SERVER_NAME'].htmlentities($_SERVER['REQUEST_URI']); 
    if(stristr($_SERVER['REQUEST_URI'], "?")){ 
      echo "&amp;"; 
    } 
    else {
      echo "?";
    }
    ?>view=printable">Printable</a>&nbsp;</td></tr>
</table>
    </div>
<div id="main">
<div id="notice" class="tip">
<?php
  if(isset($_GET['notice'])){
    echo "<p>".$_GET['notice']."</p>";
  }
?>
</div>
