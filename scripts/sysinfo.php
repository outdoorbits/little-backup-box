<html lang="en">
    <!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

    <head>
	<meta charset="utf-8">
	<title>Little Backup Box</title>
	<link rel="shortcut icon" href="favicon.png" />
	<link rel="stylesheet" href="terminal.css">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
	 #content {
	     margin: 0px auto;
             text-align: center;
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
            <p><?php echo L::sysinfo_hed; ?></p>
	    <h3 style="margin-top: 2em;">Devices</h3>
	    <div style="display: inline-block; text-align: left; margin-bottom: 1.5em;">
		<?php
		echo '<pre>';
		passthru("lsblk");
		echo '</pre>';
		?>
	    </div>
	    <h3><?php echo L::diskspace_lbl; ?></h3>
	    <p></p>
	    <div style="display: inline-block; text-align: left; margin-bottom: 1.5em;">
		<?php
		echo '<pre>';
		passthru("df -H");
		echo '</pre>';
		?>
	    </div>
	    <h3><?php echo L::memory_lbl; ?></h3>
	    <div style="display: inline-block; text-align: left; margin-bottom: 2em;">
		<?php
		echo '<pre>';
		passthru("free -m");
		echo '</pre>';
		?>
	    </div>
	    <form>
		<button class="btn btn-default btn-ghost" type="button" onClick="history.go(0)" role="button"><?php echo L::refresh_btn; ?></button>
	    </form>
            <p>
		<a href="https://gumroad.com/l/linux-photography"><img src="svg/life-ring.svg" height="35px" alt="Linux Photography book"></a>
	    </p>
	</div>
    </body>
</html>
