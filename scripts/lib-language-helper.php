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
	$WORKING_DIR=dirname(__FILE__);

	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);

	#get argument (translation-key)
	$key="$argv[1]";

	require_once 'i18n.class.helper.php';
	$i18n = i18n_setup($config['conf_LANGUAGE']);

	try {
		echo L::$key();
	}
	catch(Error $e) {
// 		echo 'Message: ' .$e->getMessage();
		echo $key;
	}
?>
