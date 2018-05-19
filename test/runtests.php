<?php
	namespace ValveKV;
	require "../src/valveKV.php";

	define("PRINT_ERRORS", true);

	$totalNum = 0;
	$totalPass = 0;

	// Basic tests to run
	$tests = [
		'basic.kv' => ['A' => ['B' => 'C']],
		'basic2.kv' => ['A' => ['B' => 'C', 'D' => 'E']],
		'nested.kv'	=> ['A' => ['B' => ['C' => ['D' => []]]]],
		'comments.kv'	=> ['A' => ['B' => ['C' => ['D' => []]]]],
		'stringescapes.kv'	=> ['Test' => ['A' => '3\\"5', 'B' => 'A\\\\', 'C' => '\\\\']],
		'quoteless.kv' => ['TestDocument' => ['QuotedChild' => 'edge\\ncase\\"haha\\\\"', 'UnquotedChild' => ['Key1' => 'Value1', 'Key2' => 'Value2', 'Key3' => 'Value3']]],
		'quotelessBracket.kv' => ['TestDocument' => ['$envmaptint' => '[ .4 .4 .4]', '$envmapsaturation' => '[.5 .5 .5]']],
		'quotelessSpecial.kv' => ['TestDocument' => ['$QuotedChild' => 'edge\\ncase\\"haha\\\\"', '#UnquotedChild' => ['&Key1' => '$Value1', '!Key2' => '@Value2', '%Key3' => 'Value3']]],
		'conditional.kv' => ['test case' => ['operating system' => ['windows 32-bit', 'something else'], 'platform' => 'windows', 'ui type' => ['Widescreen Xbox 360', 'Xbox 360'], 
			'ui size' => ['small', 'medium', 'large']]],
		'base.kv' => ['root' => ['rootProp' => 'A', 'included1' => 'B', 'included2' => ['C' => 'D']]],
		'multipleroots.kv' => ['root1' => ['A' => 'B'], 'root2' => ['C' => 'D']],
		'duplicatekeys.kv' => ['root' => ['key1' => ['2', ['key2' => ['5', '6']], ['key3' => '4']]]],
		'duplicaterootkeys.kv' => ['root' => [['key1' => 2, 'key2' => 4], ['key1' => 3, 'key2' => 5]]]
	];

	// Run tests
	$num = count($tests);
	$pass = 0;
	function runTest($file, $expected, $merge = false) {
		global $pass;
		echo "Testing ".$file."<br>";
		// Create new parser
		$parser = new ValveKV();

		// Parse the file
		try {
			$result = $parser->parseFromFile("testcases/".$file, $merge);
			if ($expected == $result) {
				// Pass!
				$pass++;
			} else {
				// Fail - wrong value
				if (PRINT_ERRORS) {
					echo "Result:<br>";
					print_r($result);
					echo "<br>Expected:<br>";
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
	echo "<b>Running functionality tests...</b><br>";

	foreach ($tests as $file => $expected) {
		runTest($file, $expected);
	}
	// Extra duplicate merge test
	$num++;
	runTest("duplicatekeys.kv", ["root" => ["key1" => ["key2" => "6", "key3" => "4"]]], true);

	$totalNum += $num;
	$totalPass += $pass;
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
		"robustness/baseunexisting.kv" => "Exception",
	];

	$num = count($tests);
	$pass = 0;
	echo "<b>Running robustness tests...</b><br>";
	foreach ($tests as $file => $expected) {
		echo "Testing ".$file."<br>";
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

	$totalNum += $num;
	$totalPass += $pass;
	echo "<font color='".($num == $pass ? "green" : "orange")."'>Passed (".$pass."/".$num.") tests.</font><br><br>";

	// Test vkv testcases
	$tests = [
		"vkv/comment_singleline.vdf" => true,
		"vkv/comment_singleline_wholeline.vdf" => true,
		"vkv/comment_singleline_singleslash.vdf" => true,
		"vkv/comment_singleline_singleslash_wholeline.vdf" => true,
		"vkv/conditional.vdf" => true,
		"vkv/conditional_in_key.vdf" => true,
		"vkv/duplicate_keys.vdf" => true,
		"vkv/duplicate_keys_object.vdf" => true,
		"vkv/empty.vdf" => true,
		"vkv/escaped_backslash.vdf" => true,
		"vkv/escaped_backslash_not_special.vdf" => true,
		"vkv/escaped_garbage.vdf" => true,
		"vkv/escaped_quotation_marks.vdf" => true,
		"vkv/escaped_whitespace.vdf" => true,
		"vkv/invalid_conditional.vdf" => true, // No conditional validation
		"vkv/invalid_zerobracerepeated.vdf" => false,
		"vkv/kv_base_included.vdf" => true,
		"vkv/kv_included.vdf" => true,
		"vkv/kv_with_base.vdf" => true,
		"vkv/kv_with_include.vdf" => false, // #include not supported
		"vkv/legacydepotdata_subset.vdf" => true,
		"vkv/list_of_values.vdf" => true,
		"vkv/list_of_values_empty_key.vdf" => true,
		"vkv/list_of_values_skipping_keys.vdf" => true,
		"vkv/nameonly.vdf" => false,
		"vkv/nested_object_graph.vdf" => true,
		"vkv/object_person.vdf" => true,
		"vkv/object_person_attributes.vdf" => true,
		"vkv/object_person_mixed_case.vdf" => true,
		"vkv/partial_noclose.vdf" => false,
		"vkv/partial_nodata.vdf" => false,
		"vkv/partial_novalue.vdf" => false,
		"vkv/partial_opening_key.vdf" => false,
		"vkv/partial_opening_value.vdf" => false,
		"vkv/partial_partialkey.vdf" => false,
		"vkv/partial_partialvalue.vdf" => false,
		"vkv/partialname.vdf" => false,
		"vkv/quoteonly.vdf" => false,
		"vkv/serialization_expected.vdf" => true,
		"vkv/steam_440.vdf" => true,
		"vkv/top_level_list_of_values.vdf" => true,
		"vkv/type_guessing.vdf" => true,
		"vkv/unquoted_document.vdf" => true,
	];

	$num = count($tests);
	$pass = 0;

	echo "<b>Running vkv tests...</b><br>";
	foreach ($tests as $file => $shouldParse) {
		echo "Testing ".$file."<br>";
		// Create new parser
		$parser = new ValveKV();
		// Try to parse
		try {
			$parser->parseFromFile("testcases/".$file);
			if ($shouldParse) {
				$pass++;
			} else {
				if (PRINT_ERRORS) {
					echo $file." successfully parsed but should have failed.";
				}
				echo "<font color='red'>Testcase ".$file." failed.</font><br>";
			}
		} catch(\Exception $e) {
			if (!$shouldParse) {
				$pass++;
			} else {
				if (PRINT_ERRORS) {
					print_r($e);
				}
				echo "<font color='red'>Testcase ".$file." failed.</font><br>";
			}
		}
	}

	$totalNum += $num;
	$totalPass += $pass;
	echo "<font color='".($num == $pass ? "green" : "orange")."'>Passed (".$pass."/".$num.") tests.</font><br><br>";

	// Test if benchmark files successfully parse
	$tests = [
		"benchmark/dota_english.txt",
		"benchmark/items.txt",
		"benchmark/npc_units.txt",
		"benchmark/weapon_ak47.txt",
	];

	$num = count($tests);
	$pass = 0;

	echo "<b>Running benchmark tests...</b><br>";
	foreach ($tests as $file) {
		echo "Testing ".$file."<br>";
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

	$totalNum += $num;
	$totalPass += $pass;
	echo "<font color='".($num == $pass ? "green" : "orange")."'>Passed (".$pass."/".$num.") tests.</font><br><br>";

	// Report total
	echo "<font color='".($totalNum == $totalPass ? "green" : "orange")."'>TOTAL: Passed (".$totalPass."/".$totalNum.") tests.</font><br><br>";