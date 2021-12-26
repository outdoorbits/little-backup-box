<?php
$WORKING_DIR=dirname(__FILE__);
$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);

$theme = $config["conf_THEME"];
$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='/img/backgrounds/" . $config["conf_BACKGROUND_IMAGE"] . "'";

include($WORKING_DIR . "/sub-popup.php");

$LogFile = "${WORKING_DIR}/tmp/little-backup-box.log";
?>

<html lang="en" data-theme="<?php echo $theme; ?>">
<!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
        <meta charset="utf-8">
        <title>Little Backup Box</title>
        <meta charset="utf-8">
        <link rel="shortcut icon" href="favicon.png" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="css/classless.css">

        <script src="js/refresh_iframe.js"></script>

</head>

<body onload="refreshIFrame()" <?php echo $background; ?>>
        <?php
			// include i18n class and initialize it
			require_once 'i18n.class.php';
			$i18n = new i18n('lang/{LANGUAGE}.json', 'cache/', 'en');
			if ($config["conf_LANGUAGE"] !== "") {$i18n->setForcedLang($config["conf_LANGUAGE"]);}
			$i18n->init();
        ?>

        <h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;"><?php echo l::tools_tools; ?></h1>
        <nav>
                <ul>
                    <?php include "${WORKING_DIR}/sub-menu.php"; ?>
                </ul>
        </nav>

        <div class="card">
            <h3 class="text-center" style="margin-top: 0em;"><?php echo l::tools_mount_header; ?></h3>
            <hr>
                <form class="text-center" style="margin-top: 1em;" method="POST">
                    <?php
                        $button = trim(shell_exec("./mount-storage.sh check usb_1"))==""?"<button name='mount_usb_1'>" . l::tools_mount_b . " USB 1</button>":"<button name='umount_usb_1'>" . l::tools_umount_b . " USB 1</button>";
                        echo ($button);
                        $button = trim(shell_exec("./mount-storage.sh check usb_2"))==""?"<button name='mount_usb_2'>" . l::tools_mount_b . " USB 2</button>":"<button name='umount_usb_2'>" . l::tools_umount_b . " USB 2</button>";
                        echo ($button);

                    ?>
                </form>
        </div>

        <div class="card" style="margin-top: 3em;">
            <h3 class="text-center" style="margin-top: 0em;"><?php echo l::tools_repair; ?></h3>
            <hr>
                <form class="text-center" style="margin-top: 1em;" method="POST">
                        <label for="partition"><?php echo l::tools_select_partition ?></label>
                        <select name="partition">
                            <?php
                            unset ($devices);
                            exec("ls /dev/sd* | xargs -n 1 basename", $devices);
                            foreach ($devices as $n => $device) {
                                    echo "<option value='$device'>$device</option>";
                            }
                            echo "</select>";
                            echo ("<button name='fsck_check'>" . l::tools_fsck_check_b . "</button>");
                            echo ("<button name='fsck_autorepair' class='danger'>" . l::tools_fsck_autorepair_b . "</button>");
                            ?>
                </form>
        </div>

        <?php include "sub-logmonitor.php"; ?>

        <div class="card" style="margin-top: 3em;">
            <details>
                <summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo l::tools_help; ?></summary>
                <p><?php echo l::tools_help_text; ?></p>
            </details>
        </div>

        <?php
        if (isset($_POST['mount_usb_1'])) {
                $command = "./mount-storage.sh mount usb_1";
                $MSG=date("H:i:s") . "\n> ${command}:\\n" . shell_exec("${command}") . "\\n" . shell_exec ("cat \"${LogFile}\"");
                exec ("echo \"${MSG}\" > \"${LogFile}\"");
                echo "<script>";
					echo "window.location = window.location.href;";
                echo "</script>";
        }
        if (isset($_POST['umount_usb_1'])) {
                $command = "./mount-storage.sh umount usb_1";
                $MSG=date("H:i:s") . "\n> ${command}:\\n" . shell_exec("${command}") . "\\n" . shell_exec ("cat \"${LogFile}\"");
                exec ("echo \"${MSG}\" > \"${LogFile}\"");
                echo "<script>";
					echo "window.location = window.location.href;";
                echo "</script>";
        }
        if (isset($_POST['mount_usb_2'])) {
                $command = "./mount-storage.sh mount usb_2";
                $MSG=date("H:i:s") . "\n> ${command}:\\n" . shell_exec("${command}") . "\\n" . shell_exec ("cat \"${LogFile}\"");
                exec ("echo \"${MSG}\" > \"${LogFile}\"");
                echo "<script>";
					echo "window.location = window.location.href;";
                echo "</script>";
        }
        if (isset($_POST['umount_usb_2'])) {
                $command = "./mount-storage.sh umount usb_2";
                $MSG=date("H:i:s") . "\n> ${command}:\\n" . shell_exec("${command}") . "\\n" . shell_exec ("cat \"${LogFile}\"");
                exec ("echo \"${MSG}\" > \"${LogFile}\"");
                echo "<script>";
					echo "window.location = window.location.href;";
                echo "</script>";
        }
        if (isset($_POST['fsck_check'])) {

                $device = $_POST['partition'];

                popup($device . l::tools_fsck_check_m,$config["conf_POPUP_MESSAGES"]);
                echo "</script>";

                $command = "sudo umount /dev/${device}";
                $MSG=date("H:i:s") . "\n> ${command}:\\n" . shell_exec("${command}");

                $command = "sudo fsck /dev/${device}";
                $MSG=date("H:i:s") . "\n> ${command}:\\n" . shell_exec("${command}") . "\\n" . shell_exec ("cat \"${LogFile}\"");
                exec ("echo \"${MSG}\" > \"${LogFile}\"");
        }
        if (isset($_POST['fsck_autorepair'])) {

                $device = $_POST['partition'];

                popup($device . l::tools_fsck_autorepair_m,$config["conf_POPUP_MESSAGES"]);
                echo "</script>";

                $command = "sudo umount /dev/${device}";
                $MSG=date("H:i:s") . "\n> ${command}:\\n" . shell_exec("${command}");

                $command = "sudo fsck -a /dev/${device}";
                $MSG=date("H:i:s") . "\n> ${command}:\\n" . shell_exec("${command}") . "\\n" . shell_exec ("cat \"${LogFile}\"");
                exec ("echo \"${MSG}\" > \"${LogFile}\"");
        }
        ?>
</body>

</html>
