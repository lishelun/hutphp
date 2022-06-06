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
use hutphp\storage\LocalStorage;

abstract class Storage
{
    protected App    $app;
    protected string $type;
    protected string $link;
    protected string $prefix;

    /**
     * @param \think\App $app
     */
    public function __construct(App $app)
    {
        $this->app  = $app;
        $this->link = config('storage.link_type');
        $this->initialize();
    }

    abstract protected function initialize();


    /**
     * @throws \Exception
     */
    public static function __callStatic($method , $arguments)
    {
        if ( method_exists($class = static::instance() , $method) ) {
            return call_user_func_array([$class , $method] , $arguments);
        } else {
            throw new \Exception("Storage method not exists: " . get_class($class) . "->{$method}()");
        }
    }

    /**
     * @param string|null $name
     * @return mixed
     * @throws \hutphp\Exception
     */
    public static function instance(?string $name = null): static
    {
        $class = ucfirst(strtolower($name ?: config('storage.type')));
        if ( class_exists($object = "hutphp\\storage\\{$class}Storage") ) {
            return Container::getInstance()->make($object);
        } else {
            throw new Exception("File driver [{$class}Storage] does not exist.");
        }
    }

    /**
     * 获取文件相对名称
     * @param string $url 文件访问链接
     * @param string $ext 文件后缀名称
     * @param string $pre 文件存储前缀
     * @param string $fun 名称规则方法
     * @return string
     */
    public static function name(string $url , string $ext = '' , string $pre = '' , string $fun = 'md5'): string
    {
        [$hah , $ext] = [$fun($url) , trim($ext ?: pathinfo($url , 4) , '.\\/')];
        $attr = [trim($pre , '.\\/') , substr($hah , 0 , 2) , substr($hah , 2 , 30)];
        return trim(join('/' , $attr) , '/') . '.' . strtolower($ext ?: 'tmp');
    }

    /**
     * 下载文件到本地
     * @param string  $url    文件URL地址
     * @param boolean $force  是否强制下载
     * @param integer $expire 文件保留时间
     * @return array
     */
    public static function down(string $url , bool $force = false , int $expire = 0): array
    {
        try {
            $file = LocalStorage::instance();
            $name = static::name($url , '' , 'down/');
            if ( empty($force) && $file->has($name) ) {
                if ( $expire < 1 || filemtime($file->path($name)) + $expire > time() ) {
                    return $file->info($name);
                }
            }
            return $file->set($name , static::curlGet($url));
        } catch (Exception $exception) {
            return ['url' => $url , 'hash' => md5($url) , 'key' => $url , 'file' => $url];
        }
    }

    /**
     * 根据文件后缀获取文件MINE
     * @param array|string $ext  文件后缀
     * @param array        $mime 文件信息
     * @return string
     */
    public static function mime(array|string $ext , array $mime = []): string
    {
        $mimes = static::mimes();
        foreach ( is_string($ext) ? explode(',' , $ext) : $ext as $ext ) {
            $mime[] = $mimes[strtolower($ext)] ?? 'application/octet-stream';
        }
        return join(',' , array_unique($mime));
    }

    /**
     * 获取所有文件的信息
     * @return array
     */
    public static function mimes(): array
    {
        static $mimes = [];
        if ( count($mimes) > 0 ) return $mimes;
        return $mimes = include __DIR__ . '/storage/bin/mimes.php';
    }

    /**
     * 使用CURL读取网络资源
     * @param string $url 资源地址
     * @return string
     */
    public static function curlGet(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch , CURLOPT_URL , $url);
        curl_setopt($ch , CURLOPT_HEADER , 0);
        curl_setopt($ch , CURLOPT_RETURNTRANSFER , 1);
        curl_setopt($ch , CURLOPT_FOLLOWLOCATION , 1);
        curl_setopt($ch , CURLOPT_SSL_VERIFYPEER , false);
        curl_setopt($ch , CURLOPT_SSL_VERIFYHOST , false);
        $content = curl_exec($ch);
        curl_close($ch);
        return $content ?: '';
    }

    /**
     * 获取下载链接后缀
     * @param null|string $attname  下载名称
     * @param null|string $filename 文件名称
     * @return string
     */
    protected function getSuffix(?string $attname = null , ?string $filename = null): string
    {
        $suffix = '';
        if ( is_string($filename) && stripos($this->link , 'compress') !== false ) {
            $compress  = [
                'LocalStorage'  => '' ,
                'QiniuStorage'  => '?imageslim' ,
                'TxcosStorage'  => '?imageMogr2/format/webp' ,
                'AliossStorage' => '?x-oss-process=image/format,webp' ,
            ];
            $class     = basename(get_class($this));
            $extension = strtolower(pathinfo($this->delSuffix($filename) , PATHINFO_EXTENSION));
            $suffix    = in_array($extension , ['png' , 'jpg' , 'jpeg']) ? ($compress[$class] ?? '') : '';
        }
        if ( is_string($attname) && strlen($attname) > 0 && stripos($this->link , 'full') !== false ) {
            $suffix .= ($suffix ? '&' : '?') . 'attname=' . urlencode($attname);
        }
        return $suffix;
    }

    /**
     * 获取文件基础名称
     * @param string $name 文件名称
     * @return string
     */
    protected function delSuffix(string $name): string
    {
        if ( str_contains($name , '?') ) {
            return strstr($name , '?' , true);
        }
        return $name;
    }

}