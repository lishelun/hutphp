<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2021 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace think\middleware;

use Closure;
use think\App;
use think\Request;
use think\Session;
use think\Response;

/**
 * Session初始化
 */
class SessionInit
{

    /** @var App */
    protected $app;

    /** @var Session */
    protected $session;

    public function __construct(App $app , Session $session)
    {
        $this->app     = $app;
        $this->session = $session;
    }

    /**
     * Session初始化
     * @access public
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request , Closure $next)
    {
        // Session初始化
        $varSessionId = $this->app->config->get('session.var_session_id');
        $cookieName   = $this->session->getName();
        $had          = false;
        if ( $varSessionId && $request->request($varSessionId) ) {
            $sessionId = $request->request($varSessionId);
        } else {
            $sessionId = $request->cookie($cookieName);
        }


        if ( $sessionId ) {
            $this->session->setId($sessionId);
            $had = true;
        }

        $this->session->init();

        $request->withSession($this->session);

        /** @var Response $response */
        $response = $next($request);

        $response->setSession($this->session);

        !$had && $this->app->cookie->set($cookieName , $this->session->getId());

        return $response;
    }

    public function end(Response $response)
    {
        $this->session->save();
    }
}
