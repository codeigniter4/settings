# Installation

- [Composer Installation](#composer-installation)
- [Manual Installation](#manual-installation)
- [Database Migration](#database-migration)

## Composer Installation

The only thing you have to do is to run this command, and you're ready to go.

    composer require codeigniter4/settings

## Manual Installation

In the example below we will assume, that files from this project will be located in `app/ThirdParty/settings` directory.

Download this project and then enable it by editing the `app/Config/Autoload.php` file and adding the `CodeIgniter\Settings` namespace to the `$psr4` array, like in the below example:

```php
<?php

// ...

public $psr4 = [
    APP_NAMESPACE => APPPATH, // For custom app namespace
    'Config'      => APPPATH . 'Config',
    'CodeIgniter\Settings' => APPPATH . 'ThirdParty/settings/src',
];

// ...
```

## Database Migration

Regardless of which installation method you chose, we also need to migrate the database to add new tables.

You can do this with the following command:

    php spark migrate --all

The above command will also migrate all other packages. If you don't want to do that you can run migrate with the `-n` flag and specify the project namespace:

1. **For Windows:**
    ```console
    php spark migrate -n CodeIgniter\Settings
    ```
2. **For Unix:**
    ```console
    php spark migrate -n CodeIgniter\\Settings
    ```
