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
        $this->assertSame(config('Example')->siteName, $this->settings->get('Example.siteName'));
    }

    public function testSettingsNotFound()
    {
        $this->assertSame(config('Example')->siteName, $this->settings->get('Example.siteName'));
    }

    public function testGetWithContext()
    {
        $this->settings->set('Example.siteName', 'NoContext');
        $this->settings->set('Example.siteName', 'YesContext', 'testing:true');

        $this->assertSame('NoContext', $this->settings->get('Example.siteName'));
        $this->assertSame('YesContext', $this->settings->get('Example.siteName', 'testing:true'));
    }

    public function testGetWithoutContextUsesGlobal()
    {
        $this->settings->set('Example.siteName', 'NoContext');

        $this->assertSame('NoContext', $this->settings->get('Example.siteName', 'testing:true'));
    }

    public function testForgetWithContext()
    {
        $this->settings->set('Example.siteName', 'Bar');
        $this->settings->set('Example.siteName', 'Amnesia', 'category:disease');

        $this->settings->forget('Example.siteName', 'category:disease');

        $this->assertSame('Bar', $this->settings->get('Example.siteName', 'category:disease'));
    }
}
