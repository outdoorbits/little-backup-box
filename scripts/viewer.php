<html lang="en">
    <!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

    <head>
        <meta charset="utf-8">
        <title>Little Backup Box</title>
        <link rel="shortcut icon" href="favicon.png" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
	 #content {
	     margin: 0px auto;
	     text-align: center;
	 }
	</style>
    </head>
    
    <body>
	<div id="content">
	    <a href="/"><div style="margin-bottom: 1.9em;"><img src="svg/logo.svg" height="51px" alt="Little Backup Box"></a></div>
	    <div style="display: inline-block; text-align: left;">
		<?php
		$format = Array ( 'jpeg', 'jpg' );
		$rdi = new RecursiveDirectoryIterator("storage/");
		$it = new RecursiveIteratorIterator($rdi);
		foreach($it as $item)
		if (in_array(strtolower(array_pop(explode('.', $item))), $format))
		    echo '<a href="'.$item.'"><img style="vertical-align:middle;" src="svg/image1.svg"></a> <div style="vertical-align:middle; display:inline;">'.basename($item).'</div><br />';
		?>
	    </div>
	    <p>
		<a href="https://gumroad.com/l/linux-photography"><img src="svg/life-ring.svg" height="35px" alt="Linux Photography book"></a>
	    </p>
	</div>
    </body>
</html>
