<?php

$curl = new CURL($prefix, $headers);
$curl->url('/api/v2/data', $_GET);
$curl->exec();
$echo($curl);
