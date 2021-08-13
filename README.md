# CodeIgniter 4 Settings

Provides database storage and retrieval of application settings, with a fallback to the 
config classes.

## Quick Start

1. Install with Composer: `> composer require codeigniter4/settings`
2. Create a new migration and copy the provided class from below into it.

`Settings` provides a simple interface that you can use in place of calling `config()` to allow you to read and store
config values in the database. If the value has not been updated and saved in the database then the original value
from the config file will be used.

This allows you to save your application's default state as values in config files, all stored in version control,
and still allows your users to override those settings once the site is live. 

## Installation

Install easily via Composer to take advantage of CodeIgniter 4's autoloading capabilities
and always be up-to-date:
* `> composer require codeigniter4/settings`

Or, install manually by downloading the source files and adding the directory to
`app/Config/Autoload.php`.

## Setup

In order to store the settings in the database, you can run the provided migration: 

```
> php spark migrate --all
```

This will also migrate all other packages. If you don't want to do that you can copy the file
from `vendor/codeigniter4/settings/src/Database/Migrations/2021-07-04-041948_CreateSettingsTable.php`
into `app/Database/Migrations`, and migrate without the `--all` flag. 

## Usage

To retrieve a config value use the `settings` service. 

```php
// The same as config('App')->siteName;
$siteName = service('settings')->get('App', 'siteName');
```

In this case we used the short class name, `App`, which the `config()` method automatically locates within the 
`app/Config` directory. If it was from a module, it would be found there. Either way, the fully qualified name
is automatically detected by the Settings class to keep values separated from config files that may share the 
same name but different namespaces. If no config file match is found, the short name will be used, so it can
be used to store settings without config files. 

To save a value, call the `set()` method on the settings class, providing the class name, the key, and the value.
Note that boolean `true`/`false` will be converted to strings `:true` and `:false` when stored in the database, but
will be converted back into a boolean when retrieved. Arrays and objects are serialized when saved, and unserialized
when retrieved. 

```php
service('setting')->set('App', 'siteName', 'My Great Site');
```

### Using the Helper

The helper provides a shortcut to the using the service. It must first be loaded using the `helper()` method
or telling your BaseController to always load it.

```php
helper('setting');
$name = setting('App', 'siteName');
$name = setting()->get('App', 'siteName');
$setting()->set('App', 'siteName', 'My Great Site');
```
