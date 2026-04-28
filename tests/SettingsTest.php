<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Settings\Settings;
use Config\Services;
use Tests\Support\Config\Example;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class SettingsTest extends TestCase
{
    public function testSettingsUsesParameter(): void
    {
        $config           = config('Settings');
        $config->handlers = [];

        $settings = new Settings($config);
        $result   = $this->getPrivateProperty($settings, 'handlers');

        $this->assertSame([], $result);
    }

    public function testServiceUsesConfig(): void
    {
        Services::resetSingle('settings');

        $config           = config('Settings');
        $config->handlers = [];

        $settings = service('settings');
        $result   = $this->getPrivateProperty($settings, 'handlers');

        $this->assertSame([], $result);
    }

    public function testSettingsGetsFromConfig(): void
    {
        $this->assertSame(config('Example')->siteName, $this->settings->get('Example.siteName'));
    }

    public function testSettingsNotFound(): void
    {
        $this->assertSame(config('Example')->siteName, $this->settings->get('Example.siteName'));
    }

    public function testGetWithContext(): void
    {
        $this->settings->set('Example.siteName', 'NoContext');
        $this->settings->set('Example.siteName', 'YesContext', 'testing:true');

        $this->assertSame('NoContext', $this->settings->get('Example.siteName'));
        $this->assertSame('YesContext', $this->settings->get('Example.siteName', 'testing:true'));
    }

    public function testSetManyStoresMultipleValues(): void
    {
        $this->settings->setMany([
            'Example.siteName'  => 'BatchName',
            'Example.siteEmail' => 'batch@example.com',
        ]);

        $this->assertSame('BatchName', $this->settings->get('Example.siteName'));
        $this->assertSame('batch@example.com', $this->settings->get('Example.siteEmail'));
    }

    public function testSetManyWithContext(): void
    {
        $this->settings->setMany([
            'Example.siteName'  => 'BatchName',
            'Example.siteEmail' => 'batch@example.com',
        ], 'testing:true');

        $this->assertSame(config('Example')->siteName, $this->settings->get('Example.siteName'));
        $this->assertSame('BatchName', $this->settings->get('Example.siteName', 'testing:true'));
        $this->assertSame('batch@example.com', $this->settings->get('Example.siteEmail', 'testing:true'));
    }

    public function testSetManyUsesLastNormalizedKey(): void
    {
        $this->settings->setMany([
            'Example.siteName'           => 'ShortName',
            Example::class . '.siteName' => 'FullName',
        ]);

        $this->assertSame('FullName', $this->settings->get('Example.siteName'));
    }

    public function testBatchMethodsAcceptEmptyArrays(): void
    {
        $this->settings->set('Example.siteName', 'ExistingName');

        $this->settings->setMany([]);
        $this->settings->forgetMany([]);

        $this->assertSame('ExistingName', $this->settings->get('Example.siteName'));
    }

    public function testGetWithoutContextUsesGlobal(): void
    {
        $this->settings->set('Example.siteName', 'NoContext');

        $this->assertSame('NoContext', $this->settings->get('Example.siteName', 'testing:true'));
    }

    public function testForgetWithContext(): void
    {
        $this->settings->set('Example.siteName', 'Bar');
        $this->settings->set('Example.siteName', 'Amnesia', 'category:disease');

        $this->settings->forget('Example.siteName', 'category:disease');

        $this->assertSame('Bar', $this->settings->get('Example.siteName', 'category:disease'));
    }

    public function testForgetManyRemovesMultipleValues(): void
    {
        $this->settings->setMany([
            'Example.siteName'  => 'BatchName',
            'Example.siteEmail' => 'batch@example.com',
        ]);

        $this->settings->forgetMany([
            'Example.siteName',
            'Example.siteEmail',
        ]);

        $this->assertSame(config('Example')->siteName, $this->settings->get('Example.siteName'));
        $this->assertNull($this->settings->get('Example.siteEmail'));
    }
}
