<?php

namespace Tests;

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

        $this->assertNull(service('settings')->get('Foo.bam'));
        $this->assertNull(setting('Foo.bam'));
    }

    public function testReturnsValueDotArray()
    {
        service('settings')->set('Foo.bar', 'baz');

        $this->assertSame('baz', setting('Foo.bar'));
    }

    public function testSettingValueDotArray()
    {
        service('settings')->set('Foo.bar', 'baz');

        setting('Foo.bar', false);

        $this->assertFalse(service('settings')->get('Foo.bar'));
        $this->assertFalse(setting('Foo.bar'));
    }
}
