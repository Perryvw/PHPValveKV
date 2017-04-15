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
    class ValveKV {

        const IGNORE = " \t\r\n";

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
            $this->index = -1;
            $this->stream = $str.PHP_EOL;
            $this->streamlen = strlen($str);

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
            while ($this->next == "#") {
                $path = $this->parseBase();
                $parser = new ValveKV();
                $bases[$path] = $parser->parseFromFile($this->path.$path);
            }

            $root = [];

            // Check if this file contains a root object
            if ($this->index < $this->streamlen) {
                // Parse root name
                $name = $this->parseString();

                // Parse root
                $root = $this->parseObject();
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

            while ($this->next != "}") {
                $key = $this->parseString();
                $val = $this->parseValue();

                $properties[$key] = $val;
            }

            $this->nextChar("}");

            return $properties;
        }

        // Parse a value, either a string or an object.
        private function parseValue() {
            if ($this->next == "\"") {
                return $this->parseString();
            }
            else if ($this->next == "{") {
                return $this->parseObject();
            }
        }

        // Parse a string.
        private function parseString() {
            $this->nextChar("\"");

            $str = "";
            while ($this->next != "\"") {
                $str .= $this->nextChar(null, false);
            }

            $this->nextChar("\"");

            return $str;
        }

        // Get the next character, allows an expected value. If the next character does not
        // match the expected character throws an error. Ignores whitespace and comments by default
        // the $ignore parameter can be set to false to read comments and whitespace (for example for
        // reading inside strings).
        private function nextChar($expected = null, $ignore = true) {

            $current = $this->next;

            if ($this->index == $this->streamlen) {
                throw new ParseException("Unexpected EOF (end-of-file).", $this->line, $this->index - $this->lineStart + 1);
            }

            if ($expected && $current != $expected) {
                throw new ParseException("Unexpected character '".$current."', expected '".$expected.".", $this->line, $this->index - $this->lineStart + 1);
            }

            $this->index++;
            $c = $this->stream[$this->index];

            if ($ignore) {
                // Ignore whitespace
                while ((strpos(self::IGNORE, $c) !== false || $c == "/") && $this->index < $this->streamlen - 1) {
                    if ($c == "/") {
                        $c2 = $this->stream[$this->index + 1];
                        if ($c2 == "/") {
                            $this->ignoreSLComment();
                        } else if ($c2 == "*") {
                            $this->ignoreMLComment();
                        } else {
                            throw new ParseException("Malformed comment found.", $this->line, $this->index - $this->lineStart + 1);
                        }
                        $this->index++;
                        $c = $this->stream[$this->index];
                    } else {
                        if ($c == "\n") {
                            $this->line++;
                            $this->lineStart = $this->index;
                        }
                        $this->index++;
                        $c = $this->stream[$this->index];
                    }
                }
            }

            $this->next = $this->stream[$this->index];

            return $current;
        }

        // Advance the read index until after the single line comment
        private function ignoreSLComment() {
            $this->index++;
            $c = $this->stream[$this->index];

            while($c !== "\n") {
                $this->index++;
                $c = $this->stream[$this->index];
            }

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

                if ($c == "/") {
                    $this->index++;
                    break;
                }
            }

        }
    }

    class ParseException extends Exception {
        // Redefine consructor
        public function __construct($message, $line, $column) {
            // Super
            parent::__construct($message." Line: ".$line."; Column: ".$column.".");
        }
    }

    class KeyCollisionException extends Exception {
        // Redefine consructor
        public function __construct($key, $file1, $file2) {
            // Super
            parent::__construct("Key collision on key '".$key."'"." Path 1: '".$file1."'; File 2: '".$file2."'.");
        }
    }
