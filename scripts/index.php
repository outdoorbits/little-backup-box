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
	 button {width: 15em;}
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
	    <p><a href="sysinfo.php"><?php echo L::sysinfo; ?></a> &bull; <a href="viewer/"><?php echo L::viewer; ?></a> &bull; <a href="edit.php"><?php echo L::edit; ?></a></p>
            <p>
		<form method="post">
                    <button class="btn btn-primary" name="cardbackup"><?php echo L::cardbackup_btn; ?></button>
		</form>
            </p>
            <p>
		<form method="post">
                    <button class="btn btn-primary" name="camerabackup"><?php echo L::camerabackup_btn; ?></button>
		</form>
            </p>
            <p>
		<form method="post">
                    <button class="btn btn-primary" name="internalbackup"><?php echo L::internalbackup_btn; ?></button>
		</form>
            </p>
            <p>
		<form method="post">
                    <button class="btn btn-error" name="shutdown"><?php echo L::shutdown_btn; ?></button>
		</form>
            </p>

	    <?php
	    if (isset($_POST['cardbackup']))
	    {
		shell_exec('sudo ./card-backup.sh > /dev/null 2>&1 & echo $!');
		echo '<script language="javascript">';
		echo 'alert("'.L::cardbackup_msg.'")';
		echo '</script>';
	    }
	    if (isset($_POST['camerabackup']))
	    {
		shell_exec('sudo ./camera-backup.sh > /dev/null 2>&1 & echo $!');
		echo '<script language="javascript">';
		echo 'alert("'.L::camerabackup_msg.'")';
		echo '</script>';
	    }
	    if (isset($_POST['internalbackup']))
	    {
		shell_exec('sudo ./internal-backup.sh > /dev/null 2>&1 & echo $!');
		echo '<script language="javascript">';
		echo 'alert("'.L::internalbackup_msg.'")';
		echo '</script>';
	    }
	    if (isset($_POST['shutdown']))
	    {
		shell_exec('sudo shutdown -h now > /dev/null 2>&1 & echo $!');
		echo '<script language="javascript">';
		echo 'alert("'.L::shutdown_msg.'")';
		echo '</script>';
	    }
	    ?>
	    <p>
		<details>
		    <summary><?php echo L::help; ?></summary>
		    <div div style="display: inline-block; text-align: left;"><?php echo L::help_txt; ?></div>
		</details>
	    </p>

	    <p><a href="https://gumroad.com/l/linux-photography"><img src="info.svg" height="35px" alt="Linux Photography book"></a></p>
	</div>
    </body>
</html>
