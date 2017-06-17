<?php
	namespace ValveKV;
	require "../src/valveKV.php";

	define("PRINT_ERRORS", true);

	// Basic tests to run
	$tests = [
		"basic.kv" => ["A" => ["B" => "C"]],
		"basic2.kv" => ["A" => ["B" => "C", "D" => "E"]],
		"nested.kv"	=> ["A" => ["B" => ["C" => ["D" => []]]]],
		"comments.kv"	=> ["A" => ["B" => ["C" => ["D" => []]]]],
		"stringescapes.kv"	=> ["Test" => ["A" => "3\\\"5", "B" => "A\\\\", "C" => "\\\\"]],
		"quoteless.kv" => ["TestDocument" => ["QuotedChild" => "edge\\ncase\\\"haha\\\\\"", "UnquotedChild" => ["Key1" => "Value1", "Key2" => "Value2", "Key3" => "Value3"]]],
		"conditional.kv" => ["test case" => ["operating system" => "something else", "platform" => "windows", "ui type" => "Xbox 360", "ui size" => "large"]],
		"base.kv" => ["root" => ["rootProp" => "A", "included1" => "B", "included2" => ["C" => "D"]]],
		"multipleroots.kv" => ["root1" => ["A" => "B"], "root2" => ["C" => "D"]]
	];

	// Run tests
	$num = count($tests);
	$pass = 0;
	echo "Running functionality tests...<br>";
	foreach ($tests as $file => $expected) {
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
				if (PRINT_ERRORS) {
					echo "Result:<br>";
					print_r($result);
					echo "Expected:<br>";
					print_r($expected);
				}
				echo "<font color='red'>Testcase ".$file." failed.</font><br>";
			}
		} catch (\Exception $e) {
			// Fail - exception
			if (PRINT_ERRORS) echo $e;
			echo "<font color='red'>Testcase ".$file." failed.</font><br>";
		}
	}

	echo "<font color='".($num == $pass ? "green" : "orange")."'>Passed (".$pass."/".$num.") tests.</font><br><br>";

	// Robustness tests
	$tests = [
		"robustness/nonexistingfile.kv" => "Exception",
		"robustness/eof.kv" => "ValveKV\ParseException",
		"robustness/badescaping.kv" => "ValveKV\ParseException",
		"robustness/unclosedstring.kv" => "ValveKV\ParseException",
		"robustness/brackets.kv" => "ValveKV\ParseException",
		"robustness/brackets2.kv" => "ValveKV\ParseException",
		"robustness/keycollision.kv" => "ValveKV\KeyCollisionException",
	];

	$num = count($tests);
	$pass = 0;
	echo "Running robustness tests...<br>";
	foreach ($tests as $file => $expected) {
		// Create new parser
		$parser = new ValveKV();

		// Try to parse the file
		try  {
			$result = $parser->parseFromFile("testcases/".$file);

			// Successful parse -> we were expecting an error!
			if (PRINT_ERRORS) {
				echo "Did not encounter expected error: ".$expected;
				print_r($result);
			}

			echo "<font color='red'>Testcase ".$file." failed.</font><br>";
		} catch (\Exception $e) {
			// Check if correct error was thrown
			if (get_class($e) === $expected) {
				$pass++;
			} else {
				// Wrong error thrown
				if (PRINT_ERRORS) {
					echo "Expected an exception of type: ".$expected."<br>";
					echo "Encountered: ".get_class($e)."<br>";
					print_r($e);
				}
				echo "<font color='red'>Testcase ".$file." failed.</font><br>";
			}
		}
	}

	echo "<font color='".($num == $pass ? "green" : "orange")."'>Passed (".$pass."/".$num.") tests.</font><br><br>";

	// Test if benchmark files successfully parse
	$tests = [
		"benchmark/dota_english.txt",
		"benchmark/weapon_ak47.txt",
		"benchmark/items.txt",
	];

	$num = count($tests);
	$pass = 0;

	echo "Running benchmark tests...<br>";
	foreach ($tests as $file) {
		// Create new parser
		$parser = new ValveKV();
		// Try to parse
		try {
			$parser->parseFromFile("testcases/".$file);
			$pass++;
		} catch(\Exception $e) {
			if (PRINT_ERRORS) {
				print_r($e);
			}
			echo "<font color='red'>Testcase ".$file." failed.</font><br>";
		}
	}

	echo "<font color='".($num == $pass ? "green" : "orange")."'>Passed (".$pass."/".$num.") tests.</font><br><br>";