<?php
// Author: Stefan Saam, github@saams.de
// License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt

// lib-language.sh and lib-language-helper.php are a bridge from php i18n to bash
	$WORKING_DIR=dirname(__FILE__);

	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);

	#get argument (translation-key)
	$key="$argv[1]";

	require_once 'i18n.class.helper.php';
	$i18n = i18n_setup($config);

	if ($text=L::$key()) {
		echo $text;
	} else {
		echo $key;
	}
?>
