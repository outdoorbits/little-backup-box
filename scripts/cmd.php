<!-- Author: Stefan Saam, github@saams.de
License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->
<!doctype html>

<?php
	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
	$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='/img/backgrounds/" . $config["conf_BACKGROUND_IMAGE"] . "'";

# expected parameters:
# CMD: "update", "format", "f3"
# optional parameters:
# PARAM1, PARAM2

?>

<html lang="<?php echo $config["conf_LANGUAGE"]; ?>" data-theme="<?php echo $theme; ?>">

<head>
	<?php include "${WORKING_DIR}/sub-standards-header-loader.php"; ?>
</head>

<body <?php echo $background; ?>>
<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>

<?php

if (count($_GET) > 0) {
	extract($_GET);
} else {
	extract($_POST);
}

if (! isset($CMD)) {$CMD	= "";}

switch($CMD) {
	case 'update':
		$CMD_HEADER			= L::cmd_update_header;
		$INFO_TEXT			= L::cmd_update_warning;
		$CMD_DESCRIPTION	= "";
		$CMD_ARGUMENTS		= "CMD=update";
		$PASSWORD_REQ		= True;
		$ALLOW_MAIL_RESULT	= True;
		break;

	case 'format':
		$CMD_HEADER			= L::cmd_format_header;
		$INFO_TEXT			= L::cmd_format_warning;
		$CMD_DESCRIPTION	= L::cmd_format_description.": <ul class='danger'><li>" . L::cmd_format_header . ": $PARAM1 &rarr; $PARAM2</li></ul>";
		$CMD_ARGUMENTS		= "CMD=format&PARAM1=$PARAM1&PARAM2=$PARAM2";
		$PASSWORD_REQ		= True;
		$ALLOW_MAIL_RESULT	= True;
		break;

	case 'f3':
		$CMD_HEADER			= L::cmd_no_cmd;
		$INFO_TEXT			= "";
		$CMD_DESCRIPTION	= "";
		$CMD_ARGUMENTS		= "";
		$PASSWORD_REQ		= False;
		$ALLOW_MAIL_RESULT	= False;

		switch($PARAM2) {
			case 'f3probe_non_destructive':
				$CMD_HEADER			= L::cmd_f3_header;
				$INFO_TEXT			= L::cmd_f3_warning_non_destructive;
				$CMD_DESCRIPTION	= L::cmd_f3_description.": <ul class='danger'><li>" . L::cmd_f3_header . ": $PARAM1 &rarr; " . L::cmd_f3_description_non_destructive . "</li></ul>";
				$CMD_ARGUMENTS		= "CMD=f3&PARAM1=$PARAM1&PARAM2=$PARAM2";
				$PASSWORD_REQ		= True;
				$ALLOW_MAIL_RESULT	= True;
				break;

			case 'f3probe_destructive':
				$CMD_HEADER			= L::cmd_f3_header;
				$INFO_TEXT			= L::cmd_f3_warning_destructive;
				$CMD_DESCRIPTION	= L::cmd_f3_description.": <ul class='danger'><li>" . L::cmd_f3_header . ": $PARAM1 &rarr; " . L::cmd_f3_description_destructive . "</li></ul>";
				$CMD_ARGUMENTS		= "CMD=f3&PARAM1=$PARAM1&PARAM2=$PARAM2";
				$PASSWORD_REQ		= True;
				$ALLOW_MAIL_RESULT	= True;
				break;
		}
		break;

			case 'comitup_reset':
				$CMD_HEADER			= L::config_comitup_section;
				$INFO_TEXT			= '';
				$CMD_DESCRIPTION	= L::config_comitup_text;
				$CMD_ARGUMENTS		= "CMD=comitup&PARAM1=reset";
				$PASSWORD_REQ		= True;
				$ALLOW_MAIL_RESULT	= True;
				break;
	default:
		$CMD_HEADER			= L::cmd_no_cmd;
		$INFO_TEXT			= "";
		$CMD_DESCRIPTION	= "";
		$CMD_ARGUMENTS		= "";
		$PASSWORD_REQ		= False;
		$ALLOW_MAIL_RESULT	= False;
}

$PASSWORD_ASK	= false;
if ($PASSWORD_REQ) {
	if (isset($PWD)) {
		$PASSWORD_ASK	= ($PWD !== $config['conf_PASSWORD']);
	} else {
		$PASSWORD_ASK	= true;
	}
}
?>

	<h1><?php echo $CMD_HEADER; ?></h1>
	<p>
		<b><u><?php echo $INFO_TEXT; ?></u></b><br>

		<?php echo $CMD_DESCRIPTION; ?><br>
	</p>

	<?php
		if ($PASSWORD_ASK) {
// 			password check necessary
			if ($config['conf_PASSWORD'] != "") {
	?>
			<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
				<input type="hidden" name="CMD" value="<?php echo $CMD; ?>">
				<input type="hidden" name="PARAM1" value="<?php echo $PARAM1; ?>">
				<input type="hidden" name="PARAM2" value="<?php echo $PARAM2; ?>">
				<div class="card" style="margin-top: 2em;">

					<?php
						if (
						($ALLOW_MAIL_RESULT) and
							(strlen($config['conf_SMTP_SERVER']) > 0) and
							(strlen($config['conf_SMTP_PORT']) > 0) and
							(strlen($config['conf_MAIL_SECURITY']) > 0) and
							(strlen($config['conf_MAIL_USER']) > 0) and
							(strlen($config['conf_MAIL_PASSWORD']) > 0) and
							(strlen($config['conf_MAIL_FROM']) > 0) and
							(strlen($config['conf_MAIL_TO']) > 0)
						) {
							echo '<input type="checkbox" name="MAIL_RESULT" id="MAIL_RESULT" checked>&nbsp;';
							echo '<label for="MAIL_RESULT">' . L::cmd_mail_result . ':</label><br>';
						}
					?>

					<label for="PWD"><?php echo L::cmd_input_password; ?>:</label>
					<input type="password" size="20" name="PWD" id="PWD"><br>

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
		} elseif ($CMD_ARGUMENTS != "") { ?>
<!-- 			run command -->
		<?php if ($MAIL_RESULT) {$CMD_ARGUMENTS = $CMD_ARGUMENTS . '&MAIL_RESULT=true';} ?>
		<iframe id="cmdmonitor" src="/cmd-runner.php?<?php echo $CMD_ARGUMENTS; ?>" width="100%" height="500" style="background: #FFFFFF;"></iframe>
		<p>
			<a href="/"><?php echo L::cmd_link_text_home_running; ?></a>
		</p>
	<?php } ?>
</body>
</html>

