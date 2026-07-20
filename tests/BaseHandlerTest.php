<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Settings\Handlers\ArrayHandler;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class BaseHandlerTest extends TestCase
{
    public function testPrepareValueSerializesBooleansAsStrings(): void
    {
        $prepareValue = self::getPrivateMethodInvoker(new ArrayHandler(), 'prepareValue');

        $this->assertSame('1', $prepareValue(true));
        $this->assertSame('0', $prepareValue(false));
    }
}
