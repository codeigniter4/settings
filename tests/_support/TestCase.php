<?php

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use Nexus\PHPUnit\Extension\Expeditable;

abstract class TestCase extends CIUnitTestCase
{
    use Expeditable;

    protected $namespace = 'Sparks\Settings';
    protected $refresh   = true;

    /**
     * @var string
     */
    protected $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = config('Settings')->database['table'];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->resetServices();
    }
}
