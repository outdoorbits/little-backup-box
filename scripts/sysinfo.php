<html lang="en">
    <!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

    <head>
	<meta charset="utf-8">
	<title>Little Backup Box</title>
	<link rel="shortcut icon" href="favicon.png" />
	<link rel="stylesheet" href="milligram.min.css">
	<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Roboto:300,300italic,700,700italic">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
	 #content {
	     margin: 0px auto;
             text-align: center;
	 }
	 h2 {
	     letter-spacing: 3px;
	 }
	 img {
	     display: block;
	     margin-left: auto;
	     margin-right: auto;
	     margin-top: 1%;
	     margin-bottom: 1%;
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
	<div id="content">
	    <a href="/"><img src="logo.svg" height="51px" alt="Little Backup Box"></a>
            <h2>Little Backup Box</h2>
            <p><?php echo L::sysinfo_hed; ?></p>
	    <h2>Devices</h2>
	    <div style="display: inline-block; text-align: left;">
		<?php
		echo '<pre>';
		passthru("lsblk");
		echo '</pre>';
		?>
	    </div>
	    <h2><?php echo L::diskspace_lbl; ?></h2>
	    <div style="display: inline-block; text-align: left;">
		<?php
		echo '<pre>';
		passthru("df -H");
		echo '</pre>';
		?>
	    </div>
	    <h2><?php echo L::memory_lbl; ?></h2>
	    <div style="display: inline-block; text-align: left;">
		<?php
		echo '<pre>';
		passthru("free -m");
		echo '</pre>';
		?>
	    </div>
	    <form>
		<input type="button" onClick="history.go(0)" value="<?php echo L::refresh_btn; ?>">
	    </form>
	    <p>Read the <a href="https://gumroad.com/l/linux-photography">Linux Photography</a> book</p>
	</div>
    </body>
</html>
