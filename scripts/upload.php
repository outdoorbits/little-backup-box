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
	 button {width: 9em;}
	</style>
    </head>

    <body>
	<div id="content">
	    <a href="/"><div style="margin-bottom: 1.9em;"><img src="svg/logo.svg" height="51px" alt="Little Backup Box"></a></div>
	    <?php
	    // include i18n class and initialize it
	    require_once 'i18n.class.php';
	    $i18n = new i18n('lang/{LANGUAGE}.ini', 'cache/', 'en');
	    $i18n->init();
	    $upload_dir = getenv("HOME") . "/UPLOAD";
	    if(isset($_POST['submit'])){
		// count total files
		$countfiles = count($_FILES['file']['name']);
		// looping all files
		for($i=0;$i<$countfiles;$i++){
		    $filename = $_FILES['file']['name'][$i];
		    if (!file_exists($upload_dir)) {
			mkdir($upload_dir, 0777, true);
		    }
		    // upload file
		    move_uploaded_file($_FILES['file']['tmp_name'][$i], $upload_dir.DIRECTORY_SEPARATOR.$filename);
		}
	    } 
	    ?>
	    <form method='post' action='' enctype='multipart/form-data'>
		<input type="file" name="file[]" id="file" multiple>
		<p></p>
		<button class="btn btn-primary" type="submit" role="button" name="submit"><?php echo L::upload_btn; ?></button>
	    </form>
	    <p>
		<a href="https://gumroad.com/l/little-backup-book"><img src="svg/life-ring.svg" height="35px" alt="Little Backup Book"></a>
	    </p>
	</div>
    </body>
</html>
