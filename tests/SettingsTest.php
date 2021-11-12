<?php

namespace Tests;

use CodeIgniter\I18n\Time;
use CodeIgniter\Test\DatabaseTestTrait;
use Sparks\Settings\Settings;
use Tests\Support\TestCase;

/**
 * NOTE: $this->table is set in the TestCase itself
 *
 * @internal
 */
final class SettingsTest extends TestCase
{
    use DatabaseTestTrait;

    public function testSettingsUsesParameter()
    {
        $config           = config('Settings');
        $config->handlers = [];

        $settings = new Settings($config);
        $result   = $this->getPrivateProperty($settings, 'handlers');

        $this->assertSame([], $result);
    }

    public function testSettingsGetsFromConfig()
    {
        $settings = new Settings();

        $this->assertSame(config('Test')->siteName, $settings->get('Test.siteName'));
    }

    public function testSettingsDatabaseNotFound()
    {
        $settings = new Settings();

        $this->assertSame(config('Test')->siteName, $settings->get('Test.siteName'));
    }

    public function testSetInsertsNewRows()
    {
        $settings = new Settings();

        $results = $settings->set('Test.siteName', 'Foo');

        $this->assertTrue($results);
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
            'value' => 'Foo',
            'type'  => 'string',
        ]);
    }

    public function testSetInsertsBoolTrue()
    {
        $settings = new Settings();

        $results = $settings->set('Test.siteName', true);

        $this->assertTrue($results);
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
            'value' => '1',
            'type'  => 'boolean',
        ]);

        $this->assertTrue($settings->get('Test.siteName'));
    }

    public function testSetInsertsBoolFalse()
    {
        $settings = new Settings();

        $results = $settings->set('Test.siteName', false);

        $this->assertTrue($results);
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
            'value' => '0',
            'type'  => 'boolean',
        ]);

        $this->assertFalse($settings->get('Test.siteName'));
    }

    public function testSetInsertsArray()
    {
        $settings = new Settings();
        $data     = ['foo' => 'bar'];

        $results = $settings->set('Test.siteName', $data);

        $this->assertTrue($results);
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
            'value' => serialize($data),
            'type'  => 'array',
        ]);

        $this->assertSame($data, $settings->get('Test.siteName'));
    }

    public function testSetInsertsObject()
    {
        $settings = new Settings();
        $data     = (object) ['foo' => 'bar'];

        $results = $settings->set('Test.siteName', $data);

        $this->assertTrue($results);
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
            'value' => serialize($data),
            'type'  => 'object',
        ]);

        $this->assertSame((array) $data, (array) $settings->get('Test.siteName'));
    }

    public function testSetUpdatesExistingRows()
    {
        $settings = new Settings();

        $this->hasInDatabase($this->table, [
            'class'      => 'Tests\Support\Config\Test',
            'key'        => 'siteName',
            'value'      => 'foo',
            'created_at' => Time::now()->toDateTimeString(),
            'updated_at' => Time::now()->toDateTimeString(),
        ]);

        $results = $settings->set('Test.siteName', 'Bar');

        $this->assertTrue($results);
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
            'value' => 'Bar',
        ]);
    }

    public function testWorksWithoutConfigClass()
    {
        $settings = new Settings();

        $results = $settings->set('Nada.siteName', 'Bar');

        $this->assertTrue($results);
        $this->seeInDatabase($this->table, [
            'class' => 'Nada',
            'key'   => 'siteName',
            'value' => 'Bar',
        ]);

        $this->assertSame('Bar', $settings->get('Nada.siteName'));
    }

    public function testForgetSuccess()
    {
        $settings = new Settings();

        $this->hasInDatabase($this->table, [
            'class'      => 'Tests\Support\Config\Test',
            'key'        => 'siteName',
            'value'      => 'foo',
            'created_at' => Time::now()->toDateTimeString(),
            'updated_at' => Time::now()->toDateTimeString(),
        ]);

        $results = $settings->forget('Test.siteName');

        $this->assertTrue($results);
        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
        ]);
    }

    public function testForgetWithNoStoredRecord()
    {
        $settings = new Settings();

        $results = $settings->forget('Test.siteName');

        $this->assertTrue($results);
    }
}
