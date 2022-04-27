<?php

declare (strict_types=1);

namespace hutphp\storage;

use hutphp\Exception;
use hutphp\extend\Curl;
use hutphp\Storage;

/**
 * 七牛云存储支持
 * Class QiniuStorage
 * @package hutphp\storage
 */
class QiniuStorage extends Storage
{

    /**
     * @var string
     */
    private string $bucket;
    /**
     * @var string
     */
    private string $accessKey;
    /**
     * @var string
     */
    private string $secretKey;
    /**
     * Protocol
     * @var string
     */
    private string $protocol;

    /**
     * 初始化入口
     * @throws \hutphp\Exception
     */
    public function initialize()
    {
        // 读取配置文件
        $this->bucket = config('storage.qiniu.bucket');
        $this->accessKey = config('storage.qiniu.access_key');
        $this->secretKey = config('storage.qiniu.secret_key');
        // 计算链接前缀
        $type = strtolower(config('storage.qiniu.protocol'));
        $domain = strtolower(config('storage.qiniu.domain'));
        if ( $type === 'auto' ) {
            $this->prefix = "//{$domain}";
            $this->protocol = 'https';
        } elseif ( in_array($type , ['http' , 'https']) ) {
            $this->prefix = "{$type}://{$domain}";
            $this->protocol = $type;
        } else throw new Exception('未配置七牛云URL域名哦');
    }

    /**
     * 获取当前实例对象
     * @param null|string $name
     * @return QiniuStorage
     * @throws \hutphp\Exception
     */
    public static function instance(?string $name = null): static
    {
        return parent::instance('qiniu');
    }

    /**
     * 上传文件内容
     * @param string      $name    文件名称
     * @param string      $file    文件内容
     * @param boolean     $safe    安全模式
     * @param null|string $attname 下载名称
     * @return array
     * @throws \hutphp\Exception
     */
    public function set(string $name , string $file , bool $safe = false , ?string $attname = null): array
    {
        $token = $this->buildUploadToken($name , 3600 , $attname);
        $data = ['key' => $name , 'token' => $token , 'fileName' => $name];
        $file = ['field' => "file" , 'name' => $name , 'content' => $file];
        $result = Curl::submit($this->upload() , $data , $file , [] , 'POST' , false);
        return json_decode($result , true);
    }


    /**
     * 根据文件名读取文件内容
     * @param string  $name 文件名称
     * @param boolean $safe 安全模式
     * @return string
     */
    public function get(string $name , bool $safe = false): string
    {
        $url = $this->url($name , $safe) . "?e=" . time();
        $token = "{$this->accessKey}:{$this->safeBase64(hash_hmac('sha1', $url, $this->secretKey, true))}";
        return static::curlGet("{$url}&token={$token}");
    }

    /**
     * 删除存储的文件
     * @param string  $name 文件名称
     * @param boolean $safe 安全模式
     * @return boolean
     */
    public function del(string $name , bool $safe = false): bool
    {
        [$EncodedEntryURI , $AccessToken] = $this->getAccessToken($name , 'delete');
        $data = json_decode(Curl::post("{$this->protocol}://rs.qiniu.com/delete/{$EncodedEntryURI}" , [] , [
            'headers' => ["Authorization:QBox {$AccessToken}"] ,
        ]) , true);
        return empty($data['error']);
    }

    /**
     * 检查文件是否已经存在
     * @param string  $name 文件名称
     * @param boolean $safe 安全模式
     * @return boolean
     */
    public function has(string $name , bool $safe = false): bool
    {
        return is_array($this->info($name , $safe));
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
        return "{$this->prefix}/{$this->delSuffix($name)}{$this->getSuffix($attname,$name)}";
    }

    /**
     * 获取文件存储路径
     * @param string  $name 文件名称
     * @param boolean $safe 安全模式
     * @return string
     */
    public function path(string $name , bool $safe = false): string
    {
        return $this->url($name , $safe);
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
        [$entry , $token] = $this->getAccessToken($name);
        $data = json_decode(Curl::get("{$this->protocol}://rs.qiniu.com/stat/{$entry}" , [] , ['headers' => ["Authorization: QBox {$token}"]]) , true);
        return isset($data['md5']) ? ['file' => $name , 'url' => $this->url($name , $safe , $attname) , 'key' => $name] : [];
    }

    /**
     * 获取文件上传地址
     * @return string
     * @throws \hutphp\Exception
     */
    public function upload(): string
    {
        $protocol = $this->protocol;
        switch ( config('storage.qiniu.region') ) {
            case '华东':
                return "{$protocol}://up.qiniup.com";
            case '华北':
                return "{$protocol}://up-z1.qiniup.com";
            case '华南':
                return "{$protocol}://up-z2.qiniup.com";
            case '北美':
                return "{$protocol}://up-na0.qiniup.com";
            case '东南亚':
                return "{$protocol}://up-as0.qiniup.com";
            default:
                throw new Exception('未配置七牛云空间区域哦');
        }
    }

    /**
     * 获取文件上传令牌
     * @param null|string $name    文件名称
     * @param integer     $expires 有效时间
     * @param null|string $attname 下载名称
     * @return string
     */
    public function buildUploadToken(?string $name = null , int $expires = 3600 , ?string $attname = null): string
    {
        $policy = $this->safeBase64(json_encode([
            "deadline" => time() + $expires , "scope" => is_null($name) ? $this->bucket : "{$this->bucket}:{$name}" ,
            'returnBody' => json_encode(['uploaded' => true , 'filename' => '$(key)' , 'url' => "{$this->prefix}/$(key){$this->getSuffix($attname,$name)}" , 'key' => $name , 'file' => $name] , JSON_UNESCAPED_UNICODE) ,
        ]));
        return "{$this->accessKey}:{$this->safeBase64(hash_hmac('sha1', $policy, $this->secretKey, true))}:{$policy}";
    }

    /**
     * URL安全的Base64编码
     * @param string $content
     * @return string
     */
    private function safeBase64(string $content): string
    {
        return str_replace(['+' , '/'] , ['-' , '_'] , base64_encode($content));
    }

    /**
     * 获取对象管理凭证
     * @param string $name 文件名称
     * @param string $type 操作类型
     * @return array
     */
    private function getAccessToken(string $name , string $type = 'stat'): array
    {
        $entry = $this->safeBase64("{$this->bucket}:{$name}");
        $sign = hash_hmac('sha1' , "/{$type}/{$entry}\n" , $this->secretKey , true);
        return [$entry , "{$this->accessKey}:{$this->safeBase64($sign)}"];
    }

    /**
     * 七牛云对象存储区域
     * @return array
     */
    public static function region(): array
    {
        return [
            'up.qiniup.com' => '华东' ,
            'up-z1.qiniup.com' => '华北' ,
            'up-z2.qiniup.com' => '华南' ,
            'up-na0.qiniup.com' => '北美' ,
            'up-as0.qiniup.com' => '东南亚' ,
        ];
    }
}