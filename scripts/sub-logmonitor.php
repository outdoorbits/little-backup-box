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
?>

<div class="card" style="margin-top: 3em;">
	<h2 style="margin-top: 0em;"><?php echo L::log_logmonitor; ?></h2>
	<hr>
	<iframe id="logmonitor" src="/tmp/little-backup-box.log" width="100%" height="300" style="background: #FFFFFF;" onfocus="clearIntervalLogMonitor()"></iframe>
	<div class="text-center" style="margin-top: 0.5em;">
		<button name="refresh" onclick="window.location.reload();"><?php echo L::log_refresh_button; ?></button>
		<a role="button" href="/tmp/little-backup-box.log" download="little-backup-box.log"><button>Download</button></a>

		<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
			<button style="margin-top: 2em;" type="delete" name="delete" class="danger"><?php echo (L::log_delete_button); ?></button>
		</form>
	</div>
</div>
<?php
	if (isset($_POST['delete'])) {
		unlink($constants["const_LOGFILE"]);
		$LogFileHandle = fopen($constants["const_LOGFILE"], 'w') or die('Error opening file: ' . $constants["const_LOGFILE"]);
		fwrite($LogFileHandle,"");
		fclose("$LogFileHandle");
	};
?>
