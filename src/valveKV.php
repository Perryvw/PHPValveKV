<?php
    /* ValveKV
     * Valve KeyValue (1) format recursive descent parser with 1 symbol lookahead.
     * This parser parses a kv file string to an associative array.
     * 
     * Example usage:
     * $parser = new ValveKV();
     * $kv = $parser->parseFromFule(path, [mergeDuplicates = false]);
     * $kv = $parser->parseFromString(string, [mergeDuplicates = false]);
     */
    namespace ValveKV;

    class ValveKV {

        private bool $mergeDuplicates; // This flag determines what to do with duplicate files

        private int $index;
        private string $stream;
        private int $streamlen;
        private ?string $next;
        private string $path;

        public function __construct() {
        }

        public function parseFromString(string $str, bool $mergeDuplicates = false) : array {
            $this->path = "./";
            return $this->initialise($str, $mergeDuplicates);
        }

        public function parseFromFile(string $path, bool $mergeDuplicates = false) : array {
            if (file_exists($path)) {
                $this->path = $path;
                $str = file_get_contents($path);
                if(!$str) {
                    throw new \Exception("Failed to read '".$path."'.");
                }
                return $this->initialise($str, $mergeDuplicates);
            } else {
                throw new \Exception("Could not find a file at path '".$path."'.");
            }
        }

        private function initialise(string $str, bool $mergeDuplicates) : array {
            $this->mergeDuplicates = $mergeDuplicates;
            $this->stream = $str;
            unset($str);

            // detect and convert utf-16, utf-32 and convert to utf8
            if      (substr($this->stream, 0, 2) === "\xFE\xFF")         $this->stream = mb_convert_encoding($this->stream, 'UTF-8', 'UTF-16BE');
            else if (substr($this->stream, 0, 2) === "\xFF\xFE")         $this->stream = mb_convert_encoding($this->stream, 'UTF-8', 'UTF-16LE');
            else if (substr($this->stream, 0, 4) === "\x00\x00\xFE\xFF") $this->stream = mb_convert_encoding($this->stream, 'UTF-8', 'UTF-32BE');
            else if (substr($this->stream, 0, 4) === "\xFF\xFE\x00\x00") $this->stream = mb_convert_encoding($this->stream, 'UTF-8', 'UTF-32LE');

            // Strip BOM header
            $this->stream = preg_replace('/^[\xef\xbb\xbf\xff\xfe\xfe\xff]*/', '', $this->stream);

            // Check for empty files, return empty object
            if (strlen($this->stream) == 0) {
                return [];
            }

            $this->streamlen = strlen($this->stream);
            $this->index = 0;
            $this->next = $this->stream[0];

            $this->skipWhitespace();

            return $this->parseKV();
        }

        // Parse a complete KV string.
        private function parseKV() : array {
            // Parse bases
            $bases = [];
            while ($this->next === "#") {
                $path = $this->parseBase();
                $this->skipWhitespace();
                $parser = new ValveKV();
                $bases[$path] = $parser->parseFromFile(dirname($this->path).DIRECTORY_SEPARATOR.$path);
            }

            $roots = $this->parseObject(false);

            // Check if we successfully reached the end of the file
            if ($this->next !== null) {
                throw new ParseException("Expected EOF but found (end-of-file).", $this->index);
            }

            // Get first key in $roots
            reset($roots);
            $firstRoot = key($roots);
            // Add bases to root
            foreach ($bases as $path => $base) {
                // Get first root in base
                $firstBaseRoot = reset($base);
                // Merge
                foreach ($firstBaseRoot as $key => $value) {
                    if (!isset($roots[$firstRoot][$key])) {
                        $roots[$firstRoot][$key] = $value;
                    } else {
                        throw new KeyCollisionException($key, $this->path, $this->path.$path);
                    }
                }
            }

            return $roots;
        }

        private function parseBase() : string {
            $this->nextChar("#");
            $this->nextChar("b");
            $this->nextChar("a");
            $this->nextChar("s");
            $this->nextChar("e");

            $this->skipWhitespace();
            
            return $this->parseString();
        }

        // Parse a single object.
        private function parseObject(bool $expectBrackets = true) : array {
            if ($expectBrackets === true) $this->nextChar("{");

            $properties = array();

            // Skip whitespace
            $this->skipWhitespace();

            while ($this->next !== null && $this->next !== "}") {
                // Read key, if it does not start with " read quoteless
                $key = $this->next === "\"" ? $this->parseString() : $this->parseQuotelessString();

                // Skip whitespace after key
                $this->skipWhitespace();

                // Read value
                $val = $this->parseValue();

                // Check if this is a duplicate
                if (isset($properties[$key])) {
                    // Handle duplicate according to $this->mergeDuplicates
                    if ($this->mergeDuplicates) {
                        // merge duplicates
                        $properties = array_replace_recursive($properties, [$key => $val]);
                    } else {
                        // Store list of duplicates
                        if (is_array($properties[$key]) && isset($properties[$key][0])) {
                            $properties[$key][] = $val;
                        } else {
                            $properties[$key] = [$properties[$key], $val];
                        }
                    }
                } else {
                    $properties[$key] = $val;
                }

                // Skip whitespace
                $this->skipWhitespace();

                // If next is conditional skip that too
                if ($this->next == "[") {
                    $this->ignoreConditional();
                    $this->skipWhitespace();
                }
            }

            if ($expectBrackets === true) $this->nextChar("}");

            return $properties;
        }

        /**
         * Parse a value, either a string or an object.
         *
         * @return array|string
         */
        private function parseValue() {
            if ($this->next === null) {
                throw new ParseException("Unexpected EOF (end-of-file).", $this->index);
            } else if ($this->next === "\"") {
                return $this->parseString();
            } else if ($this->next === "{") {
                return $this->parseObject();
            } else if ($this->next === "[") {
                return $this->parseBracketString();
            } else if (ctype_graph($this->next)) {
                return $this->parseQuotelessString();
            } else {
                throw new ParseException("Unexpected character '".$this->next."'.", $this->index);
            }
        }

        // Parse a string.
        private function parseString() : string {
            $this->nextChar("\"");

            // Find next quote
            $endPos = $this->index - 1;
            while ($endPos !== -1) {
                $endPos = strpos($this->stream, "\"", $endPos + 1);

                if ($endPos === false) {
                    throw new ParseException("Missing ending quote for string.", $this->index);
                }

                // Count backslashes before closing quote
                $index = 0;
                $prevChar = $this->stream[$endPos - $index - 1];
                while ($prevChar === "\\") {
                    $index++;
                    $prevChar = $this->stream[$endPos - $index - 1];
                }

                // If there is an even number of backslashes before the quote break the loop
                // Otherwise, continue
                if ($index % 2 === 0) {
                    break;
                }
            }

            //Extract string
            $str = substr($this->stream, $this->index, $endPos - $this->index);

            // Set index
            $this->index = $endPos;
            $this->next = $this->stream[$this->index];

            $this->nextChar("\"");

            return $str;
        }

        // Parse a string.
        private function parseBracketString() : string {
            $this->nextChar("[");

            $start = $this->index;

            while ($this->next !== null && $this->next !== "]") {
                $this->step();
            }

            $str = (string)substr($this->stream, $start, $this->index - $start);

            $this->nextChar("]");

            return "[".$str."]";
        }

        private function parseQuotelessString() : string {
            $start = $this->index;

            while ($this->next !== null && !ctype_space($this->next)) {
                $this->step();
            }

            return (string)substr($this->stream, $start, $this->index - $start);
        }

        // Get the next character, allows an expected value. If the next character does not
        // match the expected character throws an error.
        private function nextChar(?string $expected = null) : string {

            $current = $this->next;

            if ($current === null) {
                throw new ParseException("Unexpected EOF (end-of-file).", $this->index);
            }

            if ($expected && $current !== $expected) {
                throw new ParseException("Unexpected character '".$current."', expected '".$expected."'.", $this->index);
            }

            $this->step();

            return $current;
        }

        // Step forward through the stream
        private function step() : void {
            // Do not allow stepping from beyond the end of the stream
            if ($this->index >= $this->streamlen) {
                throw new ParseException("Unexpected EOF (end-of-file).", $this->index);
            }
            $this->index++;

            if ($this->index >= $this->streamlen) {
                $this->next = null;
            } else {
                $this->next = $this->stream[$this->index];
            }            
        }

        private function skipWhitespace() : void {
            // Ignore whitespace
            while ($this->next === " " || $this->next === "\t" || $this->next === "\r" || $this->next === "\n") {
                $this->step();
            }

            if ($this->next === "/") {
                $this->ignoreComment();
                $this->skipWhitespace();
            }
        }

        private function ignoreConditional() : void {
            $this->nextChar("[");

            $end = strpos($this->stream, "]", $this->index);

            if ($end === false) {
                throw new ParseException("Missing ending ] for conditional.", $this->index);
            }

            $this->index = $end;
            $this->next = $this->stream[$this->index];

            $this->nextChar("]");
        }

        // Advance the read index until after the single line comment
        private function ignoreComment() : void {
            $this->step();

            $end = strpos($this->stream, "\n", $this->index);

            if ($end === false) {
                $this->index = $this->streamlen-1;
                $this->next = null;
            } else {
                $this->index = $end;
                $this->next = $this->stream[$this->index];
            }
        }
    }

    class ParseException extends \Exception {
        // Redefine consructor
        public function __construct(string $message, int $index) {
            // Super
            parent::__construct($message." At index: ".$index.".");
        }
    }

    class KeyCollisionException extends \Exception {
        // Redefine consructor
        public function __construct(string $key, string $file1, string $file2) {
            // Super
            parent::__construct("Key collision on key '".$key."'"." Path 1: '".$file1."'; File 2: '".$file2."'.");
        }
    }
