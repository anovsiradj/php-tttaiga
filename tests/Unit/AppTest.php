<?php

namespace tests\Unit;

use app\helpers\App;
use PHPUnit\Framework\TestCase;

final class AppTest extends TestCase
{
	private string $configDir;
	private array $originalEnvironment;

	protected function setUp(): void
	{
		$this->originalEnvironment = $_ENV;
		$this->configDir = dirname(__DIR__) . '/tmp/unit-' . bin2hex(random_bytes(8));

		if (!mkdir($this->configDir, 0777, true) && !is_dir($this->configDir)) {
			self::fail('Unable to create the temporary configuration directory.');
		}

		App::$configDirs = [$this->configDir];
		App::$configCaches = [];
		unset($_ENV['APP_ENV']);
	}

	protected function tearDown(): void
	{
		$_ENV = $this->originalEnvironment;
		App::$configDirs = [];
		App::$configCaches = [];

		$files = glob($this->configDir . '/*') ?: [];
		foreach ($files as $file) {
			unlink($file);
		}

		if (is_dir($this->configDir)) {
			rmdir($this->configDir);
		}
	}

	public function testEnvironmentUsesConfiguredValue(): void
	{
		$_ENV['APP_ENV'] = 'test';

		self::assertSame('test', App::env());
	}

	public function testEnvironmentFallbacks(): void
	{
		self::assertSame('staging', App::env('staging'));
		self::assertSame('dev', App::env());
	}

	public function testConfigMergesBaseEnvironmentAndAnyFiles(): void
	{
		$_ENV['APP_ENV'] = 'test';
		$this->writeConfig('taiga.php', [
			'base' => true,
			'overridden' => 'base',
		]);
		$this->writeConfig('taiga.test.php', [
			'environment' => true,
			'overridden' => 'environment',
		]);
		$this->writeConfig('taiga.any.php', [
			'any' => true,
			'overridden' => 'any',
		]);

		self::assertSame(
			[
				'base' => true,
				'overridden' => 'any',
				'environment' => true,
				'any' => true,
			],
			App::config('taiga')
		);
	}

	public function testConfigReadsAKeyAndSupportsFallback(): void
	{
		$this->writeConfig('taiga.php', ['api_url' => 'https://taiga.example.test']);

		self::assertSame(
			'https://taiga.example.test',
			App::config('taiga', 'api_url')
		);
		self::assertSame('fallback', App::config('taiga', 'missing', 'fallback'));
	}

	public function testConfigIsCachedAfterFirstRead(): void
	{
		$this->writeConfig('taiga.php', ['value' => 'initial']);

		self::assertSame('initial', App::config('taiga', 'value'));

		$this->writeConfig('taiga.php', ['value' => 'changed']);

		self::assertSame('initial', App::config('taiga', 'value'));
	}

	private function writeConfig(string $fileName, array $config): void
	{
		$content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
		file_put_contents($this->configDir . '/' . $fileName, $content);
	}
}
