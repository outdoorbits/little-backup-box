<!--
sub expects from calling script:
- ${WORKING_DIR}
- included language
-->

<?php
    $HTTP_HOST = $_SERVER['SERVER_PORT']==80?$_SERVER['HTTP_HOST'] . ":80":$_SERVER['HTTP_HOST'];
?>

    <li><a href="index.php"><?php echo L::main; ?></a></li>
    <li><a href="sysinfo.php"><?php echo L::sysinfo; ?></a></li>
    <li><a href="config.php"><?php echo L::config; ?></a></li>
    <li><a href="tools.php"><?php echo L::tools; ?></a></li>
    <li><a href="<?php echo ("http://" . str_replace(":" . $_SERVER['SERVER_PORT'], ":8200", $HTTP_HOST)); ?>"><?php echo L::minidlna; ?></a></li>
    <li><a href="<?php echo ("http://" . str_replace(":" . $_SERVER['SERVER_PORT'], ":8080", $HTTP_HOST)); ?>"><?php echo L::filebrowser; ?></a></li>
    <?php
        if (file_exists("${WORKING_DIR}/../../mejiro/index.php")) {
            echo ("<li><a href='http://" . str_replace(":" . $_SERVER['SERVER_PORT'], ":8081", $HTTP_HOST) . "'>" . L::mejiro . "</a></li>");
        }
    ?>
    <li class="float-right"><a href="upload.php"><?php echo L::upload; ?></a></li>



