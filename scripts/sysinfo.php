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
	<script src="js/justgage.js"></script>
	<script src="js/raphael.min.js"></script>
</head>

<body>
	<?php
	// include i18n class and initialize it
	require_once 'i18n.class.php';
	$i18n = new i18n('lang/{LANGUAGE}.ini', 'cache/', 'en');
	$i18n->init();
	?>
	<div class="uk-container uk-margin-top">
		<div class="uk-card uk-card-primary uk-card-body uk-width-1-2@m">
			<h1 class="uk-card-title uk-heading-line uk-text-center"><?php echo L::status; ?></h1>
			<div class="uk-flex uk-flex-center">
				<?php
				passthru("./status.sh");
				?>
			</div>
			<div class="uk-flex uk-flex-center">
				<button class="uk-button uk-button-primary uk-margin-top" onClick="history.go(0)" role="button"><?php echo L::refresh_b; ?></button>
			</div>
		</div>
		<div class="uk-card uk-card-default uk-card-body uk-width-1-2@m">
			<h1 class="uk-card-title uk-heading-line uk-text-center"><?php echo L::sysinfo; ?></h1>
			<?php
			$temp = shell_exec('cat /sys/class/thermal/thermal_zone*/temp');
			$temp = round($temp / 1000, 1);
			$cpuusage = 100 - shell_exec("vmstat | tail -1 | awk '{print $15}'");
			$mem = shell_exec("free | grep Mem | awk '{print $3/$2 * 100.0}'");
			$mem = round($mem, 1);
			if (isset($temp) && is_numeric($temp)) { ?>
				<div id="tempgauge"></div>
				<script>
					var t = new JustGage({
						id: "tempgauge",
						value: <?php echo $temp; ?>,
						min: 0,
						max: 100,
						title: "<?php echo L::temp; ?>",
						label: "°C"
					});
				</script>
			<?php } ?>
			<?php if (isset($cpuusage) && is_numeric($cpuusage)) { ?>
				<div id="cpugauge"></div>
				<script>
					var u = new JustGage({
						id: "cpugauge",
						value: <?php echo $cpuusage; ?>,
						min: 0,
						max: 100,
						title: "<?php echo L::cpuload; ?>",
						label: "%"
					});
				</script>
			<?php } ?>
			<?php if (isset($mem) && is_numeric($mem)) { ?>
				<div id="memgauge"></div>
				<script>
					var u = new JustGage({
						id: "memgauge",
						value: <?php echo $mem; ?>,
						min: 0,
						max: 100,
						title: "<?php echo L::memory; ?>",
						label: "%"
					});
				</script>
			<?php } ?>
		</div>
		<div class="uk-card uk-card-default uk-card-body uk-width-1-2@m">
			<h3 class="uk-card-title uk-heading-line uk-text-center"><?php echo L::devices; ?></h3>
			<?php
			echo '<pre>';
			passthru("lsblk");
			echo '</pre>';
			?>
		</div>
		<div class="uk-card uk-card-default uk-card-body uk-width-1-2@m">
			<h3 class="uk-card-title uk-heading-line uk-text-center"><?php echo L::diskspace; ?></h3>
			<?php
			echo '<pre>';
			passthru("df -H");
			echo '</pre>';
			?>
		</div>
		<button class="uk-button uk-button-primary uk-margin-top" onClick="history.go(0)" role="button"><?php echo L::refresh_b; ?></button>
		<a class="uk-button uk-button-default uk-margin-top" href="index.php"><?php echo L::back_b; ?></a>
	</div>
</body>

</html>