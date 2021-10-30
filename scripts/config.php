<?php
$theme = "dark";
$WORKING_DIR=dirname(__FILE__);

$config_standard = parse_ini_file("$WORKING_DIR/config-standards.cfg", false);
?>

<html lang="en" data-theme="<?php echo $theme; ?>">
<!-- Author: Dmitri Popov, dmpop@linux.com; Stefan Saam, github@saams.de
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<meta charset="utf-8">
	<title>Little Backup Box</title>
	<meta charset="utf-8">
	<link rel="shortcut icon" href="favicon.png" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="css/classless.css">
	<style>
		textarea {
			font-size: 15px;
			width: 100%;
			height: 55%;
			line-height: 1.9;
			margin-top: 2em;
		}
	</style>
</head>

<body>
	<?php
	// include i18n class and initialize it
	require_once 'i18n.class.php';
	$i18n = new i18n('lang/{LANGUAGE}.ini', 'cache/', 'en');
	$i18n->init();

	// write new config
	if (isset($_POST['save'])) {
		Write();
	};

	// read new config
	$config = parse_ini_file("$WORKING_DIR/config.cfg", false);
	foreach($config_standard as $key => $value) {
		if (! isset($config[$key]) ) {
			$config[$key]	= $value;
		}
	}
	?>
	<nav>
		<ul>
			<?php include "${WORKING_DIR}/sub-menu.php"; ?>
		</ul>
	</nav>
	<h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;"><?php echo L::config; ?></h1>
	<?php

	function Write()
	{
		extract ($_POST);

		list($BACKUP_DEFAULT_SOURCE,$BACKUP_DEFAULT_TARGET)=explode(" ",$BACKUP_MODE,2);
		$POWER_OFF=$POWER_OFF=="on"?"true":"false";
		$NOTIFY=$NOTIFY=="on"?"true":"false";
		$MAIL_HTML=$MAIL_HTML=="on"?"true":"false";
		$DISP=$DISP=="on"?"true":"false";
		$DISP_IP_REPEAT=$DISP_IP_REPEAT=="on"?"true":"false";
		$LOG=$LOG=="on"?"true":"false";

		${CONFIGFILE} = "config.cfg";
		$config_file_handle = fopen(${CONFIGFILE}, "w");

		$config_file_content = <<<CONFIGDATA
BACKUP_DEFAULT_SOURCE="$BACKUP_DEFAULT_SOURCE"
BACKUP_DEFAULT_TARGET="$BACKUP_DEFAULT_TARGET"
POWER_OFF=$POWER_OFF
NOTIFY=$NOTIFY
MAIL_HTML=$MAIL_HTML
DISP=$DISP
DISP_IP_REPEAT=$DISP_IP_REPEAT
LOG=$LOG
STORAGE_DEV_MASK="$STORAGE_DEV_MASK"
STORAGE_MOUNT_POINT="$STORAGE_MOUNT_POINT"
SOURCE_MOUNT_POINT="$SOURCE_MOUNT_POINT"
IOS_MOUNT_POINT="$IOS_MOUNT_POINT"
INTERAL_BACKUP_DIR="$INTERAL_BACKUP_DIR"
SMTP_SERVER="$SMTP_SERVER"
SMTP_PORT="$SMTP_PORT"
MAIL_USER="$MAIL_USER"
MAIL_PASSWORD="$MAIL_PASSWORD"
MAIL_TO="$MAIL_TO"
RSYNC_SERVER="$RSYNC_SERVER"
RSYNC_PORT="$RSYNC_PORT"
RSYNC_USER="$RSYNC_USER"
RSYNC_PASSWORD="$RSYNC_PASSWORD"
RSYNC_PATH="$RSYNC_PATH"
CONFIGDATA;

		fwrite($config_file_handle, $config_file_content);
		fclose($config_file_handle);
		exec ("dos2linux ./${CONFIGFILE}");
		echo '<div class="card" style="margin-top: 2em;">config.cfg written successfully</div>';
	}

	?>


	<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">

		<div class="card" style="margin-top: 2em;">
			<h2>Backup</h2>

				<h3>Default backup mode</h3>
					 <label for="BACKUP_MODE">Select the default backup-mode:</label>

					<select name="BACKUP_MODE" id="BACKUP_MODE">
						<option value="none none" <?php echo $config["BACKUP_DEFAULT_SOURCE"] . " " . $config["BACKUP_DEFAULT_TARGET"]=="none none"?"selected":""; ?>>no automatic backup</option>
						<option value="storage external" <?php echo $config["BACKUP_DEFAULT_SOURCE"] . " " . $config["BACKUP_DEFAULT_TARGET"]=="storage external"?"selected":""; ?>>Source storage to external storage</option>
						<option value="storage internal" <?php echo $config["BACKUP_DEFAULT_SOURCE"] . " " . $config["BACKUP_DEFAULT_TARGET"]=="storage internal"?"selected":""; ?>>Source storage to internal storage</option>
						<option value="camera external" <?php echo $config["BACKUP_DEFAULT_SOURCE"] . " " . $config["BACKUP_DEFAULT_TARGET"]=="camera external"?"selected":""; ?>>Camera to external storage</option>
						<option value="camera internal" <?php echo $config["BACKUP_DEFAULT_SOURCE"] . " " . $config["BACKUP_DEFAULT_TARGET"]=="camera internal"?"selected":""; ?>>Camera to internal storage</option>
						<option value="iOS external" <?php echo $config["BACKUP_DEFAULT_SOURCE"] . " " . $config["BACKUP_DEFAULT_TARGET"]=="iOS external"?"selected":""; ?>>iOS to external storage</option>
						<option value="iOS internal" <?php echo $config["BACKUP_DEFAULT_SOURCE"] . " " . $config["BACKUP_DEFAULT_TARGET"]=="iOS internal"?"selected":""; ?>>iOS to internal storage</option>
					</select>

				<h3>Power off</h3>
					<p>Power off after backup</p>
					<input type="checkbox" id="POWER_OFF" name="POWER_OFF" size="6" <?php echo $config['POWER_OFF']=="1"?"checked":""; ?>>

		</div>

		<div class="card" style="margin-top: 2em;">
			<h2>Behavior</h2>

				<h3>Send mails</h3>
					<p>Try to send mails about actual links to this Little Backup Box and about finished backups?</p>
					<input type="checkbox" id="NOTIFY" name="NOTIFY"<?php echo $config['NOTIFY']=="1"?"checked":""; ?>>

				<h3>Send mails in HTML-format</h3>
					<p>If mails are activated, should they be formated in HTML?</p>
					<input type="checkbox" id="MAIL_HTML" name="MAIL_HTML"<?php echo $config['MAIL_HTML']=="1"?"checked":""; ?>>

				<h3>Display</h3>
					<p>Output to display.</p>
					<input type="checkbox" id="DISP" name="DISP" size="6" <?php echo $config['DISP']=="1"?"checked":""; ?>>

				<h3>Repeat IP on display</h3>
					<p>If display is activated, print IP every minute.</p>
					<input type="checkbox" id="DISP_IP_REPEAT" name="DISP_IP_REPEAT" size="6" <?php echo $config['DISP_IP_REPEAT']=="1"?"checked":""; ?>>

				<h3>Log to file</h3>
					<p>Log to file for debugging?<br>
					(Logfile: "<?php echo $WORKING_DIR; ?>/tmp/little-backup-box.log")</p>
					<input type="checkbox" id="LOG" name="LOG"<?php echo $config['LOG']=="1"?"checked":""; ?>>

		</div>

		<div class="card" style="margin-top: 2em;">
			<h2>Devices</h2>

				<h3>Storage-device-mask</h3>
					<p>The common first characters of all mountable external storage-devices. (default: "<?php echo $config_standard["STORAGE_DEV_MASK"]; ?>" like <u>sd</u>a, <u>sd</u>a1, <u>sd</u>b etc.)</p>
					<input type="text" id="STORAGE_DEV_MASK" name="STORAGE_DEV_MASK" size="6" value="<?php echo $config['STORAGE_DEV_MASK']; ?>">

				<h3>Storage-mountpoint</h3>
					<p>The mountpoint for the external target-storage. (default: "<?php echo $config_standard["STORAGE_MOUNT_POINT"]; ?>")</p>
					<input type="text" id="STORAGE_MOUNT_POINT" name="STORAGE_MOUNT_POINT" size="20" value="<?php echo $config['STORAGE_MOUNT_POINT']; ?>">

				<h3>Source-mountpoint</h3>
					<p>The mountpoint for the external source-storage. (default: "<?php echo $config_standard["SOURCE_MOUNT_POINT"]; ?>")</p>
					<input type="text" id="SOURCE_MOUNT_POINT" name="SOURCE_MOUNT_POINT" size="20" value="<?php echo $config['SOURCE_MOUNT_POINT']; ?>">

				<h3>iOS-mountpoint</h3>
					<p>The mountpoint for iOS-source-devices. (default: "<?php echo $config_standard["IOS_MOUNT_POINT"]; ?>")</p>
					<input type="text" id="IOS_MOUNT_POINT" name="IOS_MOUNT_POINT" size="20" value="<?php echo $config['IOS_MOUNT_POINT']; ?>">

				<h3>Internal Backup-Directory</h3>
					<p>The path to the internal target-storage. (default: "<?php echo $config_standard["INTERAL_BACKUP_DIR"]; ?>")</p>
					<input type="text" id="INTERAL_BACKUP_DIR" name="INTERAL_BACKUP_DIR" size="20" value="<?php echo $config['INTERAL_BACKUP_DIR']; ?>">
		</div>

		<div class="card" style="margin-top: 2em;">
			<h2>Mail-server-configuration</h2>

				<h3>SMTP-Server</h3>
					<p>Address of the SMTP-mailserver</p>
					<input type="text" id="SMTP_SERVER" name="SMTP_SERVER" size="6" value="<?php echo $config['SMTP_SERVER']; ?>">

				<h3>SMTP-Port</h3>
					<p>Port of the SMTP-Mailserver (default: "<?php echo $config_standard["SMTP_PORT"]; ?>")</p>
					<input type="text" id="SMTP_PORT" name="SMTP_PORT" size="20" value="<?php echo $config['SMTP_PORT']; ?>">

				<h3>Username</h3>
					<p>Username for the mailserver</p>
					<input type="text" id="MAIL_USER" name="MAIL_USER" size="20" value="<?php echo $config['MAIL_USER']; ?>">

				<h3>Password</h3>
					<p>Password for the mailserver</p>
					<input type="password" id="MAIL_PASSWORD" name="MAIL_PASSWORD" size="20" value="<?php echo $config['MAIL_PASSWORD']; ?>">

				<h3>Recipient</h3>
					<p>Mailaddress of the recipient</p>
					<input type="text" id="MAIL_TO" name="MAIL_TO" size="20" value="<?php echo $config['MAIL_TO']; ?>">
		</div>

		<div class="card" style="margin-top: 2em;">
			<h2>rsync-server-configuration</h2>

				<h3>rsync-Server</h3>
					<p>Address of the rsync-server</p>
					<input type="text" id="RSYNC_SERVER" name="RSYNC_SERVER" size="6" value="<?php echo $config['RSYNC_SERVER']; ?>">

				<h3>rsync-Port</h3>
					<p>Port of the rsync-server (default: "<?php echo $config_standard["RSYNC_PORT"]; ?>")</p>
					<input type="text" id="RSYNC_PORT" name="RSYNC_PORT" size="20" value="<?php echo $config['RSYNC_PORT']; ?>">

				<h3>Username</h3>
					<p>Username for the rsync-server</p>
					<input type="text" id="RSYNC_USER" name="RSYNC_USER" size="20" value="<?php echo $config['RSYNC_USER']; ?>">

				<h3>Password</h3>
					<p>Password for the rsync-server</p>
					<input type="password" id="RSYNC_PASSWORD" name="RSYNC_PASSWORD" size="20" value="<?php echo $config['RSYNC_PASSWORD']; ?>">

				<h3>rsync-path</h3>
					<p>Path at the rsync-server, e.g. "/little-backup-box" (default: "<?php echo $config_standard["RSYNC_PATH"]; ?>")</p>
					<input type="text" id="RSYNC_PATH" name="RSYNC_PATH" size="20" value="<?php echo $config['RSYNC_PATH']; ?>">
		</div>
		<div class="card" style="margin-top: 2em;">
			<?php echo '<button style="margin-top: 2em;" type="submit" name="save">' . L::edit_save_b . '</button>'; ?>
		</div>
	</form>

	</div>
</body>

</html>
