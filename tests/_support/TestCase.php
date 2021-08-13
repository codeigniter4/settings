<?php

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;

abstract class TestCase extends CIUnitTestCase
{
	protected $namespace = 'Sparks\Settings';
	protected $refresh   = true;

	/**
	 * @var string
	 */
	protected $table;

	public function setUp(): void
	{
		parent::setUp();

		$this->table = config('Settings')->database['table'];
	}

	public function tearDown(): void
	{
		parent::tearDown();

		$this->resetServices();
	}
}
