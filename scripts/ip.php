<?php
if( $_GET["ip"] )
{
    echo $_GET['ip'];
    $f = fopen("ip", "w");
    fwrite($f, $_GET['ip']);
    fclose($f);
} else {
    echo file_get_contents('ip', true);
}
?>
