<?php

declare (strict_types=1);

namespace hutphp\helper;

use hutphp\Controller;
use hutphp\Helper;
use think\App;
use think\facade\Db;

/**
 * 数据表助手
 * TableHelper::instance()->init('table_name');
 */
class TableHelper extends Helper
{
    protected string $prefix;
    protected array $config;
    protected string $table;

    /**
     * 构造函数
     * @param \hutphp\Controller $controller
     * @param \think\App         $app
     */
    public function __construct(Controller $controller , App $app)
    {
        $this->config = dbconfig(true);
        $this->prefix = dbtbpre(true);
        parent::__construct($controller , $app);
    }

    /**
     * 初始化实例
     * @param string $table
     * @return $this
     */
    public function init(string $table): static
    {
        return $this->setTable($table);
    }

    /**
     * 设置表名
     * @param string $name
     * @return $this
     */
    public function setTable(string $name): static
    {
        $this->table = $name;
        return $this;
    }

    /**
     * 获取表名
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * 创建表
     * @param string $pk   主键
     * @param bool   $auto 是否自增
     * @return bool
     * @throws \think\db\exception\DbException
     */
    public function createTable(string $pk = 'id' , bool $auto = true): bool
    {
        return DB::execute('CREATE TABLE IF NOT EXISTS `:table` (`:pk`  int NOT NULL :auto_increment ,PRIMARY KEY (`:pk`));' , ['table' => $this->table , 'pk' => $pk , 'auto_increment' => $auto ? 'AUTO_INCREMENT' : '']) !== 0;
    }


    /**
     * 清空表
     * @param int $auto_increment
     * @return bool
     * @throws \think\db\exception\DbException
     */
    public function clearTable(int $auto_increment = 1): bool
    {
        $result = Db::execute('TRUNCATE TABLE `:table`;' , ['table' => $this->table]) !== 0;
        $this->setIncrement($auto_increment);
        $this->optimizeTable();
        return $result;
    }

    /**
     * 删除表
     * @return bool
     * @throws \think\db\exception\DbException
     */
    public function removeTable(): bool
    {
        return Db::execute('DROP TABLE IF EXISTS `:table`;' , ['table' => $this->table]) !== 0;
    }

    /**
     * 优化表
     * @return bool
     * @throws \think\db\exception\DbException
     */
    public function optimizeTable(): bool
    {
        return Db::execute('OPTIMIZE table :table ;' , ['table' => $this->table]) !== 0;
    }

    /**
     * 设置自增值
     * @param int $increment
     * @return bool
     * @throws \think\db\exception\DbException
     */
    public function setIncrement(int $increment = 1): bool
    {
        return Db::execute('ALTER TABLE :table AUTO_INCREMENT=:increment ;' , ['table' => $this->table , 'increment' => $increment]) !== 0;
    }

    /**
     * 添加字段
     * @param string    $name           字段名
     * @param string    $type           字段类型
     * @param array|int $length         字段长度
     * @param bool      $isnull         是NULL
     * @param string    $default        默认值
     * @param bool      $auto_increment 是否自增值
     * @param bool      $unsigned       是否无符号
     * @param string    $comment        注释
     * @param string    $after          某字段之后
     * @param string    $charset        字符集
     * @param string    $order          排序规则
     * @param bool      $binary         二进制
     * @return bool
     * @throws \think\db\exception\DbException
     */
    public function addColumn(string $name , string $type = 'varchar' , array|int $length = 255 , bool $isnull = true , string $default = '' , bool $auto_increment = false , bool $unsigned = false , string $comment = '' , string $after = '' , string $charset = '' , string $order = '' , bool $binary = false): bool
    {
        //特殊类型字段无需长度限制
        $length = in_array($type , ['text' , 'mediumtext' , 'tinytext' , 'longtext' , 'date' , 'year' , 'tinyblob' , 'blob' , 'longblob' , 'mediumblob' , 'json' , 'geometrycollection' , 'multipolygon' , 'multilinestring' , 'multipoint' , 'geometry' , 'polygon' , '']) ? '' : $length;
        $bind = [
            'after' => $after ? " AFTER `{$after}`" : '' ,
            'charset' => $charset ? " CHARACTER SET {$charset}" : '' ,
            'order' => $order ? " COLLATE {$order}" : '' ,
            'length' => $length ,
            'isnull' => $isnull ? ' NULL' : ' NOT NULL' ,
            'auto_increment' => $auto_increment ? ' AUTO_INCREMENT' : '' ,
            'unsigned' => $unsigned ? ' UNSIGNED' : '' ,
            'default' => $default ? "DEFAULT '{$default}'" : '' ,
            'table' => $this->table ,
            'name' => $name ,
            'comment' => $comment ? "COMMENT '{$comment}'" : '' ,
            'binary' => $binary ? 'BINARY' : ''
        ];
        return Db::execute('ALTER TABLE `:table` ADD COLUMN `:name`  :type:length :binary :charset :order :unsigned :isnull :after' , $bind) !== 0;
    }

    /**
     * @param string    $name           字段名
     * @param string    $new_name       新字段名 不更改为空
     * @param string    $type           字段类型
     * @param array|int $length         字段长度
     * @param bool      $isnull         是NULL
     * @param string    $default        默认值
     * @param bool      $auto_increment 是否自增值
     * @param bool      $unsigned       是否无符号
     * @param string    $comment        注释
     * @param string    $after          某字段之后
     * @param string    $charset        字符集
     * @param string    $order          排序规则
     * @param bool      $binary         二进制
     * @return bool
     * @throws \think\db\exception\DbException
     */
    public function changeColumn(string $name , string $new_name = '' , string $type = 'varchar' , array|int $length = 255 , bool $isnull = true , string $default = '' , bool $auto_increment = false , bool $unsigned = false , string $comment = '' , string $after = '' , string $charset = '' , string $order = '' , bool $binary = false): bool
    {
        //特殊类型字段无需长度限制
        $length = in_array($type , ['text' , 'mediumtext' , 'tinytext' , 'longtext' , 'date' , 'year' , 'tinyblob' , 'blob' , 'longblob' , 'mediumblob' , 'json' , 'geometrycollection' , 'multipolygon' , 'multilinestring' , 'multipoint' , 'geometry' , 'polygon' , '']) ? '' : $length;
        $bind = [
            'after' => $after ? " AFTER `{$after}`" : '' ,
            'charset' => $charset ? " CHARACTER SET {$charset}" : '' ,
            'order' => $order ? " COLLATE {$order}" : '' ,
            'length' => $length ,
            'isnull' => $isnull ? ' NULL' : ' NOT NULL' ,
            'auto_increment' => $auto_increment ? ' AUTO_INCREMENT' : '' ,
            'unsigned' => $unsigned ? ' UNSIGNED' : '' ,
            'default' => $default ? "DEFAULT '{$default}'" : '' ,
            'table' => $this->table ,
            'name' => $name ,
            'comment' => $comment ? "COMMENT '{$comment}'" : '' ,
            'binary' => $binary ? 'BINARY' : '' ,
            'new_name' => $new_name && $new_name != $name ? "`{$new_name}`" : '' ,
            'change' => $new_name ? 'CHANGE' : 'MODIFY'
        ];
        return Db::execute('ALTER TABLE `:table` :change COLUMN `:name` :new_name  :type:length :binary :charset :order :unsigned :isnull :after' , $bind) !== 0;
    }

    /**
     * 删除字段
     * @param string $name
     * @return bool
     * @throws \think\db\exception\DbException
     */
    public function removeColumn(string $name): bool
    {
        return Db::execute('ALTER TABLE `:table` DROP COLUMN `name`;' , ['table' => $this->table , 'name' => $name]) !== 0;
    }

    /**
     * 添加索引
     * @param array|string $field      字段
     * @param string       $index_name 索引名 可为空
     * @param string       $type       索引类型
     * @param string       $using      索引方法
     * @return bool
     * @throws \think\db\exception\DbException
     */
    public function addIndex(array|string $field , string $index_name = '' , string $type = 'NORMAL' , string $using = 'BTREE'): bool
    {
        $using = strtoupper($type) == 'FULLTEXT' ? '' : 'USING ' . $using;
        $bind = ['table' => $this->table , 'type' => $type , 'index_name' => $index_name ?: $this->getIndexName($field) , 'fields' => $this->getIndexFieldText($field) , 'using' => $using];
        return Db::execute('ALTER TABLE `:table` ADD :type INDEX `:index_name` (:fields) :using ;' , $bind) !== 0;
    }

    /**
     * 按索引名删除索引
     * @param $name
     * @return bool
     * @throws \think\db\exception\DbException
     */
    public function removeIndex($name): bool
    {
        return Db::execute('ALTER TABLE `:table` DROP INDEX `:name`;' , ['table' => $this->table , 'name' => $name]) !== 0;
    }

    /**
     * 按字段删除索引
     * @param array|string $field
     * @return bool
     * @throws \think\db\exception\DbException
     */
    public function removeIndexByField(array|string $field): bool
    {
        return $this->removeIndex($this->getIndexName($field));
    }

    /**
     * 按字段获得索引字段名
     * @param array|string $field
     * @return string
     */
    public function getIndexName(array|string $field): string
    {
        if ( is_array($field) ) {
            $field = array_map(function ($val) {
                return strtolower($val);
            } , $field);
            sort($field);
        }
        return is_string($field) ? 'hut_' . $field : 'hut_' . implode('_' , $field);
    }

    /**
     * 获得索引字段文本
     * @param array|string $field
     * @return string
     */
    public function getIndexFieldText(array|string $field): string
    {
        return is_string($field) ? "`{$field}`" : implode(',' , array_map(function ($val) {
            return "`{$val}`";
        } , $field));
    }

}