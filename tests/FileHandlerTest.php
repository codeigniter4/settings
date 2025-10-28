<?php

namespace Tests;

use CodeIgniter\Settings\Config\Settings as ConfigSettings;
use CodeIgniter\Settings\Settings;
use ReflectionClass;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class FileHandlerTest extends TestCase
{
    /**
     * @var string
     */
    protected $path;

    /**
     * Ensures we are using the file handler.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary directory for testing
        $this->path = sys_get_temp_dir() . '/settings_test_' . uniqid() . '/';

        /** @var ConfigSettings $config */
        $config               = config('Settings');
        $config->handlers     = ['file'];
        $config->file['path'] = $this->path;

        $this->settings = new Settings($config);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test directory
        if (is_dir($this->path)) {
            $files = glob($this->path . '*', GLOB_NOSORT);

            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            @rmdir($this->path);
        }
    }

    public function testSetCreatesDirectory()
    {
        $this->assertDirectoryExists($this->path);
        $this->assertIsWritable($this->path);
    }

    public function testSetCreatesFile()
    {
        $this->settings->set('Example.siteName', 'Foo');

        $files = glob($this->path . '*.php', GLOB_NOSORT);
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.php', $files[0]);
    }

    public function testSetStoresString()
    {
        $this->settings->set('Example.siteName', 'Foo');

        $this->assertSame('Foo', $this->settings->get('Example.siteName'));
    }

    public function testSetStoresBoolTrue()
    {
        $this->settings->set('Example.siteName', true);

        $this->assertTrue($this->settings->get('Example.siteName'));
    }

    public function testSetStoresBoolFalse()
    {
        $this->settings->set('Example.siteName', false);

        $this->assertFalse($this->settings->get('Example.siteName'));
    }

    public function testSetStoresNull()
    {
        $this->settings->set('Example.siteName', null);

        $this->assertNull($this->settings->get('Example.siteName'));
    }

    public function testSetStoresInteger()
    {
        $this->settings->set('Example.siteName', 42);

        $this->assertSame(42, $this->settings->get('Example.siteName'));
    }

    public function testSetStoresFloat()
    {
        $this->settings->set('Example.siteName', 3.14);

        $this->assertSame(3.14, $this->settings->get('Example.siteName'));
    }

    public function testSetStoresArray()
    {
        $data = ['foo' => 'bar', 'baz' => 'qux'];
        $this->settings->set('Example.siteName', $data);

        $this->assertSame($data, $this->settings->get('Example.siteName'));
    }

    public function testSetStoresObject()
    {
        $data = (object) ['foo' => 'bar'];
        $this->settings->set('Example.siteName', $data);

        $result = $this->settings->get('Example.siteName');
        $this->assertSame((array) $data, (array) $result);
    }

    public function testSetUpdatesExistingValue()
    {
        $this->settings->set('Example.siteName', 'Foo');
        $this->assertSame('Foo', $this->settings->get('Example.siteName'));

        $this->settings->set('Example.siteName', 'Bar');
        $this->assertSame('Bar', $this->settings->get('Example.siteName'));

        // Should still only have one file
        $files = glob($this->path . '*.php', GLOB_NOSORT);
        $this->assertCount(1, $files);
    }

    public function testGetNonExistentReturnsNull()
    {
        $this->assertNull($this->settings->get('Example.nonExistent'));
    }

    public function testWorksWithoutConfigClass()
    {
        $this->settings->set('Nada.siteName', 'Bar');

        $this->assertSame('Bar', $this->settings->get('Nada.siteName'));
    }

    public function testForgetRemovesValue()
    {
        $this->settings->set('Example.siteName', 'Foo');
        $this->assertSame('Foo', $this->settings->get('Example.siteName'));

        $this->settings->forget('Example.siteName');

        // Should fall back to default value from config file
        $this->assertSame('Settings Test', $this->settings->get('Example.siteName'));
    }

    public function testForgetWithNoStoredRecord()
    {
        // Should not throw an exception
        $this->settings->forget('Example.siteName');

        // Should return default value from config file
        $this->assertSame('Settings Test', $this->settings->get('Example.siteName'));
    }

    public function testFlushRemovesAllFiles()
    {
        $this->settings->set('Example.siteName', 'Foo');
        $this->settings->set('Example.siteEmail', 'test@example.com');

        $files = glob($this->path . '*.php', GLOB_NOSORT);
        $this->assertNotEmpty($files);

        $this->settings->flush();

        $files = glob($this->path . '*.php', GLOB_NOSORT);
        $this->assertEmpty($files);

        // Should be back to the default value
        $this->assertSame('Settings Test', $this->settings->get('Example.siteName'));
    }

    public function testFlushRemovesFilesAndContextDirectories()
    {
        // Create files in main directory (null context)
        $this->settings->set('Example.siteName', 'Main');
        $this->settings->set('Example.siteEmail', 'main@example.com');

        // Create files in context subdirectories
        $this->settings->set('Example.siteName', 'Production', 'production');
        $this->settings->set('Example.siteTitle', 'Prod Site', 'production');
        $this->settings->set('Example.siteName', 'Testing', 'testing');

        $mainFiles = glob($this->path . '*.php', GLOB_NOSORT);
        $this->assertNotEmpty($mainFiles);

        $directories = glob($this->path . '*', GLOB_ONLYDIR | GLOB_NOSORT);
        $this->assertCount(2, $directories); // production and testing

        $this->settings->flush();

        $mainFiles = glob($this->path . '*.php', GLOB_NOSORT);
        $this->assertEmpty($mainFiles);

        $directories = glob($this->path . '*', GLOB_ONLYDIR | GLOB_NOSORT);
        $this->assertEmpty($directories);

        // Should be back to default values
        $this->assertSame('Settings Test', $this->settings->get('Example.siteName'));
        $this->assertSame('Settings Test', $this->settings->get('Example.siteName', 'production'));
    }

    public function testSetWithContext()
    {
        $this->settings->set('Example.siteName', 'Banana', 'environment:test');

        $this->assertSame('Banana', $this->settings->get('Example.siteName', 'environment:test'));
    }

    public function testSetUpdatesContextOnly()
    {
        $this->settings->set('Example.siteName', 'Humpty');
        $this->settings->set('Example.siteName', 'Jack', 'context:male');
        $this->settings->set('Example.siteName', 'Jill', 'context:female');
        $this->settings->set('Example.siteName', 'Jane', 'context:female');

        $this->assertSame('Humpty', $this->settings->get('Example.siteName'));
        $this->assertSame('Jack', $this->settings->get('Example.siteName', 'context:male'));
        $this->assertSame('Jane', $this->settings->get('Example.siteName', 'context:female'));
    }

    public function testContextFallsBackToGeneral()
    {
        $this->settings->set('Example.siteName', 'General');

        // Should return general value when context-specific value doesn't exist
        $this->assertSame('General', $this->settings->get('Example.siteName', 'context:nonexistent'));
    }

    public function testMultiplePropertiesInSameFile()
    {
        $this->settings->set('Example.siteName', 'Foo');
        $this->settings->set('Example.siteEmail', 'test@example.com');

        $this->assertSame('Foo', $this->settings->get('Example.siteName'));
        $this->assertSame('test@example.com', $this->settings->get('Example.siteEmail'));

        // Should only have one file for same class
        $files = glob($this->path . '*.php', GLOB_NOSORT);
        $this->assertCount(1, $files);
    }

    public function testDifferentClassesCreateDifferentFiles()
    {
        $this->settings->set('Example.siteName', 'Foo');
        $this->settings->set('Nada.siteName', 'Bar');

        $this->assertSame('Foo', $this->settings->get('Example.siteName'));
        $this->assertSame('Bar', $this->settings->get('Nada.siteName'));

        // Should have two files - one per class
        $files = glob($this->path . '*.php', GLOB_NOSORT);
        $this->assertCount(2, $files);
    }

    public function testPersistenceAcrossInstances()
    {
        // Set value in first instance
        $this->settings->set('Example.siteName', 'Persistent');

        // Create new instance
        /** @var ConfigSettings $config */
        $config               = config('Settings');
        $config->handlers     = ['file'];
        $config->file['path'] = $this->path;
        $newSettings          = new Settings($config);

        // Should retrieve value from file
        $this->assertSame('Persistent', $newSettings->get('Example.siteName'));
    }

    public function testFileContentIsValidPHP()
    {
        $this->settings->set('Example.siteName', 'Test');

        $files = glob($this->path . '*.php', GLOB_NOSORT);
        $this->assertNotEmpty($files);

        $content = file_get_contents($files[0]);

        // Should start with PHP tag
        $this->assertStringStartsWith('<?php', $content);

        // Should contain a return statement
        $this->assertStringContainsString('return', $content);

        // Should be valid PHP (no syntax errors)
        $data = include $files[0];
        $this->assertIsArray($data);
    }

    public function testUsesClassNameForFilename()
    {
        $this->settings->set('Example.siteName', 'Test');

        // Null context files use class name with backslashes replaced by underscores
        $expectedFile = $this->path . 'Tests_Support_Config_Example.php';

        $this->assertFileExists($expectedFile);
    }

    public function testContextUsesHashedSubdirectory()
    {
        $context = 'environment:production';
        $this->settings->set('Example.siteName', 'Prod', $context);

        // Context files are in subdirectories named by context hash
        $contextHash  = hash('xxh128', $context);
        $expectedFile = $this->path . $contextHash . '/Tests_Support_Config_Example.php';

        $this->assertFileExists($expectedFile);
    }

    public function testEmptyStringContextIsDifferentFromNull()
    {
        // Set with null context
        $this->settings->set('Example.siteName', 'Null', null);

        // Set with empty string context
        $this->settings->set('Example.siteName', 'Empty', '');

        // Null context: file in main directory
        $nullContextFile = $this->path . 'Tests_Support_Config_Example.php';
        $this->assertFileExists($nullContextFile);

        // Empty string context: file in subdirectory
        $emptyContextHash = hash('xxh128', '');
        $emptyContextFile = $this->path . $emptyContextHash . '/Tests_Support_Config_Example.php';
        $this->assertFileExists($emptyContextFile);

        // Verify correct values
        $this->assertSame('Null', $this->settings->get('Example.siteName', null));
        $this->assertSame('Empty', $this->settings->get('Example.siteName', ''));
    }

    public function testConcurrentReadsDontLoadFileTwice()
    {
        $this->settings->set('Example.siteName', 'Test');
        $this->settings->set('Example.siteEmail', 'test@example.com');

        // First get - loads from file
        $value1 = $this->settings->get('Example.siteName');

        // Modify file directly
        $files = glob($this->path . '*.php', GLOB_NOSORT);
        file_put_contents($files[0], "<?php\nreturn ['corrupted' => 'data'];");

        // Second get - should use cached value, not reload from file
        $value2 = $this->settings->get('Example.siteEmail');

        $this->assertSame('Test', $value1);
        $this->assertSame('test@example.com', $value2);
    }

    public function testHasReturnsTrueWhenValueExists()
    {
        $this->settings->set('Example.siteName', 'Test');

        // Access has() method through reflection since it's not exposed via Settings class
        $reflection       = new ReflectionClass($this->settings);
        $handlersProperty = $reflection->getProperty('handlers');
        $handlers         = $handlersProperty->getValue($this->settings);

        $this->assertTrue($handlers['file']->has('Tests\Support\Config\Example', 'siteName'));
    }

    public function testHasReturnsFalseWhenValueDoesNotExist()
    {
        $reflection       = new ReflectionClass($this->settings);
        $handlersProperty = $reflection->getProperty('handlers');
        $handlers         = $handlersProperty->getValue($this->settings);

        $this->assertFalse($handlers['file']->has('Tests\Support\Config\Example', 'nonExistent'));
    }

    /**
     * Simulate writes from different PHP processes (each with separate in-memory state)
     * by manually modifying the file between operations
     */
    public function testMergesChangesFromDifferentProcesses()
    {
        // Process A writes siteName
        $this->settings->set('Example.siteName', 'First');

        // Get the file path
        $files = glob($this->path . '*.php', GLOB_NOSORT);
        $this->assertCount(1, $files);
        $filePath = $files[0];

        // Simulate Process B writing siteEmail (has different in-memory state)
        // Process B doesn't know about siteName in its memory, but it exists on disk
        $data              = include $filePath;
        $data['siteEmail'] = ['value' => 'concurrent@example.com', 'type' => 'string'];
        $content           = "<?php\n\nreturn " . var_export($data, true) . ";\n";
        file_put_contents($filePath, $content);

        // Process A writes siteTitle (still doesn't know about siteEmail in memory)
        // The merge logic should read current file state and preserve all properties
        $this->settings->set('Example.siteTitle', 'Second');

        // Verify all three properties exist
        $this->assertSame('First', $this->settings->get('Example.siteName'));
        $this->assertSame('Second', $this->settings->get('Example.siteTitle'));

        // Reload from disk to verify persistence
        /** @var ConfigSettings $config */
        $config               = config('Settings');
        $config->handlers     = ['file'];
        $config->file['path'] = $this->path;
        $newSettings          = new Settings($config);

        $this->assertSame('First', $newSettings->get('Example.siteName'));
        $this->assertSame('concurrent@example.com', $newSettings->get('Example.siteEmail'));
        $this->assertSame('Second', $newSettings->get('Example.siteTitle'));
    }
}
