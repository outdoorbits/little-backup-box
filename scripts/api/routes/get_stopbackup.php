<?php
require_once('../Api.php');
Api::getHeaders();
Api::returnJson([]);
exec('sudo pkill -f backup*');
