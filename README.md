# HUTPHP
**ThinkPHP V6.0 Development Library**

**基于** [Anyon](https://gitee.com/zoujingli) / [ThinkLibrary](https://gitee.com/zoujingli/ThinkLibrary) **精简改造**


## 控制器

```PHp
<?php

class Controller extend \hutphp\Controller{

}

?>
```

## 模板

```php
$this->fetch($templage,$vars)
$this->assign($name,$value);  
$this->assign(['name1'=>'val1','name2'=>'val2']);
```

## 查询

```php

$this->query('tableName')->where("id",'1')->select();
$this->query('tableName')->page_fetch();
$this->query('tableName')->like('table_filed_id#param_id,name#name')
$this->query('tableName')->equal('table_filed_id#param_id,name#name');
$this->query('tableName')->list('order','page','limit');
$this->query('tableName')->equal('table_filed_id#param_id');
$this->query('tableName')->valueBetween('id#param_id');
$this->query('tableName')->dateBetween('datetime#param_datetime');
$this->query('tableName')->timeBetween('newstime#param_newstime');
```

## 表单

```php
$this->_vali();

// 表单显示及数据更新
$this->_form($query, $template, $pk , $where, $data);
//表单回调
[_ACTION]_form_filter(array &$data)
[_ACTION]_form_result(bool $result, array $data)
    
// 数据删除处理
$this->_deleted($query);

// 数据禁用处理
$this->_save($query, ['status'=>'0']);
// 数据启用处理
$this->_save($query, ['status'=>'1']);
```



## 文件

文件存储支持<u>**本地服务器存储,七牛云存储管理,阿里云OSS存储,腾讯云COS存储**</u>。

>   -    文件存储默认使用文件`hash`命名，同一个文件只会存储一份；
>   -   支持文件秒传，当文件已经上传到服务之后，再次上传同一个文件时将立即成功；
>   -   支持以日期+随机的方式命名，注意此方法不支持秒传功能；
>   -   所有文件上传之后，将统一返回`url`可访问的链接地址，直接存储到内容即可访问；
>   -   更多文件规则可以在系统后台文件管理处配置参数，不同的存储方式配置不同的参数；

```php
use hutphp\Storage;

$content = '文件内容';
$filename = '文件名称（支持路径）';
$location = '文件远程链接，如：https://www.baidu.com/favicon.ico';

$result = Storage::instance()->set($filename, $content); // 上传文件
$result = Storage::instance()->get($filename); // 读取文件
$result = Storage::instance()->del($filename); // 删除文件
$result = Storage::instance()->has($filename); // 判断是否存在
$result = Storage::instance()->url($filename); // 生成文件链接
$result = Storage::instance()->info($filename); // 获取文件参数
$result = Storage::instance()->down($location); // 下载远程文件到本地
```

关于参数`safe`为存储文件到安全目录，不允许直接使用`url`访问，主要用于上传一些私有文件。

**当然也可以指定存储引擎操作文件**：

```php
// 本地服务器文件操作
use hutphp\storage\LocalStorage;

$result = LocalStorage::instance()->set($filename, $content, $safe); // 上传文件
$result = LocalStorage::instance()->get($filename, $safe); // 读取文件
$result = LocalStorage::instance()->del($filename, $safe); // 删除文件
$result = LocalStorage::instance()->has($filename, $safe); // 判断是否存在
$result = LocalStorage::instance()->url($filename, $safe); // 生成文件链接
$result = LocalStorage::instance()->info($filename, $safe); // 获取文件参数
$result = LocalStorage::instance()->down($url); // 下载远程文件到本地

// 阿里云 OSS 存储
use hutphp\storage\AliossStorage;

$result = AliossStorage::instance()->set($filename, $content); // 上传文件
$result = AliossStorage::instance()->get($filename); // 读取文件
$result = AliossStorage::instance()->del($filename); // 删除文件
$result = AliossStorage::instance()->has($filename); // 判断是否存在
$result = AliossStorage::instance()->url($filename); // 生成文件链接
$result = AliossStorage::instance()->info($filename); // 获取文件参数

// 腾讯云 COS 存储
use hutphp\storage\TxcosStorage;

$result = TxcosStorage::instance()->set($filename, $content); // 上传文件
$result = TxcosStorage::instance()->get($filename); // 读取文件
$result = TxcosStorage::instance()->del($filename); // 删除文件
$result = TxcosStorage::instance()->has($filename); // 判断是否存在
$result = TxcosStorage::instance()->url($filename); // 生成文件链接
$result = TxcosStorage::instance()->info($filename); // 获取文件参数


// 七牛云存储
use hutphp\storage\QiniuStorage;

$result = QiniuStorage::instance()->set($filename, $content); // 上传文件
$result = QiniuStorage::instance()->get($filename); // 读取文件
$result = QiniuStorage::instance()->del($filename); // 删除文件
$result = QiniuStorage::instance()->has($filename); // 判断是否存在
$result = QiniuStorage::instance()->url($filename); // 生成文件链接
$result = QiniuStorage::instance()->info($filename); // 获取文件参数
```



也可以使用助手函数

```php
storage()->get($filename); //默认配置读取文件
storage('local')->get($filename); //本地存储读取文件
storage('alioss')->get($filename); //本地存储读取文件
storage('txcos')->get($filename); //本地存储读取文件
storage('qiniu')->get($filename); //本地存储读取文件
```

## JWT

```php
use hutphp\extend\JWTHelper

//编码
JWTHelper::instance()->encode(array $data,int $exp = 86400);//:string
//解码
JWTHelper::instance()->decode($jwtToken);//:object|bool

```

## Table

```php
use hutphp\helper\TableHelper;
$table=TableHelper::instance()->init('SomeTableName');
$table->createTable('id',true);
$table->removeTable();
$table->clearTable(auto_increment: int=1);
$table->optimizeTable();
$table->addColumn(name,type,length,isnull,defaultval,auto_increment,unsigned,comment,after,charset,order,binary);
$table->removeColumn(name);
$table->changeColumn(name,new_name ,type,length,isnull,defaultval,auto_increment,unsigned,comment,after,charset,order,binary)
$table->addIndex('name'/*or [name1,name2]*/);
$table->removeIndex(index_name);
$table->removeIndexByField('name'/*or [name1,name2]*/);
```

