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

        public function __construct($str) {
            $this->index = 0;
            $this->stream = $str.PHP_EOL;
            $this->streamlen = strlen($str);
            $this->next = $this->stream[$this->index];

            $this->line = 1;
            $this->lineStart = 0;
        }

        // Parse a complete KV string.
        public function parse() {
            while ($this->next !== "{") {
                $this->nextChar();
            }

            $obj = $this->parseObject();

            $this->result = $obj;

            return $obj;
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
                throw new Exception("Unexpected EOF (end-of-file) at line ".$this->line.", column ".($this->index - $this->lineStart).".");
            }

            if ($expected && $current != $expected) {
                throw new Exception("Unexpected character '".$current."', expected '".$expected."' at line ".$this->line.", column ".($this->index - $this->lineStart).".");
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
                            throw new Exception("Malformed comment found.");
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
?>
