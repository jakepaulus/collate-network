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
	   
	  echo "<a href=\"blocks.php\">[root]</a> ";
	  if(stristr($_SERVER['REQUEST_URI'], "subnet_id")){ # let's get the block_id
	    $subnet_id = (preg_match("/[0-9]*/", $_GET['subnet_id'])) ? $_GET['subnet_id'] : null;
		$sql = "SELECT name, block_id FROM subnets WHERE id='$subnet_id'";
		$result = mysql_query($sql);
	    if(mysql_num_rows($result) == '1'){
		  list($subnet_name,$block_id) = mysql_fetch_row($result);
		}		
	  }
	  
	  if((isset($_GET['block_id']) && preg_match("/[0-9]*/", $_GET['block_id'])) || !empty($block_id)){
	    $block_id = (!empty($block_id)) ? $block_id : $_GET['block_id'];
	    $sql = "SELECT name, parent_id, type FROM blocks WHERE id='$block_id'";
	    $result = mysql_query($sql);
	    if(mysql_num_rows($result) == '1'){
		  list($block_name,$parent_id,$block_type) = mysql_fetch_row($result);
		  if($block_type == 'container'){
		    $block_path = "/ <a href=\"blocks.php?block_id=$block_id\">$block_name</a>";
		  }
		  else{
	        $block_path = "/ <a href=\"subnets.php?block_id=$block_id\">$block_name</a>";
		  }
		  $toomanylookups=1;
		  while($parent_id !== null && $toomanylookups < 5){
		    $sql = "SELECT `name`, `parent_id`, `type` FROM blocks WHERE id='$parent_id'";
			$recursive_result = mysql_query($sql);
			list($recursive_parent_name,$recursive_parent_id,$recursive_block_type) = mysql_fetch_row($recursive_result);
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
	
    echo "</td>\n<td align=\"right\"><a href=\"search.php\">".$COLLATE['languages']['selected']['AdvancedSearch']."</a></td></tr>";
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
