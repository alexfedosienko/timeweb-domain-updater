<?php

namespace TimewebAutoupdateIP;

use TimewebAutoupdateIP\Service\Daemon;

require __DIR__ . "/../vendor/autoload.php";

$daemon = new Daemon();
$daemon->run();
