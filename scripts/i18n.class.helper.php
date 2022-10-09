<?php
// Author: Stefan Saam, github@saams.de
// License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt

// lib-language.sh and lib-language-helper.php are a bridge from php i18n to bash

function i18n_setup($config) {
	$WORKING_DIR=dirname(__FILE__);

	require_once 'i18n.class.php';
	$i18n = new i18n($WORKING_DIR . '/lang/{LANGUAGE}.json', 'cache/', 'en');

	if ($config["conf_LANGUAGE"] !== "") {$i18n->setForcedLang($config["conf_LANGUAGE"]);}
	$i18n->setCachePath('/var/www/little-backup-box/tmp');

	$i18n->init();

	return($i18n);
}
?>
