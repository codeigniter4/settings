# Upgrade Guide

## Version 2.2.0 to 2.3.0

* The minimum required version of CodeIgniter is now `4.3` and PHP `8.2`.
* All source files now declare `strict_types=1`.
* `BaseHandler` methods `set()`, `forget()`, `flush()`, and `persistPendingProperties()` now have `void` return types. Any custom handler extending `BaseHandler` must update its method signatures to include `: void`.

## Version 1 to 2
***

* The namespace has been migrated from `Sparks\Settings` to `CodeIgniter\Settings`; any references will need to be updated.
* Due to the addition of contexts the `BaseHandler` abstract class was changed. Update any handlers that extend this class to include the new and changed methods.
* The main library (`Settings`) now requires a Settings config for the constructor (this is supplied by the Service); update any direct calls to the library constructor.
