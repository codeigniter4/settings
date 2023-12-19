# CodeIgniter Settings

This project provides database storage and retrieval of application settings, with a fallback to the
config classes for CodeIgniter 4 framework.

[![](https://github.com/codeigniter4/settings/workflows/PHPUnit/badge.svg)](https://github.com/codeigniter4/settings/actions/workflows/test.yml)
[![](https://github.com/codeigniter4/settings/workflows/PHPStan/badge.svg)](https://github.com/codeigniter4/settings/actions/workflows/analyze.yml)
[![](https://github.com/codeigniter4/settings/workflows/Deptrac/badge.svg)](https://github.com/codeigniter4/settings/actions/workflows/inspect.yml)
[![Coverage Status](https://coveralls.io/repos/github/codeigniter4/settings/badge.svg?branch=develop)](https://coveralls.io/github/codeigniter4/settings?branch=develop)

![PHP](https://img.shields.io/badge/PHP-%5E7.4-blue)
![CodeIgniter](https://img.shields.io/badge/CodeIgniter-%5E4.2.3-blue)
![License](https://img.shields.io/badge/License-MIT-blue)

## Installation

    composer require codeigniter4/settings

Migrate your database:

    php spark migrate --all

## Basic usage

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

## Docs

Read the full documentation: https://settings.codeigniter.com

## Contributing

We accept and encourage contributions from the community in any shape. It doesn't matter
whether you can code, write documentation, or help find bugs, all contributions are welcome.
See the [CONTRIBUTING.md](CONTRIBUTING.md) file for details.

