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
<?php } ?>

</head>
<body id="collate-inventory">

<div id="page">
    
    <div id="header">
        <a href="index.php">Collate:Network</a>&nbsp;
    </div>
 

        <div id="content">
	
<div class="path">
  <table width="100%">
    <tr><td align="left">
      <?php 
	    echo "<a href=\"panel.php\">Control Panel</a>";
		if(isset($_SESSION['username'])){
		  echo " | <a href=\"login.php?op=logout\">Logout</a></td>";
		}
		else{
		  echo " | <a href=\"login.php\"> Login </a></td>";
		}
		
     // This little mess here makes sure that the print URL is formed properly.
     
    echo "<td align=\"right\"><a href=\"http://".$_SERVER['SERVER_NAME'].htmlentities($_SERVER['REQUEST_URI']); 
    if(stristr($_SERVER['REQUEST_URI'], "?") == TRUE){ 
      echo "&amp;"; 
    } 
    else {
      echo "?";
    }
    ?>view=printable">Printable</a>&nbsp;</td></tr>
</table>
    </div>
<div id="main">
<?php
  if(isset($_GET['notice'])){
    echo "<div class=\"tip\"><p>".$_GET['notice']."</p></div>";
  }
?>
