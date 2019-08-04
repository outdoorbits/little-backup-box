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
	<div id="content">
	    <a href="/"><img src="logo.svg" height="51px" alt="Little Backup Box"></a>
            <h2>Little Backup Box</h2>
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
	    <p>Back up a storage card to the internal storage</p>
            <p>
		<form method="post">
                    <button name="internalbackup">Internal backup</button>
		</form>
            </p>
	    <p>Start DLNA, Samba, and Syncthing</p>
            <p>
		<form method="post">
                    <button class="button button-outline" name="servers">Start servers</button>
		</form>
            </p>
            <p class="left">Shut down the Little Backup Box</p>
            <p>
		<form method="post">
                    <button class="red" name="shutdown">Shut down</button>
		</form>
            </p>

	    <?php
	    if (isset($_POST['cardbackup']))
	    {
		shell_exec('sudo ./card-backup.sh > /dev/null 2>&1 & echo $!');
		echo '<script language="javascript">';
		echo 'alert("Card backup initiated.")';
		echo '</script>';
	    }
	    if (isset($_POST['internalbackup']))
	    {
		shell_exec('sudo ./internal-backup.sh > /dev/null 2>&1 & echo $!');
		echo '<script language="javascript">';
		echo 'alert("Internal backup initiated.")';
		echo '</script>';
	    }
	    if (isset($_POST['camerabackup']))
	    {
		shell_exec('sudo ./camera-backup.sh > /dev/null 2>&1 & echo $!');
		echo '<script language="javascript">';
		echo 'alert("Camera backup initiated.")';
		echo '</script>';
	    }
	    if (isset($_POST['servers']))
	    {
		shell_exec('sudo ./start-servers.sh > /dev/null 2>&1 & echo $!');
		sleep(5); # Give Syncthing time to start
		echo '<script language="javascript">';
		echo 'alert("DLNA, Samba, and Syncthing are up and running.")';
		echo '</script>';
		exec("pgrep syncthing", $pids);
		if( !empty($pids)) {
		    $ip=shell_exec("hostname -I | cut -d' ' -f1");
                    echo '<p><a href="http://'.$ip.':8384">Syncthing server</a></p>';
		}
	    }
	    if (isset($_POST['shutdown']))
	    {
		shell_exec('sudo shutdown -h now > /dev/null 2>&1 & echo $!');
		echo '<script language="javascript">';
		echo 'alert("Little Backup Box is shut down. You can close this page.")';
		echo '</script>';
	    }
	    ?>
	    Read the <a href="https://gumroad.com/l/linux-photography">Linux Photography</a> book
	</div>
    </body>
</html>
