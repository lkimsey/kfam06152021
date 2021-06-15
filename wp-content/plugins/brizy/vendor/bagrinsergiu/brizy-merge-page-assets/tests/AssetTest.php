<?php

namespace BrizyMergeTests;

use BrizyMerge\Assets\Asset;
use PHPUnit\Framework\TestCase;

class AssetTest extends TestCase
{
    public function test_instanceFromJsonData()
    {
        $data = [
            "name"    => "main",
            "score"   => 30,
            "content" => "content",
            "pro"     => false,
        ];

        $asset = Asset::instanceFromJsonData($data);

        $this->assertEquals($data['name'], $asset->getName(), 'It should return the correct value for name');
        $this->assertEquals($data['score'], $asset->getScore(), 'It should return the correct value for score');
        $this->assertEquals($data['content'], $asset->getContent(), 'It should return the correct value for content');
        $this->assertEquals($data['pro'], $asset->isPro(), 'It should return the correct value for pro');
    }

    public function test_instanceFromJsonData_exceptions1()
    {
        $this->expectException(\Exception::class);

        $data = [
            "name"    => "main",
            "score"   => 30,
            "content" => "content",
            "pro"     => false,
            "additional_key"=>""
        ];

        $asset = Asset::instanceFromJsonData($data);
    }

    public function test_instanceFromJsonData_exceptions2()
    {
        $this->expectException(\Exception::class);

        $data = [
            "name"    => "main",
            "score"   => 30,
            "content" => "content",
        ];

        $asset = Asset::instanceFromJsonData($data);
    }
}
