<?php

declare(strict_types=1);

namespace Tests\Commands;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Filters\CITestStreamFilter;
use CodeIgniter\Test\PhpStreamWrapper;

/**
 * @internal
 */
final class ClearSettingsTest extends CIUnitTestCase
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

    public function testSingleHandlerArray(): void
    {
        PhpStreamWrapper::register();
        PhpStreamWrapper::setContent("y\n");

        $config           = config('Settings');
        $config->handlers = ['array'];

        command('settings:clear');

        PhpStreamWrapper::restore();

        $output = CITestStreamFilter::$buffer;

        $this->assertStringContainsString('array handler', $output);
        $this->assertStringContainsString('Settings cleared from array handler', $output);
    }

    public function testSingleHandlerFile(): void
    {
        PhpStreamWrapper::register();
        PhpStreamWrapper::setContent("y\n");

        $config           = config('Settings');
        $config->handlers = ['file'];

        command('settings:clear');

        PhpStreamWrapper::restore();

        $output = CITestStreamFilter::$buffer;

        $this->assertStringContainsString('file handler', $output);
        $this->assertStringContainsString('Settings cleared from file handler', $output);
    }

    public function testMultipleHandlers(): void
    {
        PhpStreamWrapper::register();
        PhpStreamWrapper::setContent("y\n");

        $config           = config('Settings');
        $config->handlers = ['array', 'file'];

        command('settings:clear');

        PhpStreamWrapper::restore();

        $output = CITestStreamFilter::$buffer;

        $this->assertStringContainsString('array and file handlers', $output);
        $this->assertStringContainsString('Settings cleared from array and file handlers', $output);
    }

    public function testThreeHandlers(): void
    {
        PhpStreamWrapper::register();
        PhpStreamWrapper::setContent("y\n");

        $config                     = config('Settings');
        $config->handlers           = ['array', 'file'];
        $config->array['writeable'] = false; // Make array not writeable

        command('settings:clear');

        PhpStreamWrapper::restore();

        $output = CITestStreamFilter::$buffer;

        $this->assertStringContainsString('file handler', $output);
    }

    public function testNoWriteableHandlers(): void
    {
        PhpStreamWrapper::register();
        PhpStreamWrapper::setContent("y\n");

        $config                     = config('Settings');
        $config->handlers           = ['array'];
        $config->array['writeable'] = false;

        command('settings:clear');

        PhpStreamWrapper::restore();

        $output = CITestStreamFilter::$buffer;

        $this->assertStringContainsString('No handlers available to clear', $output);
    }

    public function testEmptyHandlersArray(): void
    {
        PhpStreamWrapper::register();
        PhpStreamWrapper::setContent("y\n");

        $config           = config('Settings');
        $config->handlers = [];

        command('settings:clear');

        PhpStreamWrapper::restore();

        $output = CITestStreamFilter::$buffer;

        $this->assertStringContainsString('No handlers available to clear', $output);
    }

    public function testUserCancelsOperation(): void
    {
        PhpStreamWrapper::register();
        PhpStreamWrapper::setContent("n\n"); // User answers 'n' to prompt

        $config           = config('Settings');
        $config->handlers = ['array'];

        command('settings:clear');

        PhpStreamWrapper::restore();

        $output = CITestStreamFilter::$buffer;

        // Should show the prompt but not the success message
        $this->assertStringContainsString('delete all settings from array handler', $output);
        $this->assertStringNotContainsString('Settings cleared', $output);
    }

    public function testUserConfirmsOperation(): void
    {
        PhpStreamWrapper::register();
        PhpStreamWrapper::setContent("y\n"); // User answers 'y' to prompt

        $config           = config('Settings');
        $config->handlers = ['array'];

        command('settings:clear');

        PhpStreamWrapper::restore();

        $output = CITestStreamFilter::$buffer;

        // Should show both prompt and success message
        $this->assertStringContainsString('delete all settings from array handler', $output);
        $this->assertStringContainsString('Settings cleared from array handler', $output);
    }

    public function testActuallyFlushesSettings(): void
    {
        PhpStreamWrapper::register();
        PhpStreamWrapper::setContent("y\n");

        // Set some settings
        $settings = service('settings');
        $settings->set('Example.siteName', 'Test');

        $this->assertSame('Test', $settings->get('Example.siteName'));

        // Run clear command
        $config           = config('Settings');
        $config->handlers = ['array'];

        command('settings:clear');

        PhpStreamWrapper::restore();

        // Verify settings were cleared
        $this->assertSame('Settings Test', $settings->get('Example.siteName')); // Back to default
    }
}
