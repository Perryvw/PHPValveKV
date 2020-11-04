<?php
use \ValveKV\ValveKV;

class SuccessParseTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * @dataProvider successFileProvider
	 */
	public function testSuccessful( string $file ) : void
	{
		$parser = new ValveKV();
		$parser->parseFromFile( __DIR__ . "/testcases/" . $file );

		$this->assertTrue( true );
	}

	/**
	 * @return array<array<string>>
	 */
	public function successFileProvider() : array
	{
		return [
			[ "vkv/comment_singleline.vdf" ],
			[ "vkv/comment_singleline_wholeline.vdf" ],
			[ "vkv/comment_singleline_singleslash.vdf" ],
			[ "vkv/comment_singleline_singleslash_wholeline.vdf" ],
			[ "vkv/conditional.vdf" ],
			[ "vkv/conditional_in_key.vdf" ],
			[ "vkv/duplicate_keys.vdf" ],
			[ "vkv/duplicate_keys_object.vdf" ],
			[ "vkv/empty.vdf" ],
			[ "vkv/escaped_backslash.vdf" ],
			[ "vkv/escaped_backslash_not_special.vdf" ],
			[ "vkv/escaped_garbage.vdf" ],
			[ "vkv/escaped_quotation_marks.vdf" ],
			[ "vkv/escaped_whitespace.vdf" ],
			[ "vkv/invalid_conditional.vdf" ], // No conditional validation
			[ "vkv/kv_base_included.vdf" ],
			[ "vkv/kv_included.vdf" ],
			[ "vkv/kv_with_base.vdf" ],
			[ "vkv/legacydepotdata_subset.vdf" ],
			[ "vkv/list_of_values.vdf" ],
			[ "vkv/list_of_values_empty_key.vdf" ],
			[ "vkv/list_of_values_skipping_keys.vdf" ],
			[ "vkv/nested_object_graph.vdf" ],
			[ "vkv/object_person.vdf" ],
			[ "vkv/object_person_attributes.vdf" ],
			[ "vkv/object_person_mixed_case.vdf" ],
			[ "vkv/serialization_expected.vdf" ],
			[ "vkv/steam_440.vdf" ],
			[ "vkv/top_level_list_of_values.vdf" ],
			[ "vkv/type_guessing.vdf" ],
			[ "vkv/unquoted_document.vdf" ],
			[ "benchmark/dota_english.txt" ],
			[ "benchmark/items.txt" ],
			[ "benchmark/npc_units.txt" ],
			[ "benchmark/weapon_ak47.txt" ],
		];
	}
}
