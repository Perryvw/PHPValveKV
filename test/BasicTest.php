<?php
use \ValveKV\ValveKV;

class BasicTest extends \PHPUnit\Framework\TestCase
{
	public function testMergeDuplicate() : void
	{
		$parser = new ValveKV();
		$result = $parser->parseFromFile( __DIR__ . "/testcases/duplicatekeys.kv", true );

		$this->assertEquals( ["root" => ["key1" => ["key2" => "6", "key3" => "4"]]], $result );
	}

	/**
	 * @dataProvider fileProvider
	 */
	public function testBasic( string $file, array $expected ) : void
	{
		$parser = new ValveKV();
		$result = $parser->parseFromFile( __DIR__ . "/testcases/" . $file );

		$this->assertEquals( $expected, $result );
	}

	public function fileProvider() : array
	{
		return [
			[ 'basic.kv', ['A' => ['B' => 'C']] ],
			[ 'basic2.kv', ['A' => ['B' => 'C', 'D' => 'E']] ],
			[ 'nested.kv', ['A' => ['B' => ['C' => ['D' => []]]]] ],
			[ 'comments.kv', ['A' => ['B' => ['C' => ['D' => []]]]] ],
			[ 'commenteof.kv', [] ],
			[ 'stringescapes.kv', ['Test' => ['A' => '3\\"5', 'B' => 'A\\\\', 'C' => '\\\\']] ],
			[ 'quoteless.kv', ['TestDocument' => ['QuotedChild' => 'edge\\ncase\\"haha\\\\"', 'UnquotedChild' => ['Key1' => 'Value1', 'Key2' => 'Value2', 'Key3' => 'Value3']]] ],
			[ 'quotelessBracket.kv', ['TestDocument' => ['$envmaptint' => '[ .4 .4 .4]', '$envmapsaturation' => '[.5 .5 .5]']] ],
			[ 'quotelessSpecial.kv', ['TestDocument' => ['$QuotedChild' => 'edge\\ncase\\"haha\\\\"', '#UnquotedChild' => ['&Key1' => '$Value1', '!Key2' => '@Value2', '%Key3' => 'Value3']]] ],
			[ 'conditional.kv', ['test case' => ['operating system' => ['windows 32-bit', 'something else'], 'platform' => 'windows', 'ui type' => ['Widescreen Xbox 360', 'Xbox 360'], 'ui size' => ['small', 'medium', 'large']]] ],
			[ 'base.kv', ['root' => ['rootProp' => 'A', 'included1' => 'B', 'included2' => ['C' => 'D']]] ],
			[ 'multipleroots.kv', ['root1' => ['A' => 'B'], 'root2' => ['C' => 'D']] ],
			[ 'duplicatekeys.kv', ['root' => ['key1' => ['2', ['key2' => ['5', '6']], ['key3' => '4']]]] ],
			[ 'duplicaterootkeys.kv', ['root' => [['key1' => 2, 'key2' => 4], ['key1' => 3, 'key2' => 5]]] ],
		];
	}
}
