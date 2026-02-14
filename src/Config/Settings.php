<?php

declare(strict_types=1);

namespace CodeIgniter\Settings\Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Settings\Handlers\ArrayHandler;
use CodeIgniter\Settings\Handlers\DatabaseHandler;
use CodeIgniter\Settings\Handlers\FileHandler;

class Settings extends BaseConfig
{
    /**
     * The available handlers. The alias must
     * match a public class var here with the
     * settings array containing 'class'.
     *
     * @var list<string>
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
        'class'       => DatabaseHandler::class,
        'table'       => 'settings',
        'group'       => null,
        'writeable'   => true,
        'deferWrites' => false,
    ];

    /**
     * File handler settings.
     */
    public $file = [
        'class'       => FileHandler::class,
        'path'        => WRITEPATH . 'settings',
        'writeable'   => true,
        'deferWrites' => false,
    ];
}
