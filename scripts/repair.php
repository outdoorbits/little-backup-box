<?php
$theme = "dark";

$LogFile = "fsck.log";
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

        <h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;"><?php echo L::repair; ?></h1>
        <nav>
                <ul>
                        <li><a href="index.php"><?php echo L::main; ?></a></li>
                        <li><a href="sysinfo.php"><?php echo L::sysinfo; ?></a></li>
                        <li><a href="config.php"><?php echo L::config; ?></a></li>
                        <li class="float-right"><a href="upload.php"><?php echo L::upload; ?></a></li>
                </ul>
        </nav>
        <div class="card">
                <form class="text-center" style="margin-top: 1em;" method="POST">
                        <label for="partition">Select partition:</label>
                        <select name="partition">
                                <?php
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
                <h2 style="margin-top: 0em;"><?php echo L::help; ?></h2>
                <hr>
                <p><?php echo L::repair_txt; ?></p>
        </div>

        <div class="card" style="margin-top: 3em;">
                <h2 style="margin-top: 0em;"><?php echo L::logmonitor; ?></h2>
                <hr>
                <iframe id="logscreen" src="./fsck.log" width="100%" height="200" style="background: #FFFFFF;"></iframe>
        </div>

        <?php
        exec("echo '' > ${LogFile}");

        if (isset($_POST['fsck_check'])) {

                $device = $_POST['partition'];

                echo "<script>";
                echo 'alert("' . $device . L::fsck_check_m . '")';
                echo "</script>";

                $command = "sudo umount /dev/${device}";
                exec("echo \"${command}:\\n\" > \"${LogFile}\"");
                exec("${command} >> \"${LogFile}\"");

                $command = "sudo fsck /dev/${device}";
                exec("echo \"\\n${command}:\\n\" >> \"$LogFile\"");
                exec("${command} >> $LogFile");
        }
        if (isset($_POST['fsck_autorepair'])) {

                $device = $_POST['partition'];

                echo "<script>";
                echo 'alert("' . $device . L::fsck_autorepair_m  . '")';
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