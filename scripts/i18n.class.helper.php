<?php
/*
# Author: Stefan Saam, github@saams.de

#######################################################################
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
#######################################################################*/

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
