<?php

use app\helpers\App;

require __DIR__ . '/../vendor/autoload.php';

App::$configDirs[] = __DIR__ . '/configs';

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
