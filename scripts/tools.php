<?php
$theme = "dark";

$WORKING_DIR=dirname(__FILE__);
$LogFileFsck = "${WORKING_DIR}/tmp/fsck.log";
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
        <script>
                function refreshIFrame() {
                        var x = document.getElementById("logscreen");
                        x.contentWindow.location.reload();
                        var t = setTimeout(refreshIFrame, 2000);
                }
        </script>
</head>

<body onload="refreshIFrame()">
        <?php
        // include i18n class and initialize it
        require_once 'i18n.class.php';
        $i18n = new i18n('lang/{LANGUAGE}.ini', 'cache/', 'en');
        $i18n->init();
        ?>

        <h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;"><?php echo L::tools; ?></h1>
        <nav>
                <ul>
                    <?php include "${WORKING_DIR}/sub-menu.php"; ?>
                </ul>
        </nav>

        <div class="card">
            <h3 class="text-center" style="margin-top: 0em;"><?php echo L::mount_header; ?></h3>
            <hr>
                <form class="text-center" style="margin-top: 1em;" method="POST">
                    <?php
                        $button = trim(shell_exec("./mounthelper.sh check usb_1"))==""?"<button name='mount_usb_1'>" . L::mount_b . " USB 1</button>":"<button name='umount_usb_1'>" . L::umount_b . " USB 1</button>";
                        echo ($button);
                        $button = trim(shell_exec("./mounthelper.sh check usb_2"))==""?"<button name='mount_usb_2'>" . L::mount_b . " USB 2</button>":"<button name='umount_usb_2'>" . L::umount_b . " USB 2</button>";
                        echo ($button);

                    ?>
                </form>
        </div>

        <div class="card" style="margin-top: 3em;">
            <h3 class="text-center" style="margin-top: 0em;"><?php echo L::repair; ?></h3>
            <hr>
                <form class="text-center" style="margin-top: 1em;" method="POST">
                        <label for="partition"><?php echo L::select_partition ?></label>
                        <select name="partition">
                            <?php
                            unset ($devices);
                            exec("ls /dev/sd* | xargs -n 1 basename", $devices);
                            foreach ($devices as $n => $device) {
                                    echo "<option value='$device'>$device</option>";
                            }
                            echo "</select>";
                            echo ("<button name='fsck_check'>" . L::fsck_check_b . "</button>");
                            echo ("<button name='fsck_autorepair'>" . L::fsck_autorepair_b . "</button>");
                            ?>
                </form>
        </div>

        <div class="card" style="margin-top: 3em;">
            <h2 style="margin-top: 0em;"><?php echo L::logmonitor; ?></h2>
            <hr>
            <iframe id="logscreen" src="./tmp/fsck.log" width="100%" height="200" style="background: #FFFFFF;"></iframe>
        </div>

        <div class="card" style="margin-top: 3em;">
            <details>
                <summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::help; ?></summary>
                <p><?php echo L::repair_txt; ?></p>
            </details>
        </div>

        <?php
        if (isset($_POST['mount_usb_1'])) {
                $command = "./mounthelper.sh mount usb_1";
                $MSG="${MSG}\\n${command}:\\n" . shell_exec("${command}") . "---\\n" . shell_exec ("cat \"${LogFileFsck}\"");
                exec ("echo \"${MSG}\" > \"${LogFileFsck}\"");
                echo "<script>";
                echo "window.location = window.location.href;";
                echo "</script>";
        }
        if (isset($_POST['umount_usb_1'])) {
                $command = "./mounthelper.sh umount usb_1";
                $MSG="${MSG}\\n${command}:\\n" . shell_exec("${command}") . "---\\n" . shell_exec ("cat \"${LogFileFsck}\"");
                exec ("echo \"${MSG}\" > \"${LogFileFsck}\"");
                echo "<script>";
                echo "window.location = window.location.href;";
                echo "</script>";
        }
        if (isset($_POST['mount_usb_2'])) {
                $command = "./mounthelper.sh mount usb_2";
                $MSG="${MSG}\\n${command}:\\n" . shell_exec("${command}") . "---\\n" . shell_exec ("cat \"${LogFileFsck}\"");
                exec ("echo \"${MSG}\" > \"${LogFileFsck}\"");
                echo "<script>";
                echo "window.location = window.location.href;";
                echo "</script>";
        }
        if (isset($_POST['umount_usb_2'])) {
                $command = "./mounthelper.sh umount usb_2";
                $MSG="${MSG}\\n${command}:\\n" . shell_exec("${command}") . "---\\n" . shell_exec ("cat \"${LogFileFsck}\"");
                exec ("echo \"${MSG}\" > \"${LogFileFsck}\"");
                echo "<script>";
                echo "window.location = window.location.href;";
                echo "</script>";
        }
        if (isset($_POST['fsck_check'])) {

                $device = $_POST['partition'];

                echo "<script>";
                echo 'alert("' . $device . L::fsck_check_m . '")';
                echo "</script>";

                $command = "sudo umount /dev/${device}";
                $MSG="${command}:\\n" . shell_exec("${command}");

                $command = "sudo fsck /dev/${device}";
                $MSG="${MSG}\\n${command}:\\n" . shell_exec("${command}") . "---\\n" . shell_exec ("cat \"${LogFileFsck}\"");
                exec ("echo \"${MSG}\" > \"${LogFileFsck}\"");
        }
        if (isset($_POST['fsck_autorepair'])) {

                $device = $_POST['partition'];

                echo "<script>";
                echo 'alert("' . $device . L::fsck_autorepair_m  . '")';
                echo "</script>";

                $command = "sudo umount /dev/${device}";
                $MSG="${command}:\\n" . shell_exec("${command}");

                $command = "sudo fsck -a /dev/${device}";
                $MSG="${MSG}\\n${command}:\\n" . shell_exec("${command}") . "---\\n" . shell_exec ("cat \"${LogFileFsck}\"");
                exec ("echo \"${MSG}\" > \"${LogFileFsck}\"");
        }
        ?>
</body>

</html>
