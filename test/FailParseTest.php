<?php
use \ValveKV\ValveKV;
use \ValveKV\ParseException;

class FailParseTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * @dataProvider failFileProvider
	 */
	public function testFail( string $file ) : void
	{
		$this->expectException( ParseException::class );

		$parser = new ValveKV();
		$parser->parseFromFile( __DIR__ . "/testcases/" . $file );

		$this->fail( 'Did not throw' );
	}

	/**
	 * @return array<array<string>>
	 */
	public function failFileProvider() : array
	{
		return [
			[ "vkv/invalid_zerobracerepeated.vdf" ],
			[ "vkv/kv_with_include.vdf" ], // #include not supported
			[ "vkv/nameonly.vdf" ],
			[ "vkv/partial_noclose.vdf" ],
			[ "vkv/partial_nodata.vdf" ],
			[ "vkv/partial_novalue.vdf" ],
			[ "vkv/partial_opening_key.vdf" ],
			[ "vkv/partial_opening_value.vdf" ],
			[ "vkv/partial_partialkey.vdf" ],
			[ "vkv/partial_partialvalue.vdf" ],
			[ "vkv/partialname.vdf" ],
			[ "vkv/quoteonly.vdf" ],
			[ "vkv/missingconditionalend.kv" ],
			[ "vkv/neverendingcomment.kv" ],
			[ "vkv/unexpectedcharacter.kv" ],
		];
	}
}
