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

### Deferred writes

Handlers like `database` and `file` support deferred writes. When `deferWrites` is enabled, multiple `set()` and `forget()` calls
are batched and persisted efficiently at the end of the request during the `post_system` event. This minimizes the number of
database queries or file I/O operations, improving performance for write-heavy operations.

!!! note
    This is separate from the explicit `setMany()` and `forgetMany()` APIs. Batch APIs allow callers to group multiple settings
    in one method call, while deferred writes decide whether writes are persisted immediately or at the end of the request.
    The two features are independent and can be combined.

### Multiple handlers

Example:

```php
public $handlers = ['file', 'database'];
```

This configuration will:

1. Check the file handler first
2. If not found, check the database handler
3. If not found in any handler, return the default value from the config file

### Writeable Handlers

Only handlers marked as `writeable => true` will be used when calling `set()`, `forget()`, or `flush()` methods.

---

## DatabaseHandler

This handler stores settings in a database table and is production-ready for high-traffic applications.

**Available options:**

* `class` - The handler class. Default: `DatabaseHandler::class`
* `table` - The database table name for storing settings. Default: `'settings'`
* `group` - The database connection group to use. Default: `null` (uses default connection)
* `writeable` - Whether this handler supports write operations. Default: `true`
* `deferWrites` - Whether to defer writes until the end of request (`post_system` event). Default: `false`

Example:

```php
public $database = [
    'class'        => DatabaseHandler::class,
    'table'        => 'settings',
    'group'        => null,
    'writeable'    => true,
    'deferWrites'  => false,
];
```

!!! note
    You need to run migrations to create the settings table: `php spark migrate -n CodeIgniter\\Settings`

**Deferred Writes**

When `deferWrites` is enabled, multiple `set()` or `forget()` calls are batched and persisted in a single transaction at the end of the request. This significantly reduces database queries:

```php
// With deferWrites = false: 1 SELECT (hydrates all settings for context) + 3 separate INSERT/UPDATE queries
$settings->set('Example.prop1', 'value1');
$settings->set('Example.prop2', 'value2');
$settings->set('Example.prop3', 'value3');

// With deferWrites = true: 1 SELECT + 1 INSERT batch + 1 UPDATE batch (all batched at end of request)
```

The deferred approach is especially beneficial when updating existing records or performing many operations in a single request.

For explicit batches, use `setMany()` or `forgetMany()`:

```php
$settings->setMany([
    'Example.prop1' => 'value1',
    'Example.prop2' => 'value2',
    'Example.prop3' => 'value3',
]);
```

---

## FileHandler

This handler stores settings as PHP files and is optimized for production use with built-in race condition protection.

**Available options:**

* `class` - The handler class. Default: `FileHandler::class`
* `path` - The directory path where settings files are stored. Default: `WRITEPATH . 'settings'`
* `writeable` - Whether this handler supports write operations. Default: `true`
* `deferWrites` - Whether to defer writes until the end of request (`post_system` event). Default: `false`

Example:

```php
public $file = [
    'class'        => FileHandler::class,
    'path'         => WRITEPATH . 'settings',
    'writeable'    => true,
    'deferWrites'  => false,
];
```

!!! note
    The `FileHandler` automatically creates the directory if it doesn't exist and checks write permissions on instantiation.

**Deferred Writes**

When `deferWrites` is enabled, multiple `set()` or `forget()` calls to the same class are batched into a single file write at the end of the request. This significantly reduces I/O operations:

```php
// With deferWrites = false: 1 file read (hydrates all settings for class) + 3 separate file writes
$settings->set('Example.prop1', 'value1');
$settings->set('Example.prop2', 'value2');
$settings->set('Example.prop3', 'value3');

// With deferWrites = true: 1 file read + 1 file write (all changes batched at end of request)
```

The deferred approach is especially beneficial when updating multiple properties in the same class.

For explicit batches, use `setMany()` or `forgetMany()`:

```php
$settings->setMany([
    'Example.prop1' => 'value1',
    'Example.prop2' => 'value2',
    'Example.prop3' => 'value3',
]);
```

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
