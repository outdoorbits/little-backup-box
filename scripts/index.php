<?php include_once './header.php'; ?>
<form method='POST' class='overflow-x-hidden overflow-y-scroll'>
    <main class='flex-1 bg-gray-200 dark:bg-gray-900 transition duration-500 ease-out'>
        <div class='container mx-auto px-6 py-8'>
            <h1 class='text-gray-700 dark:text-gray-200 text-5xl font-medium'><?= L::mainmenue_main; ?></h1>

            <div class='grid gap-3 md:grid-cols-3 grid-cols-1 mt-5'>
                <div class='card'>
                    <button name='backup_storage_external'
                            class='btn'><?= L::main_source_button . L::right_arrow . L::main_external_button; ?></button>
                    <button name="backup_storage_internal"
                            class="btn"><?= L::main_source_button . L::right_arrow . L::main_internal_button; ?></button>
                    <button name="backup_internal_external"
                            class="btn"><?= L::main_internal_button . L::right_arrow . L::main_external_button; ?></button>
                </div>
                <div class='card'>
                    <button name='backup_camera_external'
                            class='btn'><?= L::main_camera_button . L::right_arrow . L::main_external_button; ?></button>
                    <button name="backup_camera_internal"
                            class="btn"><?= L::main_camera_button . L::right_arrow . L::main_internal_button; ?></button>
                </div>
                <div class='card'>
                    <button name='backup_ios_external'
                            class='btn'><?= L::main_ios_button . L::right_arrow . L::main_external_button; ?></button>
                    <button name="backup_ios_internal"
                            class="btn"><?= L::main_ios_button . L::right_arrow . L::main_internal_button; ?></button>
                </div>
                <?php if (!($config['conf_RSYNC_SERVER'] == '' or $config['conf_RSYNC_PORT'] == '' or $config['conf_RSYNC_USER'] == '' or $config['conf_RSYNC_conf_PASSWORD'] == '' or $config['conf_RSYNC_SERVER_MODULE'] == '')) { ?>
                    <div class='card'>
                        <button name="backup_external_server"
                                class="btn"><?= L::main_external_button . L::right_arrow . L::main_rsync_button ?></button>
                        <button name="backup_internal_server"
                                class="btn"><?= L::main_internal_button . L::right_arrow . L::main_rsync_button ?></button>
                    </div>
                <?php } ?>

                <?php foreach ($CloudServices as $CloudService) { ?>

                    <div class='card'>
                        <button name="backup_external_cloud_<?= $CloudService ?>"
                                class="btn"><?= L::main_external_button . L::right_arrow . ' ' . $CloudService ?></button>
                        <button name="backup_internal_cloud_<?= $CloudService ?>"
                                class="btn"><?= L::main_internal_button . L::right_arrow . ' ' . $CloudService ?></button>
                    </div>
                <?php } ?>
            </div>

            <button name='stopbackup'
                    class='mt-10 bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded'><?= L::main_stopbackup_button; ?></button>

            <div class='mt-10'>
                <h2 class='text-gray-700 dark:text-gray-200 text-4xl font-medium'><?= L::log_logmonitor; ?></h2>
                <div class='card mt-4'>
                    <?php include 'sub-logmonitor.php'; ?>
                </div>
            </div>

        </div>
    </main>
</form>


<?php
exec('mkdir -p tmp');
exec('sudo chown www-data:www-data ./tmp -R');

if (isset($_POST['backup_storage_external'])) {
    exec('sudo pkill -f backup*');
    exec('sudo ./backup.sh storage external > /dev/null 2>&1 & echo $!');
    popup(L::main_backup_backup . ' ' . L::main_source_button . ' ' . L::main_backup_to . ' ' . L::main_external_button . ' ' . L::main_backup_initiated . '.', $config['conf_POPUP_MESSAGES']);
}
if (isset($_POST['backup_storage_internal'])) {
    exec('sudo pkill -f backup*');
    exec('sudo ./backup.sh storage internal > /dev/null 2>&1 & echo $!');
    popup(L::main_backup_backup . ' ' . L::main_source_button . ' ' . L::main_backup_to . ' ' . L::main_internal_button . ' ' . L::main_backup_initiated . '.', $config['conf_POPUP_MESSAGES']);
}
if (isset($_POST['backup_internal_external'])) {
    exec('sudo pkill -f backup*');
    exec('sudo ./backup.sh internal external > /dev/null 2>&1 & echo $!');
    popup(L::main_backup_backup . ' ' . L::main_internal_button . ' ' . L::main_backup_to . ' ' . L::main_external_button . ' ' . L::main_backup_initiated . '.', $config['conf_POPUP_MESSAGES']);
}
if (isset($_POST['backup_camera_external'])) {
    exec('sudo pkill -f backup*');
    exec('sudo ./backup.sh camera external > /dev/null 2>&1 & echo $!');
    popup(L::main_backup_backup . ' ' . L::main_camera_button . ' ' . L::main_backup_to . ' ' . L::main_external_button . ' ' . L::main_backup_initiated . '.', $config['conf_POPUP_MESSAGES']);
}
if (isset($_POST['backup_camera_internal'])) {
    exec('sudo pkill -f backup*');
    exec('sudo ./backup.sh camera internal > /dev/null 2>&1 & echo $!');
    popup(L::main_backup_backup . ' ' . L::main_camera_button . ' ' . L::main_backup_to . ' ' . L::main_internal_button . ' ' . L::main_backup_initiated . '.', $config['conf_POPUP_MESSAGES']);
}
if (isset($_POST['backup_ios_external'])) {
    exec('sudo pkill -f backup*');
    exec('sudo ./backup.sh ios external > /dev/null 2>&1 & echo $!');
    popup(L::main_backup_backup . ' ' . L::main_ios_button . ' ' . L::main_backup_to . ' ' . L::main_external_button . ' ' . L::main_backup_initiated . '.', $config['conf_POPUP_MESSAGES']);
}
if (isset($_POST['backup_ios_internal'])) {
    exec('sudo pkill -f backup*');
    exec('sudo ./backup.sh ios internal > /dev/null 2>&1 & echo $!');
    popup(L::main_backup_backup . ' ' . L::main_ios_button . ' ' . L::main_backup_to . ' ' . L::main_internal_button . ' ' . L::main_backup_initiated . '.', $config['conf_POPUP_MESSAGES']);
}
if (isset($_POST['backup_external_server'])) {
    exec('sudo pkill -f backup*');
    exec('sudo ./backup.sh storage rsyncserver > /dev/null 2>&1 & echo $!');
    popup(L::main_backup_backup . ' ' . L::main_external_button . ' ' . L::main_backup_to . ' ' . L::main_rsync_button . ' ' . L::main_backup_initiated . '.', $config['conf_POPUP_MESSAGES']);
}
if (isset($_POST['backup_internal_server'])) {
    exec('sudo pkill -f backup*');
    exec('sudo ./backup.sh internal rsyncserver > /dev/null 2>&1 & echo $!');
    popup(L::main_backup_backup . ' ' . L::main_internal_button . ' ' . L::main_backup_to . ' ' . L::main_rsync_button . ' ' . L::main_backup_initiated . '.', $config['conf_POPUP_MESSAGES']);
}
foreach (['external', 'internal'] as $Source) {
    foreach ($CloudServices as $CloudService) {
        if (isset($_POST['backup_' . $Source . '_cloud_' . $CloudService])) {
            exec('sudo pkill -f backup*');
            $SourceDevice = $Source === 'external' ? 'storage' : 'internal';
            exec('sudo ./backup.sh ' . $SourceDevice . ' cloud_' . $CloudService . ' > /dev/null 2>&1 & echo $!');

            popup(L::main_backup_backup . ' ' . ($Source == 'external' ? L::main_external_button : L::main_internal_button) . ' ' . L::main_backup_to . ' ' . L::main_cloudservice . ' ' . $CloudService . ' ' . L::main_backup_initiated . '.', $config['conf_POPUP_MESSAGES']);
        }
    }
}
if (isset($_POST['stopbackup'])) {
    popup(L::main_stopbackup_m, $config['conf_POPUP_MESSAGES']);

    exec('sudo pkill -f backup*');
}
?>

<?php include_once './footer.php'; ?>



