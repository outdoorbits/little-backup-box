<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
     /* latin-ext */
     @font-face {
       font-family: 'Lato';
       font-style: normal;
       font-weight: 400;
       src: local('Lato Regular'), local('Lato-Regular'), url(fonts/lato_regular_1.woff2) format('woff2');
       unicode-range: U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
     }
     /* latin */
     @font-face {
       font-family: 'Lato';
       font-style: normal;
       font-weight: 400;
       src: local('Lato Regular'), local('Lato-Regular'), url(fonts/lato_regular_2.woff2) format('woff2');
       unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
     }
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
         width: 11em;  height: 2em;
         background: #3399ff;
         border-radius: 5px;
         color: #fff;
         font-family: 'Lato', sans-serif; font-size: 25px; font-weight: 900;
         letter-spacing: 3px;
         border:none;
     }
     #btn.green {
         background: #33ff00;
     }
     #btn.yellow {
         background: #ffff00;
     }
     #btn.red {
         background: #ff0000;
     }
    </style> 
</head>
<title>BackupPi</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<form method="POST" action="/">
    <div id="content">
	<div id="header"><img src="static/ichigo.svg" height="39px" alt="Ichigo" align=""> BackupPi</div>
	<p class="center">Free disk space on <i>/home</i>: <b>{{freespace_home}}</b> GB</p>
	<hr>
	<p class="left">Sync all data from one USB drive to another. Plugin first the destination drive, wait for the LED to change blink pattern and then plug in the source.</p>
	<p><input id="btn" name="cardbackup" type="submit" value="USB Drive Sync"></p>
	<p class="left">Transfer files directly from the connected camera to the BackupPi SD Card.</p>
	<p><input id="btn" class="green" name="camerabackup" type="submit" value="Camera Backup"></p>
	<p class="left">Back up files from the internal storage of this BackupPi to an USB drive.</p>
	<p><input id="btn" class="yellow" name="devicebackup" type="submit" value="BackupPi Backup"></p>
	<p class="left">Shut down BackupPi</p>
	<p><input id="btn" class="red" name="shutdown" value="Shut down" type="submit" /></p>
    </div>
