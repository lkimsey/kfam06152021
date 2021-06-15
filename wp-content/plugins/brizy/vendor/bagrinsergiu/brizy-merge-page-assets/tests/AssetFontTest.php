<?php

namespace BrizyMergeTests;

use BrizyMerge\Assets\Asset;
use BrizyMerge\Assets\AssetFont;
use PHPUnit\Framework\TestCase;

class AssetFontTest extends TestCase
{
    public function test_instanceFromJsonData()
    {
        $data = [
            "name"    => "main",
            "score"   => 30,
            "content" => "content",
            "pro"     => false,
            "type"     => "type",
        ];

        $asset = AssetFont::instanceFromJsonData($data);

        $this->assertEquals($data['name'], $asset->getName(), 'It should return the correct value for name');
        $this->assertEquals($data['score'], $asset->getScore(), 'It should return the correct value for score');
        $this->assertEquals($data['content'], $asset->getContent(), 'It should return the correct value for content');
        $this->assertEquals($data['pro'], $asset->isPro(), 'It should return the correct value for pro');
        $this->assertEquals($data['type'], $asset->getType(), 'It should return the correct value for type');

    }

    public function test_instanceFromJsonData_exceptions1()
    {
        $this->expectException(\Exception::class);

        $data = [
            "name"    => "main",
            "score"   => 30,
            "content" => "content",
            "pro"     => false,
            "type"     => "type",
            "additional_key"=>""
        ];

        $asset = AssetFont::instanceFromJsonData($data);
    }

    public function test_instanceFromJsonData_exceptions2()
    {
        $this->expectException(\Exception::class);

        $data = [
            "name"    => "main",
            "score"   => 30,
            "content" => "content",
            "type"     => "type",
        ];

        $asset = AssetFont::instanceFromJsonData($data);
    }

    public function test_instanceFromJsonData_exceptions3()
    {
        $this->expectException(\Exception::class);

        $data = [
            "score"   => 30,
            "content" => "content",
            "type"     => "type",
            "pro"     => false,
        ];

        $asset = AssetFont::instanceFromJsonData($data);
    }
}
