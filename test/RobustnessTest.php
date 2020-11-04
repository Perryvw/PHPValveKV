<?php
use \ValveKV\ValveKV;

class RobustnessTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * @dataProvider fileProvider
	 */
	public function testRobustness( string $file, string $expect ) : void
	{
		$this->expectException( $expect );

		$parser = new ValveKV();
		$parser->parseFromFile( __DIR__ . "/testcases/" . $file );

		$this->fail( 'Did not throw' );
	}

	/**
	 * @return array<array<string>>
	 */
	public function fileProvider() : array
	{
		return [
			[ "robustness/nonexistingfile.kv", "Exception" ],
			[ "robustness/eof.kv", "ValveKV\ParseException" ],
			[ "robustness/badescaping.kv", "ValveKV\ParseException" ],
			[ "robustness/unclosedstring.kv", "ValveKV\ParseException" ],
			[ "robustness/brackets.kv", "ValveKV\ParseException" ],
			[ "robustness/brackets2.kv", "ValveKV\ParseException" ],
			[ "robustness/keycollision.kv", "ValveKV\KeyCollisionException" ],
			[ "robustness/baseunexisting.kv", "Exception" ],
			[ "robustness/empty.kv", "Exception" ],
		];
	}
}
