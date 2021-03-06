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
use think\Config;
use think\Request;
use think\Response;

/**
 * 跨域请求支持
 */
class AllowCrossDomain
{
    protected $cookieDomain;

    protected $header = [
        'Access-Control-Allow-Credentials' => 'true' ,
        'Access-Control-Max-Age'           => 1800 ,
        'Access-Control-Allow-Methods'     => 'GET, POST, PATCH, PUT, DELETE, OPTIONS' ,
        'Access-Control-Allow-Headers'     => 'Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-CSRF-TOKEN, X-Requested-With' ,
    ];

    public function __construct(Config $config)
    {
        $this->cookieDomain = $config->get('cookie.domain' , '');
    }

    /**
     * 允许跨域请求
     * @access public
     * @param Request $request
     * @param Closure $next
     * @param array   $header
     * @return Response
     */
    public function handle($request , Closure $next , ?array $header = [])
    {
        $header = !empty($header) ? array_merge($this->header , $header) : $this->header;

        if ( !isset($header['Access-Control-Allow-Origin']) ) {
            $origin = $request->header('origin');

            if ( $origin && ('' == $this->cookieDomain || strpos($origin , $this->cookieDomain)) ) {
                $header['Access-Control-Allow-Origin'] = $origin;
            } else {
                $header['Access-Control-Allow-Origin'] = '*';
            }
        }

        return $next($request)->header($header);
    }
}
