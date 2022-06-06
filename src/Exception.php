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

namespace hutphp;

class Exception extends \Exception
{
    /**
     * 异常数据对象
     */
    protected $data = [];

    /**
     * Exception constructor.
     * @param string  $message
     * @param integer $code
     * @param         $data
     */
    public function __construct($message = "" , $code = 0 , $data = [])
    {
        $this->code    = $code;
        $this->data    = $data;
        $this->message = $message;
        parent::__construct($message , $code);
    }

    /**
     * 获取异常停止数据
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 设置异常停止数据
     * @param $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}