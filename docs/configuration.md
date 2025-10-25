# Configuration

To make changes to the config file, you need to have your own copy in `app/Config/Settings.php`. The easiest way to do it is by using the publish command.

When you run:

    php spark settings:publish

You will get your copy ready for modifications.

---

## Handlers

An array of handler aliases to use for storing and retrieving settings. Handlers are checked in order, with the first handler that has a value returning it.

**Type:** `array`

**Default:** `['database']`

**Available handlers:** `database`, `file`, `array`

Example:

```php
public $handlers = ['database'];
```
### Multiple handlers

When multiple handlers are configured, they are checked in the order specified in $handlers. The first handler that has a value for the requested setting will return it.

Example with fallback:

```php
public $handlers = ['file', 'database'];
```
This configuration will:

1. Check the file handler first
2. If not found, check the database handler
3. If not found in any handler, return the default value from the config file

### Writeable Handlers

Only handlers marked as `writeable => true` will be used when calling `set()`, `forget()`, or `flush()` methods.

## DatabaseHandler

This handler stores settings in a database table and is production-ready for high-traffic applications.

**Available options:**

* `class` - The handler class. Default: `DatabaseHandler::class`
* `table` - The database table name for storing settings. Default: `'settings'`
* `group` - The database connection group to use. Default: `null` (uses default connection)
* `writeable` - Whether this handler supports write operations. Default: `true`

Example:

```php
public $database = [
    'class'     => DatabaseHandler::class,
    'table'     => 'settings',
    'group'     => null,
    'writeable' => true,
];
```

!!! note
    You need to run migrations to create the settings table: `php spark migrate -n CodeIgniter\\Settings`

---

## FileHandler

This handler stores settings as PHP files and is optimized for production use with built-in race condition protection.

**Available options:**

* `class` - The handler class. Default: `FileHandler::class`
* `path` - The directory path where settings files are stored. Default: `WRITEPATH . 'settings'`
* `writeable` - Whether this handler supports write operations. Default: `true`

Example:

```php
public $file = [
    'class'     => FileHandler::class,
    'path'      => WRITEPATH . 'settings',
    'writeable' => true,
];
```

!!! note
    The `FileHandler` automatically creates the directory if it doesn't exist and checks write permissions on instantiation.

---

## ArrayHandler

This handler stores settings in memory only and is primarily useful for testing or as a parent class for other handlers.

**Available options:**

* `class` - The handler class. Default: `ArrayHandler::class`
* `writeable` - Whether this handler supports write operations. Default: `true`

Example:

```php
public $array = [
    'class'     => ArrayHandler::class,
    'writeable' => true,
];
```

!!! note
    `ArrayHandler` does not persist data between requests. It's mainly used for testing or extended by other handlers.
