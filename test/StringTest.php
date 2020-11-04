<?php
use \ValveKV\ValveKV;

class StringTest extends \PHPUnit\Framework\TestCase
{
	public function testParsingFromString() : void
	{
		$parser = new ValveKV();
		$result = $parser->parseFromString( '"A" { "B"	"C" }' );

		$this->assertEquals( ['A' => ['B' => 'C']], $result );
	}
}
