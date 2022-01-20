<?php
require_once('../Api.php');
Api::getHeaders();
$WORKING_DIR = Api::workDir();

$constants = parse_ini_file($WORKING_DIR . '/constants.sh', false);

$temp = shell_exec('cat /sys/class/thermal/thermal_zone*/temp');
$temp = round($temp / 1000, 1);

$cpuusage = 100 - shell_exec("vmstat | tail -1 | awk '{print $15}'");

$mem_ram_frac = shell_exec("free | grep Mem | awk '{print $3/$2 * 100.0}'");
$mem_ram_all = shell_exec("free | grep Mem | awk '{print $2 / 1024}'");
$mem_ram = round($mem_ram_frac, 1) . ' % * ' . round($mem_ram_all) . ' MB';

$mem_swap_frac = shell_exec("free | grep Swap | awk '{print $3/$2 * 100.0}'");
$mem_swap_all = shell_exec("free | grep Swap | awk '{print $2 / 1024}'");
$mem_swap = round($mem_swap_frac, 1) . ' % * ' . round($mem_swap_all) . ' MB';

$abnormal_conditions = shell_exec("${WORKING_DIR}/system_conditions.sh 'abnormal_conditions'");

exec("sudo gphoto2 --summary | grep 'Model' | cut -d: -f2 | tr -d '[:space:]'",$DEVICES);

ob_start();
passthru('lsblk');
$devices = ob_get_clean();

ob_start();
passthru('df -H');
$diskspace = ob_get_clean();

$result = [
    'mem_ram'             => $mem_ram,
    'mem_swap'            => $mem_swap,
    'abnormal_conditions' => $abnormal_conditions,
    'devices' => $devices,
    'diskspace' => $diskspace,
    'camera' => $DEVICES,
];

if (isset($temp) && is_numeric($temp)) {
    $result['temp'] = $temp . '°C';
}

if (isset($cpuusage) && is_numeric($cpuusage)) {
    $result['cpuusage'] = $cpuusage . '%';
}

Api::returnJson($result);
