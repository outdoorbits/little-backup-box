

<?php include_once './header.php'; ?>

<main class='flex-1 overflow-x-hidden overflow-y-auto bg-gray-200 dark:bg-gray-900'>
    <div class='container mx-auto px-6 py-8'>


	<?php
		// write new config
		if (isset($_POST['save'])) {
			write_config();
		}

    // Upload settings
		if (isset($_POST['upload_settings'])) {
			upload_settings();
		}

    // read (new) config
		$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
		foreach($config_standard as $key => $value) {
			if (! isset($config[$key]) ) {
				$config[$key]	= $value;
			}
		}
	?>
        <h1 class='text-gray-700 dark:text-gray-200 text-5xl font-medium'><?= L::config_config; ?></h1>
	<?php

	function check_new_password($title, $pwd_1, $pwd_2) {
		$pwd_valid = false;
			if ($pwd_1 !== $pwd_2) {
				popup($title . "\n" . L::config_alert_password_not_identical, true);
			} elseif (strlen($pwd_1) < 5) {
				popup($title . "\n" . L::config_alert_password_too_short, true);
			} elseif (strpos("_" . $pwd_1,"\\") or strpos("_" . $pwd_1,"\"")) {
				popup($title . "\n" . L::config_alert_password_characters_not_allowed, true);
			} else {
				$pwd_valid=true;
			}

		return($pwd_valid);
	}

	function write_config()
	{
		extract ($_POST);

		list($conf_BACKUP_DEFAULT_SOURCE,$conf_BACKUP_DEFAULT_TARGET)=explode(" ",$BACKUP_MODE,2);
		$conf_POWER_OFF				= isset($conf_POWER_OFF)?"true":"false";
		$conf_NOTIFY				= isset($conf_NOTIFY)?"true":"false";
		$conf_MAIL_HTML				= isset($conf_MAIL_HTML)?"true":"false";
		$conf_DISP					= isset($conf_DISP)?"true":"false";
		$conf_conf_DISP_IP_REPEAT	= isset($conf_conf_DISP_IP_REPEAT)?"true":"false";
		$conf_LOG_SYNC				= isset($conf_LOG_SYNC)?"true":"false";
		$conf_POPUP_MESSAGES		= isset($conf_POPUP_MESSAGES)?"true":"false";

		$conf_PASSWORD_LINE="conf_PASSWORD=\"$conf_PASSWORD_OLD\"";

		if ($conf_MAIL_conf_PASSWORD != "") {
			if (! check_new_password (L::config_alert_password_mail_header, $conf_MAIL_conf_PASSWORD, $conf_MAIL_conf_PASSWORD)) {
				$conf_MAIL_conf_PASSWORD	= "";
			}
		}

		if ($conf_RSYNC_conf_PASSWORD != "") {
			if (! check_new_password (L::config_alert_password_rsync_header, $conf_RSYNC_conf_PASSWORD, $conf_RSYNC_conf_PASSWORD)) {
				$conf_RSYNC_conf_PASSWORD	= "";
			}
		}

		if (isset($conf_PASSWORD_REMOVE)) {
			$conf_PASSWORD_LINE="conf_PASSWORD=\"\"";
			exec("sudo " . $_SERVER['CONTEXT_DOCUMENT_ROOT'] . "/password.sh remove");
			popup($title . "\n" . L::config_alert_password_change_after_reboot,true);
		} elseif ($conf_PASSWORD_1 != "") {
			if (check_new_password (L::config_alert_password_global, $conf_PASSWORD_1, $conf_PASSWORD_2)) {
				$conf_PASSWORD_LINE="conf_PASSWORD=\"$conf_PASSWORD_1\"";
				exec("sudo " . $_SERVER['CONTEXT_DOCUMENT_ROOT'] . "/password.sh set \"" . $conf_PASSWORD_1 . "\"");
				popup($title . "\n" . L::config_alert_password_change_after_reboot,true);
			}
		}

		$CONFIGFILE = "config.cfg";
		$config_file_handle = fopen($CONFIGFILE, "w");

		$config_file_content = <<<CONFIGDATA
conf_LANGUAGE="$conf_LANGUAGE"
conf_BACKUP_DEFAULT_SOURCE="$conf_BACKUP_DEFAULT_SOURCE"
conf_BACKUP_DEFAULT_TARGET="$conf_BACKUP_DEFAULT_TARGET"
conf_BACKUP_CAMERA_FOLDER_MASK="$conf_BACKUP_CAMERA_FOLDER_MASK"
conf_BACKUP_TARGET_BASEDIR_CLOUD="$conf_BACKUP_TARGET_BASEDIR_CLOUD"
conf_POWER_OFF=$conf_POWER_OFF
conf_NOTIFY=$conf_NOTIFY
conf_MAIL_HTML=$conf_MAIL_HTML
conf_DISP=$conf_DISP
conf_conf_DISP_IP_REPEAT=$conf_conf_DISP_IP_REPEAT
conf_THEME=$conf_THEME
conf_BACKGROUND_IMAGE=$conf_BACKGROUND_IMAGE
conf_POPUP_MESSAGES=$conf_POPUP_MESSAGES
conf_LOGLEVEL=$conf_LOGLEVEL
conf_LOG_SYNC=$conf_LOG_SYNC
conf_POWER_OFF_IDLE_TIME=$conf_POWER_OFF_IDLE_TIME
conf_SMTP_SERVER="$conf_SMTP_SERVER"
conf_SMTP_PORT="$conf_SMTP_PORT"
conf_MAIL_USER="$conf_MAIL_USER"
conf_MAIL_conf_PASSWORD="$conf_MAIL_conf_PASSWORD"
conf_MAIL_TO="$conf_MAIL_TO"
conf_RSYNC_SERVER="$conf_RSYNC_SERVER"
conf_RSYNC_PORT="$conf_RSYNC_PORT"
conf_RSYNC_USER="$conf_RSYNC_USER"
conf_RSYNC_conf_PASSWORD="$conf_RSYNC_conf_PASSWORD"
conf_RSYNC_SERVER_MODULE="$conf_RSYNC_SERVER_MODULE"
$conf_PASSWORD_LINE
CONFIGDATA;

		fwrite($config_file_handle, $config_file_content);
		fclose($config_file_handle);
		exec ("dos2unix './" . $CONFIGFILE . "'");
		echo '<div class="card" style="margin-top: 2em;">' . L::config_message_settings_saved . '</div>';
	}

function upload_settings() {
	global $WORKING_DIR, $config, $constants;

	if($_FILES["settings_file"]["name"]) {
		$filename = $_FILES["settings_file"]["name"];
		$source = $_FILES["settings_file"]["tmp_name"];
		$type = $_FILES["settings_file"]["type"];
		$name = explode(".", $filename);
		$accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');
		foreach($accepted_types as $mime_type) {
			if($mime_type == $type) {
				$okay = true;
				break;
			}
		}
		$continue = strtolower($name[1]) == 'zip' ? true : false;
		if(!$continue) {
			popup(L::config_alert_settings_upload_not_zip,$config["conf_POPUP_MESSAGES"]);
		}
		/* PHP current path */
		$filenoext = basename ($filename, '.zip');
		$filenoext = basename ($filenoext, '.ZIP');

		$targetdir = $constants["const_WEB_ROOT_LBB"].'/tmp/unzip/';
		$targetzip = $targetdir . $filename;
		/* create directory if not exists' */
		@mkdir($targetdir, 0777);

		/* here it is really happening */

		if(move_uploaded_file($source, $targetzip)) {
			$zip = new ZipArchive();
			$file_opened = $zip->open($targetzip);  // open the zip file to extract
			if ($file_opened === true) {
				$zip->extractTo($targetdir); // place in the directory with same name
				$zip->close();

				unlink($targetzip);
				$Files_Copied="";

				if (file_exists($targetdir."config.cfg")) {
					@unlink($constants["const_WEB_ROOT_LBB"]/config.cfg);
					if (rename($targetdir."config.cfg",$constants["const_WEB_ROOT_LBB"]."/config.cfg")) {$Files_Copied="\n* 'config.cfg'";}
				}
				if (file_exists($targetdir."rclone.conf")) {
					@unlink($constants["const_WEB_ROOT_LBB"]/config.cfg);
					if (rename($targetdir."rclone.conf",$constants["const_RCLONE_CONFIG_FILE"])) {$Files_Copied=$Files_Copied."\n* 'rclone.cfg'";}
				}
				popup(L::config_alert_settings_upload_success. " ". $Files_Copied,true);

				$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
				if (isset ($config["conf_PASSWORD"]) and check_new_password(L::config_alert_password_global,$config["conf_PASSWORD"],$config["conf_PASSWORD"])) {
					exec("sudo " . $WORKING_DIR . "/password.sh set \"" . $config["conf_PASSWORD"] . "\"");
				} else {
					exec("sudo " . $WORKING_DIR . "/password.sh remove");
				}
				popup(L::config_alert_password_change_after_reboot,true);
			}

		} else {
			popup(L::config_alert_settings_upload_problem,$config["conf_POPUP_MESSAGES"]);
		}

		exec("sudo rm -R ".$targetdir);
	}
}
?>


	<form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST">


        <div class="max-w-4xl mx-auto">
            <button class="mt-5 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" type="submit" name="save"><?= L::config_save_button ?> </button>
        </div>

		<div class="card mt-5">
			<details>
				<summary class="uppercase tracking-wide"><?= L::config_lang_section; ?></summary>

				<h3><?= L::config_lang_header; ?></h3>
					<label for="conf_LANGUAGE"><?= L::config_lang_label; ?></label><br>
						<select name="conf_LANGUAGE" id="conf_LANGUAGE">
						<?php
							echo "<option value='' " . ($config["conf_LANGUAGE"] == ""?" selected":"") . ">" . L::config_lang_browser_detect . "</option>";
							$languages=array();
							exec ("find '" . $WORKING_DIR . "/lang'/*.json -type f ",$languages);
							foreach($languages as $language) {
								$language = basename($language, ".json");
								echo "<option value='" . $language . "' " . ($config["conf_LANGUAGE"] == $language?" selected":"") . ">" . $language . "</option>";
							}
						?>
					</select>
			</details>
		</div>

		<div class="card mt-5">
			<details>
				<summary class='uppercase tracking-wide'><?= L::config_backup_section; ?></summary>

				<h3><?= L::config_backup_header; ?></h3>
					<label for="BACKUP_MODE"><?= L::config_backup_label; ?></label><br>

					<select name="BACKUP_MODE" id="BACKUP_MODE">
						<option value="none none" <?= $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="none none"?" selected":""; ?>><?= L::config_backup_none; ?></option>
						<option value="storage external" <?= $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="storage external"?" selected":""; ?>><?= L::config_backup_storage_external; ?></option>
						<option value="storage internal" <?= $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="storage internal"?" selected":""; ?>><?= L::config_backup_storage_internal; ?></option>
						<option value="camera external" <?= $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="camera external"?" selected":""; ?>><?= L::config_backup_camera_external; ?></option>
						<option value="camera internal" <?= $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="camera internal"?" selected":""; ?>><?= L::config_backup_camera_internal; ?></option>
						<option value="ios external" <?= $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="ios external"?" selected":""; ?>><?= L::config_backup_ios_external; ?></option>
						<option value="ios internal" <?= $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="ios internal"?" selected":""; ?>><?= L::config_backup_ios_external; ?></option>
					</select>

				<h3><?= L::config_backup_camera_folder_mask_header; ?></h3>
					<label for="conf_BACKUP_CAMERA_FOLDER_MASK"><?= L::config_backup_camera_folder_mask_label; ?></label><br>
					<input type="text" id="conf_BACKUP_CAMERA_FOLDER_MASK" name="conf_BACKUP_CAMERA_FOLDER_MASK" size="6" value="<?= $config['conf_BACKUP_CAMERA_FOLDER_MASK']; ?>">

				<h3><?= L::config_backup_target_basedir_cloud_header; ?></h3>
					<label for="conf_BACKUP_TARGET_BASEDIR_CLOUD"><?= L::config_backup_target_basedir_cloud_label; ?></label><br>
					<input type="text" id="conf_BACKUP_TARGET_BASEDIR_CLOUD" name="conf_BACKUP_TARGET_BASEDIR_CLOUD" size="6" value="<?= $config['conf_BACKUP_TARGET_BASEDIR_CLOUD']; ?>">

				<h3><?= L::config_backup_power_off_header; ?></h3>
					<label for="conf_POWER_OFF"><?= L::config_backup_power_off_label; ?></label><br>
					<input type="checkbox" id="conf_POWER_OFF" name="conf_POWER_OFF" <?= $config['conf_POWER_OFF']=="1"?"checked":""; ?>>
			</details>
		</div>

		<div class="card mt-5">
			<details>
				<summary class='uppercase tracking-wide'><?= L::config_behavior_section; ?></summary>

				<h3><?= L::config_behavior_notify_header; ?></h3>
					<label for="conf_NOTIFY"><?= L::config_behavior_notify_label; ?></label><br>
					<input type="checkbox" id="conf_NOTIFY" name="conf_NOTIFY"<?= $config['conf_NOTIFY']=="1"?"checked":""; ?>>

				<h3><?= L::config_behavior_mail_html_header; ?></h3>
					<label for="conf_MAIL_HTML"><?= L::config_behavior_mail_html_label; ?></label><br>
					<input type="checkbox" id="conf_MAIL_HTML" name="conf_MAIL_HTML"<?= $config['conf_MAIL_HTML']=="1"?"checked":""; ?>>

				<h3><?= L::config_behavior_display_header; ?></h3>
					<label for="conf_DISP"><?= L::config_behavior_display_label; ?></label><br>
					<input type="checkbox" id="conf_DISP" name="conf_DISP" <?= $config['conf_DISP']=="1"?"checked":""; ?>>

				<h3><?= L::config_behavior_disp_ip_header; ?></h3>
					<label for="conf_conf_DISP_IP_REPEAT"><?= L::config_behavior_disp_ip_label; ?></label><br>
					<input type="checkbox" id="conf_conf_DISP_IP_REPEAT" name="conf_conf_DISP_IP_REPEAT" <?= $config['conf_conf_DISP_IP_REPEAT']=="1"?"checked":""; ?>>

				<h3><?= L::config_behavior_loglevel_header; ?></h3>
					<p><?= L::config_behavior_loglevel_text . " " . $constants["const_LOGFILE"]; ?>)</p>
					<label for="conf_LOGLEVEL"><?= L::config_behavior_loglevel_label; ?></label><br>
					<select name="conf_LOGLEVEL" id="conf_LOGLEVEL">
						<option value="1" <?= $config["conf_LOGLEVEL"]=="1"?" selected":""; ?>>1, <?= L::config_behavior_loglevel_minimum; ?></option>
						<option value="2" <?= $config["conf_LOGLEVEL"]=="2"?" selected":""; ?>>2, <?= L::config_behavior_loglevel_medium; ?></option>
						<option value="3" <?= $config["conf_LOGLEVEL"]=="3"?" selected":""; ?>>3, <?= L::config_behavior_loglevel_maximum; ?></option>
					</select>

				<h3><?= L::config_behavior_log_sync_protokoll_header; ?></h3>
					<label for="conf_LOG_SYNC"><?= L::config_behavior_log_sync_protokoll_label; ?></label><br>
					<input type="checkbox" id="conf_LOG_SYNC" name="conf_LOG_SYNC"<?= $config['conf_LOG_SYNC']=="1"?"checked":""; ?>>

				<h3><?= L::config_behavior_power_off_idle_time_header; ?></h3>
					<label for="conf_POWER_OFF_IDLE_TIME"><?= L::config_behavior_power_off_idle_time_label; ?></label><br>
					<select name="conf_POWER_OFF_IDLE_TIME" id="conf_POWER_OFF_IDLE_TIME">
						<?php
							$IDLE_TIME_OPTIONS=array(0,2,5,10,15,20,30);
							foreach($IDLE_TIME_OPTIONS as $IDLE_TIME_OPTION) {
								echo "<option value=\"" . $IDLE_TIME_OPTION ."\"". ($config["conf_POWER_OFF_IDLE_TIME"]==$IDLE_TIME_OPTION?" selected":"") . ">" . ($IDLE_TIME_OPTION=="0"?L::config_behavior_power_off_idle_time_none:$IDLE_TIME_OPTION . " " . L::minutes_long) ."</option>";
							}
						?>

					</select>

			</details>
		</div>

        <?php if(false): ?>
		<div class="card mt-5">
			<details>
				<summary class='uppercase tracking-wide'><?= L::config_view_section; ?></summary>

				<h3><?= L::config_view_theme_header; ?></h3>
					<label for="conf_THEME"><?= L::config_view_theme_label; ?></label><br>
						<select name="conf_THEME" id="conf_THEME">
						<option value="light" <?= $config["conf_THEME"] == "light"?" selected":""; ?>><?= L::config_view_theme_light; ?></option>
						<option value="dark" <?= $config["conf_THEME"] == "dark"?" selected":""; ?>><?= L::config_view_theme_dark; ?></option>
						<option value="sepia" <?= $config["conf_THEME"] == "sepia"?" selected":""; ?>><?= L::config_view_theme_sepia; ?></option>
					</select>

				<h3><?= L::config_view_bg_image_header; ?></h3>
					<label for="conf_BACKGROUND_IMAGE"><?= L::config_view_bg_image_label; ?> &quot;<?= $WORKING_DIR . "/img/backgrounds" ;?>&quot;.</label><br>
						<select name="conf_BACKGROUND_IMAGE" id="conf_BACKGROUND_IMAGE">
						<option value="" <?= $config["conf_BACKGROUND_IMAGE"] ==""?" selected":""; ?>>none</option>
						<?php
							$bg_images=array();
							exec ("find '" . $WORKING_DIR . "/img/backgrounds' -type f -exec file --mime-type {} \+ | awk -F: '{if ($2 ~/image\//) print $1}'",$bg_images);
							foreach($bg_images as $bg_image) {
								$bg_image = basename($bg_image);
								echo "<option value='" . $bg_image . "' " . ($config["conf_BACKGROUND_IMAGE"] == $bg_image?" selected":"") . ">" . $bg_image . "</option>";
							}
						?>
					</select>

				<h3><?= L::config_view_popup_header; ?></h3>
					<label for="conf_POPUP_MESSAGES"><?= L::config_view_popup_label; ?></label><br>
					<input type="checkbox" id="conf_POPUP_MESSAGES" name="conf_POPUP_MESSAGES" <?= $config['conf_POPUP_MESSAGES']=="1"?"checked":""; ?>>

			</details>
		</div>
        <?php endif; ?>

		<div class="card mt-5">
			<details>
				<summary class='uppercase tracking-wide'><?= L::config_mail_section; ?></summary>

				<h3><?= L::config_mail_smtp_header; ?></h3>
					<label for="conf_SMTP_SERVER"><?= L::config_mail_smtp_label; ?></label><br>
					<input type="text" id="conf_SMTP_SERVER" name="conf_SMTP_SERVER" size="6" value="<?= $config['conf_SMTP_SERVER']; ?>">

				<h3><?= L::config_mail_port_header; ?></h3>
					<label for="conf_SMTP_PORT"><?= L::config_mail_port_label . " " . $config_standard["conf_SMTP_PORT"]; ?>)</label><br>
					<input type="text" id="conf_SMTP_PORT" name="conf_SMTP_PORT" size="20" value="<?= $config['conf_SMTP_PORT']; ?>">

				<h3><?= L::config_mail_user_header; ?></h3>
					<label for="conf_MAIL_USER"><?= L::config_mail_user_label; ?></label><br>
					<input type="text" id="conf_MAIL_USER" name="conf_MAIL_USER" size="20" value="<?= $config['conf_MAIL_USER']; ?>">

				<h3><?= L::config_mail_password_header; ?></h3>
					<label for="conf_MAIL_conf_PASSWORD"><?= L::config_mail_password_label; ?></label><br>
					<input type="password" id="conf_MAIL_conf_PASSWORD" name="conf_MAIL_conf_PASSWORD" size="20" value="<?= $config['conf_MAIL_conf_PASSWORD']; ?>">

				<h3><?= L::config_mail_recipient_header; ?></h3>
					<label for="conf_MAIL_TO"><?= L::config_mail_recipient_label; ?></label><br>
					<input type="text" id="conf_MAIL_TO" name="conf_MAIL_TO" size="20" value="<?= $config['conf_MAIL_TO']; ?>">
			</details>
		</div>

		<div class="card mt-5">
			<details>
				<summary class='uppercase tracking-wide'><?= L::config_rsync_section; ?></summary>

				<h3><?= L::config_rsync_server_header; ?></h3>
					<label for="conf_RSYNC_SERVER"><?= L::config_rsync_server_label; ?></label><br>
					<input type="text" id="conf_RSYNC_SERVER" name="conf_RSYNC_SERVER" size="6" value="<?= $config['conf_RSYNC_SERVER']; ?>">

				<h3><?= L::config_rsync_port_header; ?></h3>
					<label for="conf_RSYNC_PORT"><?= L::config_rsync_port_label . " " . $config_standard["conf_RSYNC_PORT"]; ?>)</label><br>
					<input type="text" id="conf_RSYNC_PORT" name="conf_RSYNC_PORT" size="20" value="<?= $config['conf_RSYNC_PORT']; ?>">

				<h3><?= L::config_rsync_user_header; ?></h3>
					<label for="conf_RSYNC_USER"><?= L::config_rsync_user_label; ?></label><br>
					<input type="text" id="conf_RSYNC_USER" name="conf_RSYNC_USER" size="20" value="<?= $config['conf_RSYNC_USER']; ?>">

				<h3><?= L::config_rsync_password_header; ?></h3>
					<label for="conf_RSYNC_conf_PASSWORD"><?= L::config_rsync_password_label; ?></label><br>
					<input type="password" id="conf_RSYNC_conf_PASSWORD" name="conf_RSYNC_conf_PASSWORD" size="20" value="<?= $config['conf_RSYNC_conf_PASSWORD']; ?>">

				<h3><?= L::config_rsync_module_header; ?></h3>
					<label for="conf_RSYNC_SERVER_MODULE"><?= L::config_rsync_module_label1 .  $config_standard['conf_RSYNC_SERVER_MODULE'] . L::config_rsync_module_label2; ?></label><br>
					<input type="text" id="conf_RSYNC_SERVER_MODULE" name="conf_RSYNC_SERVER_MODULE" size="20" value="<?= $config['conf_RSYNC_SERVER_MODULE']; ?>">
			</details>
		</div>

		<div class="card mt-5">
			<details>
				<summary class='uppercase tracking-wide'><?= L::config_password_section; ?></summary>

				<h3><?= L::config_password_header; ?></h3>
					<input type="hidden" id="conf_PASSWORD_OLD" name="conf_PASSWORD_OLD" value="<?= $config['conf_PASSWORD']; ?>">
					<label for="conf_PASSWORD_1"><?= L::config_password_label1; ?></label><br>
					<input type="password" id="conf_PASSWORD_1" name="conf_PASSWORD_1" size="20" value="">
					<label for="conf_PASSWORD_2"><?= L::config_password_label2; ?></label><br>
					<input type="password" id="conf_PASSWORD_2" name="conf_PASSWORD_2" size="20" value="">

					<?php
						if ($config['conf_PASSWORD'] != "") {
							echo "<h3>" . L::config_password_remove_header . "</h3>";
							echo "<label for=\"conf_PASSWORD_REMOVE\">" . L::config_password_remove_label ."</label><br>";
							echo "<input type=\"checkbox\" id=\"conf_PASSWORD_REMOVE\" name=\"conf_PASSWORD_REMOVE\">";
						}
					?>
			</details>
		</div>

		<div class="max-w-4xl mx-auto">
            <button type="submit" class="mt-5 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" name="save"><?= L::config_save_button ?></button>
		</div>
	</form>


		<div class="card mt-5">
			<details>
				<summary class='uppercase tracking-wide'><?= L::config_cloud_section; ?></summary>

				<h3><?= L::config_cloud_header; ?></h3>
					<p>
						Depending on your cloud-service, your configuration can expire. Maybe you have to repeat this step always before cloud-access.<br>
						<?php
							if (empty($config['conf_PASSWORD'])) {
								echo L::config_username . ": 'lbb', " . L::config_password . ": 'lbb'";
							}
							else {
								echo L::config_username . ": 'lbb', " . L::config_password_as_set_in . " '" . L::config_password_section . "'";
							}
						?>

					</p>
					<?php
						if (strpos($_SERVER['SERVER_PORT'], "443") !== false) {
							$rclone_link="https://" . $_SERVER['SERVER_ADDR'] . ":8443";
						} else {
							$rclone_link="http://" . $_SERVER['SERVER_ADDR'] . ":81";
						}
					?>
					<a href="<?= $rclone_link; ?>" target="_blank"><?= L::config_cloud_rclone_gui; ?></a>

			</details>
		</div>

		<div class="card mt-5">
			<details>
				<summary class='uppercase tracking-wide'><?= L::config_save_settings_section; ?></summary>
					<h3><?= L::config_save_settings_download_header; ?></h3>
						<p><?= L::config_save_settings_download_text; ?></p>
						<a href="download-settings.php"><?= L::config_save_settings_download_link_text; ?></a>

					<h3><?= L::config_save_settings_upload_header; ?></h3>
						<form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST" enctype="multipart/form-data">
							<label for="settings_file"><?= L::config_save_settings_upload_label; ?></label>
							<input type="file" name="settings_file" id="settings_file">
							<button style="margin-top: 2em;" type="submit" name="upload_settings"><?= L::config_save_settings_upload_button; ?></button>
						</form>

			</details>
		</div>

		<div class="card mt-5">
			<details>
				<summary class='uppercase tracking-wide'><?= L::config_update_section; ?></summary>
				<?= L::config_update_text; ?>
			</details>
		</div>
    </div>
</main>
<?php include_once './footer.php'; ?>
