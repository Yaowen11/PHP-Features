<?php
/**
 * Created by PhpStorm.
 * User: z
 * Date: 18-8-22
 * Time: 上午5:51
 */


class Sensitive
{
    /**
     * @var string 替换敏感词字符
     */
    private $replaceCode = '*';

    /**
     * @var array 敏感词树形数组
     */
    public $trieTreeMap = [];

    /**
     * @var array 干扰字符
     */
    public $disturbList = [
        '!', '!', '$', '#', '^', '&', '。', ',', '.', '/', '\\', '[', ']', '{', '}',
        '+', '-', '=', '：', ':', ';', '‘', '‘', '`', '>', '<',
    ];

    /**
     * @param string $string
     */
    public function setReplaceString(string $string)
    {
        $this->replaceCode = $string;
    }

    /**
     * @param array $disturbList
     */
    public function setDisturbList(array $disturbList)
    {
        $this->disturbList = array_merge($this->disturbList, $disturbList);
    }

    /**
     * Sensitive constructor.
     */
    public function __construct()
    {
        $sensitiveWords = require_once 'filterWords.php';
        foreach ($sensitiveWords as $words) {
            $nowWords = &$this->trieTreeMap;
            $len = mb_strlen($words);
            for ($i = 0; $i < $len; $i++) {
                $word = mb_substr($words, $i, 1);
                if (!isset($nowWords[$word])) {
                    $nowWords[$word] = false;
                }
                $nowWords = &$nowWords[$word];
            }
        }
    }

    /**
     * @param $txt
     * @param bool $hasReplace
     * @param array $replaceCodeList
     * @return array
     * 查找敏感词
     */
    public function search($txt, $hasReplace = false, &$replaceCodeList = [])
    {
        $wordsList = array();
        $txtLength = mb_strlen($txt);
        for ($i = 0; $i < $txtLength; $i++) {
            $wordLength = $this->checkWord($txt, $i, $txtLength);
            if ($wordLength > 0) {
                $words = mb_substr($txt, $i, $wordLength);
                $wordsList[] = $words;
                $hasReplace && $replaceCodeList[] = str_repeat($this->replaceCode, mb_strlen($words));
                $i += $wordLength - 1;
            }
        }
        return $wordsList;
    }

    /**
     * @param $txt
     * @return mixed
     * 替换敏感词
     */
    public function filter($txt)
    {
        $replaceCodeList = array();
        $wordsList = $this->search($txt, true, $replaceCodeList);
        if (empty($wordsList)) {
            return $txt;
        }
        return str_replace($wordsList, $replaceCodeList, $txt);
    }

    /**
     * @param $txt
     * @param $beginIndex
     * @param $length
     * @return int
     * 检测是否为敏感词
     */
    private function checkWord($txt, $beginIndex, $length)
    {
        $flag = false;
        $wordLength = 0;
        $trieTree = &$this->trieTreeMap;
        for ($i = $beginIndex; $i < $length; $i++) {
            $word = mb_substr($txt, $i, 1);
            if (in_array($word, $this->disturbList)) {
                $wordLength++;
                continue;
            }
            if (!isset($trieTree[$word])) {
                break;
            }
            $wordLength++;
            if ($trieTree[$word] !== false) {
                $trieTree = &$trieTree[$word];
            } else {
                $flag = true;
            }
        }
        $flag || $wordLength = 0;
        return $wordLength;
    }
}