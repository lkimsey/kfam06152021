<?php


namespace BrizyMerge\Assets;


class AssetFont extends Asset
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @param $data
     */
    static function instanceFromJsonData($data)
    {
        $assetKeys = array_keys($data);

        $allowedKeys = ['name', 'score', 'content', 'pro', 'type'];
        if (count($keyDiff = array_diff($assetKeys, $allowedKeys)) !== 0) {
            throw new \Exception('Invalid AssetFont fields provided: '.json_encode($keyDiff));
        }

        if (count($keyDiff = array_diff($allowedKeys,$assetKeys)) !== 0) {
            throw new \Exception('Missing AssetFont field: '.json_encode($keyDiff));
        }


        return new self($data['name'], $data['score'], $data['content'], $data['pro'], $data['type']);
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
    public function __construct($name = '', $score = 0, $content = '', $pro = false, $type = [])
    {
        parent::__construct($name, $score, $content, $pro);

        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return AssetFont
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }
}
