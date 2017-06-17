# PHPValveKV
A parser for Valve's KeyValue serialization format, written in PHP.

The parser supports parsing from files and strings. It can deal with most of the quirks of the Valve KV language such as conditionals, un-quoted keys or values, #base includes.

Thanks to [xPaw](https://github.com/xPaw) and [ValveKeyValue](https://github.com/SteamDatabase/ValveKeyValue)

## Usage
```php
require "valveKV.php";

$parser = new \ValveKV\ValveKV();
$kvFromString = $parser->parseFromString('"root"{"A" "B"}');
$kvFromFile = $parser->parseFromFile("myKVFile.txt");
```

## Test status
Currently the parser is covered by a large amount of test cases that all pass.
