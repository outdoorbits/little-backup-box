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
	<div id="content">
	    <img src="ichigo.svg" height="39px" alt="Ichigo">
            <h1>Little Backup Box</h1>
	    <p><a href="sysinfo.php">System info</a></p>
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

		// folder to check
		$dir = '/media/storage';

		// get disk space free (in bytes)
		$disk_free = disk_free_space($dir);

		// get disk space total (in bytes)
		$disk_total = disk_total_space($dir);

		// calculate the disk space used (in bytes)
		$disk_used = $disk_total - $disk_free;

		// percentage of disk used
		$disk_used_p = sprintf('%.2f',($disk_used / $disk_total) * 100);

		// this function will convert bytes value to KB, MB, GB and TB
		function convertSize( $bytes )
		{
		    $sizes = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		    for( $i = 0; $bytes >= 1024 && $i < ( count( $sizes ) -1 ); $bytes /= 1024, $i++ );
		    return( round( $bytes, 2 ) . " " . $sizes[$i] );
		}

		// format the disk sizes using the function (B, KB, MB, GB and TB)
		$disk_free = convertSize($disk_free);
		$disk_used = convertSize($disk_used);
		$disk_total = convertSize($disk_total);

		echo '<ul>';
		echo '<li>Total: '.$disk_total.'</li>';
		echo '<li>Used: '.$disk_used.' ('.$disk_used_p.'%)</li>';
		echo '<li>Free: '.$disk_free.'</li>';
		echo '</ul>';
	    }
	    if (isset($_POST['shutdown']))
	    {
		shell_exec('sudo shutdown -h now 2>&1 & echo $!');
	    }
	    ?>
	</div>
    </body>
</html>
