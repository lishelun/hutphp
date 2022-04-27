<?php

declare (strict_types = 1);

namespace hutphp\controller;

use Exception;
use hutphp\Storage;
use think\Response;
use hutphp\Controller;
use think\file\UploadedFile;
use hutphp\storage\LocalStorage;
use hutphp\storage\QiniuStorage;
use hutphp\storage\TxcosStorage;
use hutphp\storage\AliossStorage;
use think\exception\HttpResponseException;

class Upload extends Controller
{

    /**
     * 文件上传脚本
     * @return Response
     */
    public function index(): Response
    {
        $data = ['exts' => []];
        foreach ( str2arr(config('storage.allow_exts')) as $ext ) {
            $data['exts'][$ext] = Storage::mime($ext);
        }
        $template         = realpath(__DIR__ . '/../view/upload.js');
        $data['exts']     = json_encode($data['exts'] , JSON_UNESCAPED_UNICODE);
        $data['nameType'] = config('storage.name_type') ?: 'xmd5';
        return view($template , $data)->contentType('application/x-javascript');
    }

    /**
     * 文件上传检查
     * @login true
     * @throws \hutphp\Exception
     */
    public function state()
    {
        [$name , $safe] = [input('name') , $this->getSafe()];
        $data = ['uptype' => $this->getType() , 'safe' => intval($safe) , 'key' => input('key')];
        if ( $info = Storage::instance($data['uptype'])->info($data['key'] , $safe , $name) ) {
            $data['url'] = $info['url'];
            $data['key'] = $info['key'];
            $this->success('文件已经上传' , $data , 200);
        } else if ( 'local' === $data['uptype'] ) {
            $data['url']    = LocalStorage::instance()->url($data['key'] , $safe , $name);
            $data['server'] = LocalStorage::instance()->upload();
        } else if ( 'qiniu' === $data['uptype'] ) {
            $data['url']    = QiniuStorage::instance()->url($data['key'] , $safe , $name);
            $data['token']  = QiniuStorage::instance()->buildUploadToken($data['key'] , 3600 , $name);
            $data['server'] = QiniuStorage::instance()->upload();
        } else if ( 'alioss' === $data['uptype'] ) {
            $token                  = AliossStorage::instance()->buildUploadToken($data['key'] , 3600 , $name);
            $data['url']            = $token['siteurl'];
            $data['policy']         = $token['policy'];
            $data['signature']      = $token['signature'];
            $data['OSSAccessKeyId'] = $token['keyid'];
            $data['server']         = AliossStorage::instance()->upload();
        } else if ( 'txcos' === $data['uptype'] ) {
            $token                    = TxcosStorage::instance()->buildUploadToken($data['key'] , 3600 , $name);
            $data['url']              = $token['siteurl'];
            $data['q-ak']             = $token['q-ak'];
            $data['policy']           = $token['policy'];
            $data['q-key-time']       = $token['q-key-time'];
            $data['q-signature']      = $token['q-signature'];
            $data['q-sign-algorithm'] = $token['q-sign-algorithm'];
            $data['server']           = TxcosStorage::instance()->upload();
        }
        $this->success('获取上传授权参数' , $data , 404);
    }

    /**
     * 文件上传入口
     * @login true
     */
    public function file()
    {
        if ( !($file = $this->getFile())->isValid() ) {
            $this->error('文件上传异常，文件过大或未上传！');
        }
        $safeMode  = $this->getSafe();
        $extension = strtolower($file->getOriginalExtension());
        $saveName  = input('key') ?: Storage::name($file->getPathname() , $extension , '' , 'md5_file');
        // 检查文件名称是否合法
        if ( strpos($saveName , '../') !== false ) {
            $this->error('文件路径不能出现跳级操作！');
        }
        // 检查文件后缀是否被恶意修改
        if ( pathinfo(parse_url($saveName , PHP_URL_PATH) , PATHINFO_EXTENSION) !== $extension ) {
            $this->error('文件后缀异常，请重新上传文件！');
        }
        // 屏蔽禁止上传指定后缀的文件
        if ( !in_array($extension , str2arr(config('storage.allow_exts'))) ) {
            $this->error('文件类型受限，请在后台配置规则！');
        }
        if ( in_array($extension , ['sh' , 'asp' , 'bat' , 'cmd' , 'exe' , 'php']) ) {
            $this->error('文件安全保护，禁止上传可执行文件！');
        }
        //对图片文件验证处理
        if ( in_array($extension , ['jpg' , 'gif' , 'png' , 'bmp' , 'jpeg' , 'wbmp']) ) {
            if ( $file->getSize() > config('storage.max_image_size') ) {
                $this->error('图片大小超限！');
            }
            if ( $this->imgNotSafe($file->getRealPath()) ) {
                $this->error('图片未通过安全检查！');
            }
            [$width , $height] = getimagesize($file->getRealPath());
            if ( ($width < 1 || $height < 1) ) {
                $this->error('读取图片的尺寸失败！');
            }
        } else {
            if ( $file->getSize() > config('storage.max_file_size') ) {
                $this->error('文件大小超限！');
            }
        }
        try {
            if ( $this->getType() === 'local' ) {
                $local    = LocalStorage::instance();
                $distName = $local->path($saveName , $safeMode);
                $file->move(dirname($distName) , basename($distName));
                $info = $local->info($saveName , $safeMode , $file->getOriginalName());
            } else {
                $file_content = file_get_contents($file->getPathname());
                $info         = Storage::instance($this->getType())->set($saveName , $file_content , $safeMode , $file->getOriginalName());
            }
            if ( isset($info['url']) ) {
                $this->success('文件上传成功！' , ['url' => $safeMode ? $saveName : $info['url']]);
            } else {
                $this->error('文件处理失败，请稍候再试！');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 获取文件上传类型
     * @return boolean
     */
    private function getSafe(): bool
    {
        return boolval(input('safe' , '0'));
    }

    /**
     * 获取文件上传方式
     * @return string
     */
    private function getType(): string
    {
        $type = strtolower(input('uptype' , ''));
        if ( in_array($type , ['local' , 'qiniu' , 'alioss' , 'txcos']) ) {
            return $type;
        } else {
            return strtolower(config('storage.type'));
        }
    }

    /**
     * 获取本地文件对象
     * @return UploadedFile|void
     */
    private function getFile(): UploadedFile
    {
        try {
            $file = $this->request->file('file');
            if ( $file instanceof UploadedFile ) {
                return $file;
            } else {
                $this->error('未获取到上传的文件对象！');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            $this->error(lang($exception->getMessage()));
        }
    }

    /**
     * 检查图片是否安全
     * @param string $filename
     * @return boolean
     */
    private function imgNotSafe(string $filename): bool
    {
        $source = fopen($filename , 'rb');
        if ( ($size = filesize($filename)) > 512 ) {
            $hex = bin2hex(fread($source , 512));
            fseek($source , $size - 512);
            $hex .= bin2hex(fread($source , 512));
        } else {
            $hex = bin2hex(fread($source , $size));
        }
        if ( is_resource($source) ) fclose($source);
        $bins = hex2bin($hex);
        /* 匹配十六进制中的 <% ( ) %> 或 <? ( ) ?> 或 <script | /script> */
        foreach ( ['<?php ' , '<% ' , '<script '] as $key ) if ( stripos($bins , $key) !== false ) return true;
        return preg_match("/(3c25.*?28.*?29.*?253e)|(3c3f.*?28.*?29.*?3f3e)|(3C534352495054)|(2F5343524950543E)|(3C736372697074)|(2F7363726970743E)/is" , $hex);
    }
}
