<!--
sub expects from calling script:
- included language
- $config
-->

<?php
    $HTTP_HOST = $_SERVER['SERVER_PORT']==80?$_SERVER['HTTP_HOST'] . ":80":$_SERVER['HTTP_HOST'];

	if ($config["conf_THEME"]=="dark") {
		$nav_theme_class="navbar-dark bg-dark";
	} else {
		$nav_theme_class="navbar-light bg-light";
	}
	$scriptname=basename($_SERVER["SCRIPT_FILENAME"]);
?>

 <nav class="<?= $nav_theme_class; ?>">
      <ul class="flex items-center justify-between flex-wrap p-6 max-w-4xl mx-auto">
        <li class="mr-6"><a class="text-blue-500 hover:text-blue-800 <?= $scriptname=="index.php"?" active":""; ?>" href="/index.php"><?= L::mainmenue_main; ?></a></li>
		<li class="mr-6"><a class="text-blue-500 hover:text-blue-800 <?= $scriptname=="sysinfo.php"?" active":""; ?>" href="/sysinfo.php"><?= L::mainmenue_sysinfo; ?></a></li>
		<li class="mr-6"><a class="text-blue-500 hover:text-blue-800 <?= $scriptname=="config.php"?" active":""; ?>" href="/config.php"><?= L::mainmenue_config; ?></a></li>
		<li class="mr-6"><a class="text-blue-500 hover:text-blue-800 <?= $scriptname=="tools.php"?" active":""; ?>" href="/tools.php"><?= L::mainmenue_tools; ?></a></li>
        <li class="mr-6"><a class="text-blue-500 hover:text-blue-800" href="/files" target="_blank"><?= L::mainmenue_filebrowser; ?></a></li>
		<?php
			if (strpos($_SERVER['SERVER_PORT'], "443") !== false) { ?>
				<li class="mr-6"><a class="text-blue-500 hover:text-blue-800" href="<?= "http://" . $_SERVER['SERVER_ADDR'] . ":8200" ?>" target='_blank'><?= L::mainmenue_minidlna ?></a></li>
			<?php } else { ?>
				<li class="mr-6"><a class="text-blue-500 hover:text-blue-800" href="<?= "http://" . str_replace(":" . $_SERVER['SERVER_PORT'], ":8200", $HTTP_HOST) ?>" target='_blank'><?= L::mainmenue_minidlna ?></a></li>
			<?php } ?>
		<?php if (file_exists("${WORKING_DIR}/../mejiro/index.php")) { ?>
				<li class="mr-6"><a class="text-blue-500 hover:text-blue-800" href='/mejiro' target='_blank'><?= L::mainmenue_mejiro ?></a></li>
        <?php } ?>
      </ul>
</nav>

