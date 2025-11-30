<?php
	namespace tinyfilemanager;

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
#######################################################################

	define('TINYFILEMANAGER_PATH', '/var/www/tinyfilemanager');
	define('TINYFILEMANAGER_CONFIG', TINYFILEMANAGER_PATH . '/config.php');

	class tinyfilemanager {

		private function read_config() {
			try {
				include(TINYFILEMANAGER_CONFIG);
			}
			catch(Exception $e) {
				print('Error reading "' . TINYFILEMANAGER_CONFIG . '": ' . $e);
			}

			# read all SETTINGS comming from config file
			foreach(get_defined_vars() as $key => $value) {
				if (array_key_exists($key, $this->SETTINGS)) {
					$value	= gettype($this->SETTINGS[$key]) == 'boolean' ? $value == 'true' : $value;
					$this->SETTINGS[$key] = $value;
				}
			}

			# read $CONFIG_line
			if (array_key_exists('CONFIG', get_defined_vars())) {
				$CONFIG_read	= substr($CONFIG, strpos($CONFIG, '{') + 1, strpos($CONFIG, '}') -1);
				$CONFIGS_read	= explode(',', $CONFIG_read);

				foreach($CONFIGS_read as $line) {
					$line_parts	= explode(':', $line, 2);
					if (count($line_parts) == 2) {
						$key	= trim($line_parts[0], "\" ");
						$value	= trim($line_parts[1], "\" ");
						$value	= gettype($this->CONFIGS[$key]) == 'boolean' ? $value == 'true' : $value;

						if (array_key_exists($key, $this->CONFIGS)) {
							$this->CONFIGS[$key]	= $value;
						}
					}
				}
			} else {
				print("\$CONFIG not found in \"" . TINYFILEMANAGER_CONFIG . "\"!\n");
			}
		}

		private function update_config($arguments) {
			if (count($arguments) > 0) {

				foreach($arguments as $argument) {
					$arg_parts	= explode('=', $argument, 2);
					if (count($arg_parts) == 2) {
						$key	= trim($arg_parts[0], "\" ");
						$value	= trim($arg_parts[1], "\" ");

						if (array_key_exists($key, $this->CONFIGS)) {
							$value	= gettype($this->CONFIGS[$key]) == 'boolean' ? $value == 'true' : $value;
							$this->CONFIGS[$key] = $value;
						}
						if (array_key_exists($key, $this->SETTINGS)) {
							$value	= gettype($this->SETTINGS[$key]) == 'boolean' ? $value == 'true' : $value;
							$this->SETTINGS[$key] = $value;
						}
					}
				}
			}
		}

		private function write_config() {
			$CONFIGS_line	= "\$CONFIG = '{";
			foreach ($this->CONFIGS as $key => $value) {
				$value	= gettype($this->CONFIGS[$key]) == 'boolean' ? ($value ? 'true' : 'false') : $value;
				$value	= gettype($this->CONFIGS[$key]) == 'string' ? "\"$value\"" : $value;

				$CONFIGS_line	.= ("\"$key\":$value,");
			}
			$CONFIGS_line	.= "}';";
			$CONFIGS_line	= str_replace(",}'", "}'", $CONFIGS_line);

			# write to file
			$config_file	= fopen(TINYFILEMANAGER_CONFIG, 'w') or die('Unable to open file "' . TINYFILEMANAGER_CONFIG . '" for writing.');
			fwrite($config_file, "<?php\n");
			fwrite($config_file, "//Default Configuration\n");
			fwrite($config_file, "$CONFIGS_line\n");

			foreach ($this->SETTINGS as $key => $value) {
				$value	= gettype($this->SETTINGS[$key]) == 'boolean' ? ($value ? 'true' : 'false') : $value;
				$value	= gettype($this->SETTINGS[$key]) == 'string' ? "\"$value\"" : $value;

				fwrite($config_file, "   \$$key = $value;\n");
			}

			fwrite($config_file, "?>\n");
			fclose($config_file);
		}

		function __construct()
			{
				$this->CONFIGS	= array(
					'lang'				=>	'en',
					'error_reporting'	=>	false,
					'show_hidden'		=>	false,
					'hide_Cols'			=>	false,
					'theme'				=>	'light'
				);

				$this->SETTINGS	= array(
					'use_auth'				=> 	false,
					'root_path'				=>	'/var/www/tinyfilemanager/media',
					'root_url'				=>	'files/media',
					'max_upload_size_bytes'	=>	1048576000
				);
			}

		function reconfig($arguments) { # $arguments as array
			$this->read_config();
			$this->update_config($arguments);
			$this->write_config();
		}

	}
?>
