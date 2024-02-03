<!-- Author: Stefan Saam, github@saams.de
License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->
<!doctype html>

<?php
	ini_set("session.use_only_cookies", 0);
	ini_set("session.use_trans_sid", 1);

	session_start();

	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
	$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='" . $constants["const_MEDIA_DIR"] . "/" . $constants["const_BACKGROUND_IMAGES_DIR"] . "/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	include("sub-virtual-keyboard.php");
# expected parameters:
# CMD: "update", "format", "f3"
# optional parameters:
# PARAM1, PARAM2

?>

<html lang="<?php echo $config["conf_LANGUAGE"]; ?>" data-theme="<?php echo $theme; ?>">

<head>
	<?php
		include "${WORKING_DIR}/sub-standards-header-loader.php";
		virtual_keyboard_css($config["conf_VIRTUAL_KEYBOARD_ENABLED"]);
	?>
</head>

<body <?php echo $background; ?>>
<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>

<?php

if (count($_GET) > 0) {
	$INPUT	= $_GET;
} else {
	$INPUT	= $_POST;
}
// allowed parameters
$PWD						= isset($INPUT['PWD']) ? $INPUT['PWD'] : '';
$MAIL_ASKED					= isset($INPUT['MAIL_ASKED']) ? $INPUT['MAIL_ASKED'] : '';

$_SESSION['MAIL_RESULT']	= isset($INPUT['MAIL_RESULT']);

$_SESSION['CMD']			= isset($INPUT['CMD']) ? $INPUT['CMD'] : '';
$_SESSION['PARAM1']			= isset($INPUT['PARAM1']) ? $INPUT['PARAM1'] : '';
$_SESSION['PARAM2']			= isset($INPUT['PARAM2']) ? $INPUT['PARAM2'] : '';

session_write_close();

switch($_SESSION['CMD']) {
	case 'update':
		$CMD_HEADER			= L::cmd_update_header;
		$INFO_TEXT			= L::cmd_update_warning;
		$CMD_DESCRIPTION	= "";
		$PASSWORD_REQ		= True;
		$ALLOW_MAIL_RESULT	= False;
		break;

	case 'update_development':
		$CMD_HEADER			= L::cmd_update_development_header;
		$INFO_TEXT			= L::cmd_update_development_warning;
		$CMD_DESCRIPTION	= "";
		$PASSWORD_REQ		= True;
		$ALLOW_MAIL_RESULT	= False;
		break;

	case 'fsck':
		$CMD_HEADER			= L::cmd_fsck_header;
		$INFO_TEXT			= L::cmd_fsck_warning;
		$CMD_DESCRIPTION	= "";
		$PASSWORD_REQ		= ($_SESSION['PARAM2'] == 'repair');
		$ALLOW_MAIL_RESULT	= True;
		break;

	case 'format':
		$CMD_HEADER			= L::cmd_format_header;
		$INFO_TEXT			= L::cmd_format_warning;
		$CMD_DESCRIPTION	= L::cmd_format_description.": <ul class='danger'><li>" . L::cmd_format_header . ": " . $_SESSION['PARAM1'] . " &rarr; " . $_SESSION['PARAM2'] . "</li></ul>";
		$PASSWORD_REQ		= True;
		$ALLOW_MAIL_RESULT	= True;
		break;

	case 'f3':
		$CMD_HEADER			= L::cmd_no_cmd;
		$INFO_TEXT			= "";
		$CMD_DESCRIPTION	= "";
		$PASSWORD_REQ		= False;
		$ALLOW_MAIL_RESULT	= False;

		switch($_SESSION['PARAM2']) {
			case 'f3probe_non_destructive':
				$CMD_HEADER			= L::cmd_f3_header;
				$INFO_TEXT			= L::cmd_f3_warning_non_destructive;
				$CMD_DESCRIPTION	= L::cmd_f3_description.": <ul class='danger'><li>" . L::cmd_f3_header . ": " . $_SESSION['PARAM1'] . " &rarr; " . L::cmd_f3_description_non_destructive . "</li></ul>";
				$PASSWORD_REQ		= True;
				$ALLOW_MAIL_RESULT	= True;
				break;

			case 'f3probe_destructive':
				$CMD_HEADER			= L::cmd_f3_header;
				$INFO_TEXT			= L::cmd_f3_warning_destructive;
				$CMD_DESCRIPTION	= L::cmd_f3_description.": <ul class='danger'><li>" . L::cmd_f3_header . ": " . $_SESSION['PARAM1'] . " &rarr; " . L::cmd_f3_description_destructive . "</li></ul>";
				$PASSWORD_REQ		= True;
				$ALLOW_MAIL_RESULT	= True;
				break;
		}
		break;

			case 'comitup_reset':
				$CMD_HEADER			= L::config_comitup_section;
				$INFO_TEXT			= '';
				$CMD_DESCRIPTION	= L::config_comitup_text;
				$PASSWORD_REQ		= True;
				$ALLOW_MAIL_RESULT	= True;
				break;
	default:
		$CMD_HEADER			= L::cmd_no_cmd;
		$INFO_TEXT			= "";
		$CMD_DESCRIPTION	= "";
		$PASSWORD_REQ		= False;
		$ALLOW_MAIL_RESULT	= False;
}

$PASSWORD_ASK	= false;
if ($PASSWORD_REQ) {
	if ($PWD !== '') {
		$PASSWORD_ASK	= ($PWD !== $config['conf_PASSWORD']);
	} else {
		$PASSWORD_ASK	= true;
	}
}

$MAIL_ASK = false;
if ($ALLOW_MAIL_RESULT and ($MAIL_ASKED == '')) {
	$MAIL_ASK	= (
		(strlen($config['conf_SMTP_SERVER']) > 0) and
		(strlen($config['conf_SMTP_PORT']) > 0) and
		(strlen($config['conf_MAIL_SECURITY']) > 0) and
		(strlen($config['conf_MAIL_USER']) > 0) and
		(strlen($config['conf_MAIL_PASSWORD']) > 0) and
		(strlen($config['conf_MAIL_FROM']) > 0) and
		(strlen($config['conf_MAIL_TO']) > 0)
	);
}

if (isset($CMD_HEADER)) {
?>

	<h1><?php echo $CMD_HEADER; ?></h1>
	<p>
		<b><u><?php echo $INFO_TEXT; ?></u></b><br>

		<?php echo $CMD_DESCRIPTION; ?><br>
	</p>

	<?php
		if ($PASSWORD_ASK or $MAIL_ASK) {
// 			password check necessary
			if ($config['conf_PASSWORD'] != "") {
	?>
			<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
				<input type="hidden" name="CMD" value="<?php echo $_SESSION['CMD']; ?>">
				<input type="hidden" name="PARAM1" value="<?php echo $_SESSION['PARAM1']; ?>">
				<input type="hidden" name="PARAM2" value="<?php echo $_SESSION['PARAM2']; ?>">

				<div class="card" style="margin-top: 2em;">

					<?php
						if ($MAIL_ASK) {
							echo '<input type="hidden" name="MAIL_ASKED" value="1">';
							echo '<input type="checkbox" name="MAIL_RESULT" id="MAIL_RESULT" checked>&nbsp;';
							echo '<label for="MAIL_RESULT">' . L::cmd_mail_result . ':</label><br>';
						}
					?>

					<?php
						if ($PASSWORD_ASK) {
							echo '<label for="PWD">' . L::cmd_input_password . ':</label>';
							echo '<input type="password" ' . virtual_keyboard_options('','all','bottom','true') . ' size="20" name="PWD" id="PWD"><br>';
						}
					?>

					<button style="margin-top: 2em;" type="submit" name="upload_settings"><?php echo L::cmd_execute; ?></button>
				</div>
			</form>
	<?php
			} else {
	?>
				<div class="card" style="margin-top: 2em;">
					<?php echo l::cmd_password_input_info . "<br><a href ='/config.php'>" . l::cmd_password_set_link . '</a>'; ?><br>
				</div>
	<?php
			}
			?>
				<p>
					<a href="/"><?php echo L::cmd_link_text_home; ?></a>
				</p>
			<?php
		} elseif (isset($_SESSION['CMD'])) {
			?>
<!-- 			run command -->

		<iframe id="cmdmonitor" src="/cmd-runner.php?<?php echo htmlspecialchars(SID); ?>" width="100%" height="500" style="background: #FFFFFF;"></iframe>
		<p>
			<a href="/"><?php echo L::cmd_link_text_home_running; ?></a>
		</p>
	<?php
		}
}
		virtual_keyboard_js($config["conf_VIRTUAL_KEYBOARD_ENABLED"],$config["conf_LANGUAGE"],$config["conf_THEME"],'virtual_keyboard');
	?>
</body>
</html>

