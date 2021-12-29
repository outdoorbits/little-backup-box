<!-- Author: Stefan Saam, github@saams.de
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<?php
	// include i18n class and initialize it
	require_once 'i18n.class.php';

	$i18n = new i18n('lang/{LANGUAGE}.json', 'cache/', 'en');
	if ($config["conf_LANGUAGE"] !== "") {$i18n->setForcedLang($config["conf_LANGUAGE"]);}
	$i18n->init();
?>

<script type="text/javascript" src="js/bootstrap.bundle.min.js"></script>
