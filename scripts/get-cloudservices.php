<!-- Author: Stefan Saam, github@saams.de
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->
<!--
expects $constants

defines $CloudServices as array
-->

<?php
	$CloudServices=array();
	exec("sudo rclone config show --config \"" . $constants["const_RCLONE_CONFIG_FILE"] . "\" | grep '^\[.*\]$' | sed 's/^\[//' | sed 's/\]$//'",$CloudServices);
?>
