<?php

namespace CodeIgniter4\Tests;

use CodeIgniter\I18n\Time;
use CodeIgniter\Settings\Settings;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class HelperTest extends TestCase
{
    use DatabaseTestTrait;

    protected function setUp(): void
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
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('$key must contain both the class and field name, i.e. Foo.bar');

        setting('Foobar');
    }

    public function testSetsNull()
    {
        setting('Foo.bam', null);

        $this->seeInDatabase($this->table, [
            'class' => 'Foo',
            'key'   => 'bam',
            'value' => null,
            'type'  => 'NULL',
        ]);

        $this->assertNull(setting('Foo.bam'));
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

        $this->assertSame('baz', setting('Foo.bar'));
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
