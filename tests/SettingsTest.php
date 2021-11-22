<?php

namespace Tests;

use CodeIgniter\Settings\Settings;
use Config\Services;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class SettingsTest extends TestCase
{
    public function testSettingsUsesParameter()
    {
        $config           = config('Settings');
        $config->handlers = [];

        $settings = new Settings($config);
        $result   = $this->getPrivateProperty($settings, 'handlers');

        $this->assertSame([], $result);
    }

    public function testServiceUsesConfig()
    {
        Services::resetSingle('settings');

        $config           = config('Settings');
        $config->handlers = [];

        $settings = service('settings');
        $result   = $this->getPrivateProperty($settings, 'handlers');

        $this->assertSame([], $result);
    }

    public function testSettingsGetsFromConfig()
    {
        $this->assertSame(config('Test')->siteName, $this->settings->get('Test.siteName'));
    }

    public function testSettingsNotFound()
    {
        $this->assertSame(config('Test')->siteName, $this->settings->get('Test.siteName'));
    }

    public function testGetWithContext()
    {
        $this->settings->set('Test.siteName', 'NoContext');
        $this->settings->set('Test.siteName', 'YesContext', 'testing:true');

        $this->assertSame('NoContext', $this->settings->get('Test.siteName'));
        $this->assertSame('YesContext', $this->settings->get('Test.siteName', 'testing:true'));
    }

    public function testGetWithoutContextUsesGlobal()
    {
        $this->settings->set('Test.siteName', 'NoContext');

        $this->assertSame('NoContext', $this->settings->get('Test.siteName', 'testing:true'));
    }

    public function testForgetWithContext()
    {
        $this->settings->set('Test.siteName', 'Bar');
        $this->settings->set('Test.siteName', 'Amnesia', 'category:disease');

        $this->settings->forget('Test.siteName', 'category:disease');

        $this->assertSame('Bar', $this->settings->get('Test.siteName', 'category:disease'));
    }
}
