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
//$WORKING_DIR, $constants and $config must be defined in main script.-->

if ($config['conf_DISP']=="1" and $config['conf_DISP_RESOLUTION_X'] > 0 and $config['conf_DISP_RESOLUTION_Y'] > 0) {
?>
	<div style="display: flex; justify-content: flex-end;">
		<div style="width: <?php echo $config['conf_DISP_RESOLUTION_X']; ?>px; height: <?php echo $config['conf_DISP_RESOLUTION_Y']; ?>px; background-color: black;">
			<iframe id="display" src="<?php echo str_replace('/var/www/little-backup-box', '' ,$constants['const_DISPLAY_EXPORT_PATH']); ?>" width="<?php echo $config['conf_DISP_RESOLUTION_X']; ?>" height="<?php echo $config['conf_DISP_RESOLUTION_Y']; ?>" style="background: #000000;"></iframe>
		</div>
	</div>
<?php
}
