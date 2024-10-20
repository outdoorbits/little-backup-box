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

function get_cloudservices($constants) {
	exec("sudo rclone config show --config \"" . $constants["const_MEDIA_DIR"] . '/' . $constants["const_RCLONE_CONFIG_FILE"] . "\" | grep '^\[.*\]$' | sed 's/^\[//' | sed 's/\]$//'",$CloudServices);
	return ($CloudServices);
}

function get_secondary_backup_selector($fieldname, $CloudServices, $config, $NVMe_available, $ignore_preset=false) {

	if ($ignore_preset) {
		$BACKUP_DEFAULT_SOURCE2	= 'none';
		$BACKUP_DEFAULT_TARGET2	= 'none';
	} else {
		$BACKUP_DEFAULT_SOURCE2	= $config["conf_BACKUP_DEFAULT_SOURCE2"];
		$BACKUP_DEFAULT_TARGET2	= $config["conf_BACKUP_DEFAULT_TARGET2"];
	}
	?>

	<select name="<?php echo $fieldname; ?>" id="<?php echo $fieldname; ?>">
		<option value="none none" <?php echo $BACKUP_DEFAULT_SOURCE2 . " " . $BACKUP_DEFAULT_TARGET2=="none none"?" selected":""; ?>><?php echo L::config_backup_none; ?></option>
		<?php
			if (! ($config["conf_RSYNC_SERVER"]=="" or $config["conf_RSYNC_PORT"]=="" or $config["conf_RSYNC_USER"]=="" or $config["conf_RSYNC_PASSWORD"]=="" or $config["conf_RSYNC_SERVER_MODULE"]=="")) {
		?>
				<optgroup label="&rarr; <?php echo L::main_rsync_button; ?>">
					<option value="usb cloud_rsync" <?php echo $BACKUP_DEFAULT_SOURCE2 . " " . $BACKUP_DEFAULT_TARGET2=="usb cloud_rsync"?" selected":""; ?>><?php echo L::main_usb_button . L::right_arrow . L::main_rsync_button; ?></option>
					<?php
						if ($NVMe_available) {
							?>
								<option value="nvme cloud_rsync" <?php echo $BACKUP_DEFAULT_SOURCE2 . " " . $BACKUP_DEFAULT_TARGET2=="nvme cloud_rsync"?" selected":""; ?>><?php echo L::main_nvme_button . L::right_arrow . L::main_rsync_button; ?></option>
							<?php
						}
					?>
					<option value="internal cloud_rsync" <?php echo $BACKUP_DEFAULT_SOURCE2 . " " . $BACKUP_DEFAULT_TARGET2=="internal cloud_rsync"?" selected":""; ?>><?php echo L::main_internal_button . L::right_arrow . L::main_rsync_button; ?></option>
				</optgroup>
		<?php
			}

			foreach($CloudServices as $CloudService) {
		?>
				<optgroup label="&rarr; <?php echo $CloudService; ?>">
					<option value="usb cloud:<?php print $CloudService; ?>" <?php echo $BACKUP_DEFAULT_SOURCE2 . " " . $BACKUP_DEFAULT_TARGET2=="usb cloud:${CloudService}"?" selected":""; ?>><?php echo L::main_usb_button . L::right_arrow . $CloudService; ?></option>
					<?php
						if ($NVMe_available) {
							?>
								<option value="nvme cloud:<?php print $CloudService; ?>" <?php echo $BACKUP_DEFAULT_SOURCE2 . " " . $BACKUP_DEFAULT_TARGET2=="nvme cloud:${CloudService}"?" selected":""; ?>><?php echo L::main_nvme_button . L::right_arrow . $CloudService; ?></option>
							<?php
						}
					?>
					<option value="internal cloud:<?php print $CloudService; ?>" <?php echo $BACKUP_DEFAULT_SOURCE2 . " " . $BACKUP_DEFAULT_TARGET2=="internal cloud:${CloudService}"?" selected":""; ?>><?php echo L::main_internal_button . L::right_arrow . $CloudService; ?></option>
				</optgroup>
		<?php
			}
		?>
	</select>

	<?php

}

function get_nvme_available($WORKING_DIR, $constants, $ignore_fs = false) {
	// 	NVMe available?
	$ignore_fs	= $ignore_fs ? 'True' : 'False';

	exec("sudo python3 ${WORKING_DIR}/lib_storage.py --Action get_available_partitions --skipMounted False --ignore-fs ${ignore_fs}", $Partitions);
	$NVMe_available	= false;

	foreach ($Partitions as $Partition) {
		if (str_starts_with($Partition,"/dev/".$constants['const_STORAGE_NVME_MASK'])) {
			$NVMe_available	= true;
		}
	}

	return($NVMe_available);
}
?>
