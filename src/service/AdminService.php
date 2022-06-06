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

namespace hutphp\service;

use hutphp\Service;
use hutphp\extend\Data;
use hutphp\helper\JWTHelper;

/**
 * 系统权限管理服务
 * Class AdminService
 *
 * @package think\admin\service
 */
class AdminService extends Service
{

    /**
     * 是否已经登录
     *
     * @return boolean
     */
    public function isLogin(): bool
    {
        return (bool)JWTHelper::instance()->checkLogin();
    }

    /**
     * 是否为超级用户
     *
     * @return boolean
     */
    public function isSuper(): bool
    {
        return in_array($this->getUserName() , $this->getSuperName());
    }

    /**
     * 获取超级用户账号
     *
     * @return array
     */
    public function getSuperName(): array
    {
        return $this->app->config->get('app.super_username' , 'admin');
    }

    /**
     * 获取后台用户ID
     *
     * @return integer
     */
    public function getUserId(): int
    {
        return intval($this->app->session->get('user.id' , 0));
    }

    /**
     * 获取后台用户名称
     *
     * @return string
     */
    public function getUserName(): string
    {
        return $this->app->session->get('user.username' , '');
    }

    /**
     * 检查指定节点授权
     * --- 需要读取缓存或扫描所有节点
     *
     * @param null|string $node
     *
     * @return boolean
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
        if ( function_exists('admin_check_filter') ) {
            return admin_check_filter($current , $methods , $this->app->session->get('user.nodes' , []) , $this);
        } else if ( $this->isSuper() ) {
            return true;
        } else if ( empty($methods[$current]['isAuth']) ) {
            return !( !empty($methods[$current]['isLogin']) && !$this->isLogin());
        } else {
            return in_array($current , $this->app->session->get('user.nodes' , []));
        }
    }


    /**
     * 获取授权节点列表
     *
     * @param array $checks
     *
     * @return array
     * @throws \ReflectionException
     */
    public function getNodesTree(array $checks = []): array
    {
        [$nodes , $pnodes , $methods] = [[] , [] , array_reverse(NodeService::instance()->getMethods())];
        foreach ( $methods as $node => $method ) {
            [$count , $pnode] = [substr_count($node , '/') , substr($node , 0 , strripos($node , '/'))];
            if ( $count === 2 && !empty($method['isAuth']) ) {
                in_array($pnode , $pnodes) or array_push($pnodes , $pnode);
                $nodes[$node] = ['node' => $node , 'title' => $method['title'] , 'pnode' => $pnode , 'checked' => in_array($node , $checks)];
            } else if ( $count === 1 && in_array($pnode , $pnodes) ) {
                $nodes[$node] = ['node' => $node , 'title' => $method['title'] , 'pnode' => $pnode , 'checked' => in_array($node , $checks)];
            }
        }
        foreach ( array_keys($nodes) as $key ) foreach ( $methods as $node => $method ) if ( stripos($key , $node . '/') !== false ) {
            $pnode         = substr($node , 0 , strripos($node , '/'));
            $nodes[$node]  = ['node' => $node , 'title' => $method['title'] , 'pnode' => $pnode , 'checked' => in_array($node , $checks)];
            $nodes[$pnode] = ['node' => $pnode , 'title' => ucfirst($pnode) , 'pnode' => '' , 'checked' => in_array($pnode , $checks)];
        }
        return Data::arr2tree(array_reverse($nodes) , 'node' , 'pnode' , '_sub_');
    }

    /**
     * 清理节点缓存
     * 清理csrf缓存和节点缓存
     *
     * @return $this
     */
    public function clearCache(): AdminService
    {
        TokenService::instance()->clearCache();
        NodeService::instance()->clearCache();
        return $this;
    }

    /**
     * 登陆创建session
     *
     * @param $r
     *
     * @return void
     */
    public function setSession($r)
    {
        session('user.id' , $r['id']);
        session('user.username' , $r['username']);
        session('user.role_id' , $r['role_id']);
        session('user.auth' , $r['auth']);
        session('user.token' , $r['token']);
    }

    /**
     * 登出清除session
     *
     * @return void
     */
    public function clearSession()
    {
        session('user.id' , null);
        session('user.username' , null);
        session('user.role_id' , null);
        session('user.auth' , null);
        session('user.token' , null);
    }

}