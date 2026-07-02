const fs = require('node:fs');
const path = require('node:path');
const { defineConfig, devices } = require('@playwright/test');

function readAppUrl() {
	const envPath = path.join(__dirname, '.env');
	const env = fs.readFileSync(envPath, 'utf8');
	const match = env.match(/^APP_URL\s*=\s*(.+?)\s*$/m);

	if (!match) {
		throw new Error('APP_URL is not defined in .env');
	}

	const value = match[1].replace(/^(['"])(.*)\1$/, '$2');

	return value.endsWith('/') ? value : `${value}/`;
}

module.exports = defineConfig({
	testDir: './tests/Browser',
	outputDir: './tests/tmp/playwright',
	fullyParallel: false,
	forbidOnly: Boolean(process.env.CI),
	retries: process.env.CI ? 2 : 0,
	reporter: 'list',
	use: {
		baseURL: readAppUrl(),
		screenshot: 'only-on-failure',
		trace: 'retain-on-failure',
	},
	projects: [
		{
			name: 'chromium',
			use: {
				...devices['Desktop Chrome'],
			},
		},
	],
});
