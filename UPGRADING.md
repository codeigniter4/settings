# Upgrade Guide

## Version 1 to 2
***

* The namespace has been migrated from `Sparks\Settings` to `CodeIgniter\Settings`; any references will need to be updated.
* Due to the addition of contexts the `BaseHandler` abstract class was changed. Update any handlers that extend this class to include the new and changed methods.
* The main library (`Settings`) now requires a Settings config for the constructor (this is supplied by the Service); update any direct calls to the library constructor.
