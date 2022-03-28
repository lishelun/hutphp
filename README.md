# HUTPHP
ThinkPHP v6.0 Development Library

基础控制器

```PHp
<?php

class Controller extend \hutphp\BaseController{

}

?>
```

模板

```php
$this->fetch($templage,$vars)
$this->assign($name,$value);  
$this->assign(['name1'=>'val1','name2'=>'val2']);
```





查询助手

```php

$this->query('tableName')->where("id",'1')->select();
$this->query('tableName')->page_fetch();
$this->query('tableName')->like('table_filed_id#param_id,name#name')
$this->query('tableName')->equal('table_filed_id#param_id,name#name');
$this->query('tableName')->list([order,page,limit]);
$this->query('tableName')->equal('table_filed_id#param_id');
$this->query('tableName')->valueBetween('id#param_id');
$this->query('tableName')->dateBetween('datetime#param_datetime');
$this->query('tableName')->timeBetween('newstime#param_newstime');
```

验证

```php
$this->_vali();

// 表单显示及数据更新
$this->_form($dbQuery, $tplFile, $pkField , $where, $data);

// 数据删除处理
$this->_deleted($dbQuery);

// 数据禁用处理
$this->_save($dbQuery, ['status'=>'0']);

// 数据启用处理
$this->_save($dbQuery, ['status'=>'1']);
```

