<?php

declare (strict_types = 1);

namespace hutphp\service;

use hutphp\Service;

/**
 * 表单令牌管理服务
 * Class TokenService
 * @package hutphp\service
 */
class TokenService extends Service
{
    /**
     * 缓存分组名称
     * @var string
     */
    private string $name;

    /**
     * 当前缓存数据
     * @var array
     */
    private array $items = [];

    /**
     * 令牌有效时间
     * @var integer
     */
    private int $expire = 600;

    /**
     * 令牌服务初始化
     */
    protected function initialize()
    {
        $this->name  = $this->getCacheName();
        $this->items = $this->getCacheList(true);
        $this->app->event->listen('HttpEnd' , function () {
            TokenService::instance()->saveCacheData();
        });
    }

    /**
     * 获取缓存名称
     * @return string
     */
    public function getCacheName(): string
    {
        $sid = $this->app->session->getId();
        return 'systoken_' . ($sid ?: 'default');
    }

    /**
     * 保存缓存到文件
     */
    public function saveCacheData()
    {
        $this->clearTimeoutCache();
        $this->app->cache->set($this->name , $this->items , $this->expire);
    }

    /**
     * 获取当前请求 CSRF 值
     * @return string
     */
    public function getInputToken(): string
    {
        return $this->app->request->header('form-token') ?: input('_csrf_' , '');
    }

    /**
     * 验证 CSRF 是否有效
     * @param null|string $token 表单令牌
     * @param null|string $node  授权节点
     * @return boolean
     */
    public function checkFormToken(?string $token = null , ?string $node = null): bool
    {
        $cache = $this->getCacheItem($token ?: $this->getInputToken());
        if ( empty($cache['node']) || empty($cache['time']) ) return false;
        return $cache['node'] === NodeService::instance()->fullnode($node);
    }

    /**
     * 清理表单 CSRF 数据
     * @param null|string $token
     * @return static
     */
    public function clearFormToken(?string $token = null): static
    {
        $this->delCacheItem($token ?: $this->getInputToken());
        return $this;
    }

    /**
     * 生成表单 CSRF 数据
     * @param null|string $node
     * @return array
     */
    public function buildFormToken(?string $node = null): array
    {
        $cnode = NodeService::instance()->fullnode($node);
        [$token , $time] = [md5(uniqid(strval(mt_rand(100000 , 999999)))) , time()];
        $this->setCacheItem($token , $item = ['node' => $cnode , 'time' => $time]);
        return array_merge($item , ['token' => $token]);
    }

    /**
     * 清空所有 CSRF 数据
     */
    public function clearCache()
    {
        $this->app->cache->delete($this->name);
    }

    /**
     * 设置缓存数据
     * @param string $token
     * @param array  $value
     */
    private function setCacheItem(string $token , array $value)
    {
        $this->items[$token] = $value;
    }

    /**
     * 删除缓存
     * @param string $token
     */
    private function delCacheItem(string $token)
    {
        unset($this->items[$token]);
    }

    /**
     * 获取指定缓存
     * @param string $token
     * @return array
     */
    private function getCacheItem(string $token): array
    {
        $this->clearTimeoutCache();
        return $this->items[$token] ?? [];
    }

    /**
     * 获取缓存列表
     * @param bool $clear 强制清理
     * @return array
     */
    private function getCacheList(bool $clear = false): array
    {
        $this->items = $this->app->cache->get($this->name , []);
        if ( $clear ) $this->items = $this->clearTimeoutCache();
        return $this->items;
    }

    /**
     * 清理超时的缓存
     * @return array
     */
    private function clearTimeoutCache(): array
    {
        $time = time();
        foreach ( $this->items as $key => $item ) {
            if ( empty($item['time']) || $item['time'] + $this->expire < $time ) {
                unset($this->items[$key]);
            }
        }
        if ( count($this->items) > 999 ) {
            $this->items = array_slice($this->items , -999);
        }
        return $this->items;
    }
}