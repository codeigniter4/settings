<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\I18n\Time;
use CodeIgniter\Settings\Settings;
use CodeIgniter\Test\DatabaseTestTrait;
use InvalidArgumentException;
use ReflectionClass;
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

        /** @var \CodeIgniter\Settings\Config\Settings $config */
        $config           = config('Settings');
        $config->handlers = ['database'];

        $this->settings = new Settings($config);
        $this->table    = $config->database['table'];
    }

    /**
     * Creates a Settings instance with deferred writes enabled.
     */
    private function createDeferredSettings(): Settings
    {
        /** @var \CodeIgniter\Settings\Config\Settings $config */
        $config                          = config('Settings');
        $config->handlers                = ['database'];
        $config->database['deferWrites'] = true;

        return new Settings($config);
    }

    /**
     * Manually triggers deferred writes for a Settings instance.
     */
    private function persistDeferredWrites(Settings $settings): void
    {
        $reflection       = new ReflectionClass($settings);
        $handlersProperty = $reflection->getProperty('handlers');
        $handlers         = $handlersProperty->getValue($settings);
        $handlers['database']->persistPendingProperties();
    }

    public function testSetInsertsNewRows(): void
    {
        $this->settings->set('Example.siteName', 'Foo');

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
            'value' => 'Foo',
            'type'  => 'string',
        ]);
    }

    public function testInvalidGroup(): void
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var \CodeIgniter\Settings\Config\Settings $config */
        $config                    = config('Settings');
        $config->handlers          = ['database'];
        $config->database['group'] = 'another';

        $this->settings = new Settings($config);

        $this->settings->set('Example.siteName', true);
    }

    public function testSetDefaultGroup(): void
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

    public function testSetInsertsBoolTrue(): void
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

    public function testSetInsertsBoolFalse(): void
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

    public function testSetInsertsNull(): void
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

    public function testSetInsertsArray(): void
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

    public function testSetInsertsObject(): void
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

    public function testSetUpdatesExistingRows(): void
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

    public function testWorksWithoutConfigClass(): void
    {
        $this->settings->set('Nada.siteName', 'Bar');

        $this->seeInDatabase($this->table, [
            'class' => 'Nada',
            'key'   => 'siteName',
            'value' => 'Bar',
        ]);

        $this->assertSame('Bar', $this->settings->get('Nada.siteName'));
    }

    public function testForgetSuccess(): void
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

    public function testForgetWithNoStoredRecord(): void
    {
        $this->settings->forget('Example.siteName');

        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
        ]);
    }

    public function testFlush(): void
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

    public function testSetWithContext(): void
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

    public function testSetManyInsertsNewRows(): void
    {
        $this->settings->setMany([
            'Example.siteName'    => 'BatchName',
            'Example.siteEmail'   => 'batch@example.com',
            'Example.siteTitle'   => 'BatchTitle',
            'Example.siteEnabled' => true,
        ]);

        foreach ([
            ['key' => 'siteName', 'value' => 'BatchName', 'type' => 'string'],
            ['key' => 'siteEmail', 'value' => 'batch@example.com', 'type' => 'string'],
            ['key' => 'siteTitle', 'value' => 'BatchTitle', 'type' => 'string'],
            ['key' => 'siteEnabled', 'value' => '1', 'type' => 'boolean'],
        ] as $expected) {
            $this->seeInDatabase($this->table, [
                'class' => 'Tests\Support\Config\Example',
                'key'   => $expected['key'],
                'value' => $expected['value'],
                'type'  => $expected['type'],
            ]);
        }
    }

    public function testSetManyUpdatesExistingRowsWithoutDuplicates(): void
    {
        $this->settings->setMany([
            'Example.siteName'  => 'InitialName',
            'Example.siteEmail' => 'initial@example.com',
        ]);

        $this->settings->setMany([
            'Example.siteName'  => 'UpdatedName',
            'Example.siteEmail' => 'updated@example.com',
            'Example.siteTitle' => 'NewTitle',
        ]);

        $totalCount = $this->db->table($this->table)
            ->where('class', 'Tests\Support\Config\Example')
            ->countAllResults();

        $this->assertSame(3, $totalCount);

        foreach ([
            'siteName'  => 'UpdatedName',
            'siteEmail' => 'updated@example.com',
            'siteTitle' => 'NewTitle',
        ] as $key => $value) {
            $this->seeInDatabase($this->table, [
                'class' => 'Tests\Support\Config\Example',
                'key'   => $key,
                'value' => $value,
            ]);
        }
    }

    public function testSetManyWithContext(): void
    {
        $this->settings->setMany([
            'Example.siteName'  => 'ContextName',
            'Example.siteEmail' => 'context@example.com',
        ], 'environment:test');

        $this->seeInDatabase($this->table, [
            'class'   => 'Tests\Support\Config\Example',
            'key'     => 'siteName',
            'value'   => 'ContextName',
            'context' => 'environment:test',
        ]);
        $this->seeInDatabase($this->table, [
            'class'   => 'Tests\Support\Config\Example',
            'key'     => 'siteEmail',
            'value'   => 'context@example.com',
            'context' => 'environment:test',
        ]);

        $this->assertSame('ContextName', $this->settings->get('Example.siteName', 'environment:test'));
    }

    public function testSetManyStoresDifferentClasses(): void
    {
        $this->settings->setMany([
            'Example.siteName' => 'BatchName',
            'Nada.siteName'    => 'NadaName',
        ]);

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
            'value' => 'BatchName',
        ]);
        $this->seeInDatabase($this->table, [
            'class' => 'Nada',
            'key'   => 'siteName',
            'value' => 'NadaName',
        ]);
    }

    public function testForgetManyDeletesRows(): void
    {
        $this->settings->setMany([
            'Example.siteName'  => 'BatchName',
            'Example.siteEmail' => 'batch@example.com',
            'Example.siteTitle' => 'BatchTitle',
        ]);

        $this->settings->forgetMany([
            'Example.siteName',
            'Example.siteEmail',
        ]);

        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
        ]);
        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteEmail',
        ]);
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteTitle',
            'value' => 'BatchTitle',
        ]);
    }

    public function testForgetManyDeletesDifferentClasses(): void
    {
        $this->settings->setMany([
            'Example.siteName' => 'BatchName',
            'Nada.siteName'    => 'NadaName',
        ]);

        $this->settings->forgetMany([
            'Example.siteName',
            'Nada.siteName',
        ]);

        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
        ]);
        $this->dontSeeInDatabase($this->table, [
            'class' => 'Nada',
            'key'   => 'siteName',
        ]);
    }

    /**
     * @see https://github.com/codeigniter4/settings/issues/20
     */
    public function testSetUpdatesContextOnly(): void
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

    public function testDeferredSetManyPersistsAfterPersist(): void
    {
        $deferredSettings = $this->createDeferredSettings();

        $deferredSettings->setMany([
            'Example.siteName'  => 'DeferredName',
            'Example.siteEmail' => 'deferred@example.com',
        ]);

        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
        ]);
        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteEmail',
        ]);

        $this->persistDeferredWrites($deferredSettings);

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
            'value' => 'DeferredName',
        ]);
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteEmail',
            'value' => 'deferred@example.com',
        ]);
    }

    public function testDeferredSetReturnsPendingValueBeforePersist(): void
    {
        $this->settings->set('Example.siteName', 'StoredSingle');

        $deferredSettings = $this->createDeferredSettings();

        $deferredSettings->set('Example.siteName', 'PendingSingle');

        $this->assertSame('PendingSingle', $deferredSettings->get('Example.siteName'));
    }

    public function testDeferredSetManyReturnsPendingValueBeforePersist(): void
    {
        $this->settings->set('Example.siteName', 'StoredBatch');

        $deferredSettings = $this->createDeferredSettings();

        $deferredSettings->setMany([
            'Example.siteName' => 'PendingBatch',
        ]);

        $this->assertSame('PendingBatch', $deferredSettings->get('Example.siteName'));
    }

    public function testDeferredSetManyPersistsDifferentClassesAfterPersist(): void
    {
        $deferredSettings = $this->createDeferredSettings();

        $deferredSettings->setMany([
            'Example.siteName' => 'DeferredName',
            'Nada.siteName'    => 'DeferredNada',
        ]);

        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
        ]);
        $this->dontSeeInDatabase($this->table, [
            'class' => 'Nada',
            'key'   => 'siteName',
        ]);

        $this->persistDeferredWrites($deferredSettings);

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
            'value' => 'DeferredName',
        ]);
        $this->seeInDatabase($this->table, [
            'class' => 'Nada',
            'key'   => 'siteName',
            'value' => 'DeferredNada',
        ]);
    }

    public function testDeferredForgetManyDeletesAfterPersist(): void
    {
        $this->settings->setMany([
            'Example.siteName'  => 'BatchName',
            'Example.siteEmail' => 'batch@example.com',
        ]);

        $deferredSettings = $this->createDeferredSettings();

        $deferredSettings->forgetMany([
            'Example.siteName',
            'Example.siteEmail',
        ]);

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
        ]);
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteEmail',
        ]);

        $this->persistDeferredWrites($deferredSettings);

        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
        ]);
        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteEmail',
        ]);
    }

    public function testDeferredForgetManyDeletesDifferentClassesAfterPersist(): void
    {
        $this->settings->setMany([
            'Example.siteName' => 'BatchName',
            'Nada.siteName'    => 'NadaName',
        ]);

        $deferredSettings = $this->createDeferredSettings();

        $deferredSettings->forgetMany([
            'Example.siteName',
            'Nada.siteName',
        ]);

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
        ]);
        $this->seeInDatabase($this->table, [
            'class' => 'Nada',
            'key'   => 'siteName',
        ]);

        $this->persistDeferredWrites($deferredSettings);

        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
        ]);
        $this->dontSeeInDatabase($this->table, [
            'class' => 'Nada',
            'key'   => 'siteName',
        ]);
    }

    public function testDeferredWritesReducesDatabaseQueries(): void
    {
        // Create new settings instance with deferred writes enabled
        $deferredSettings = $this->createDeferredSettings();

        // Multiple set calls to same class
        $deferredSettings->set('Example.siteName', 'Value1');
        $deferredSettings->set('Example.siteEmail', 'test@example.com');
        $deferredSettings->set('Example.siteTitle', 'Value3');

        // Database should NOT have the rows yet (writes are deferred)
        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
        ]);
        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteEmail',
        ]);
        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteTitle',
        ]);

        // Trigger the deferred write manually
        $this->persistDeferredWrites($deferredSettings);

        // Now all rows should exist in database
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
            'value' => 'Value1',
            'type'  => 'string',
        ]);
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteEmail',
            'value' => 'test@example.com',
            'type'  => 'string',
        ]);
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteTitle',
            'value' => 'Value3',
            'type'  => 'string',
        ]);
    }

    public function testDeferredWritesForgetDeletesAfterPersist(): void
    {
        // First, insert a record to delete
        $this->settings->set('Example.siteName', 'InitialValue');

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
            'value' => 'InitialValue',
        ]);

        // Create new settings instance with deferred writes enabled
        $deferredSettings = $this->createDeferredSettings();

        // Call forget
        $deferredSettings->forget('Example.siteName');

        // Database should STILL have the row (delete is deferred)
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
        ]);

        // Trigger the deferred write manually
        $this->persistDeferredWrites($deferredSettings);

        // Now the row should be deleted
        $this->dontSeeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
        ]);
    }

    public function testDeferredWritesDeleteThenSet(): void
    {
        // First, insert a record
        $this->settings->set('Example.siteName', 'InitialValue');

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
            'value' => 'InitialValue',
        ]);

        // Create new settings instance with deferred writes enabled
        $deferredSettings = $this->createDeferredSettings();

        // Delete then set
        $deferredSettings->forget('Example.siteName');
        $deferredSettings->set('Example.siteName', 'NewValue');

        // Database should STILL have the old value (writes are deferred)
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
            'value' => 'InitialValue',
        ]);

        // Trigger the deferred write manually
        $this->persistDeferredWrites($deferredSettings);

        // Now the row should have the new value (set overwrites delete in pending operations)
        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
            'value' => 'NewValue',
        ]);
    }

    public function testNoDuplicatesWhenUpdatingExistingRecords(): void
    {
        // Pre-populate database with existing records
        $this->settings->set('Example.siteName', 'InitialValue1');
        $this->settings->set('Example.siteEmail', 'InitialValue2');
        $this->settings->set('Example.siteTitle', 'InitialValue3');

        $totalCount = $this->db->table($this->table)
            ->where('class', 'Tests\Support\Config\Example')
            ->countAllResults();

        $this->assertSame(3, $totalCount);

        /** @var \CodeIgniter\Settings\Config\Settings $config */
        $config                          = config('Settings');
        $config->handlers                = ['database'];
        $config->database['deferWrites'] = true;
        $deferredSettings                = new Settings($config);

        $deferredSettings->set('Example.siteName', 'UpdatedValue1');
        $deferredSettings->set('Example.siteName', 'UpdatedValue2');
        $deferredSettings->set('Example.siteEmail', 'UpdatedEmail1');
        $deferredSettings->set('Example.siteEmail', 'UpdatedEmail2');

        // Trigger the deferred write manually
        $this->persistDeferredWrites($deferredSettings);

        // Verify no duplicates - should have exactly 3 records total
        $totalCount = $this->db->table($this->table)
            ->where('class', 'Tests\Support\Config\Example')
            ->countAllResults();

        $this->assertSame(3, $totalCount);

        // Verify each property has exactly 1 record
        $siteNameCount = $this->db->table($this->table)
            ->where('class', 'Tests\Support\Config\Example')
            ->where('key', 'siteName')
            ->countAllResults();

        $this->assertSame(1, $siteNameCount);

        $siteEmailCount = $this->db->table($this->table)
            ->where('class', 'Tests\Support\Config\Example')
            ->where('key', 'siteEmail')
            ->countAllResults();

        $this->assertSame(1, $siteEmailCount);

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteName',
            'value' => 'UpdatedValue2',
        ]);

        $this->seeInDatabase($this->table, [
            'class' => 'Tests\Support\Config\Example',
            'key'   => 'siteEmail',
            'value' => 'UpdatedEmail2',
        ]);
    }

    public function testNoDuplicatesWithMixedNewAndExistingRecords(): void
    {
        // Pre-populate database with some existing records
        $this->settings->set('Example.siteName', 'ExistingValue');
        $this->settings->set('Example.siteEmail', 'existing@example.com');

        /** @var \CodeIgniter\Settings\Config\Settings $config */
        $config                          = config('Settings');
        $config->handlers                = ['database'];
        $config->database['deferWrites'] = true;
        $deferredSettings                = new Settings($config);

        $deferredSettings->set('Example.siteName', 'UpdatedValue');     // Update existing
        $deferredSettings->set('Example.siteTitle', 'NewValue1');       // Create new
        $deferredSettings->set('Example.siteEmail', 'new@example.com'); // Update existing
        $deferredSettings->set('Example.tagline', 'NewValue2');         // Create new

        // Trigger the deferred write manually
        $this->persistDeferredWrites($deferredSettings);

        // Verify no duplicates - should have exactly 4 records total
        $totalCount = $this->db->table($this->table)
            ->where('class', 'Tests\Support\Config\Example')
            ->countAllResults();

        $this->assertSame(4, $totalCount);

        // Verify each property has exactly 1 record
        foreach (['siteName', 'siteEmail', 'siteTitle', 'tagline'] as $key) {
            $count = $this->db->table($this->table)
                ->where('class', 'Tests\Support\Config\Example')
                ->where('key', $key)
                ->countAllResults();

            $this->assertSame(1, $count, "Expected exactly 1 record for key '{$key}'");
        }
    }
}
