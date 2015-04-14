</div>
    </div> 
     <?php require_once('./include/nav.php'); ?>

    <div id="footer">
      <p>
	    <?php 
		$footertext = str_replace("%version_number%", $COLLATE['settings']['version'], $COLLATE['languages']['selected']['footertext']);
        echo $footertext;
		?>
      </p>
    </div>

</div>

