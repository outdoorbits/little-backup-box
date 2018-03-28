<head>
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
</head>
<title>Little Backup Box</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<form method="POST" action="/">
    <div id="content">
	<div id="header"><img src="static/ichigo.svg" height="39px" alt="Ichigo" align=""> Little Backup Box</div>
	<p class="center">Free disk space on <i>/home</i>: <b>{{freespace_home}}</b> GB</p>
	<hr>
	<p class="left">Back up a storage card connected via a card reader</p>
	<p><input id="btn" name="cardbackup" type="submit" value="Card backup"></p>
	<p class="left">Transfer files directly from the connected camera</p>
	<p><input id="btn" name="camerabackup" type="submit" value="Camera backup"></p>
	<p class="left">Back up files from the internal storage to an external storage device</p>
	<p><input id="btn" class="orange" name="devicebackup" type="submit" value="Device backup"></p>
	<p class="left">Shut down the Little Backup Box</p>
	<p><input id="btn" class="red" name="shutdown" value="Shut down" type="submit" /></p>
    </div>
