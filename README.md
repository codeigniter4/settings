# CodeIgniter 4 Settings

Provides database storage and retrieval of application settings, with a fallback to the 
config classes.

[![](https://github.com/codeigniter4/settings/workflows/PHPUnit/badge.svg)](https://github.com/codeigniter4/settings/actions/workflows/test.yml)
[![](https://github.com/codeigniter4/settings/workflows/PHPStan/badge.svg)](https://github.com/codeigniter4/settings/actions/workflows/analyze.yml)
[![](https://github.com/codeigniter4/settings/workflows/Deptrac/badge.svg)](https://github.com/codeigniter4/settings/actions/workflows/inspect.yml)
[![Coverage Status](https://coveralls.io/repos/github/codeigniter4/settings/badge.svg?branch=develop)](https://coveralls.io/github/codeigniter4/settings?branch=develop)

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

## dot Notation

This library uses what we call "dot notation" to specify the class name and the property name to use.
These are joined by a dot, hence the name. 

If you have a class named `App`, and the property you are wanting to use is `siteName`, then the key
would be `App.siteName`.

## Usage

To retrieve a config value use the `settings` service. 

```php
// The same as config('App')->siteName;
$siteName = service('settings')->get('App.siteName');
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
service('setting')->set('App.siteName', 'My Great Site');
```

You can delete a value from the persistent storage with the `forget()` method. Since it is removed from the storage,
it effectively resets itself back to the default value in config file, if any.

```php
service('setting')->forget('App.siteName')
```

### Contextual Settings

In addition to the default behavior describe above, `Settings` can can be used to define "contextual settings".
A context may be anything you want, but common examples are a runtime environment or an authenticated user.
In order to use a context you pass it as an additional parameter to the `get()`/`set()`/`forget()` methods; if
a context setting is requested and does not exist then the general value will be used.

Contexts may be any unique string you choose, but a recommended format for supplying some consistency is to
give them a category and identifier, like `environment:production` or `group:42`.

An example... Say your App config includes the name of a theme to use to enhance your display. By default
your config file specifies `App.theme = 'default'`. When a user changes their theme, you do not want this to
change the theme for all visitors to the site, so you need to provide the user as the *context* for the change:

```php
$context = 'user:' . user_id();
service('setting')->set('App.theme', 'dark', $context);
```

Now when your filter is determining which theme to apply it can check for the current user as the context:

```php
$context = 'user:' . user_id();
$theme = service('setting')->get('App.theme', $context);

// or using the helper
setting()->get('App.theme', $context);
```

Contexts are a cascading check, so if a context does not match a value it will fall back on general,
i.e. `service('setting')->get('App.theme')`. Return value priority is as follows:
"Setting with a context > Setting without context > Config value > null".

### Using the Helper

The helper provides a shortcut to the using the service. It must first be loaded using the `helper()` method
or telling your BaseController to always load it.

```php
helper('setting');

$name = setting('App.siteName');
// Store a value
setting('App.siteName', 'My Great Site');

// Using the service through the helper
$name = setting()->get('App.siteName');
setting()->set('App.siteName', 'My Great Site');

// Forgetting a value
setting()->forget('App.siteName');
```

> Note: Due to the shorthand nature of the helper function it cannot access contextual settings.

## Known Limitations

The following are known limitations of the library:

1. You can currently only store a single setting at a time. While the `DatabaseHandler` uses a local cache to
keep performance as high as possible for reads, writes must be done one at a time. 
2. You can only access the first level within a property directly. In most config classes this is a non-issue, 
since the properties are simple values. Some config files, like the `database` file, contain properties that
are arrays.
