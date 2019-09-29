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
	 textarea {
	     font-size: 15px;
	     width: 75%;
	     height: 55%;
	     line-height: 1.9;
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
	    <a href="/"><img src="logo.svg" height="51px" alt="Little Backup Box"></a>
            <h2>Little Backup Box</h2>
            <?php
            function Read() {
		$CONFIGFILE = "config.cfg";
                echo file_get_contents($CONFIGFILE);
            }
            function Write() {
		$CONFIGFILE = "config.cfg";
                $fp = fopen($CONFIGFILE, "w");
                $data = $_POST["text"];
                fwrite($fp, $data);
                fclose($fp);
            }
            ?>
            <?php
	    // include i18n class and initialize it
	    require_once 'i18n.class.php';
	    $i18n = new i18n('lang/{LANGUAGE}.ini', 'cache/', 'en');
	    $i18n->init();
            if ($_POST["submit_check"]){
		Write();
            };
            ?>      
            <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
		<textarea name="text"><?php Read(); ?></textarea><br /><br />
		<input type="submit" name="submit" value="<?php echo L::edit_save_btn; ?>">
		<input type="hidden" name="submit_check" value="1">
            </form>
            <p>Read the <a href='https://gumroad.com/l/linux-photography'>Linux Photography</a> book</p>
	</div>
    </body>
</html>
