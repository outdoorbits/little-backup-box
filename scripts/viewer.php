<html lang="en">
    <!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

    <head>
        <meta charset="utf-8">
        <title>Little Backup Box</title>
        <link rel="shortcut icon" href="favicon.png" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="css/featherlight.min.css" type="text/css" rel="stylesheet" />
	<style>
	 #content {
	     margin: 0px auto;
	     text-align: center;
	 }
	 ul {
	     -webkit-column-count: 3;
	     -moz-column-count: 3;
	     column-count: 3;
	     list-style-type: none;
	 }
	</style>
    </head>
    
    <body>
	<script src="js/jquery.min.js"></script>
	<script src="js/featherlight.min.js" type="text/javascript" charset="utf-8"></script>
	<div id="content">
	    <a href="/"><div style="margin-bottom: 1.9em;"><img src="svg/logo.svg" height="51px" alt="Little Backup Box"></a></div>
	    <ul>
		<?php
		$format = Array ( 'jpeg', 'jpg' );
		$rdi = new RecursiveDirectoryIterator("storage/");
		$it = new RecursiveIteratorIterator($rdi);
		foreach($it as $item)
		if (in_array(strtolower(array_pop(explode('.', $item))), $format))
		    echo '<li><a href="'.$item.'" data-featherlight="image"><img style="vertical-align:middle;" src="svg/image1.svg"></a> <div style="vertical-align:middle; display:inline;">'.pathinfo($item, PATHINFO_FILENAME).'</div></li>';
		?>
	    </ul>
	    <p>
		<a href="https://gumroad.com/l/little-backup-book"><img src="svg/life-ring.svg" height="35px" alt="Little Backup Book"></a>
	    </p>
	</div>
    </body>
</html>
