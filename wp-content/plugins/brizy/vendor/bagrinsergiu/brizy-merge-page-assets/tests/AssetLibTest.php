<?php

namespace BrizyMergeTests;

use BrizyMerge\Assets\Asset;
use BrizyMerge\Assets\AssetLib;
use PHPUnit\Framework\TestCase;

class AssetLibTest extends TestCase
{
    public function test_instanceFromJsonData()
    {
        $data = [
            "name"      => "main",
            "score"     => 30,
            "content"   => "content",
            "pro"       => false,
            "selectors" => ['selector1', 'selector2'],
        ];

        $asset = AssetLib::instanceFromJsonData($data);

        $this->assertEquals($data['name'], $asset->getName(), 'It should return the correct value for name');
        $this->assertEquals($data['score'], $asset->getScore(), 'It should return the correct value for score');
        $this->assertEquals($data['content'], $asset->getContent(), 'It should return the correct value for content');
        $this->assertEquals($data['pro'], $asset->isPro(), 'It should return the correct value for pro');
        $this->assertEquals(
            $data['selectors'],
            $asset->getSelectors(),
            'It should return the correct value for selectors'
        );

    }

    public function test_instanceFromJsonData_exceptions1()
    {
        $this->expectException(\Exception::class);

        $data = [
            "name"           => "main",
            "score"          => 30,
            "content"        => "content",
            "pro"            => false,
            "selectors"      => ['selector1', 'selector2'],
            "additional_key" => "",
        ];

        $asset = AssetLib::instanceFromJsonData($data);
    }

    public function test_instanceFromJsonData_exceptions2()
    {
        $this->expectException(\Exception::class);

        $data = [
            "name"    => "main",
            "score"   => 30,
            "content" => "content",
        ];

        $asset = AssetLib::instanceFromJsonData($data);
    }

    public function test_instanceFromJsonData_exceptions3()
    {
        $this->expectException(\Exception::class);

        $data = [
            "name"      => "main",
            "score"     => 30,
            "pro"       => false,
            "selectors" => ['selector1', 'selector2'],
        ];

        $asset = AssetLib::instanceFromJsonData($data);
    }
}
