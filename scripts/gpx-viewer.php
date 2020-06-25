<!DOCTYPE html>

<html lang="en">
    
    <!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->
    
    <head>
	<meta charset="utf-8">
	<title>Little Backup Box</title>
	<link rel="icon" href="favicon.png">
	<link rel="stylesheet" href="css/lit.css">
	<link href="https://fonts.googleapis.com/css2?family=Nunito" rel="stylesheet">
	<script type="text/javascript" src="GM_Utils/GPX2GM.js"></script>
	<meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <body>
	<div class="c">
	    <?php
		// include i18n class and initialize it
		require_once 'i18n.class.php';
		$i18n = new i18n('lang/{LANGUAGE}.ini', 'cache/', 'en');
		$i18n->init();
	    $files = glob("GPX/*.gpx");
	    $list=implode(",", $files);
		echo "<h1>";
		echo L::gpx_viewer;
		echo "</h1>";
		echo "<a class='btn' href='index.php'>";
		echo L::back_b;
		echo "</a>";
		echo "<hr style='margin-top:2em'>";
	    echo "<div id='map' class='gpxview:$list' style='width:100%;height:600px;margin-top:2em'></div>";
		echo "<noscript><p>";
		echo L::enable_js;
		echo "</p></noscript>";
	    ?>
		<script>
		var Bestaetigung = false;
		var Legende_fnm = false;
		var Fullscreenbutton = true;
		 </script>
	    <button type="button" style='margin-top:2em' class="gpxview:map:skaliere"><?php echo L::reset_scale_b; ?></button>
	<hr style='margin-top:2em'>
	</div>
    </body>
</html>
