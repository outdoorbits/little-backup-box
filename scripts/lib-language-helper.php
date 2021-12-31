<?php
// Author: Stefan Saam, github@saams.de
// License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt

// lib-language.sh and lib-language-helper.php are a bridge from php i18n to bash
	$WORKING_DIR=dirname(__FILE__);

	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);

	#get argument (translation-key)
	$key="$argv[1]";

	require_once 'i18n.class.php';
	$i18n = new i18n($WORKING_DIR . '/lang/{LANGUAGE}.json', 'cache/', 'en');
	if ($config["conf_LANGUAGE"] !== "") {$i18n->setForcedLang($config["conf_LANGUAGE"]);}
	$i18n->init();

	if ($text=L::$key()) {
		echo $text;
	} else {
		echo $key;
	}
?>
