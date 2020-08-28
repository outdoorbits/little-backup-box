<html lang="en">
<!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<meta charset="utf-8">
	<title>Little Backup Box</title>
	<meta charset="utf-8">
	<link rel="shortcut icon" href="favicon.png" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="css/uikit.min.css" />
	<script src="js/uikit.min.js"></script>
	<script src="js/uikit-icons.min.js"></script>
	<style>
		textarea {
			font-size: 15px;
			width: 100%;
			height: 55%;
			line-height: 1.9;
			margin-top: 2em;
		}
	</style>
</head>

<body>
	<div class="uk-container uk-margin-top">
		<?php
		// include i18n class and initialize it
		require_once 'i18n.class.php';
		$i18n = new i18n('lang/{LANGUAGE}.ini', 'cache/', 'en');
		$i18n->init();
		if ($_POST["save"]) {
			Write();
		};
		?>
		<h1 class="uk-heading-line uk-text-center"><span><?php echo L::config; ?><span></h1>
		<?php
		function Read()
		{
			$CONFIGFILE = "config.cfg";
			echo file_get_contents($CONFIGFILE);
		}
		function Write()
		{
			$CONFIGFILE = "config.cfg";
			$fp = fopen($CONFIGFILE, "w");
			$data = $_POST["text"];
			fwrite($fp, $data);
			fclose($fp);
		}
		?>
		<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
			<textarea name="text"><?php Read(); ?></textarea>
			<?php echo '<input class="uk-button uk-button-primary uk-margin-top" type="submit" name="save" value="' . L::edit_save_b . '">'; ?>
			<a class="uk-button uk-button-default uk-margin-top" href="index.php"><?php echo L::back_b; ?></a>
		</form>
	</div>
</body>

</html>