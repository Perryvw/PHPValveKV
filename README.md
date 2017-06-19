# PHPValveKV
A parser for Valve's KeyValue serialization format, written in PHP.

The parser supports parsing from files and strings. It can deal with most of the quirks of the Valve KV language such as conditionals, un-quoted keys or values, #base includes.

Thanks to [xPaw](https://github.com/xPaw) for his help and the [ValveKeyValue](https://github.com/SteamDatabase/ValveKeyValue) project for their testcases.

## Usage
```php
require "valveKV.php";

// Create a parser instance.
$parser = new \ValveKV\ValveKV();
// Parse a KV string.
$kvFromString = $parser->parseFromString('"root"{"A" "B"}');
// Parse a KV file.
$kvFromFile = $parser->parseFromFile("myKVFile.txt");

// Parse while merging the values of duplicate keys.
$kvFromFile2 = $parser->parseFromFile("myKVFile.txt", true);
```

### Duplicate keys
The KV format can contain duplicate keys. By default, the value of duplicate keys will be transformed into a list of its different values. The functions `parseFromString` and `parseFromFile` both have a flag `mergeDuplicates` that merges the values of duplicate keys (if they are arrays) instead of adding them to a list.

## Test status
Currently the parser is covered by a large amount of test cases that all pass.
