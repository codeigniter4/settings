<?php

namespace Tests;

use CodeIgniter\I18n\Time;
use CodeIgniter\Settings\Settings;
use CodeIgniter\Test\DatabaseTestTrait;
use InvalidArgumentException;
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
     * @var string
     */
    protected $group;

    /**
     * Ensures we are using the database handler.
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var \CodeIgniter\Settings\Config\Settings $config */
        $config           = config('Settings');
        $config->handlers = ['database'];

        $this->settings = new Settings($config);
        $this->table    = $config->database['table'];
        $this->group    = $config->database['group'];
    }

    public function testSetInsertsNewRows()
    {
        $this->settings->set('Example.siteName', 'Foo');

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
            'value' => 'Foo',
            'type'  => 'string',
        ]);
    }

    public function testInvalidGroup()
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var \CodeIgniter\Settings\Config\Settings $config */
        $config                    = config('Settings');
        $config->handlers          = ['database'];
        $config->database['group'] = 'another';

        $this->settings = new Settings($config);

        $this->settings->set('Example.siteName', true);
    }

    public function testSetDefaultGroup()
    {
        /** @var \CodeIgniter\Settings\Config\Settings $config */
        $config                    = config('Settings');
        $config->handlers          = ['database'];
        $config->database['group'] = 'default';

        $this->settings->set('Example.siteName', true);

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
            'value' => '1',
            'type'  => 'boolean',
        ]);

        $this->assertTrue($this->settings->get('Example.siteName'));
    }

    public function testSetInsertsBoolTrue()
    {
        $this->settings->set('Example.siteName', true);

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
            'value' => '1',
            'type'  => 'boolean',
        ]);

        $this->assertTrue($this->settings->get('Example.siteName'));
    }

    public function testSetInsertsBoolFalse()
    {
        $this->settings->set('Example.siteName', false);

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
            'value' => '0',
            'type'  => 'boolean',
        ]);

        $this->assertFalse($this->settings->get('Example.siteName'));
    }

    public function testSetInsertsNull()
    {
        $this->settings->set('Example.siteName', null);

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
            'value' => null,
            'type'  => 'NULL',
        ]);

        $this->assertNull($this->settings->get('Example.siteName'));
    }

    public function testSetInsertsArray()
    {
        $data = ['foo' => 'bar'];
        $this->settings->set('Example.siteName', $data);

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
            'value' => serialize($data),
            'type'  => 'array',
        ]);

        $this->assertSame($data, $this->settings->get('Example.siteName'));
    }

    public function testSetInsertsObject()
    {
        $data = (object) ['foo' => 'bar'];
        $this->settings->set('Example.siteName', $data);

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
            'value' => serialize($data),
            'type'  => 'object',
        ]);

        $this->assertSame((array) $data, (array) $this->settings->get('Example.siteName'));
    }

    public function testSetUpdatesExistingRows()
    {
        $this->hasInDatabase($this->table, [
            'class'      => 'Tests\Support\Config\Example',
            'key'        => 'siteName',
            'value'      => 'foo',
            'created_at' => Time::now()->toDateTimeString(),
            'updated_at' => Time::now()->toDateTimeString(),
        ]);

        $this->settings->set('Example.siteName', 'Bar');

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
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
            'class'      => 'Tests\Support\Config\Example',
            'key'        => 'siteName',
            'value'      => 'foo',
            'created_at' => Time::now()->toDateTimeString(),
            'updated_at' => Time::now()->toDateTimeString(),
        ]);

        $this->settings->forget('Example.siteName');

        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
        ]);
    }

    public function testForgetWithNoStoredRecord()
    {
        $this->settings->forget('Example.siteName');

        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
        ]);
    }

    public function testFlush()
    {
        // Default value in the config file
        $this->assertSame('Settings Test', $this->settings->get('Example.siteName'));

        $this->settings->set('Example.siteName', 'Foo');

        // Should be the last value set
        $this->assertSame('Foo', $this->settings->get('Example.siteName'));

        $this->settings->flush();

        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
        ]);

        // Should be back to the default value
        $this->assertSame('Settings Test', $this->settings->get('Example.siteName'));
    }

    public function testSetWithContext()
    {
        $this->settings->set('Example.siteName', 'Banana', 'environment:test');

        $this->seeInDatabase($this->table, [
            'class'   => 'Tests\Support\Config\Example',
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
        $this->settings->set('Example.siteName', 'Humpty');
        $this->settings->set('Example.siteName', 'Jack', 'context:male');
        $this->settings->set('Example.siteName', 'Jill', 'context:female');
        $this->settings->set('Example.siteName', 'Jane', 'context:female');

        $this->seeInDatabase($this->table, [
            'class'   => 'Tests\Support\Config\Example',
            'key'     => 'siteName',
            'value'   => 'Jane',
            'type'    => 'string',
            'context' => 'context:female',
        ]);

        $this->seeInDatabase($this->table, [
            'class'   => 'Tests\Support\Config\Example',
            'key'     => 'siteName',
            'value'   => 'Humpty',
            'type'    => 'string',
            'context' => null,
        ]);
        $this->seeInDatabase($this->table, [
            'class'   => 'Tests\Support\Config\Example',
            'key'     => 'siteName',
            'value'   => 'Jack',
            'type'    => 'string',
            'context' => 'context:male',
        ]);
    }
}
