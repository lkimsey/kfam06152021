<?php


namespace BrizyMerge\Assets;


class Asset
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $score;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var bool
     */
    protected $pro;

    /**
     * @param $data
     */
    static function instanceFromJsonData($data)
    {
        $assetKeys = array_keys($data);

        $allowedKeys = ['name', 'score', 'content', 'pro'];
        if (count($keyDiff = array_diff($assetKeys, $allowedKeys)) !== 0) {
            throw new \Exception('Invalid Asset fields provided: '.json_encode($keyDiff));
        }

        if (count($keyDiff = array_diff($allowedKeys, $assetKeys)) !== 0) {
            throw new \Exception('Missing Asset field: '.json_encode($keyDiff));
        }

        return new self($data['name'], $data['score'], $data['content'], $data['pro']);
    }

    /**
     * Asset constructor.
     *
     * @param string $name
     * @param int $score
     * @param string $content
     * @param false $pro
     */
    public function __construct($name = '', $score = 0, $content = '', $pro = false)
    {
        $this->name    = $name;
        $this->score   = (int)$score;
        $this->content = $content;
        $this->pro     = $pro;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Asset
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getScore()
    {
        return (int)$this->score;
    }

    /**
     * @param int $score
     *
     * @return Asset
     */
    public function setScore($score)
    {
        $this->score = (int)$score;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return Asset
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPro()
    {
        return $this->pro;
    }

    /**
     * @param bool $pro
     *
     * @return Asset
     */
    public function setPro($pro)
    {
        $this->pro = $pro;

        return $this;
    }
}
