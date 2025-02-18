<?php

namespace Tests\Support;

use CodeIgniter\Settings\Handlers\ArrayHandler;
use CodeIgniter\Settings\Settings;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

abstract class TestCase extends CIUnitTestCase
{
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
}
