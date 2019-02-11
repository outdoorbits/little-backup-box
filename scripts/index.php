<html lang="en">
    <!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

    <head>
      <meta charset="utf-8">
      <title>Little Backup Box</title>
      <link rel="shortcut icon" href="ichigo.png" />
      <link rel="stylesheet" href="milligram.min.css">
      <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Roboto:300,300italic,700,700italic">
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
            <h1>Little Backup Box</h1>
            <p>Back up a storage card connected via a card reader</p>
            <p>
		<form method="post">
                    <button name="cardbackup">Card backup</button>
		</form>
            </p>
            <p>Transfer files directly from the connected camera</p>
            <p>
		<form method="post">
                    <button name="camerabackup">Camera backup</button>
		</form>
            </p>
	    <p>Activate the DLNA server</p>
            <p>
		<form method="post">
                    <button name="minidlna">DLNA server</button>
		</form>
            </p>
            <p class="left">Shut down the Little Backup Box</p>
            <p>
		<form method="post">
                    <button class="button button-outline" name="shutdown">Shut down</button>
		</form>
            </p>
	</div>
    </body>

</html>

<?php
if (isset($_POST['cardbackup']))
{
    shell_exec('sudo ./card-backup.sh 2>&1 & echo $!');
}
if (isset($_POST['camerabackup']))
{
    shell_exec('sudo ./camera-backup.sh 2>&1 & echo $!');
}
if (isset($_POST['minidlna']))
{
    shell_exec('sudo ./minidlna.sh 2>&1 & echo $!');
}
if (isset($_POST['shutdown']))
{
    shell_exec('sudo shutdown -h now 2>&1 & echo $!');
}
?>
