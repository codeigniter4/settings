# CodeIgniter Settings Documentation

This project provides database storage and retrieval of application settings, with a fallback to the config classes.

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

![PHP](https://img.shields.io/badge/PHP-%5E7.4-red)
![CodeIgniter](https://img.shields.io/badge/CodeIgniter-%5E4.2.3-red)

### Acknowledgements

Every open-source project depends on its contributors to be a success. The following users have
contributed in one manner or another in making this project:

<a href="https://github.com/codeigniter4/settings/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=codeigniter4/settings" alt="Contributors">
</a>

Made with [contrib.rocks](https://contrib.rocks).
