<?php

namespace app\helpers;

final class TaigaApiConfig
{
	public static function allowedUrls(array $config): array
	{
		$urls = [];

		foreach (($config['servers'] ?? []) as $server) {
			if (!empty($server['api_url'])) {
				$urls[] = rtrim((string) $server['api_url'], '/');
			}
		}

		return array_values(array_unique($urls));
	}

	public static function resolveUrl(
		array $config,
		?string $requestedUrl = null,
		?string $sessionUrl = null
	): ?string {
		$allowedUrls = self::allowedUrls($config);

		if ($requestedUrl) {
			$requestedUrl = rtrim($requestedUrl, '/');

			return in_array($requestedUrl, $allowedUrls, true) ? $requestedUrl : null;
		}

		$candidates = [
			$sessionUrl,
			$config['servers']['default']['api_url'] ?? null,
		];

		foreach ($candidates as $url) {
			if (!$url) {
				continue;
			}

			$url = rtrim((string) $url, '/');
			if (in_array($url, $allowedUrls, true)) {
				return $url;
			}
		}

		return null;
	}
}
