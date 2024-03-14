<?php

declare(strict_types=1);

namespace Tests\Commands;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Filters\CITestStreamFilter;

/**
 * @internal
 */
final class SettingsPublisherTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();
        CITestStreamFilter::addErrorFilter();

    }

    protected function tearDown(): void
    {
        parent::tearDown();

        CITestStreamFilter::removeOutputFilter();
        CITestStreamFilter::removeErrorFilter();

    }

    public function testPublishConfigFile(): void
    {
        command('settings:publish');

        $filepath = APPPATH . 'Config/Settings.php';
        $this->assertFileExists($filepath);
        $this->assertStringContainsString('  Published! ', CITestStreamFilter::$buffer);

        $contents = $this->getFileContents($filepath);
        $this->assertStringContainsString('namespace Config;', $contents);
        $this->assertStringContainsString('use CodeIgniter\\Settings\\Config\\Settings as SettingsConfig;', $contents);
        $this->assertStringContainsString('class Settings extends SettingsConfig', $contents);

        if (is_file($filepath)) {
            copy($filepath, APPPATH . 'Config/Settings.php.bak');
        }
    }

    /**
     * @depends testPublishConfigFile
     */
    public function testPublishConfigFileWithForce(): void
    {

        $filepath = APPPATH . 'Config/Settings.php';

        helper('filesystem');
        write_file($filepath, 'fake text.');
        $contents = $this->getFileContents($filepath);

        $this->assertFileExists($filepath);
        $this->assertStringContainsString('fake text.', $contents);

        command('settings:publish -f');

        $expectedConfigFile = APPPATH . 'Config/Settings.php.bak';
        $this->assertFileEquals($expectedConfigFile, $filepath);

        clearstatcache(true, $expectedConfigFile);
        if (is_file($expectedConfigFile)) {
            unlink($expectedConfigFile);
        }

    }

    private function getFileContents(string $filepath): string
    {
        return (string) @file_get_contents($filepath);
    }
}
