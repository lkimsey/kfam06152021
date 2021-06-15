<?php

namespace BrizyMergeTests;

use BrizyMerge\AssetAggregator;
use BrizyMerge\Assets\AssetGroup;
use PHPUnit\Framework\TestCase;

class AssetAggregatorTest extends TestCase
{
    public function testGetAssetList()
    {
        $page = json_decode(file_get_contents("./tests/data/page.json"), true);
        $page2 = json_decode(file_get_contents("./tests/data/page2.json"), true);

        $assets   = [];
        $assets[] = AssetGroup::instanceFromJsonData($page['blocks']['freeStyles']);
        $assets[] = AssetGroup::instanceFromJsonData($page['blocks']['proStyles']);
        $assets[] = AssetGroup::instanceFromJsonData($page2['blocks']['freeStyles']);

        $aggregator = new AssetAggregator($assets);

        $list = $aggregator->getAssetList();

        print_r($list);
        exit;

        $score = 0;
        foreach($list as $i=>$item) {
            if($i==0) {
                $score = $item->getScore();
                continue;
            }

            $this->assertGreaterThanOrEqual($score,$item->getScore(),'The items are not sorted ascending');

            $score = $item->getScore();
        }
    }
}
