<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter Queue.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Commands;

use CodeIgniter\Test\Filters\CITestStreamFilter;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class PublishSettingsTest extends TestCase
{
    public function testRun(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();

        $this->assertNotFalse(command('settings:publish'));
        $output = $this->parseOutput(CITestStreamFilter::$buffer);

        CITestStreamFilter::removeOutputFilter();

        $this->assertSame('  Published! You can customize the configuration by editing the "app/Config/Settings.php" file.', $output);
    }
}
