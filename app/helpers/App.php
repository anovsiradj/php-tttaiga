<?php

namespace app\helpers;

abstract class App
{
	public static $configDirs = [];
	public static $configCaches = [];

	public static function env($envAlt = null)
	{
		return $_ENV['APP_ENV'] ?? $envAlt ?? 'dev';
	}

	public static function config($name, $key = null, $valAlt = null)
	{
		$env = static::env();
		$configName = $name;
		$names = [
			"{$configName}.php",
			"{$configName}.{$env}.php",
			"{$configName}.any.php",
		];
		if (empty(static::$configCaches[$configName])) {
			$config = [];
			foreach (static::$configDirs as $dir) {
				foreach ($names as $fileName) {
					if (is_file($file = "{$dir}/{$fileName}")) {
						$config = array_merge($config, require $file);
					}
				}
				static::$configCaches[$configName] = $config;
				break;
			}
		}
		if ($key) {
			return static::$configCaches[$configName][$key] ?? $valAlt;
		}
		return static::$configCaches[$configName];
	}
}
