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

use think\App;
use think\Container;

abstract class Service
{
    protected App $app;

    /**
     * @param \think\App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->initialize();
    }

    /**
     * 初始化
     */
    protected function initialize()
    {

    }

    /**
     * 获得服务实例
     * @param array $args
     * @param bool  $newInstance
     * @return static
     */
    public static function instance(array $args = [] , bool $newInstance = false): static
    {
        return Container::getInstance()->make(static::class , $args , $newInstance);
    }

}