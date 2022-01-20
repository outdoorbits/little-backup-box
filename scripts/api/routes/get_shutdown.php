<?php
require_once('../Api.php');
Api::getHeaders();
Api::returnJson([]);
exec('sudo ./poweroff.sh poweroff force');
