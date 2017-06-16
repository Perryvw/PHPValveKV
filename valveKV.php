<?php
    /* ValveKV
     * Valve KeyValue (1) format recursive descent parser with 1 symbol lookahead.
     * This parser parses a kv file string to an associative array.
     * 
     * Example usage:
     * $parser = new ValveKV($kvString);
     * $kv = $parser->parse();
     *
     * The result is stored in $parser->result;
     */
    namespace ValveKV;

    class ValveKV {

        public  $result;

        private $index;
        private $stream;
        private $streamlen;
        private $next;

        private $line;
        private $lineStart;

        public function __construct() {
        }

        public function parseFromString($str) {
            $this->fullPath = "./";
            $this->path = "./";
            return $this->initialise($str);
        }

        public function parseFromFile($path) {
            if (file_exists($path)) {
                // Get path for bases
                $this->fullPath = $path;
                $path = str_replace("\\", "/", $path);
                if (strpos($path, "/") !== false) {
                    $this->path = substr($path, 0, strrpos($path, "/") + 1);
                } else {
                    $this->path = "./";
                }
                $str = file_get_contents($path);
                return $this->initialise($str);
            } else {
                throw new Exception("Could not find a file at path '".$path."'.");
            }
        }

        private function initialise($str) {
            // detect and convert utf-16, utf-32 and convert to utf8
            if      (substr($str, 0, 2) === "\xFE\xFF")         $str = mb_convert_encoding($str, 'UTF-8', 'UTF-16BE');
            else if (substr($str, 0, 2) === "\xFF\xFE")         $str = mb_convert_encoding($str, 'UTF-8', 'UTF-16LE');
            else if (substr($str, 0, 4) === "\x00\x00\xFE\xFF") $str = mb_convert_encoding($str, 'UTF-8', 'UTF-32BE');
            else if (substr($str, 0, 4) === "\xFF\xFE\x00\x00") $str = mb_convert_encoding($str, 'UTF-8', 'UTF-32LE');
            
            // Strip header
            $str = preg_replace('/^[\xef\xbb\xbf\xff\xfe\xfe\xff]*/', '', $str);

            $this->index = -1;
            $this->stream = $str.PHP_EOL;
            $this->streamlen = strlen($str);

            unset($str);

            // Get first char
            $this->nextChar();

            // Keep track of line for error messages
            $this->line = 1;
            $this->lineStart = 0;

            return $this->parseKV();
        }

        // Parse a complete KV string.
        private function parseKV() {
            // Parse bases
            $bases = [];
            while ($this->next === "#") {
                $path = $this->parseBase();
                $parser = new ValveKV();
                $bases[$path] = $parser->parseFromFile($this->path.$path);
            }

            $root = [];

            // Check if this file contains a root object
            if ($this->index < $this->streamlen) {
                // Parse root name
                $name = $this->next === "\"" ? $this->parseString() : $this->parseQuotelessString();

                // Skip whitespace
                $this->skipWhitespace();

                // Parse root
                $root[$name] = $this->parseObject();
            }

            // Add bases to root
            foreach ($bases as $path => $base) {
                foreach ($base as $key => $value) {
                    if (!array_key_exists($key, $root)) {
                        $root[$key] = $value;
                    } else {
                        throw new KeyCollisionException($key, $this->fullPath, $this->path.$path);
                    }
                }
            }

            $this->result = $root;

            return $root;
        }

        private function parseBase() {
            $this->nextChar("#");
            $this->nextChar("b");
            $this->nextChar("a");
            $this->nextChar("s");
            $this->nextChar("e");
            
            return $this->parseString();
        }

        // Parse a single object.
        private function parseObject() {
            $this->nextChar("{");

            $properties = array();

            // Skip whitespace
            $this->skipWhitespace();

            while ($this->next != "}") {
                // Read key, if it does not start with " read quoteless
                $key = $this->next === "\"" ? $this->parseString() : $this->parseQuotelessString();

                // Skip whitespace after key
                $this->skipWhitespace();

                // Read value
                $val = $this->parseValue();
                if (isset($properties[$key])) {
                    $properties = array_replace_recursive($properties, [$key => $val]);
                } else {
                    $properties[$key] = $val;
                }

                // Skip whitespace
                $this->skipWhitespace();
            }

            $this->nextChar("}");

            return $properties;
        }

        // Parse a value, either a string or an object.
        private function parseValue() {
            if ($this->next === "\"") {
                return $this->parseString();
            }
            else if ($this->next === "{") {
                return $this->parseObject();
            } else {
                return $this->parseQuotelessString();
            }
        }

        // Parse a string.
        private function parseString() {
            $this->nextChar("\"");

            // Find next quote
            $endPos = $this->index - 1;
            do {
                $endPos = strpos($this->stream, "\"", $endPos + 1);
            } while ($this->stream[$endPos-1] === "\\");

            //Extract string
            $str = substr($this->stream, $this->index, $endPos - $this->index);

            // Set index
            $this->index = $endPos;
            $this->next = $this->stream[$this->index];

            $this->line += substr_count($str, "\n");

            $this->nextChar("\"");

            return $str;
        }

        private function parseQuotelessString() {
            $start = $this->index;

            while ($this->next !== " " && $this->next !== "\t" && $this->next !== "\r" && $this->next !== "\n") {

                $this->index++;
                $this->next = $this->stream[$this->index];
            }

            return substr($this->stream, $start, $this->index - $start);
        }

        // Get the next character, allows an expected value. If the next character does not
        // match the expected character throws an error. Ignores whitespace and comments by default
        // the $ignore parameter can be set to false to read comments and whitespace (for example for
        // reading inside strings).
        private function nextChar($expected = null) {

            $current = $this->next;

            if ($this->index === $this->streamlen) {
                throw new ParseException("Unexpected EOF (end-of-file).", $this->line, $this->index - $this->lineStart + 1);
            }

            if ($expected && $current != $expected) {
                throw new ParseException("Unexpected character '".$current."', expected '".$expected.".", $this->line, $this->index - $this->lineStart + 1);
            }

            $this->index++;
            $this->next = $this->stream[$this->index];

            return $current;
        }

        private function skipWhitespace() {
            // Read next character
            $c = $this->stream[$this->index];

            // Ignore whitespace
            while (($c === " " || $c === "\t" || $c === "\r" || $c === "\n" || $c === "/" || $c == "[") && $this->index < $this->streamlen - 1) {
                if ($c === "/") {
                    // Look ahead one more
                    $c2 = $this->stream[$this->index + 1];
                    // Although Valve uses the double-slash convention, the KV spec allows for single-slash comments.
                    if ($c2 === "*") {
                        $this->ignoreMLComment();
                    } else {
                        $this->ignoreSLComment();
                    }
                } else if ($c == "[") {
                    $this->next = $c;
                    $this->ignoreConditional();
                } else if ($c === "\n") {
                    // Increase line count
                    $this->line++;
                    $this->lineStart = $this->index;
                }

                // Increment position
                $this->index++;
                $c = $this->stream[$this->index];
            }

            $this->next = $this->stream[$this->index];
        }

        private function ignoreConditional() {
            $this->nextChar("[");

            $end = strpos($this->stream, "]", $this->index);

            $this->index = $end;
            $this->next = $this->stream[$this->index];

            $this->nextChar("]");
        }

        // Advance the read index until after the single line comment
        private function ignoreSLComment() {
            $this->index++;
            $c = $this->stream[$this->index];

            while($c !== "\n") {
                $this->index++;
                $c = $this->stream[$this->index];
            }
            $this->line++;
            $this->lineStart = $this->index;
        }

        // Advance read index until after the multi-line comment
        private function ignoreMLComment() {
            $this->index++;
            $c = $this->stream[$this->index];

            while (true) {
                while($c !== "*") {
                    $this->index++;
                    $c = $this->stream[$this->index];
                }

                $this->index++;
                $c = $this->stream[$this->index];

                if ($c === "/") {
                    $this->index++;
                    break;
                }
            }
        }
    }

    class ParseException extends \Exception {
        // Redefine consructor
        public function __construct($message, $line, $column) {
            // Super
            parent::__construct($message." Line: ".$line."; Column: ".$column.".");
        }
    }

    class KeyCollisionException extends \Exception {
        // Redefine consructor
        public function __construct($key, $file1, $file2) {
            // Super
            parent::__construct("Key collision on key '".$key."'"." Path 1: '".$file1."'; File 2: '".$file2."'.");
        }
    }