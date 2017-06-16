<?php
	namespace ValveKV;
	require "../src/valveKV.php";

	// Specify tests to run
	$tests = [
		"basic.kv" => ["A" => ["B" => "C"]],
		"basic2.kv" => ["A" => ["B" => "C", "D" => "E"]],
		"nested.kv"	=> ["A" => ["B" => ["C" => ["D" => []]]]],
		"comments.kv"	=> ["A" => ["B" => ["C" => ["D" => []]]]],
		"stringescapes.kv"	=> ["Test" => ["A" => "3\\\"5", "B" => "A\\\\", "C" => "\\\\"]],
		"quoteless.kv" => ["TestDocument" => ["QuotedChild" => "edge\\ncase\\\"haha\\\\\"", "UnquotedChild" => ["Key1" => "Value1", "Key2" => "Value2", "Key3" => "Value3"]]],
		"conditional.kv" => ["test case" => ["operating system" => "something else", "platform" => "windows", "ui type" => "Xbox 360", "ui size" => "large"]]
	];

	// Run tests
	$num = 0;
	$pass = 0;
	foreach ($tests as $file => $expected) {
		$num++;

		// Create new parser
		$parser = new ValveKV();

		// Parse the file
		try {
			$result = $parser->parseFromFile("testcases/".$file);
			if ($expected == $result) {
				// Pass!
				$pass++;
			} else {
				// Fail - wrong value
				echo "<font color='red'>Testcase ".$file." failed.</font><br>";
				print_r($result);
				print_r($expected);
			}
		} catch (Exception $e) {
			// Fail - exception
			echo "<font color='red'>Testcase ".$file." failed.</font><br>";
		}
	}

	Echo "<font color='".($num == $pass ? "green" : "orange")."'>Passed (".$pass."/".$num.") tests.</font>";