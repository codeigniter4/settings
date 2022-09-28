<?php

namespace Tests;

use CodeIgniter\Settings\Handlers\RedisHandler;
use CodeIgniter\Settings\Settings;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class RedisHandlerTest extends TestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'CodeIgniter\Settings';
    protected $refresh   = true;
    protected RedisHandler $handler;

    /**
     * Ensures we are using the database handler.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config           = config('Settings');
        $config->handlers = ['redis'];

        $this->settings = new Settings($config);
        $this->handler  = new RedisHandler();
    }

    public function testSetInsertsNewRows()
    {
        $this->settings->set('Test.siteName', 'Foo');

        $this->assertSame('Foo', $this->settings->get('Test.siteName'));
    }

    public function testSetInsertsBoolTrue()
    {
        $this->settings->set('Test.siteName', true);

        $this->assertTrue($this->settings->get('Test.siteName'));
    }

    public function testSetInsertsBoolFalse()
    {
        $this->settings->set('Test.siteName', false);

        $this->assertFalse($this->settings->get('Test.siteName'));
    }

    public function testSetInsertsNull()
    {
        $this->settings->set('Test.siteName', null);

        $this->assertNull($this->settings->get('Test.siteName'));
    }

    public function testSetInsertsArray()
    {
        $data = ['foo' => 'bar'];
        $this->settings->set('Test.siteName', $data);

        $this->assertSame($data, $this->settings->get('Test.siteName'));
    }

    public function testSetInsertsObject()
    {
        $data = (object) ['foo' => 'bar'];
        $this->settings->set('Test.siteName', $data);

        $this->assertSame((array) $data, (array) $this->settings->get('Test.siteName'));
    }

    public function testSetUpdatesExistingRows()
    {
        $this->settings->set('Test.siteName', 'Foo');

        $this->settings->set('Test.siteName', 'Bar');

        $this->assertSame('Bar', $this->settings->get('Test.siteName'));
    }

    public function testWorksWithoutConfigClass()
    {
        $this->settings->set('Nada.siteName', 'Bar');

        $this->assertSame('Bar', $this->settings->get('Nada.siteName'));
    }

    public function testForgetSuccess()
    {
        $this->settings->set('Test.siteName', 'Bar');
        $this->assertSame('Bar', $this->settings->get('Test.siteName'));
        $this->settings->forget('Test.siteName');
        $this->assertSame('Settings Test', $this->settings->get('Test.siteName'));
    }

    public function testForgetWithNoStoredRecord()
    {
        $this->settings->forget('Test.siteName');

        $this->assertSame('Settings Test', $this->settings->get('Test.siteName'));
    }

    public function testSetWithContext()
    {
        $this->settings->set('Test.siteName', 'Bar');
        $this->settings->set('Test.siteName', 'Banana', 'environment:test');
        $this->assertSame('Bar', $this->settings->get('Test.siteName'));
        $this->assertSame('Banana', $this->settings->get('Test.siteName', 'environment:test'));
    }
}
