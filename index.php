<html>

    <!-- This program is free software: you can redistribute it and/or modify
	 it under the terms of the GNU General Public License as published by
	 the Free Software Foundation, either version 3 of the License, or
	 (at your option) any later version.

 	 This program is distributed in the hope that it will be useful,
	 but WITHOUT ANY WARRANTY; without even the implied warranty of
	 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 GNU General Public License for more details.

	 You should have received a copy of the GNU General Public License
	 along with this program.  If not, see <http://www.gnu.org/licenses/>. -->

    <body>
	<title>Little Backup Box</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<div id="content">
	    <p class="center">Free disk space: <b><?php echo shell_exec("df -h | grep '/dev/sda' | awk '{print $4}'"); ?></b></p>
	    <form method="POST" action="/">
		<p><input id="btn" name="cardbackup" type="submit" value="Card backup"></p>
		<p class="left">Back up a storage card connected via a card reader</p>
		<p><input id="btn" class="orange" name="camerabackup" type="submit" value="Camera backup"></p>
		<p class="left">Transfer files directly from the connected camera</p>
		<p><input id="btn" class="red" name="shutdown" value="Shut down" type="submit" /></p>
		<p class="left">Shut down the Little Backup Box</p>
	    </form>

	    <style>
	     <link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet">
	     body {
		 font: 15px/25px 'Lato', sans-serif;
	     }
	     p.left {
		 text-align: left;
	     }
	     p.center {
		 text-align: center;
	     }
	     #content {
		 font: 15px/25px 'Open Sans', sans-serif;
		 margin: 0px auto;
		 width: 275px;
		 text-align: left;
	     }
	     #btn {
		 width: 11em;  height: 2em;
		 background: #3399ff;
		 border-radius: 5px;
		 color: #fff;
		 font-family: 'Lato', sans-serif; font-size: 25px; font-weight: 900;
		 letter-spacing: 3px;
		 border:none;
	     }
	     #btn.orange {
		 background: #ff9900;
	     }
	     #btn.red {
		 background: #cc0000;
	     }
	    </style>
	    
	    <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (isset($_POST['cardbackup'])) {
                    echo shell_exec("sudo /home/pi/little-backup-box/card-backup.sh");
		}
		if (isset($_POST['camerabackup'])) {
                    echo shell_exec("sudo /home/pi/little-backup-box/camera-backup.sh");
		}
		if (isset($_POST['shutdown'])) {
                    echo shell_exec("sudo shutdown -h now");
		}
            }	
	    ?>
    </body>
</html>
