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
	 button {width: 175px;}
	 button.red { background-color: #f44336; border: none; }
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
	    <p><a href="sysinfo.php"><?php echo L::sys_info; ?></a></p>
            <p><?php echo L::cardbackup_lbl; ?></p>
            <p>
		<form method="post">
                    <button name="cardbackup"><?php echo L::cardbackup_btn; ?></button>
		</form>
            </p>
            <p><?php echo L::camerabackup_lbl; ?></p>
            <p>
		<form method="post">
                    <button name="camerabackup"><?php echo L::camerabackup_btn; ?></button>
		</form>
            </p>
	    <p><?php echo L::internalbackup_lbl; ?></p>
            <p>
		<form method="post">
                    <button class="button button-outline" name="internalbackup"><?php echo L::internalbackup_btn; ?></button>
		</form>
            </p>
            <p class="left"><?php echo L::shutdown_lbl; ?></p>
            <p>
		<form method="post">
                    <button class="red" name="shutdown"><?php echo L::shutdown_btn; ?></button>
		</form>
            </p>

	    <?php
	    if (isset($_POST['cardbackup']))
	    {
		shell_exec('sudo ./card-backup.sh > /dev/null 2>&1 & echo $!');
		echo '<script language="javascript">';
		echo 'alert("'.L::cardbackup_ntf.'")';
		echo '</script>';
	    }
	    if (isset($_POST['camerabackup']))
	    {
		shell_exec('sudo ./camera-backup.sh > /dev/null 2>&1 & echo $!');
		echo '<script language="javascript">';
		echo 'alert("'.L::camerabackup_ntf.'")';
		echo '</script>';
	    }
	    if (isset($_POST['internalbackup']))
	    {
		shell_exec('sudo ./internal-backup.sh > /dev/null 2>&1 & echo $!');
		echo '<script language="javascript">';
		echo 'alert("'.L::internalbackup_ntf.'")';
		echo '</script>';
	    }
	    if (isset($_POST['shutdown']))
	    {
		shell_exec('sudo shutdown -h now > /dev/null 2>&1 & echo $!');
		echo '<script language="javascript">';
		echo 'alert("'.L::shutdown_ntf.'")';
		echo '</script>';
	    }
	    ?>
	    Read the <a href="https://gumroad.com/l/linux-photography">Linux Photography</a> book
	</div>
    </body>
</html>
