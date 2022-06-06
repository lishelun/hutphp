<?php
/*
 *  +----------------------------------------------------------------------
 *  | HUTCMS
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2006-2022 http://hutcms.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed ( https://mit-license.org )
 *  +----------------------------------------------------------------------
 *  | Author: lishelun <lishelun@qq.com>
 *  +----------------------------------------------------------------------
 */
declare (strict_types = 1);

namespace hutphp\middleware;

use Closure;
use think\Request;
use think\Response;
use think\exception\ValidateException;

/**
 * 表单令牌支持
 */
class FormTokenCheck
{

    /**
     * 表单令牌检测
     * @access public
     * @param Request $request
     * @param Closure $next
     * @param string  $token 表单令牌Token名称
     * @return Response
     */
    public function handle(Request $request , Closure $next , string $token = null)
    {
        $check = $request->checkToken($token ?: '__token__');

        if ( false === $check ) {
            throw new ValidateException('invalid token');
        }

        return $next($request);
    }

}
