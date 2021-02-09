<?php
error_reporting(E_ERROR);
include('config.php');
?>
<!DOCTYPE html>
<html>

<head>
	<title>Little Backup Box</title>
	<meta charset="utf-8">
	<link rel="shortcut icon" href="favicon.png" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="../css/classless.css">
	<style>
		div.gallery img {
			width: 100%;
			height: 9em;
			object-fit: scale-down;
		}

		div.desc {
			padding: 0.5em;
			text-align: center;
		}

		* {
			box-sizing: border-box;
		}

		.responsive {
			padding: 0 6px;
			float: left;
			width: 24.99999%;
		}

		@media only screen and (max-width: 800px) {
			.responsive {
				width: 49.99999%;
				margin: 6px 0;
			}
		}

		@media only screen and (max-width: 500px) {
			.responsive {
				width: 100%;
			}
		}
	</style>
</head>

<body>
		<?php
		// include i18n class and initialize it
		require_once '../i18n.class.php';
		$i18n = new i18n('../lang/{LANGUAGE}.ini', 'cache/', 'en');
		$i18n->init();
		?>
		<nav>
		<ul>
		<li><a href="../index.php"><?php echo L::main; ?></a></li>
			<li><a href="../sysinfo.php"><?php echo L::sysinfo; ?></a></li>
			<li><a href="../config.php"><?php echo L::config; ?></a></li>
		</ul>
	</nav>
		<h1><?php echo L::viewer; ?></h1>
		<hr style="margin-bottom: 1.5em;">
		<?php
		function extract_preview_jpeg($work_dir, $prev_dir)
		{
			shell_exec('exiftool -b -PreviewImage -w ' . $prev_dir . '%f.JPG -r ' . $work_dir);
		}

		function auto_level($prev_dir)
		{
			$files = glob($prev_dir . '*.JPG');
			foreach ($files as $file) {
				shell_exec('mogrify -auto-level ' . $prev_dir . basename($file));
			}
		}
		
		define('IMAGEPATH', $prev_dir);
		foreach (glob(IMAGEPATH . '*.JPG') as $filename) {
			echo '<div class="responsive">';
			echo '<div class="gallery">';
			echo '<div uk-lightbox>';
			echo '<a href="' . $filename . '">';
			echo '<img src="' . $filename . '" alt="' . $filename . '">';
			echo '</a>';
			echo '</div>';
			echo '<div class="desc">' . basename($filename) . '</div>';
			echo '</div>';
			echo '</div>';
		}
		?>
		<div class="uk-clearfix"></div>
		<hr style="margin-bottom: 1.5em;">
		<form method='POST' action=''>
			<button type="submit" name="refresh"><?php echo L::refresh_b; ?></button>
			<a class="uk-button uk-button-default uk-margin-top" href="../index.php"><?php echo L::back_b; ?></a>
		</form>
		<?php
		if (isset($_POST["refresh"])) {
			if (!file_exists($prev_dir)) {
				shell_exec('rm -rf ' . $prev_dir);
			}
			shell_exec('mkdir -p ' . $prev_dir);
			extract_preview_jpeg($work_dir, $prev_dir);
			if ($enable_auto_level) {
				auto_level($prev_dir);
			}
			echo '<meta http-equiv="refresh" content="0">';
		}
		?>
	</div>
</body>

</html>