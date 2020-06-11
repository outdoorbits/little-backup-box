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
    </head>

    <body>
	<?php
	// include i18n class and initialize it
	require_once 'i18n.class.php';
	$i18n = new i18n('lang/{LANGUAGE}.ini', 'cache/', 'en');
	$i18n->init();
	?>
	<div class="c">
            <h1><?php echo L::sysinfo; ?></h1>
	    <hr>
	    <h3>Devices</h3>
	    <div style="display: inline-block; text-align: left;">
		<?php
		echo '<pre>';
		passthru("lsblk");
		echo '</pre>';
		?>
	    </div>
	    <hr>
	    <h3><?php echo L::diskspace; ?></h3>
	    <p></p>
	    <div style="display: inline-block; text-align: left; margin-bottom: 1.5em;">
		<?php
		echo '<pre>';
		passthru("df -H");
		echo '</pre>';
		?>
	    </div>
	    <hr>
	    <h3><?php echo L::memory; ?></h3>
	    <div style="display: inline-block; text-align: left; margin-bottom: 2em;">
		<?php
		echo '<pre>';
		passthru("free -m");
		echo '</pre>';
		?>
	    </div>
	    <form>
		<button class="btn primary" type="button" onClick="history.go(0)" role="button"><?php echo L::refresh_b; ?></button>
		<a class="btn" href="index.php"><?php echo L::back_b; ?></a>
	    </form>
	</div>
    </body>
</html>
