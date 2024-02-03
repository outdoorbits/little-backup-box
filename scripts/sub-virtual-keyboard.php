<?php
# Author: Stefan Saam, github@saams.de
//
// #######################################################################
// # This program is free software: you can redistribute it and/or modify
// # it under the terms of the GNU General Public License as published by
// # the Free Software Foundation, either version 3 of the License, or
// # (at your option) any later version.
//
// # This program is distributed in the hope that it will be useful,
// # but WITHOUT ANY WARRANTY; without even the implied warranty of
// # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// # GNU General Public License for more details.
//
// # You should have received a copy of the GNU General Public License
// # along with this program.  If not, see <http://www.gnu.org/licenses/>.
// #######################################################################
//
// Usage:
// 1. in the beginning of the main script load the library:
// 		include("sub-virtual-keyboard.php");
//
// 2. in the html head execute this to integrate the css:
// 		virtual_keyboard_css($config["conf_VIRTUAL_KEYBOARD_ENABLED"]);
//
// 3. add <?php echo (virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],classes,type,placement,specialchararcters)); ?\>
//		enabled				= boolean
// 		classes				= string, space separated
// 		type				= one out of ['all', 'keyboard', 'numpad']
// 		placement			= one out of ['top', 'bottom']
// 		specialcharacters	= one out of ['true', 'false']
//
// 4. below the last input and before </body> execute this to activate the keyboard function:
// 		virtual_keyboard_js($config["conf_VIRTUAL_KEYBOARD_ENABLED"],$config["conf_LANGUAGE"],$config["conf_THEME"],'virtual_keyboard'[, 'virtual_keyboard_xyz']);
// as arguments pass all the keyboard types you need



$kioskboard_dir	= '/KioskBoard';

function virtual_keyboard_css($enabled) {
	if (virtual_keyboard_enabled($enabled)) {
		global $kioskboard_dir;

		echo ("<link href='${kioskboard_dir}/kioskboard-2.3.0.min.css' rel='stylesheet'>");
	}
}

function virtual_keyboard_options($enabled,$classes,$type, $placement, $specialcharacters='false') {
	if (virtual_keyboard_enabled($enabled)) {
		if (strlen($classes) == 0) {
			$classes	= 'virtual_keyboard';
		} else {
			$classes	= "virtual_keyboard $classes";
		}

		$data	= "class='$classes' data-kioskboard-type='$type' data-kioskboard-placement='$placement'";
		if ($type !== 'numpad') {
			$data	.= " data-kioskboard-specialcharacters='$specialcharacters'";
		}

		return($data);
	} else {
		if (strlen($classes) == 0) {
			return('');
		} else {
			return("class='$classes'");
		}
	}
}

function virtual_keyboard_js($enabled,$language,$theme,...$virtual_keyboard_class_names) {
	if (virtual_keyboard_enabled($enabled)) {
		echo("<script src='KioskBoard/kioskboard-2.3.0.min.js'></script>");

		$language_keys	= array(
			'ar'=>	'arabic',
			'en'=>	'english',
			'fr'=>	'french',
			'de'=>	'german',
			'hu'=>	'hungarian',
			'fa'=>	'persian',
			'ru'=>	'russian',
			'es'=>	'spanish',
			'tr'=>	'turkish'
		);

		if (! array_key_exists($language,$language_keys)) {
			$language	= 'en';
		}

		$language_filename	= "kioskboard-keys-" . $language_keys[$language] . ".json";
		$keysArrayOfObjects	= file_get_contents("KioskBoard/$language_filename");

		if ($theme !== 'dark') {
			$theme	= 'light';
		}

		foreach ($virtual_keyboard_class_names as $virtual_keyboard_class_name) {

			echo("<script>");
			echo("	KioskBoard.run('.$virtual_keyboard_class_name',{");
			echo("		keysArrayOfObjects: " . $keysArrayOfObjects . ",");
			echo("		allowRealKeyboard: false,");
			echo("		allowMobileKeyboard: true,");
			echo("		language: '$language',");
			echo("		theme: '$theme',");
			echo("	});");
			echo("</script>");
		}
	}
}

function virtual_keyboard_enabled($enabled) {
	return($enabled and ($_SERVER['REMOTE_ADDR'] == '127.0.0.1'));
}

?>
