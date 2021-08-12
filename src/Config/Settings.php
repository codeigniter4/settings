<?php

namespace Sparks\Settings\Config;

use Sparks\Settings\Handlers\DatabaseHandler;

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
     * Database handler settings.
     */
    public $database = [
        'class' => DatabaseHandler::class,
        'table' => 'settings',
        'writeable' => true,
    ];
}
