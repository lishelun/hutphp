<?php
return [
    'type' => 'local' ,
    //链接类型  可选none,或compress|full
    'link_type' => 'compress,full' ,
    //文件命名方式
    'name_type' => 'xmd5' ,
    //最大文件大小
    'max_file_size' => 1000*1000 ,
    'max_image_size' => 1000*1000 ,
    //允许上传扩展名
    'allow_exts' => 'doc,gif,icon,jpg,mp3,mp4,p12,pem,png,rar,xls,xlsx' ,
    /**
     * 本地存储
     */
    'local' => [
        //协议,可选:path,auto,http,https,follow
        'protocol' => 'https' ,
        //域名
        'domain' => 'domain.abc.com' ,
        //上传API地址
        'upload_api' => 'admin/api/upload' ,
    ] ,
    /**
     * 阿里云OSS存储
     */
    'alioss' => [
        //地域节点域名
        'point' => 'oss-cn-hangzhou.aliyuncs.com' ,
        //存储桶
        'bucket' => '' ,
        //访问密钥
        'access_key' => '' ,
        //秘密密钥
        'secret_key' => '' ,
        //协议 可选auto,http,https
        'protocol' => 'https' ,
        //域名
        'domain' => 'domain.abc.com' ,
    ] ,
    /**
     * 腾讯COS存储
     */
    'txcos' => [
        //地域节点域名
        'point' => 'cos.ap-beijing-1.myqcloud.com' ,
        //存储桶
        'bucket' => '' ,
        //访问密钥
        'access_key' => '' ,
        //秘密密钥
        'secret_key' => '' ,
        //协议 可选auto,http,https
        'protocol' => 'https' ,
        //域名
        'domain' => 'domain.abc.com' ,
    ] ,
    /**
     * 七牛云存储
     */
    'qiniu' => [
        //地域  可选 华东,华北,华南,北美,东南亚
        'region' => '华东' ,
        //存储桶
        'bucket' => '' ,
        //访问密钥
        'access_key' => '' ,
        //秘密密钥
        'secret_key' => '' ,
        //协议 可选auto,http,https
        'protocol' => 'https' ,
        //域名
        'domain' => 'abc.oss.aliyuncs.com' ,
    ] ,
];

