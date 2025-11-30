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

// Usage:
// 1. in the beginning of the main script load the library:
// 		include("sub-virtual-keyboard.php");
//
// 2. in the html head integrate the css:
// 		<?php virtual_keyboard_css();
//
//$constants and $config must be defined in main script.-->

function display($clear=true) {
	global $config;
	global $constants;

	if ($config['conf_DISP']=="1" and $config['conf_DISP_RESOLUTION_X'] > 0 and $config['conf_DISP_RESOLUTION_Y'] > 0) {
		?>

		<img id="display" src="<?php echo str_replace('/var/www/little-backup-box', '' , $constants['const_DISPLAY_IMAGE_EXPORT_FILE']); ?>" style="width: <?php echo $constants['const_DISPLAY_SIZE_UI_X']; ?>px; height: <?php echo $constants['const_DISPLAY_SIZE_UI_Y']; ?>px; background: #000000; float: right;">

		<?php
		if ($clear) {
			print('<div style="clear: both;"></div>');
		}
	}
}
