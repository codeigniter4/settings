<?php

namespace Tests;

use CodeIgniter\I18n\Time;
use CodeIgniter\Settings\Settings;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class DatabaseHandlerTest extends TestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'CodeIgniter\Settings';
    protected $refresh   = true;

    /**
     * @var string
     */
    protected $table;

    /**
     * Ensures we are using the database handler.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config           = config('Settings');
        $config->handlers = ['database'];

        $this->settings = new Settings($config);
        $this->table    = $config->database['table'];
    }

    public function testSetInsertsNewRows()
    {
        $this->settings->set('Test.siteName', 'Foo');

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
            'value' => 'Foo',
            'type'  => 'string',
        ]);
    }

    public function testSetInsertsBoolTrue()
    {
        $this->settings->set('Test.siteName', true);

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
            'value' => '1',
            'type'  => 'boolean',
        ]);

        $this->assertTrue($this->settings->get('Test.siteName'));
    }

    public function testSetInsertsBoolFalse()
    {
        $this->settings->set('Test.siteName', false);

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
            'value' => '0',
            'type'  => 'boolean',
        ]);

        $this->assertFalse($this->settings->get('Test.siteName'));
    }

    public function testSetInsertsNull()
    {
        $this->settings->set('Test.siteName', null);

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
            'value' => null,
            'type'  => 'NULL',
        ]);

        $this->assertNull($this->settings->get('Test.siteName'));
    }

    public function testSetInsertsArray()
    {
        $data = ['foo' => 'bar'];
        $this->settings->set('Test.siteName', $data);

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
            'value' => serialize($data),
            'type'  => 'array',
        ]);

        $this->assertSame($data, $this->settings->get('Test.siteName'));
    }

    public function testSetInsertsObject()
    {
        $data = (object) ['foo' => 'bar'];
        $this->settings->set('Test.siteName', $data);

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
            'value' => serialize($data),
            'type'  => 'object',
        ]);

        $this->assertSame((array) $data, (array) $this->settings->get('Test.siteName'));
    }

    public function testSetUpdatesExistingRows()
    {
        $this->hasInDatabase($this->table, [
            'class'      => 'Tests\Support\Config\Test',
            'key'        => 'siteName',
            'value'      => 'foo',
            'created_at' => Time::now()->toDateTimeString(),
            'updated_at' => Time::now()->toDateTimeString(),
        ]);

        $this->settings->set('Test.siteName', 'Bar');

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
            'value' => 'Bar',
        ]);
    }

    public function testWorksWithoutConfigClass()
    {
        $this->settings->set('Nada.siteName', 'Bar');

        $this->seeInDatabase($this->table, [
            'class' => 'Nada',
            'key'   => 'siteName',
            'value' => 'Bar',
        ]);

        $this->assertSame('Bar', $this->settings->get('Nada.siteName'));
    }

    public function testForgetSuccess()
    {
        $this->hasInDatabase($this->table, [
            'class'      => 'Tests\Support\Config\Test',
            'key'        => 'siteName',
            'value'      => 'foo',
            'created_at' => Time::now()->toDateTimeString(),
            'updated_at' => Time::now()->toDateTimeString(),
        ]);

        $this->settings->forget('Test.siteName');

        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
        ]);
    }

    public function testForgetWithNoStoredRecord()
    {
        $this->settings->forget('Test.siteName');

        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Test',
            'key'   => 'siteName',
        ]);
    }

    public function testSetWithContext()
    {
        $this->settings->set('Test.siteName', 'Banana', 'environment:test');

        $this->seeInDatabase($this->table, [
            'class'   => 'Tests\Support\Config\Test',
            'key'     => 'siteName',
            'value'   => 'Banana',
            'type'    => 'string',
            'context' => 'environment:test',
        ]);
    }

    /**
     * @see https://github.com/codeigniter4/settings/issues/20
     */
    public function testSetUpdatesContextOnly()
    {
        $this->settings->set('Test.siteName', 'Humpty');
        $this->settings->set('Test.siteName', 'Jack', 'context:male');
        $this->settings->set('Test.siteName', 'Jill', 'context:female');
        $this->settings->set('Test.siteName', 'Jane', 'context:female');

        $this->seeInDatabase($this->table, [
            'class'   => 'Tests\Support\Config\Test',
            'key'     => 'siteName',
            'value'   => 'Jane',
            'type'    => 'string',
            'context' => 'context:female',
        ]);

        $this->seeInDatabase($this->table, [
            'class'   => 'Tests\Support\Config\Test',
            'key'     => 'siteName',
            'value'   => 'Humpty',
            'type'    => 'string',
            'context' => null,
        ]);
        $this->seeInDatabase($this->table, [
            'class'   => 'Tests\Support\Config\Test',
            'key'     => 'siteName',
            'value'   => 'Jack',
            'type'    => 'string',
            'context' => 'context:male',
        ]);
    }
}
