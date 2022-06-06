<?php
/*
 *  +----------------------------------------------------------------------
 *  | HUTCMS
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2022 http://hutcms.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed ( https://mit-license.org )
 *  +----------------------------------------------------------------------
 *  | Author: lishelun <lishelun@qq.com>
 *  +----------------------------------------------------------------------
 */

declare (strict_types = 1);

namespace hutphp\extend;

/**
 * 随机数码管理扩展
 * Class Code
 * @package hutphp\extend
 */
class Code
{
    /**
     * 获取随机字符串编码
     * @param integer $size   编码长度
     * @param integer $type   编码类型(1纯数字,2纯字母,3数字字母)
     * @param string  $prefix 编码前缀
     * @return string
     */
    public static function random(int $size = 10 , int $type = 1 , string $prefix = ''): string
    {
        $numbs = '0123456789';
        $chars = 'abcdefghijklmnopqrstuvwxyz';
        if ( $type === 1 ) $chars = $numbs;
        if ( $type === 3 ) $chars = "{$numbs}{$chars}";
        $code = $prefix . $chars[mt_rand(1 , strlen($chars) - 1)];
        while ( strlen($code) < $size ) $code .= $chars[mt_rand(0 , strlen($chars) - 1)];
        return $code;
    }

    /**
     * 唯一日期编码
     * @param integer $size   编码长度
     * @param string  $prefix 编码前缀
     * @return string
     */
    public static function uniqidDate(int $size = 16 , string $prefix = ''): string
    {
        if ( $size < 14 ) $size = 14;
        $code = $prefix . date('Ymd') . (date('H') + date('i')) . date('s');
        while ( strlen($code) < $size ) $code .= mt_rand(0 , 9);
        return $code;
    }

    /**
     * 唯一数字编码
     * @param integer $size   编码长度
     * @param string  $prefix 编码前缀
     * @return string
     */
    public static function uniqidNumber(int $size = 12 , string $prefix = ''): string
    {
        $time = time() . '';
        if ( $size < 10 ) $size = 10;
        $code = $prefix . (intval($time[0]) + intval($time[1])) . substr($time , 2) . rand(0 , 9);
        while ( strlen($code) < $size ) $code .= mt_rand(0 , 9);
        return $code;
    }

    /**
     * 数据解密处理
     * @param mixed  $data 加密数据
     * @param string $key  安全密钥
     * @return string
     */
    public static function encrypt(mixed $data , string $key): string
    {
        $iv    = static::random(16 , 3);
        $value = openssl_encrypt(serialize($data) , 'AES-256-CBC' , $key , 0 , $iv);
        return static::enSafe64(json_encode(['iv' => $iv , 'value' => $value]));
    }

    /**
     * 数据加密处理
     * @param string $data 解密数据
     * @param string $key  安全密钥
     * @return mixed
     */
    public static function decrypt(string $data , string $key): mixed
    {
        $attr = json_decode(static::deSafe64($data) , true);
        return unserialize(openssl_decrypt($attr['value'] , 'AES-256-CBC' , $key , 0 , $attr['iv']));
    }

    /**
     * Base64Url 安全编码
     * @param string $text 待加密文本
     * @return string
     */
    public static function enSafe64(string $text): string
    {
        return rtrim(strtr(base64_encode($text) , '+/' , '-_') , '=');
    }

    /**
     * Base64Url 安全解码
     * @param string $text 待解密文本
     * @return string
     */
    public static function deSafe64(string $text): string
    {
        return base64_decode(str_pad(strtr($text , '-_' , '+/') , strlen($text) % 4 , '='));
    }

    /**
     * 加密 UTF8 字符串
     * @param string $content
     * @return string
     */
    public static function utf8Encode(string $content): string
    {
        [$chars , $length] = ['' , strlen($string = iconv('UTF-8' , 'GBK//TRANSLIT' , $content))];
        for ( $i = 0 ; $i < $length ; $i++ ) {
            $chars .= str_pad(base_convert(ord($string[$i]) , 10 , 36) , 2 , 0 , 0);
        }

        return $chars;
    }

    /**
     * 解密 UTF8 字符串
     * @param string $content
     * @return string
     */
    public static function utf8Decode(string $content): string
    {
        $chars = '';
        foreach ( str_split($content , 2) as $char ) {
            $chars .= chr(intval(base_convert($char , 36 , 10)));
        }
        return iconv('GBK//TRANSLIT' , 'UTF-8' , $chars);
    }
}