<?php

declare (strict_types = 1);

namespace hutphp\storage;

use hutphp\Storage;
use hutphp\Exception;

/**
 * 本地存储支持
 * Class LocalStorage
 * @package hutphp\storage
 */
class LocalStorage extends Storage
{

    /**
     * 初始化入口
     */
    public function initialize()
    {
        $type = config('storage.local.protocol') ?: 'follow';
        if ( $type === 'follow' ) $type = $this->app->request->scheme();
        $this->prefix = trim(dirname($this->app->request->baseFile(false)) , '\\/');
        if ( $type !== 'path' ) {
            $domain = config('storage.local.domain') ?: $this->app->request->host();
            if ( $type === 'auto' ) {
                $this->prefix = "//{$domain}";
            } else if ( in_array($type , ['http' , 'https']) ) {
                $this->prefix = "{$type}://{$domain}";
            }
        }
    }

    /**
     * 获取当前实例对象
     * @param null|string $name
     * @return static
     * @throws \hutphp\Exception
     */
    public static function instance(?string $name = null): static
    {
        return parent::instance('local');
    }

    /**
     * 文件储存在本地
     * @param string      $name    文件名称
     * @param string      $file    文件内容
     * @param boolean     $safe    安全模式
     * @param null|string $attname 下载名称
     * @return array
     */
    public function set(string $name , string $file , bool $safe = false , ?string $attname = null): array
    {
        try {
            $path = $this->path($name , $safe);
            file_exists(dirname($path)) || mkdir(dirname($path) , 0755 , true);
            if ( file_put_contents($path , $file) ) {
                return $this->info($name , $safe , $attname);
            }
        } catch (Exception $exception) {

        }
        return [];
    }

    /**
     * 根据文件名读取文件内容
     * @param string  $name 文件名称
     * @param boolean $safe 安全模式
     * @return string
     */
    public function get(string $name , bool $safe = false): string
    {
        if ( !$this->has($name , $safe) ) return '';
        return file_get_contents($this->path($name , $safe));
    }

    /**
     * 删除存储的文件
     * @param string  $name 文件名称
     * @param boolean $safe 安全模式
     * @return boolean
     */
    public function del(string $name , bool $safe = false): bool
    {
        if ( $this->has($name , $safe) ) {
            try {
                return unlink($this->path($name , $safe));
            } catch (\Exception $e) {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 检查文件是否已经存在
     * @param string  $name 文件名称
     * @param boolean $safe 安全模式
     * @return boolean
     */
    public function has(string $name , bool $safe = false): bool
    {
        return file_exists($this->path($name , $safe));
    }

    /**
     * 获取文件当前URL地址
     * @param string      $name    文件名称
     * @param boolean     $safe    安全模式
     * @param null|string $attname 下载名称
     * @return string
     */
    public function url(string $name , bool $safe = false , ?string $attname = null): string
    {
        return $safe ? $name : "{$this->prefix}/upload/{$this->delSuffix($name)}{$this->getSuffix($attname,$name)}";
    }

    /**
     * 获取文件存储路径
     * @param string  $name 文件名称
     * @param boolean $safe 安全模式
     * @return string
     */
    public function path(string $name , bool $safe = false): string
    {
        $root = $this->app->getRootPath();
        $path = $safe ? 'safe/upload' : 'public/upload';
        return strtr("{$root}{$path}/{$this->delSuffix($name)}" , '\\' , '/');
    }

    /**
     * 获取文件存储信息
     * @param string      $name    文件名称
     * @param boolean     $safe    安全模式
     * @param null|string $attname 下载名称
     * @return array
     */
    public function info(string $name , bool $safe = false , ?string $attname = null): array
    {
        return $this->has($name , $safe) ? [
            'url' => $this->url($name , $safe , $attname) ,
            'key' => "upload/{$name}" , 'file' => $this->path($name , $safe) ,
        ] : [];
    }

    /**
     * 获取文件上传地址
     * @return string
     */
    public function upload(): string
    {
        return url(config('storage.local.upload_api'))->build();
    }
}