<?php
$theme = "dark";

$LogFile = "/home/pi/little-backup-box/scripts/tmp/fsck.log";
?>

<!--In developement
lists all devices ls /dev/sd* and adds fsck-Button-->

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

        <h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;"><?php echo L::repair; ?></h1>
        <nav>
                <ul>
                        <li><a href="index.php"><?php echo L::main; ?></a></li>
                        <li><a href="sysinfo.php"><?php echo L::sysinfo; ?></a></li>
                        <li><a href="config.php"><?php echo L::config; ?></a></li>
                        <li class="float-right"><a href="upload.php"><?php echo L::upload; ?></a></li>
                </ul>
        </nav>
        <div class="card" style="margin-top: 3em;">
                <h2 style="margin-top: 0em;"><?php echo L::help; ?></h2>
                <hr>
                <p><?php echo L::repair_txt; ?></p>
        </div>
        <div class="card">
                <form class="text-center" style="margin-top: 1em;" method="POST">
                        <?php
                        exec("ls /dev/sd* | xargs -n 1 basename", $devices);
                        foreach ($devices as $n => $device) {
                                if ($n > 0) {
                                        echo "<hr style='margin-bottom: 1em;'>";
                                }
                                echo ("<button name='fsck_check' value='" . $device . "'>" . L::fsck_check_b . " " . $device . "</button>");
                                echo ("<button name='fsck_autorepair' value='" . $device . "'>" . L::fsck_autorepair_b . " " . $device . "</button>");
                        }
                        ?>
                </form>

                <hr style="margin-bottom: 1em;">
        </div>

        <div class="card" style="margin-top: 3em;">
                <h2 style="margin-top: 0em;"><?php echo L::logscreen; ?></h2>
                <hr>
                <iframe id="logscreen" src="tmp/fsck.log" width="100%" height="200" style="background: #FFFFFF;"></iframe>
        </div>

        <?php
        exec("mkdir -p /home/pi/little-backup-box/scripts/tmp");
        exec("echo '' > ${LogFile}");

        if (isset($_POST['fsck_check'])) {

                $device = $_POST['fsck_check'];

                echo "<script>";
                echo 'alert("' . L::fsck_check_m . $device . '")';
                echo "</script>";

                $command = "sudo umount /dev/${device}";
                exec("echo \"${command}:\\n\" > \"${LogFile}\"");
                exec("${command} >> \"${LogFile}\"");

                $command = "sudo fsck /dev/${device}";
                exec("echo \"\\n${command}:\\n\" >> \"$LogFile\"");
                exec("${command} >> $LogFile");
        }
        if (isset($_POST['fsck_autorepair'])) {

                $device = $_POST['fsck_autorepair'];

                echo "<script>";
                echo 'alert("' . L::fsck_autorepair_m . $device . '")';
                echo "</script>";

                $command = "sudo umount /dev/${device}";
                exec("echo \"${command}:\\n\" > \"${LogFile}\"");
                exec("${command} >> ${LogFile}");

                $command = "sudo fsck -a /dev/${device}";
                exec("echo \"\n${command}:\\n\" >> \"$LogFile\"");
                exec("${command} >> $LogFile");
        }
        ?>
</body>

</html>