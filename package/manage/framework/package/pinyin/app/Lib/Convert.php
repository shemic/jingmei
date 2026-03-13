<?php
namespace Pinyin\Lib;
use Dever;
Dever::apply('pinyin/overtrue/src/const');
Dever::apply('pinyin/overtrue/src/DictLoaderInterface');
Dever::apply('pinyin/overtrue/src/FileDictLoader');
Dever::apply('pinyin/overtrue/src/GeneratorFileDictLoader');
Dever::apply('pinyin/overtrue/src/MemoryFileDictLoader');
Dever::apply('pinyin/overtrue/src/Pinyin');
use Overtrue\Pinyin\Pinyin as Core;

//教程http://overtrue.me/pinyin/

class Convert
{

    /**
     * @var null|Pinyin
     */
    protected $pinyin = null;

    public function __construct()
    {
        if (!$this->pinyin) {
            $this->pinyin = new Core('Overtrue\Pinyin\MemoryFileDictLoader');
        }
    }

    /**
     * 获取拼音类
     */
    public function getPinyinFunc()
    {
        return $this->pinyin;
    }

    /**
     * 获取拼音
     */
    public function getPinyin($string, $link = '')
    {
        return $this->pinyin->permalink($string, $link);
    }

    /**
     * 获取姓名
     */
    public function getPinyinName($string)
    {
        return $this->pinyin->name($string);
    }

    /**
     * 获取整段文字
     */
    public function getPinyinContent($string, $state = false)
    {
        return $this->pinyin->sentence($string, $state);
    }

    /**
     * 二维数组根据首字母分组排序
     * @param  array  $data      二维数组
     * @param  string $targetKey 首字母的键名
     * @return array             根据首字母关联的二维数组
     */
    public function groupByPinyinFirst(array $data, $targetKey = 'name')
    {
        if (!$this->pinyin) {
            $this->pinyin = new Core('Overtrue\Pinyin\MemoryFileDictLoader');
        }
        $data = array_map(function ($item) use ($targetKey) {
            return array_merge($item, [
                'first' => $this->getPinyinFirst($item[$targetKey]),
            ]);
        }, $data);
        $data = $this->sortPinyinFirst($data);
        return $data;
    }

    /**
     * 按字母排序
     * @param  array  $data
     * @return array
     */
    public function sortPinyinFirst(array $data, $first = 'first')
    {
        $sortData = [];
        foreach ($data as $key => $value) {
            $sortData[$value[$first]][] = $value;
        }
        ksort($sortData);
        if (isset($sortData[''])) {
            $tmp = $sortData[''];
            unset($sortData['']);
            $sortData[''] = $tmp;
        }
        return $sortData;
    }

    /**
     * 获取首字母
     * @param  string $str 汉字字符串
     * @return string 首字母
     */
    public function getPinyinFirst($str)
    {
        if (empty($str)) {
            return '';
        }
        if (!$this->pinyin) {
            $this->pinyin = new Core('Overtrue\Pinyin\MemoryFileDictLoader');
        }
        return strtoupper(substr($this->pinyin->abbr($str, PINYIN_KEEP_ENGLISH), 0, 1));
    }

}
