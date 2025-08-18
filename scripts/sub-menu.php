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


// sub expects from calling script:
// - included language
// - $config
// - $WORKING_DIR

    $HTTP_HOST	= $_SERVER['SERVER_PORT']==80?$_SERVER['HTTP_HOST'] . ":80":$_SERVER['HTTP_HOST'];

	if (isset($_SERVER['HTTPS'])) {
		$PROTOCOL	= "https";
	} else {
		$PROTOCOL	= "http";
	}

	if ($config["conf_THEME"]=="dark") {
		$nav_theme_class 	= "navbar-dark bg-dark";
	} else {
		$nav_theme_class	= "navbar-light bg-light";
	}
	$scriptname=basename($_SERVER["SCRIPT_FILENAME"]);

	exec('which comitup', $comitup_path, $return_code);
	$comitup_installed	= $return_code == 0;
	$comitup_hotspot	= trim(shell_exec("sudo python3 $WORKING_DIR/lib_comitup.py --check_hotspot")) == 'active';
?>

<nav class="navbar navbar-expand-sm <?php echo $nav_theme_class; ?>">
	<div class="container-fluid">

		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>

		<div class="collapse navbar-collapse w-50" id="navbarSupportedContent">
			<ul class="navbar-nav me-auto mb-2 mb-lg-0  w-100">
				<li class="nav-item"><a class="nav-link<?php echo $scriptname=="index.php"?" active":""; ?>" href="/index.php"><?php echo L::mainmenue_main; ?></a></li>
				<li class="nav-item"><a class="nav-link<?php echo $scriptname=="sysinfo.php"?" active":""; ?>" href="/sysinfo.php"><?php echo L::mainmenue_sysinfo; ?></a></li>
				<li class="nav-item"><a class="nav-link<?php echo $scriptname=="tools.php"?" active":""; ?>" href="/tools.php"><?php echo L::mainmenue_tools; ?></a></li>
				<li class="nav-item"><a class="nav-link<?php echo $scriptname=="view.php"?" active":""; ?>" href="/view.php"><?php echo L::mainmenue_view; ?></a></li>
			</ul>
		</div>

		<div class="collapse navbar-collapse w-50" id="navbarSupportedContent">
			<ul class="navbar-nav me-auto mb-2 mb-lg-0  w-100">
				<li class="nav-item"><a class="nav-link" href="/frame.php?page=files"><?php echo L::mainmenue_filebrowser; ?></a></li>
				<?php
					if ($comitup_installed) {
						?>
							<li class="nav-item"><a class="nav-link<?php echo $comitup_hotspot?'':' disabled'; ?>" href="/frame.php?page=comitup">comitup</a></li>
						<?php
					}
				?>
			</ul>
		</div>

		<div class="navbar-collapse collapse" id="navbarSupportedContent">
			<ul class="navbar-nav me-auto mb-2 mb-lg-0">
				<?php
					if (trim(shell_exec("python3 $WORKING_DIR/lib_git.py --update-available")) == 'True') {
						?>
							<li class="nav-item"><a class="nav-link" href="/cmd.php?CMD=update"><nobr><?php echo L::mainmenue_update; ?></nobr></a></li>
						<?php
					}
				?>
				<li class="nav-item"><a class="nav-link<?php echo $scriptname=="setup.php"?" active":""; ?>" href="/setup.php"><?php echo L::mainmenue_config; ?></a></li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" id="navbarDropdownPower" role="button" data-bs-toggle="dropdown" aria-expanded="false">&#9211;</a>
					<ul class="dropdown-menu" aria-labelledby="navbarDropdownPower">
						<li><a class="dropdown-item" href="?reboot=true"><?php echo L::main_reboot_button; ?></a></li>
						<li><a class="dropdown-item" href="?shutdown=true"><?php echo L::main_shutdown_button; ?></a></li>
						<li><a class="dropdown-item" href="<?php echo "{$PROTOCOL}://logout@{$HTTP_HOST}"; ?>"><?php echo L::main_logout_button; ?></a></li>
					</ul>
				</li>
			</ul>
		</div>

	</div>
</nav>

