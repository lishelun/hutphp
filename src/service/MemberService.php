<?php

declare (strict_types = 1);

namespace hutphp\service;

use hutphp\Service;

class MemberService extends Service
{
    public function getMemberName(): string
    {
        return $this->app->session->get('member.membername' , '');
    }

    public function getMemberId(): int
    {
        return intval($this->app->session->get('member.id' , 0));
    }

    public function isLogin(): bool
    {
        return $this->getMemberId() > 0;
    }

    public function isSuper(): bool
    {
        return in_array($this->getMemberName() , $this->getSuperName());
    }

    public function getSuperName(): array
    {
        return $this->app->config->get('app.super_membername');
    }

    /**
     * 登陆创建session
     * @param $r
     * @return void
     */
    public function setSession($r)
    {
        session('member.id' , $r['id']);
        session('member.membername' , $r['membername']);
        session('member.nodes' , $r['nodes']);
        session('member.token' , $r['token']);
        session('member.group_id' , $r['group_id']);
    }

    /**
     * 登出清除session
     * @return void
     */
    public function clearSession()
    {
        session('member.id' , null);
        session('member.membername' , null);
        session('member.nodes' , null);
        session('member.token' , null);
        session('member.group_id' , null);
    }

    /**
     * @throws \ReflectionException
     */
    public function checkPermissions(?string $node = ''): bool
    {
        $service = NodeService::instance();
        $methods = $service->getMethods();
        // 兼容 windows 控制器不区分大小写的验证问题
        foreach ( $methods as $key => $rule ) {
            if ( preg_match('#.*?/.*?_.*?#' , $key) ) {
                $attr                       = explode('/' , $key);
                $attr[1]                    = strtr($attr[1] , ['_' => '']);
                $methods[join('/' , $attr)] = $rule;
            }
        }
        $current = $service->fullnode($node);
        if ( function_exists('member_check_filter') ) {
            return member_check_filter($current , $methods , $this->app->session->get('member.nodes' , []) , $this);
        } else if ( empty($methods[$current]['isMember']) ) {
            return true;
        } else {
            return in_array($current , $this->app->session->get('member.nodes' , []));
        }
    }
}