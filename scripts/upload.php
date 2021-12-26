<?php
$WORKING_DIR=dirname(__FILE__);
$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);

$theme = $config["conf_THEME"];
$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='/img/backgrounds/" . $config["conf_BACKGROUND_IMAGE"] . "'";

// read config
$config = parse_ini_file("$WORKING_DIR/config.cfg", false);

// Upload directory
$upload_dir = $config["INTERAL_BACKUP_DIR"] . "/UPLOAD";

if (! is_dir($upload_dir)) {
	mkdir($upload_dir, 0777, true);
}

function filesize_human_readable ($Bytes) {
	$Bytes_hr	= "";

	if ($Bytes >= pow(1024,4)) {
		$Bytes_hr	= round ($Bytes/pow(1024,4),1) . " TB";
	}
	elseif ($Bytes >= pow(1024,3)) {
		$Bytes_hr	= round ($Bytes/pow(1024,3),1) . " GB";
	}
	elseif ($Bytes >= pow(1024,2)) {
		$Bytes_hr	= round ($Bytes/pow(1024,2),1) . " MB";
	}
	elseif ($Bytes >= 1024) {
		$Bytes_hr	= round ($Bytes/1024,1) . " kB";
	}
	else {
		$Bytes_hr	= $Bytes . " B";
	}

	return($Bytes_hr);
}
?>

<html lang="en" data-theme="<?php echo $theme; ?>">
<!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<title>Little Backup Box</title>
	<meta charset="utf-8">
	<link rel="shortcut icon" href="favicon.png" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="css/classless.css">
</head>

<body <?php echo $background; ?>>
	<?php
	// include i18n class and initialize it
	require_once 'i18n.class.php';
	$i18n = new i18n('lang/{LANGUAGE}.json', 'cache/', 'en');
	if ($config["conf_LANGUAGE"] !== "") {$i18n->setForcedLang($config["conf_LANGUAGE"]);}
	$i18n->init();
	?>
	<nav>
		<ul>
			<?php include "${WORKING_DIR}/sub-menu.php"; ?>
        </ul>
	</nav>
	<div class="card" style="margin-top: 3em;">
		<?php
		if (isset($_POST['submit'])) {
			// count total files
			$countfiles = count($_FILES['file']['name']);
			echo "Files uploaded: " . $countfiles . "<br>";
			echo "<ol>";
			// looping all files
			for ($i = 0; $i < $countfiles; $i++) {
				$filename = $_FILES['file']['name'][$i];
				$filesize = $_FILES['file']['size'][$i];
				$fileerror = $_FILES['file']['error'][$i];
				if (!file_exists($upload_dir)) {
					mkdir($upload_dir, 0777, true);
				}
				// upload file
				move_uploaded_file($_FILES['file']['tmp_name'][$i], $upload_dir . DIRECTORY_SEPARATOR . $filename);
				echo "<li>" . $filename . " " . filesize_human_readable($filesize) . " " . ($fileerror==0?"o.k.":"Error " . $fileerror) . "</li>";
			}
			echo "</ol>";
		}
		?>
		<h1 style="margin-bottom: 1em;"><?php echo L::upload_upload; ?></h1>
		<form method='post' action='' enctype='multipart/form-data'>
			<input type="file" name="file[]" id="file" multiple>
			<button type='submit' role='button' name='submit'><?php echo L::upload_upload; ?></button>
		</form>
	</div>
</body>

</html>
