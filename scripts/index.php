<html lang="en">
    <!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

    <head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	
	<style>
	 
         <link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet">

	 p.left {
             text-align: left;
         }
         
         p.center {
             text-align: center;
         }
         
         img {
             display: block;
             margin-left: auto;
             margin-right: auto;
         }
         
         #content {
             font: 1em/1.5em 'Lato', sans-serif;
             margin: 0px auto;
             width: 275px;
             text-align: left;
         }
         
         #header {
             font: bold 1.7em/2em 'Lato', sans-serif;
             text-align: center;
         }
         
         #btn {
             width: 11em;
             height: 2em;
             background: #3399ff;
             border-radius: 5px;
             color: #fff;
             font-family: 'Lato', sans-serif;
             font-size: 25px;
             font-weight: 900;
             letter-spacing: 3px;
             border: none;
         }
         
         #btn.orange {
             background: #ff9900;
         }
         
         #btn.red {
             background: #cc0000;
         }
	</style>
    </head>

    <body>
	<title>Little Backup Box</title>

	<div id="content">
            <div id="header"><img src="ichigo.svg" height="39px" alt="Ichigo" align=""> Little Backup Box</div>
            <hr>
            <p class="left">Back up a storage card connected via a card reader</p>
            <p>
		<form method="post">
                    <button id="btn" name="cardbackup">Card backup</button>
		</form>
            </p>
            <p class="left">Transfer files directly from the connected camera</p>
            <p>
		<form method="post">
                    <button id="btn" class="orange" name="camerabackup">Camera backup</button>
		</form>
            </p>
            <p class="left">Shut down the Little Backup Box</p>
            <p>
		<form method="post">
                    <button id="btn" class="red" name="shutdown">Shut down</button>
		</form>
            </p>
	</div>
    </body>

</html>

<?php
if (isset($_POST['cardbackup']))
{
    shell_exec('sudo ./card-backup.sh');
}
if (isset($_POST['camerabackup']))
{
    shell_exec('sudo ./camera-backup.sh');
}
if (isset($_POST['shutdown']))
{
    shell_exec('sudo shutdown -h now');
}
?>
