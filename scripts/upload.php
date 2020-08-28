<html lang="en">
<!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<title>Little Backup Box</title>
	<meta charset="utf-8">
	<link rel="shortcut icon" href="favicon.png" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="css/uikit.min.css" />
	<script src="js/uikit.min.js"></script>
	<script src="js/uikit-icons.min.js"></script>
</head>

<body>
<div class="uk-container uk-margin-small-top">
		<div class="uk-card uk-card-primary uk-card-body uk-text-center">
		<?php
		// include i18n class and initialize it
		require_once 'i18n.class.php';
		$i18n = new i18n('lang/{LANGUAGE}.ini', 'cache/', 'en');
		$i18n->init();
		$upload_dir = getenv("HOME") . "/UPLOAD";
		if (isset($_POST['submit'])) {
			// count total files
			$countfiles = count($_FILES['file']['name']);
			// looping all files
			for ($i = 0; $i < $countfiles; $i++) {
				$filename = $_FILES['file']['name'][$i];
				if (!file_exists($upload_dir)) {
					mkdir($upload_dir, 0777, true);
				}
				// upload file
				move_uploaded_file($_FILES['file']['tmp_name'][$i], $upload_dir . DIRECTORY_SEPARATOR . $filename);
			}
		}
		?>
		<h1 class="uk-heading-line uk-text-center"><span><?php echo L::upload; ?></span></h1>
		<form method='post' action='' enctype='multipart/form-data'>
			<input class="uk-input" type="file" name="file[]" id="file" multiple>
			<button class="uk-button uk-button-primary uk-margin-top" type='submit' role='button' name='submit'><?php echo L::upload; ?></button>
			<a class="uk-button uk-button-default uk-margin-top" href="index.php"><?php echo L::back_b; ?></a>
		</form>
		</div>
	</div>
</body>

</html>