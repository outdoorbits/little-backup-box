<?php
$theme = "dark";
$WORKING_DIR=dirname(__FILE__);
?>

<html lang="en" data-theme="<?php echo $theme; ?>">
<!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<meta charset="utf-8">
	<title>Little Backup Box</title>
	<meta charset="utf-8">
	<link rel="shortcut icon" href="favicon.png" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="css/classless.css">
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
	<?php
	// include i18n class and initialize it
	require_once 'i18n.class.php';
	$i18n = new i18n('lang/{LANGUAGE}.ini', 'cache/', 'en');
	$i18n->init();
	if (isset($_POST['save'])) {
		Write();
	};
	?>
	<nav>
		<ul>
			<?php include "${WORKING_DIR}/menu.php"; ?>
		</ul>
	</nav>
	<h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;"><?php echo L::config; ?></h1>
	<?php
	function Read()
	{
		${CONFIGFILE} = "config.cfg";
		echo file_get_contents(${CONFIGFILE});
	}
	function Write()
	{
		${CONFIGFILE} = "config.cfg";
		$fp = fopen(${CONFIGFILE}, "w");
		$data = $_POST["text"];
		fwrite($fp, $data);
		fclose($fp);
		exec ("dos2linux ./${CONFIGFILE}");
	}
	?>
	<div class="card" style="margin-top: 2em;">
		<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
			<textarea name="text"><?php Read(); ?></textarea>
			<?php echo '<button style="margin-top: 2em;" type="submit" name="save">' . L::edit_save_b . '</button>'; ?>
		</form>
	</div>
	</div>
</body>

</html>
