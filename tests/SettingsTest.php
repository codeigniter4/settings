<?php

namespace Tests;

use Sparks\Settings\Settings;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\TestCase;

class SettingsTest extends TestCase
{
    use DatabaseTestTrait;

    public function testSettingsGetsFromConfig()
    {
        $settings = new Settings();

        $this->assertEquals(config('Test')->siteName, $settings->get('Test', 'siteName'));
    }

    public function testSettingsDatabaseNotFound()
    {
        $settings = new Settings();

        $this->assertEquals(config('Test')->siteName, $settings->get('Test', 'siteName'));
    }

    public function testSetInsertsNewRows()
    {
        $settings = new Settings();

        $results = $settings->set('Test', 'siteName', 'Foo');

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

        $results = $settings->set('Test', 'siteName', true);

        $this->assertTrue($results);
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
            'value' => '1',
            'type'  => 'boolean',
        ]);

        $this->assertSame(true, $settings->get('Test', 'siteName'));
    }

    public function testSetInsertsBoolFalse()
    {
        $settings = new Settings();

        $results = $settings->set('Test', 'siteName', false);

        $this->assertTrue($results);
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
            'value' => '0',
            'type'  => 'boolean',
        ]);

        $this->assertSame(false, $settings->get('Test', 'siteName'));
    }

    public function testSetInsertsArray()
    {
        $settings = new Settings();
        $data     = ['foo' => 'bar'];

        $results = $settings->set('Test', 'siteName', $data);

        $this->assertTrue($results);
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
            'value' => serialize($data),
            'type'  => 'array',
        ]);

        $this->assertEquals($data, $settings->get('Test', 'siteName'));
    }

    public function testSetInsertsObject()
    {
        $settings = new Settings();
        $data     = (object)['foo' => 'bar'];

        $results = $settings->set('Test', 'siteName', $data);

        $this->assertTrue($results);
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
            'value' => serialize($data),
            'type'  => 'object',
        ]);

        $this->assertEquals($data, $settings->get('Test', 'siteName'));
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

        $results = $settings->set('Test', 'siteName', 'Bar');

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

        $results = $settings->set('Nada', 'siteName', 'Bar');

        $this->assertTrue($results);
        $this->seeInDatabase($this->table, [
            'class' => 'Nada',
            'key'   => 'siteName',
            'value' => 'Bar',
        ]);

        $this->assertEquals('Bar', $settings->get('Nada', 'siteName'));
    }
}
