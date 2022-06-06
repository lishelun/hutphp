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

namespace hutphp\helper;

use hutphp\Helper;
use think\Validate;

/**
 * 快捷输入验证器
 * Class ValidateHelper
 * @package hutphp\helper
 */
class ValidateHelper extends Helper
{
    /**
     * 快捷输入并验证（ 支持 规则 # 别名 ）
     * @param array         $rules    验证规则（ 验证信息数组 ）
     * @param array|string  $input    输入内容 ( post. 或 get. )
     * @param callable|null $callable 异常处理操作
     * @return array 数据
     *                                更多规则参照 ThinkPHP 官方的验证类
     */
    public function init(array $rules , array|string $input = '' , ?callable $callable = null): array
    {
        if ( is_string($input) ) {
            $type  = trim($input , '.') ?: 'request';
            $input = $this->app->request->$type();
        }
        [$data , $rule , $info] = [[] , [] , []];
        foreach ( $rules as $name => $message ) if ( is_numeric($name) ) {
            [$name , $alias] = explode('#' , $message . '#');
            $data[$name] = $input[($alias ?: $name)] ?? null;
        } else if ( !str_contains($name , '.') ) {
            $data[$name] = $message;
        } else if ( preg_match('|^(.*?)\.(.*?)#(.*?)#?$|' , $name . '#' , $matches) ) {
            [, $_key , $_rule , $alias] = $matches;
            if ( in_array($_rule , ['value' , 'default']) ) {
                if ( $_rule === 'value' ) $data[$_key] = $message;
                else if ( $_rule === 'default' ) $data[$_key] = $input[($alias ?: $_key)] ?? $message;
            } else {
                $info[explode(':' , $name)[0]] = $message;
                $data[$_key]                   = $data[$_key] ?? ($input[($alias ?: $_key)] ?? null);
                $rule[$_key]                   = isset($rule[$_key]) ? ($rule[$_key] . '|' . $_rule) : $_rule;
            }
        }
        $validate = new Validate();
        if ( $validate->rule($rule)->message($info)->check($data) ) {
            return $data;
        } else if ( is_callable($callable) ) {
            return call_user_func($callable , $validate->getError());
        } else {
            $this->class->error($validate->getError());
        }
        return $data;
    }
}