<!--
sub expects from calling script:
- ${WORKING_DIR}
- included language
-->

<?php
    $HTTP_HOST = $_SERVER['SERVER_PORT']==80?$_SERVER['HTTP_HOST'] . ":80":$_SERVER['HTTP_HOST'];
?>
	<nav>
		<ul>
			<li><a href="/index.php"><?php echo L::mainmenue_main; ?></a></li>
			<li><a href="/sysinfo.php"><?php echo L::mainmenue_sysinfo; ?></a></li>
			<li><a href="/config.php"><?php echo L::mainmenue_config; ?></a></li>
			<li><a href="/tools.php"><?php echo L::mainmenue_tools; ?></a></li>
			<li><a href="/files" target="_blank"><?php echo L::mainmenue_filebrowser; ?></a></li>
			<?php
				if (strpos($_SERVER['SERVER_PORT'], "443") !== false) {
					echo ("<li><a href='http://" . $_SERVER['SERVER_ADDR'] . ":8200'>" . L::mainmenue_minidlna . "</a></li>");
				} else {
					echo ("<li><a href='http://" . str_replace(":" . $_SERVER['SERVER_PORT'], ":8200", $HTTP_HOST) ."'>" . L::mainmenue_minidlna . "</a></li>");
				}
			?>
			<?php
				if (file_exists("${WORKING_DIR}/../mejiro/index.php")) {
					echo ("<li><a href='/mejiro'>" . L::mainmenue_mejiro . "</a></li>");
				}
			?>
			<li class="float-right"><a href="upload.php"><?php echo L::mainmenue_upload; ?></a></li>
		</ul>
	</nav>
