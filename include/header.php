<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title>Collate:Network</title>
    
    <meta http-equiv="content-type" content="application/xhtml+xml; charset=iso-8859-1" />
    
	<link rel="stylesheet" type="text/css" href="css/bluesky.css" />
	<script src="javascripts/scriptaculous.shrunk.js" type="text/javascript" charset="ISO-8859-1"></script>
	<script type="text/javascript"><!--
	/*
     * InPlaceEditor extension that adds a 'click to edit' text when the field is 
     * empty. Taken from http://wiki.script.aculo.us/scriptaculous/show/Ajax.InPlaceEditor
     */
    Ajax.InPlaceEditor.prototype.__initialize = Ajax.InPlaceEditor.prototype.initialize;
    Ajax.InPlaceEditor.prototype.__getText = Ajax.InPlaceEditor.prototype.getText;
    Ajax.InPlaceEditor.prototype.__onComplete = Ajax.InPlaceEditor.prototype.onComplete;
    Ajax.InPlaceEditor.prototype = Object.extend(Ajax.InPlaceEditor.prototype, {
    
        initialize: function(element, url, options){
            this.__initialize(element,url,options)
            this.setOptions(options);
            this._checkEmpty();
        },
    
        setOptions: function(options){
            this.options = Object.extend(Object.extend(this.options,{
                emptyText: '<?php echo $COLLATE['languages']['selected']['clicktoedit']; ?>',
                emptyClassName: 'inplaceeditor-empty'
            }),options||{});
        },
    
        _checkEmpty: function(){
            if( this.element.innerHTML.length == 0 ){
                this.element.appendChild(
                    Builder.node('span',{className:this.options.emptyClassName},this.options.emptyText));
            }
        },
    
        getText: function(){
            document.getElementsByClassName(this.options.emptyClassName,this.element).each(function(child){
                this.element.removeChild(child);
            }.bind(this));
            return this.__getText();
        },
    
        onComplete: function(transport){
            this._checkEmpty();
            this.__onComplete(transport);
        }
    });
	--></script>

</head>
<body id="collate-network">

<div id="page">
    
    <div id="header">
        <a href="index.php">Collate:Network</a>&nbsp;
    </div>
        <div id="content">
<div id="path" class="path">
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
	  $block_id = (is_numeric($_GET['block_id'])) ? $_GET['block_id'] : null;
	  $sql = "SELECT name FROM blocks WHERE id='$block_id'";
	  $result = mysql_query($sql);
	  if(mysql_num_rows($result) == '1'){
	    $block_name = mysql_result($result, 0, 0);
	    echo "/ <a href=\"subnets.php?block_id=$block_id\">$block_name</a></td>";
	  }
	}
	elseif(stristr($_SERVER['REQUEST_URI'], "subnet_id")){
	  $subnet_id = (is_numeric($_GET['subnet_id'])) ? $_GET['subnet_id'] : null;
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
    echo "<td align=\"right\"><a href=\"search.php\">".$COLLATE['languages']['selected']['AdvancedSearch']."</a></td></tr>";
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
