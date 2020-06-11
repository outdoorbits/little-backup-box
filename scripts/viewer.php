<html lang="en">
    <!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

    <head>
        <meta charset="utf-8">
        <title>Little Backup Box</title>
        <link rel="shortcut icon" href="favicon.png" />
	<link rel="stylesheet" href="css/lit.css">
	<link href="https://fonts.googleapis.com/css2?family=Nunito" rel="stylesheet">
        <meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="css/featherlight.min.css" type="text/css" rel="stylesheet" />
	<style>
	 ul {
	     -webkit-column-count: 3;
	     -moz-column-count: 3;
	     column-count: 3;
	     list-style-type: none;
	 }
	</style>
    </head>
    
    <body>
	<?php
	// include i18n class and initialize it
	require_once 'i18n.class.php';
	$i18n = new i18n('lang/{LANGUAGE}.ini', 'cache/', 'en');
	$i18n->init();
	?>
	<div class="c">
	    <h1><?php echo L::viewer; ?></h1>
	    <hr>
	    <script src="js/jquery.min.js"></script>
	    <script src="js/featherlight.min.js" type="text/javascript" charset="utf-8"></script>
	    <div class="c">
		<link rel="stylesheet" href="css/lit.css">
		<link href="https://fonts.googleapis.com/css2?family=Nunito" rel="stylesheet">
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
	    </div>
	    <hr style="margin-bottom: 2em;">
	    <a class="btn" href="index.php"><?php echo L::back_b; ?></a>  
	</div>
    </body>
</html>
