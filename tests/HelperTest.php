<?php

namespace CodeIgniter4\Tests;

use CodeIgniter\I18n\Time;
use CodeIgniter\Test\DatabaseTestTrait;
use Sparks\Settings\Settings;
use Tests\Support\TestCase;

class HelperTest extends TestCase
{
    use DatabaseTestTrait;

    public function setUp(): void
    {
        parent::setUp();

        helper(['setting']);
    }

    public function testReturnsServiceByDefault()
    {
        $this->assertInstanceOf(Settings::class, setting());
    }

    public function testThrowsExceptionWithInvalidField()
    {
        $this->expectException(\RuntimeException::class);

        setting('Foobar');
    }

    public function testReturnsValueDotArray()
    {
        $this->hasInDatabase($this->table, [
            'class'      => 'Foo',
            'key'        => 'bar',
            'value'      => 'baz',
            'type'       => 'string',
            'created_at' => Time::now()->toDateTimeString(),
            'updated_at' => Time::now()->toDateTimeString(),
        ]);

        $this->assertEquals('baz', setting('Foo.bar'));
    }

    public function testSettingValueDotArray()
    {
        $this->hasInDatabase($this->table, [
            'class'      => 'Foo',
            'key'        => 'bar',
            'value'      => 'baz',
            'type'       => 'string',
            'created_at' => Time::now()->toDateTimeString(),
            'updated_at' => Time::now()->toDateTimeString(),
        ]);

        setting('Foo.bar', false);

        $this->seeInDatabase($this->table, [
            'class' => 'Foo',
            'key'   => 'bar',
            'value' => '0',
            'type'  => 'boolean',
        ]);

        $this->assertFalse(setting('Foo.bar'));
    }
}
