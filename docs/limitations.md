# Known Limitations

The following are known limitations of the library:

1. **Immediate writes (`deferWrites => false`)**: Each setting is written to storage immediately when you call `set()` or `forget()`.
   The first operation hydrates all settings for that context (1 SELECT query), then each subsequent write performs a separate
   INSERT or UPDATE. While `DatabaseHandler` and `FileHandler` use an in-memory cache to maintain fast reads, individual write
   operations may result in multiple database queries or file writes per request. When multiple changes are known ahead of time,
   use `setMany()` or `forgetMany()` to group them explicitly and allow supported handlers to persist them more efficiently.

2. **Deferred writes (`deferWrites => true`)**: All settings are batched and written to storage at the end of the request
   (during the `post_system` event). This minimizes the number of database queries and file writes, improving performance.
   However, there are important considerations:
    - Write operations will not appear in CodeIgniter's Debug Toolbar, since the `post_system` event executes after toolbar data collection.
    - If the request terminates early (fatal error, `exit()`, etc.) before `post_system`, pending writes are lost.
    - Write failures are logged but handled silently - no exceptions are thrown back to the calling code.

3. **First-level property access only**: You can only access the first level of a config property directly. In most config classes
   this is not an issue, since properties are simple values. However, some config files (like `Database`) contain properties that
   are nested arrays. For example, you cannot directly access `$config->database['default']['hostname']` - you would need to
   get the entire `database` property and then access the nested value.
