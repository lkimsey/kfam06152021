<?php

namespace BrizyMergeTests;

use BrizyMerge\Assets\Asset;
use BrizyMerge\Assets\AssetFont;
use BrizyMerge\Assets\AssetGroup;
use BrizyMerge\Assets\AssetLib;
use PHPUnit\Framework\TestCase;

class AssetGroupTest extends TestCase
{
    public function test_instanceFromJsonData()
    {
        $page = json_decode(file_get_contents("./tests/data/page.json"),true);

        $data  = $page['blocks']['freeStyles'];
        $asset = AssetGroup::instanceFromJsonData($data);

        $this->assertInstanceOf(Asset::class, $asset->getMain(), 'It should return the correct value for main');

        foreach ($asset->getGeneric() as $entry) {
            $this->assertInstanceOf(Asset::class, $entry, 'It should return the correct value for generic');
        }
        foreach ($asset->getLibsMap() as $entry) {
            $this->assertInstanceOf(AssetLib::class, $entry, 'It should return the correct value for libmap');
        }
        foreach ($asset->getLibsSelectors() as $entry) {
            $this->assertIsString($entry, 'It should return the correct value for lib selectors');
        }

        if (isset($data['projectFonts'])) {
            foreach ($asset->getPageFonts() as $entry) {
                $this->assertInstanceOf(
                    AssetFont::class,
                    $entry,
                    'It should return the correct value for project fonts'
                );
            }

        }

        if (isset($data['projectStyles'])) {
            foreach ($asset->getPageStyles() as $entry) {
                $this->assertInstanceOf(Asset::class, $entry, 'It should return the correct value for project styles');
            }
        }

    }

    public function test_instanceFromJsonData_exceptions1()
    {
        $this->expectException(\Exception::class);

        $data = [
            "main"          => [],
            "generic"       => [],
            "libsMap"       => [],
            "libsSelectors" => ["content"],
            "aditional"     => ["content"],
        ];

        $asset = Asset::instanceFromJsonData($data);
    }

}
