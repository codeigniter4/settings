<?php

namespace Tests\Support;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Settings\Settings;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ReflectionHelper;
use Config\Services;

abstract class TestCase extends CIUnitTestCase
{
    use ReflectionHelper;

    private array $lines = [];

    /**
     * @var Settings
     */
    protected $settings;

    /**
     * Sets up the ArrayHandler for faster & easier tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config           = config('Settings');
        $config->handlers = ['array'];
        $this->settings   = new Settings($config);

        Services::injectMock('settings', $this->settings);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->resetServices();
    }

    protected function parseOutput(string $output): string
    {
        $this->lines = [];
        $output      = $this->removeColorCodes($output);
        $this->lines = explode("\n", $output);

        return $output;
    }

    protected function getLine(int $line = 0): ?string
    {
        return $this->lines[$line] ?? null;
    }

    protected function getLines(): string
    {
        return implode('', $this->lines);
    }

    protected function removeColorCodes(string $output): string
    {
        $colors = $this->getPrivateProperty(CLI::class, 'foreground_colors');
        $colors = array_values(array_map(static fn ($color) => "\033[" . $color . 'm', $colors));
        $colors = array_merge(["\033[0m"], $colors);

        $output = str_replace($colors, '', trim($output));

        if (is_windows()) {
            $output = str_replace("\r\n", "\n", $output);
        }

        return $output;
    }
}
