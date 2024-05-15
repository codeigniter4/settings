<?php

namespace CodeIgniter\Settings\Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Settings\Handlers\ArrayHandler;
use CodeIgniter\Settings\Handlers\DatabaseHandler;

class Settings extends BaseConfig
{
    /**
     * The available handlers. The alias must
     * match a public class var here with the
     * settings array containing 'class'.
     *
     * @var list<string>
     */
    public array $handlers = ['database'];

    /**
     * Array handler settings.
     * 
     * @var array<array-key, mixed>
     */
    public array $array = [
        'class'     => ArrayHandler::class,
        'writeable' => true,
    ];

    /**
     * Database handler settings.
     * 
     * @var array<array-key, mixed>
     */
    public array $database = [
        'class'     => DatabaseHandler::class,
        'table'     => 'settings',
        'group'     => null,
        'writeable' => true,
    ];
}
