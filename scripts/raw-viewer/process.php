<?php
error_reporting(E_ERROR);
include('config.php');
?>

<!DOCTYPE html>
<html lang="en">
<!-- Author: Dmitri Popov, dmpop@linux.com
	 License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<meta charset="utf-8">
	<title>Little Backup Box</title>
	<link rel="shortcut icon" href="../favicon.png" />
	<link rel="stylesheet" href="../css/lit.css">
	<link href="https://fonts.googleapis.com/css2?family=Barlow" rel="stylesheet">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
		img {
			border-radius: 1em;
			max-width: 100%;
			display: block;
			align-self: center;
		}
	</style>
</head>

<body>
	<div class="c">
		<?php
		// include i18n class and initialize it
		require_once '../i18n.class.php';
		$i18n = new i18n('../lang/{LANGUAGE}.ini', 'cache/', 'en');
		$i18n->init();
		?>
		<h1><?php echo L::viewer; ?></h1>
		<hr style="margin-bottom:3em;">
		<?php
		if (isset($_POST["submit"])) {
			$lut = $_POST['lut'];
			$img = $_POST['img'];
			if (!file_exists($result_dir)) {
				mkdir($result_dir, 0777, true);
			}
			$imagick = new \Imagick($prev_dir . $img);
			$imagickPalette = new \Imagick(realpath($lut_dir . $lut));
			$imagick->haldClutImage($imagickPalette);
			$imagick->writeImage($result_dir . $img);
			echo '<a download="' . $result_dir . $img . '" href="' . $result_dir . $img . '" title="Click to download the file"><img alt="Click to download the file" src="' . $result_dir . $img . '"></a>';
		}
		?>
		<hr style="margin-top:3em; margin-bottom:3em;">
		<p><a class="btn" href="index.php">Back</a></p>
	</div>
</body>

</html>