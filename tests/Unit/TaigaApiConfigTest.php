<?php

namespace tests\Unit;

use app\helpers\TaigaApiConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TaigaApiConfigTest extends TestCase
{
	private array $config;

	protected function setUp(): void
	{
		$this->config = [
			'servers' => [
				'default' => [
					'api_url' => 'https://taiga.example.test/api/v1/',
				],
				'secondary' => [
					'api_url' => 'https://secondary.example.test/api/v1',
				],
			],
		];
	}

	public function testAllowedUrlsAreNormalizedAndUnique(): void
	{
		$config = $this->config;
		$config['servers']['duplicate'] = [
			'api_url' => 'https://taiga.example.test/api/v1',
		];
		$config['servers']['incomplete'] = [
			'name' => 'No API URL',
		];

		self::assertSame(
			[
				'https://taiga.example.test/api/v1',
				'https://secondary.example.test/api/v1',
			],
			TaigaApiConfig::allowedUrls($config)
		);
	}

	public function testAllowedUrlsReturnsEmptyArrayWithoutServers(): void
	{
		self::assertSame([], TaigaApiConfig::allowedUrls([]));
	}

	public function testExplicitAllowedUrlIsNormalized(): void
	{
		self::assertSame(
			'https://secondary.example.test/api/v1',
			TaigaApiConfig::resolveUrl(
				$this->config,
				'https://secondary.example.test/api/v1/'
			)
		);
	}

	public function testExplicitUnknownUrlIsRejectedWithoutFallback(): void
	{
		self::assertNull(
			TaigaApiConfig::resolveUrl(
				$this->config,
				'https://attacker.example.test/api/v1',
				'https://secondary.example.test/api/v1'
			)
		);
	}

	#[DataProvider('fallbackUrlProvider')]
	public function testUrlFallbackPriority(?string $sessionUrl, ?string $expected): void
	{
		self::assertSame(
			$expected,
			TaigaApiConfig::resolveUrl($this->config, null, $sessionUrl)
		);
	}

	public static function fallbackUrlProvider(): array
	{
		return [
			'session URL has priority' => [
				'https://secondary.example.test/api/v1/',
				'https://secondary.example.test/api/v1',
			],
			'default is used without session URL' => [
				null,
				'https://taiga.example.test/api/v1',
			],
			'default is used for unknown session URL' => [
				'https://attacker.example.test/api/v1',
				'https://taiga.example.test/api/v1',
			],
		];
	}

	public function testResolveUrlReturnsNullWithoutAnAllowedCandidate(): void
	{
		self::assertNull(TaigaApiConfig::resolveUrl([]));
	}
}
