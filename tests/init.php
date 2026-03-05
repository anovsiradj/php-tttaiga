<?php

use anovsiradj\skit\CURL;

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/tmp/v2.php';

$headers = [];
$token = null;
$user = null;

$userLogin = $config['userLogin'];
$userCache = md5(serialize($userLogin));
$userCache = __DIR__ . "/tmp/user.{$userCache}.txt";

if (file_exists($userCache)) {
	$user = file_get_contents($userCache);
	$user = unserialize($user);
} else {
	$curl = new CURL($prefix);
	$curl->url('/api/v2/login');
	$curl->post($userLogin);
	$curl->exec();
	if ($curl->code() === 200) {
		$user = $curl->data();
		file_put_contents($userCache, serialize($user));
	}
}

if ($user) {
	$token = $user['token'];
	$headers[] = "Authorization: Bearer {$token}";
}

$echo = function (CURL $curl) {
	$url = null;
	if ($curl->url) {
		$url = urldecode($curl->url);
	}
	echo <<<HTML
		<div>
			<b>{$curl->code()}</b>
			<code>{$url}</code>
		</div>
	HTML;

	$data = $curl->data();
	if (is_array($data)) {
		dump($data);
	} else {
		echo $data;
	}

	echo '<br>';
};
