# Basic usage

This library uses what we call "dot notation" to specify the class name and the property name to use.
These are joined by a dot, hence the name.

If you have a class named `App`, and the property you are wanting to use is `siteName`, then the key
would be `App.siteName`.

### General

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
service('settings')->set('App.siteName', 'My Great Site');
```

You can delete a value from the persistent storage with the `forget()` method. Since it is removed from the storage,
it effectively resets itself back to the default value in config file, if any.

```php
service('settings')->forget('App.siteName');
```

### Contextual Settings

In addition to the default behavior describe above, `Settings` can be used to define "contextual settings".
A context may be anything you want, but common examples are a runtime environment or an authenticated user.
In order to use a context you pass it as an additional parameter to the `get()`/`set()`/`forget()` methods; if
a context setting is requested and does not exist then the general value will be used.

Contexts may be any unique string you choose, but a recommended format for supplying some consistency is to
give them a category and identifier, like `environment:production`, `group:superadmin` or `lang:en`.

An example... Say your App config includes the name of a theme to use to enhance your display. By default
your config file specifies `App.theme = 'default'`. When a user changes their theme, you do not want this to
change the theme for all visitors to the site, so you need to provide the user as the *context* for the change:

```php
$context = 'user:' . user_id();
service('settings')->set('App.theme', 'dark', $context);
```

Now when your filter is determining which theme to apply it can check for the current user as the context:

```php
$context = 'user:' . user_id();
$theme = service('settings')->get('App.theme', $context);

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

!!! Note

    Due to the shorthand nature of the helper function it cannot access contextual settings.
