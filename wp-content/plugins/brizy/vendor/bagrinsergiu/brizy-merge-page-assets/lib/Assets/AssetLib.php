<?php


namespace BrizyMerge\Assets;


class AssetLib extends Asset
{
    /**
     * @var string[]
     */
    protected $selectors;

    /**
     * @param $data
     */
    static function instanceFromJsonData($data)
    {
        $assetKeys = array_keys($data);

        $allowedKeys = ['name', 'score', 'content', 'pro', 'selectors'];
        if (count($keyDiff = array_diff($assetKeys, $allowedKeys)) !== 0) {
            throw new \Exception('Invalid AssetLib fields provided: '.json_encode($keyDiff));
        }

        if (count($keyDiff = array_diff($allowedKeys,$assetKeys)) !== 0) {
            throw new \Exception('Missing AssetLib field: '.json_encode($keyDiff));
        }

        return new self($data['name'], $data['score'], $data['content'], $data['pro'], $data['selectors']);
    }

    /**
     * AssetLib constructor.
     *
     * @param string $name
     * @param int $score
     * @param string $content
     * @param false $pro
     * @param array $selectors
     */
    public function __construct($name = '', $score = 0, $content = '', $pro = false, $selectors = [])
    {
        parent::__construct($name, $score, $content, $pro);

        $this->selectors = $selectors;
    }

    /**
     * @return string[]
     */
    public function getSelectors()
    {
        return $this->selectors;
    }

    /**
     * @param string[] $selectors
     *
     * @return AssetLib
     */
    public function setSelectors($selectors)
    {
        $this->selectors = $selectors;

        return $this;
    }


}
