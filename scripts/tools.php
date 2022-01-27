<?php include_once './header.php'; ?>

<main class='flex-1 overflow-x-hidden overflow-y-auto bg-gray-200 dark:bg-gray-900'>
    <div class='container mx-auto px-6 py-8'>

        <h1 class='text-gray-700 dark:text-gray-200 text-5xl font-medium'><?= l::tools_tools; ?></h1>
        <div class="card mt-5">
            <h3 class="text-center mt-0"><?= l::tools_mount_header; ?></h3>
            <hr>
            <form class="text-center mt-4" method="POST">
                <?php
                $button = trim(shell_exec("./mount-storage.sh check usb_1")) == "" ? "<button class='bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-4' name='mount_usb_1'>" . l::tools_mount_b . " USB 1</button>" : "<button class='bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-4' name='umount_usb_1'>" . l::tools_umount_b . " USB 1</button>";
                echo($button);
                $button = trim(shell_exec("./mount-storage.sh check usb_2")) == "" ? "<button class='bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-4' name='mount_usb_2'>" . l::tools_mount_b . " USB 2</button>" : "<button class='bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-4' name='umount_usb_2'>" . l::tools_umount_b . " USB 2</button>";
                echo($button);

                ?>
            </form>
        </div>

        <div class="card mt-10">
            <h3 class="text-center mt-0"><?= l::tools_repair; ?></h3>
            <hr>
            <form class="text-center mt-5" method="POST">
                <label for="partition"><?= l::tools_select_partition ?></label>
                <select name="partition">
                    <?php
                    unset ($devices);
                    exec("ls /dev/sd* | xargs -n 1 basename", $devices);
                    foreach ($devices as $n => $device) { ?>
                        <option value='<?= $device ?>'><?= $device ?></option>
                    <?php } ?>
                </select>
                <button name='fsck_check' class="btn"><?= l::tools_fsck_check_b ?></button>
                <button name='fsck_autorepair'
                        class='bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded'><?= l::tools_fsck_autorepair_b ?></button>
            </form>
        </div>

        <div class="card mt-10">
            <?php include 'sub-logmonitor.php'; ?>
            <?php include 'lib-log-helper.php'; ?>
        </div>

        <div class="card mt-10">
            <details>
                <summary class="uppercase tracking-wide"><?= l::tools_help; ?></summary>
                <p><?= l::tools_help_text; ?></p>
            </details>
        </div>

        <?php
        if (isset($_POST['mount_usb_1'])) {
            $command = "./mount-storage.sh mount usb_1";
            exec("./lib-log-helper.sh \"log_exec\" \"${MSG}\" \"${command}\" \"1\""); ?>
            <script>
                window.location = window.location.href;
            </script>
            <?php
        }
        if (isset($_POST['umount_usb_1'])) {
            $command = "./mount-storage.sh umount usb_1";
            exec("./lib-log-helper.sh \"log_exec\" \"${MSG}\" \"${command}\" \"1\""); ?>
            <script>
                window.location = window.location.href;
            </script>
        <?php
        }
        if (isset($_POST['mount_usb_2'])) {
            $command = "./mount-storage.sh mount usb_2";
            exec("./lib-log-helper.sh \"log_exec\" \"${MSG}\" \"${command}\" \"1\""); ?>
            <script>
                window.location = window.location.href;
            </script>
        <?php
        }
        if (isset($_POST['umount_usb_2'])) {
            $command = "./mount-storage.sh umount usb_2";
            exec("./lib-log-helper.sh \"log_exec\" \"${MSG}\" \"${command}\" \"1\""); ?>
            <script>
                window.location = window.location.href;
            </script>
        <?php
        }
        if (isset($_POST['fsck_check'])) {

            $device = $_POST['partition'];

            popup($device . l::tools_fsck_check_m, $config["conf_POPUP_MESSAGES"]);

            $command = "sudo umount /dev/${device}";
            exec("./lib-log-helper.sh \"log_exec\" \"${MSG}\" \"${command}\" \"1\"");

            $command = "sudo fsck /dev/${device}";
            exec("./lib-log-helper.sh \"log_exec\" \"${MSG}\" \"${command}\" \"1\"");
        }
        if (isset($_POST['fsck_autorepair'])) {

            $device = $_POST['partition'];

            popup($device . l::tools_fsck_autorepair_m, $config["conf_POPUP_MESSAGES"]);

            $command = "sudo umount /dev/${device}";
            exec("./lib-log-helper.sh \"log_exec\" \"${MSG}\" \"${command}\" \"1\"");

            $command = "sudo fsck -a /dev/${device}";
            exec("./lib-log-helper.sh \"log_exec\" \"${MSG}\" \"${command}\" \"1\"");
        }

        ?>
    </div>
</main>

<?php include_once './footer.php'; ?>
