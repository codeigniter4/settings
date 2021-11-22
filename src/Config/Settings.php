<?php

namespace CodeIgniter\Settings\Config;

use CodeIgniter\Settings\Handlers\ArrayHandler;
use CodeIgniter\Settings\Handlers\DatabaseHandler;

class Settings
{
    /**
     * The available handlers. The alias must
     * match a public class var here with the
     * settings array containing 'class'.
     *
     * @var string[]
     */
    public $handlers = ['database'];

    /**
     * Array handler settings.
     */
    public $array = [
        'class'     => ArrayHandler::class,
        'writeable' => true,
    ];

    /**
     * Database handler settings.
     */
    public $database = [
        'class'     => DatabaseHandler::class,
        'table'     => 'settings',
        'writeable' => true,
    ];
}
