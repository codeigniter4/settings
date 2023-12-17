# Known Limitations

The following are known limitations of the library:

1. You can currently only store a single setting at a time. While the `DatabaseHandler` uses a local cache to
   keep performance as high as possible for reads, writes must be done one at a time.
2. You can only access the first level within a property directly. In most config classes this is a non-issue,
   since the properties are simple values. Some config files, like the `database` file, contain properties that
   are arrays.
