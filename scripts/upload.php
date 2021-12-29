<!doctype html>

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
	<?php include "${WORKING_DIR}/sub-standards-header-loader.php"; ?>
</head>

<body <?php echo $background; ?>>
	<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>
	<?php include "${WORKING_DIR}/sub-menu.php"; ?>

	<h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;"><?php echo l::upload_upload; ?></h1>
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
