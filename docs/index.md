# CodeIgniter Settings Documentation

This project provides a simple interface that you can use in place of calling `config()` to allow you to read and store
config values in the database. If the value has not been updated and saved in the database then the original value
from the config file will be used.

This allows you to save your application's default state as values in config files, all stored in version control,
and still allows your users to override those settings once the site is live.

**How it works:**

Set the value:

```php
service('settings')->set('App.siteName', 'Example');
```

Get the value:

```php
service('settings')->get('App.siteName');
```

Forget the value:

```php
service('settings')->forget('App.siteName');
```

### Requirements

![PHP](https://img.shields.io/badge/PHP-%5E8.1-red)
![CodeIgniter](https://img.shields.io/badge/CodeIgniter-%5E4.2.3-red)

### Acknowledgements

Every open-source project depends on its contributors to be a success. The following users have
contributed in one manner or another in making this project:

<a href="https://github.com/codeigniter4/settings/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=codeigniter4/settings" alt="Contributors">
</a>

Made with [contrib.rocks](https://contrib.rocks).
