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
		$names = [
			"{$name}.php",
			"{$name}.{$env}.php",
			"{$name}.any.php",
		];
		if (empty(static::$configCaches[$name])) {
			$config = [];
			foreach (static::$configDirs as $dir) {
				foreach ($names as $name) {
					if (is_file($file = "{$dir}/{$name}")) {
						$config = array_merge($config, require $file);
					}
				}
				static::$configCaches[$name] = $config;
				break;
			}
		}
		if ($key) {
			return static::$configCaches[$name][$key] ?? $valAlt;
		}
		return static::$configCaches[$name];
	}
}
