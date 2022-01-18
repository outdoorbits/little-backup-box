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

 <nav class="navbar navbar-expand-sm <?php echo $nav_theme_class; ?>">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse order-1 w-100" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0  w-100">
        <li class="nav-item"><a class="nav-link<?php echo $scriptname=="index.php"?" active":""; ?>" href="/index.php"><?php echo L::mainmenue_main; ?></a></li>
		<li class="nav-item"><a class="nav-link<?php echo $scriptname=="sysinfo.php"?" active":""; ?>" href="/sysinfo.php"><?php echo L::mainmenue_sysinfo; ?></a></li>
		<li class="nav-item"><a class="nav-link<?php echo $scriptname=="tools.php"?" active":""; ?>" href="/tools.php"><?php echo L::mainmenue_tools; ?></a></li>
        <li class="nav-item"><a class="nav-link" href="/files" target="_blank"><?php echo L::mainmenue_filebrowser; ?></a></li>
		<?php
			if (strpos($_SERVER['SERVER_PORT'], "443") !== false) {
				echo ("<li class=\"nav-item\"><a class=\"nav-link\" href='http://" . $_SERVER['SERVER_ADDR'] . ":8200' target='_blank'>" . L::mainmenue_minidlna . "</a></li>");
			} else {
				echo ("<li class=\"nav-item\"><a class=\"nav-link\" href='http://" . str_replace(":" . $_SERVER['SERVER_PORT'], ":8200", $HTTP_HOST) ."' target='_blank'>" . L::mainmenue_minidlna . "</a></li>");
			}
		?>
		<?php
			if (file_exists("${WORKING_DIR}/../mejiro/index.php")) {
				echo ("<li class=\"nav-item\"><a class=\"nav-link\" href='/mejiro' target='_blank'>" . L::mainmenue_mejiro . "</a></li>");
			}
		?>
		</ul>
	</div>

    <div class="navbar-collapse collapse order-3" id="navbarSupportedContent">
		<ul class="navbar-nav me-auto mb-2 mb-lg-0">
			<li class="nav-item"><a class="nav-link<?php echo $scriptname=="config.php"?" active":""; ?>" href="/config.php"><?php echo L::mainmenue_config; ?></a></li>
		</ul>
    </div>
  </div>
</nav>

