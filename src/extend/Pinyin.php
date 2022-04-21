<?php
declare (strict_types=1);

namespace hutphp\extend;
class Pinyin
{

    //utf-8中国汉字集合
    private $ChineseCharacters;
    //编码
    private string $charset = 'utf-8';

    public function __construct()
    {
        if ( empty($this->ChineseCharacters) ) {
            $this->ChineseCharacters = file_get_contents(__DIR__ . '/pinyin/ChineseCharacters.dat');
        }
    }

    /*
    * 转成带有声调的汉语拼音
    * param $input_char String  需要转换的汉字
    * param $delimiter  String   转换之后拼音之间分隔符
    * param $outside_ignore  Boolean     是否忽略非汉字内容
    */
    public function TransformWithTone($input_char , $delimiter = ' ' , $outside_ignore = false , $outside_up = false): string
    {

        $input_len = mb_strlen($input_char , $this->charset);
        $output_char = '';
        for ( $i = 0 ; $i < $input_len ; $i++ ) {
            $word = mb_substr($input_char , $i , 1 , $this->charset);
            if ( preg_match('/^[\x{4e00}-\x{9fa5}]$/u' , $word) && preg_match('/\,' . preg_quote($word) . '(.*?)\,/' , $this->ChineseCharacters , $matches) ) {
                if ( $outside_up ) {
                    $matches[1] = ucfirst($matches[1]);
                }
                $output_char .= $matches[1] . $delimiter;
            } else if ( !$outside_ignore ) {
                $output_char .= $word;
            }
        }

        return $output_char;
    }

    /*
    * 转成带无声调的汉语拼音
    * param $input_char String  需要转换的汉字
    * param $outside_up  Boolean     是否首字母大写
    * param $delimiter  String   转换之后拼音之间分隔符
    * param $outside_ignore  Boolean     是否忽略非汉字内容
    */
    public function TransformWithoutTone($input_char , $delimiter = '' , $outside_ignore = true , $outside_up = false)
    {

        $exp = '&&||&&';
        $char_with_tone = $this->TransformWithTone($input_char , $exp , $outside_ignore , $outside_up);
        $char_without_tone = $this->getChar_without_tone($char_with_tone);
        if ( $outside_up ) {

            $r = explode($exp , $char_without_tone);
            foreach ( $r as $key => $val ) {
                if ( strlen($val) == 1 ) {
                    $r[$key] = strtoupper($val);
                }
            }
            $char_without_tone = join($delimiter , $r);
        }
        return str_replace($exp , $delimiter , $char_without_tone);

    }

    /*
    * 转成汉语拼音首字母,只包括汉字
    * param $input_char String  需要转换的汉字
    * param $delimiter  String   转换之后拼音之间分隔符
    */
    public function TransformUcwordsOnlyChar($input_char , $delimiter = '')
    {

        $char_without_tone = ucwords($this->TransformWithoutTone($input_char , ' ' , true));
        $ucwords = preg_replace('/[^A-Z]/' , '' , $char_without_tone);
        if ( !empty($delimiter) ) {
            $ucwords = implode($delimiter , str_split($ucwords));
        }
        return $ucwords;


    }


    /*
    * 转成汉语拼音首字母,包含非汉字内容
    * param $input_char String  需要转换的汉字
    * param $delimiter  String   转换之后拼音之间分隔符
    */
    public function TransformUcwords($input_char , $delimiter = ' ' , $outside_ignore = false): string
    {

        $input_len = mb_strlen($input_char , $this->charset);
        $output_char = '';
        for ( $i = 0 ; $i < $input_len ; $i++ ) {
            $word = mb_substr($input_char , $i , 1 , $this->charset);
            if ( preg_match('/^[\x{4e00}-\x{9fa5}]$/u' , $word) && preg_match('/\,' . preg_quote($word) . '(.*?)\,/' , $this->ChineseCharacters , $matches) ) {
                $output_char .= $matches[1] . $delimiter;
            } else if ( !$outside_ignore ) {
                $output_char .= $delimiter . $word . $delimiter;
            }
        }
        $output_char = $this->getChar_without_tone($output_char);

        $array = explode($delimiter , $output_char);
        $array = array_filter($array);
        $res = '';
        foreach ( $array as $list ) {
            $res .= substr($list , 0 , 1);
        }
        return $res;
    }

    /**
     * @param string $char_with_tone
     * @return string|string[]
     */
    public function getChar_without_tone(string $char_with_tone)
    {
        return str_replace(array('ā' , 'á' , 'ǎ' , 'à' , 'ō' , 'ó' , 'ǒ' , 'ò' , 'ē' , 'é' , 'ě' , 'è' , 'ī' , 'í' , 'ǐ' , 'ì' , 'ū' , 'ú' , 'ǔ' , 'ù' , 'ǖ' , 'ǘ' , 'ǚ' , 'ǜ' , 'ü') ,
            array('a' , 'a' , 'a' , 'a' , 'o' , 'o' , 'o' , 'o' , 'e' , 'e' , 'e' , 'e' , 'i' , 'i' , 'i' , 'i' , 'u' , 'u' , 'u' , 'u' , 'v' , 'v' , 'v' , 'v' , 'v')
            , $char_with_tone);
    }
}