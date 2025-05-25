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


function logmonitor($sourcefile, $title='', $allow_logfile_operations=false) {
	?>
		<div class="card" style="margin-top: 3em;">
			<?php
				if (! empty($title)) {
					echo '<h2 style="margin-top: 0em;">' . $title . '</h2><hr>';
				}
			?>
			<iframe id="logmonitor" src="<?php echo str_replace('/var/www/little-backup-box', '' ,$sourcefile); ?>" width="100%" height="300" style="background: #FFFFFF;" onfocus="clearIntervalLogMonitor()"></iframe>
			<div class="text-center" style="margin-top: 0.5em;">
				<button name="refresh" onclick="refreshLogMonitor()"><?php echo L::log_refresh_button; ?></button>
				<?php
					if ($allow_logfile_operations) {
						?>
							<a role="button" href="<?php echo str_replace('/var/www/little-backup-box', '' ,$sourcefile); ?>" download="<?php echo basename($sourcefile); ?>"><button>Download</button></a>
							<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
								<button style="margin-top: 2em;" type="delete" name="delete_logfile" class="danger"><?php echo (L::log_delete_button); ?></button>
							</form>
						<?php
					}
				?>
			</div>
		</div>
		<?php
			if (isset($_POST['delete_logfile'])) {
				if (file_exists($sourcefile)) {
					unlink($sourcefile);
				}
				$LogFileHandle = fopen($sourcefile, 'w') or die('Error opening file: ' . $sourcefile);
				fwrite($LogFileHandle,"Little Backup Box\n");
				fclose($LogFileHandle);
			};
}
