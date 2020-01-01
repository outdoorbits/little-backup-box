<html lang="en">
    <!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

    <head>
        <meta charset="utf-8">
        <title>Little Backup Box</title>
        <link rel="shortcut icon" href="favicon.png" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>

    <body>
	<center><a href="/"><div style="margin-bottom: 1.9em;"><img src="logo.svg" height="51px" alt="Little Backup Box"></a></div></center>
	<?php
	$ext = Array ( 'jpeg', 'jpg' );
	$rdi = new RecursiveDirectoryIterator("storage/");
	$it = new RecursiveIteratorIterator($rdi);
	foreach($it as $item)
	if (in_array(strtolower(array_pop(explode('.', $item))), $ext))
	    echo '<a href="'.$item.'"><img src="'.$item.'" width=100px /></a>';
	?>
	<p>
	    <a href="https://gumroad.com/l/linux-photography"><img src="svg/life-ring.svg" height="35px" alt="Linux Photography book"></a>
	</p>
    </body>
</html>
