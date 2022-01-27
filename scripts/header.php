<!doctype html>
<html lang='de'>
<?php
$WORKING_DIR = dirname(__FILE__);
$config = parse_ini_file($WORKING_DIR . '/config.cfg', false);
$constants = parse_ini_file($WORKING_DIR . '/constants.sh', false);

$theme = $config['conf_THEME'];
$background = $config['conf_BACKGROUND_IMAGE'] == '' ? '' : "background='/img/backgrounds/" . $config['conf_BACKGROUND_IMAGE'] . "'";

include($WORKING_DIR . '/sub-popup.php');

$CloudServices = [];
exec("sudo rclone config show --config \"" . $constants['const_RCLONE_CONFIG_FILE'] . "\" | grep '^\[.*\]$' | sed 's/^\[//' | sed 's/\]$//'", $CloudServices);


if (isset($_POST['reboot'])) {
    popup(L::main_reboot_m, $config['conf_POPUP_MESSAGES']);

    exec('sudo ./poweroff.sh reboot force');
}
if (isset($_POST['shutdown'])) {
    popup(L::main_shutdown_m, $config['conf_POPUP_MESSAGES']);

    exec('sudo ./poweroff.sh poweroff force');
}

?>
<head>
    <?php
    $WORKING_DIR=dirname(__FILE__);
    include "${WORKING_DIR}/sub-standards-header-loader.php";
    include "${WORKING_DIR}/sub-standards-body-loader.php";
    ?>
</head>
<body>
<script src='https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js'></script>
<div id="app" :class="colorMode">
    <button
            aria-label='Erscheinungsbild umschalten'
            @click="
         colorMode == 'dark'
          ? (colorMode = 'light')
          : ( colorMode = 'dark')
      "
            class='
        p-0
        w-16
        h-16
        bg-yellow-400
        rounded-full
        hover:bg-yellow-500
        active:shadow-lg
        mouse
        shadow
        transition
        ease-in
        duration-200
        focus:outline-none
        fixed
        bottom-10
        right-10
        z-10
      '>
        <div v-if="colorMode === 'dark'" class='flex justify-center'>
            <svg class='w-10' aria-hidden='true' focusable='false' data-prefix='fas'
                 data-icon='sun' role='img' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512' data-fa-i2svg=''>
                <path fill='currentColor'
                      d='M256 160c-52.9 0-96 43.1-96 96s43.1 96 96 96 96-43.1 96-96-43.1-96-96-96zm246.4 80.5l-94.7-47.3 33.5-100.4c4.5-13.6-8.4-26.5-21.9-21.9l-100.4 33.5-47.4-94.8c-6.4-12.8-24.6-12.8-31 0l-47.3 94.7L92.7 70.8c-13.6-4.5-26.5 8.4-21.9 21.9l33.5 100.4-94.7 47.4c-12.8 6.4-12.8 24.6 0 31l94.7 47.3-33.5 100.5c-4.5 13.6 8.4 26.5 21.9 21.9l100.4-33.5 47.3 94.7c6.4 12.8 24.6 12.8 31 0l47.3-94.7 100.4 33.5c13.6 4.5 26.5-8.4 21.9-21.9l-33.5-100.4 94.7-47.3c13-6.5 13-24.7.2-31.1zm-155.9 106c-49.9 49.9-131.1 49.9-181 0-49.9-49.9-49.9-131.1 0-181 49.9-49.9 131.1-49.9 181 0 49.9 49.9 49.9 131.1 0 181z'></path>
            </svg>
        </div>
        <div v-else class='flex justify-center'>
            <svg class='w-10' aria-hidden='true' focusable='false' data-prefix='fas'
                 data-icon='moon' role='img' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512' data-fa-i2svg=''>
                <path fill='currentColor'
                      d='M283.211 512c78.962 0 151.079-35.925 198.857-94.792 7.068-8.708-.639-21.43-11.562-19.35-124.203 23.654-238.262-71.576-238.262-196.954 0-72.222 38.662-138.635 101.498-174.394 9.686-5.512 7.25-20.197-3.756-22.23A258.156 258.156 0 0 0 283.211 0c-141.309 0-256 114.511-256 256 0 141.309 114.511 256 256 256z'></path>
            </svg>
        </div>
    </button>
    <div x-data='{ sidebarOpen: false }'
         class='flex h-screen bg-gray-200 dark:bg-gray-900 transition duration-500 ease-out'>
        <div :class="sidebarOpen ? 'block' : 'hidden'" @click='sidebarOpen = false'
             class='fixed z-20 inset-0 bg-black opacity-50 transition-opacity lg:hidden'></div>

        <div :class="sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'"
             class='fixed z-30 inset-y-0 left-0 w-64 transition duration-500 transform bg-gray-900 dark:bg-gray-800 overflow-y-auto lg:translate-x-0 lg:static lg:inset-0 rounded-xl lg:ml-5 my-5 p-2'>
            <div class='flex items-center justify-center mt-8'>
                <div class='flex items-center'>
                    <span class='text-white text-2xl mx-2 font-semibold'>LITTLE BACKUP BOX</span>
                </div>
            </div>

            <?php
            $HTTP_HOST = $_SERVER['SERVER_PORT'] == 80 ? $_SERVER['HTTP_HOST'] . ':80' : $_SERVER['HTTP_HOST'];

            if ($config['conf_THEME'] == 'dark') {
                $nav_theme_class = 'navbar-dark bg-dark';
            } else {
                $nav_theme_class = 'navbar-light bg-light';
            }
            $scriptname = basename($_SERVER['SCRIPT_FILENAME']);
            ?>

            <nav class='mt-10'>
                <a class='flex items-center mt-4 py-2 px-6  <?= $scriptname == 'index.php' ? 'bg-gray-700 bg-opacity-25 text-gray-100' : 'text-gray-500 hover:bg-gray-700 hover:bg-opacity-25 hover:text-gray-100'  ?>' href='/index.php'>
                    <span class='mx-3'><?= L::mainmenue_main; ?></span>
                </a>

                <a class='flex items-center mt-4 py-2 px-6 <?= $scriptname == 'sysinfo.php' ? 'bg-gray-700 bg-opacity-25 text-gray-100' : 'text-gray-500 hover:bg-gray-700 hover:bg-opacity-25 hover:text-gray-100'  ?>' href='/sysinfo.php'>
                    <span class='mx-3'><?= L::mainmenue_sysinfo; ?></span>
                </a>

                <a class='flex items-center mt-4 py-2 px-6 <?= $scriptname == 'config.php' ? 'bg-gray-700 bg-opacity-25 text-gray-100' : 'text-gray-500 hover:bg-gray-700 hover:bg-opacity-25 hover:text-gray-100'  ?>' href='/config.php'>
                    <span class='mx-3'><?= L::mainmenue_config; ?></span>
                </a>

                <a class='flex items-center mt-4 py-2 px-6 <?= $scriptname == 'tools.php' ? 'bg-gray-700 bg-opacity-25 text-gray-100' : 'text-gray-500 hover:bg-gray-700 hover:bg-opacity-25 hover:text-gray-100'  ?>' href='/tools.php'>
                    <span class='mx-3'><?= L::mainmenue_tools; ?></span>
                </a>

                <a class='flex items-center mt-4 py-2 px-6 <?= $scriptname == 'files.php' ? 'bg-gray-700 bg-opacity-25 text-gray-100' : 'text-gray-500 hover:bg-gray-700 hover:bg-opacity-25 hover:text-gray-100'  ?>' href='/files'>
                    <span class='mx-3'><?= L::mainmenue_filebrowser; ?></span>
                </a>
                <?php
                if (strpos($_SERVER['SERVER_PORT'], '443') !== false) { ?>
                    <a class='flex items-center mt-4 py-2 px-6 text-gray-500 hover:bg-gray-700 hover:bg-opacity-25 hover:text-gray-100' href="<?= 'http://' . $_SERVER['SERVER_ADDR'] . ':8200' ?>" target='_blank'>
                        <span class="mx-3"><?= L::mainmenue_minidlna ?></span>
                    </a>
                <?php } else { ?>
                    <a class='flex items-center mt-4 py-2 px-6 text-gray-500 hover:bg-gray-700 hover:bg-opacity-25 hover:text-gray-100' href="<?= 'http://' . str_replace(':' . $_SERVER['SERVER_PORT'], ':8200', $HTTP_HOST) ?>" target='_blank'>
                        <span class="mx-3"><?= L::mainmenue_minidlna ?></span>
                    </a>
                <?php } ?>
                <?php if (file_exists("${WORKING_DIR}/../mejiro/index.php")) { ?>
                    <a class='flex items-center mt-4 py-2 px-6 text-gray-500 hover:bg-gray-700 hover:bg-opacity-25 hover:text-gray-100' href='/mejiro' target='_blank'>
                        <span class="mx-3"><?= L::mainmenue_mejiro ?></span>
                    </a>
                <?php } ?>
            </nav>
        </div>
        <div class='flex-1 flex flex-col'>
            <header class='flex justify-between items-center py-6 px-6 border border-gray-300 rounded-2xl overflow-hidden m-5'>
                <div class='flex items-center'>
                    <button @click='sidebarOpen = true' class='text-gray-500 focus:outline-none lg:hidden'>
                        <svg class='h-6 w-6' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'>
                            <path d='M4 6H20M4 12H20M4 18H11' stroke='currentColor' stroke-width='2'
                                  stroke-linecap='round' stroke-linejoin='round'></path>
                        </svg>
                    </button>
                </div>
                <div class='flex items-end'>
                    <form method='POST' class='mb-0'>
                        <button name='reboot'
                                class='text-grey-700 py-2 px-4 border-r border-gray-300 dark:text-gray-200 transition duration-500 ease-out'><?= L::main_reboot_button; ?></button>
                        <button name="shutdown"
                                class="text-grey-700 py-2 px-4 dark:text-gray-200 transition duration-500 ease-out"><?= L::main_shutdown_button; ?></button>
                    </form>
                </div>
            </header>