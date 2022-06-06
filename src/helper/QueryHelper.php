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

namespace hutphp\helper;

use hutphp\Helper;
use think\facade\Db;
use think\db\BaseQuery;

/**
 * 查询处理器
 *
 * @see \think\DbManager
 * @mixin \think\DbManager
 */
class QueryHelper extends Helper
{
    public array     $input;
    public BaseQuery $query;
    /**
     * Query方法返回值的方法列表
     * @var array|string[]
     */
    private array $methods = [
        'select' , 'find' , 'findOrEmpty' , 'findOrFail' , 'selectOrFail' ,
        'update' , 'insert' , 'delete' , 'save' , 'insertGetId' , 'insertAll' , 'useSoftDelete' ,
        'value' , 'column' , 'chunk' , 'cursor' ,
        'max' , 'min' , 'avg' , 'count' , 'sum' ,
        'inc' , 'dec' ,
        'paginateX' , 'page' , 'paginate' ,
        'selectOrFail' , 'findOrFail' , 'findOrEmpty' , 'getTable' , 'buildSql'
    ];

    /**
     * @param string|\think\Model|BaseQuery $query
     * @param array|null                    $input
     * @return $this
     */
    public function init(string|BaseQuery|\think\Model $query , array|null $input = null): QueryHelper
    {

        $this->query = $this->buildQuery($query);
        $this->input = $this->getRequestData($input);
        return $this;
    }

    /**
     * 返回thinkphp数据库查询构造器
     * @return BaseQuery
     */
    public function db(): BaseQuery
    {
        return $this->query;
    }

    /**
     * 相等查询条件
     * @param array|string      $fields 查询字段
     * @param array|string|null $input  输入类型或数据[]
     * @param string            $alias  分隔符
     * @return $this
     */
    public function equals(array|string $fields , array|string $input = null , string $alias = '#'): QueryHelper
    {
        $data = $this->getInput($input ?: $this->input);
        foreach ( is_array($fields) ? $fields : explode(',' , $fields) as $field ) {
            [$dk , $qk] = [$field , $field];
            if ( stripos($field , $alias) !== false ) {
                [$dk , $qk] = explode($alias , $field);
            }
            if ( isset($data[$qk]) && $data[$qk] !== '' ) {
                $this->query->where($dk , "{$data[$qk]}");
            }
        }
        return $this;
    }

    /**
     * 相似查询
     * @param        $fields
     * @param string $split
     * @param null   $input
     * @param string $alias
     * @return $this
     */
    public function like($fields , string $split = '' , $input = null , string $alias = '#'): QueryHelper
    {

        $data = $this->getInput($input ?: $this->input);
        foreach ( is_array($fields) ? $fields : explode(',' , $fields) as $field ) {
            [$dk , $qk] = [$field , $field];
            if ( stripos($field , $alias) !== false ) {
                [$dk , $qk] = explode($alias , $field);
            }
            if ( isset($data[$qk]) && $data[$qk] !== '' ) {
                $this->query->whereLike($dk , "%{$split}{$data[$qk]}{$split}%");
            }
        }
        return $this;
    }


    /**
     * 实例化分页管理器
     * @param boolean         $page     是否启用分页
     * @param boolean         $display  是否渲染模板
     * @param boolean|integer $total    集合分页记录数
     * @param integer         $limit    集合每页记录数
     * @param string          $template 模板文件名称
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function fetch_page(bool $page = true , bool $display = true , bool|int $total = false , int $limit = 0 , string $template = ''): array
    {
        return PageHelper::instance()->init($this->query , $page , $display , $total , $limit , $template);
    }

    public function page(int $page = 0 , int $limit = 0): BaseQuery
    {
        return $this->query->page($page , $limit);
    }


    /**
     * @param string|null  $order
     * @param integer|null $limit
     * @param integer|null $page
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function list(string $order = null , int $limit = null , int $page = null): array
    {
        $limit = $limit == null ? request()->param('limit/d') : $limit;
        $page  = $page == null ? request()->param('page/d') : $page;
        if ( method_exists($this->query , 'getTableFields') ) {
            $soft_delete_field = null;
            $fields            = $this->query->getTableFields();
            if ( in_array('delete_time' , $fields) ) $soft_delete_field = 'delete_time';
            else if ( in_array('deleted' , $fields) ) $soft_delete_field = 'deleted';
            else if ( in_array('is_deleted' , $fields) ) $soft_delete_field = 'is_deleted';
            if ( $soft_delete_field !== null ) {
                $this->query->whereRaw($soft_delete_field . 'IS NULL');
            }
        }
        $count = (clone $this->query)->count();
        if ( $order != null ) {
            $this->query->order($order);
        } else {
            $pk = $this->query->getPk();
            if ( $pk ) {
                $this->query->order($pk , 'desc');
            }
        }
        $list   = $this->query->page($page , $limit)->select();
        $result = ['data' => $list->toArray() , 'count' => $count];
        if ( false !== $this->class->callback('_list_filter' , $result) ) {
            $this->class->success('ok' , $result);
        }
        return $result;
    }

    /**
     * in区间查询
     * @param        $fields
     * @param string $split
     * @param null   $input
     * @param string $alias
     * @return $this
     */
    public function in($fields , string $split = ',' , $input = null , string $alias = '#'): QueryHelper
    {
        $data = $this->getInput($input ?: $this->input);
        foreach ( is_array($fields) ? $fields : explode(',' , $fields) as $field ) {
            [$dk , $qk] = [$field , $field];
            if ( stripos($field , $alias) !== false ) {
                [$dk , $qk] = explode($alias , $field);
            }
            if ( isset($data[$qk]) && $data[$qk] !== '' ) {
                $this->query->whereIn($dk , explode($split , $data[$qk]));
            }
        }
        return $this;
    }

    /**
     * 设置内容区间查询
     * @param array|string      $fields 查询字段
     * @param string            $split  输入分隔符
     * @param array|string|null $input  输入数据
     * @param string            $alias  别名分割符
     * @return $this
     */
    public function valueBetween(array|string $fields , string $split = ' ' , array|string $input = null , string $alias = '#'): QueryHelper
    {
        return $this->setBetweenWhere($fields , $split , $input , $alias);
    }

    /**
     * 设置日期时间区间查询
     * @param array|string      $fields 查询字段
     * @param string            $split  输入分隔符
     * @param array|string|null $input  输入数据
     * @param string            $alias  别名分割符
     * @return $this
     */
    public function dateBetween(array|string $fields , string $split = ' - ' , array|string $input = null , string $alias = '#'): QueryHelper
    {
        return $this->setBetweenWhere($fields , $split , $input , $alias , function ($value , $type) {
            if ( preg_match('#^\d{4}(-\d\d){2}\s+\d\d(:\d\d){2}$#' , $value) ) return $value;
            else return $type === 'after' ? "{$value} 23:59:59" : "{$value} 00:00:00";
        });
    }

    /**
     * 设置时间戳区间查询
     * @param array|string      $fields 查询字段
     * @param string            $split  输入分隔符
     * @param array|string|null $input  输入数据
     * @param string            $alias  别名分割符
     * @return $this
     */
    public function timeBetween(array|string $fields , string $split = ' - ' , array|string $input = null , string $alias = '#'): QueryHelper
    {
        return $this->setBetweenWhere($fields , $split , $input , $alias , function ($value , $type) {
            if ( preg_match('#^\d{4}(-\d\d){2}\s+\d\d(:\d\d){2}$#' , $value) ) return strtotime($value);
            else return $type === 'after' ? strtotime("{$value} 23:59:59") : strtotime("{$value} 00:00:00");
        });
    }

    /**
     * 设置区域查询条件
     * @param array|string      $fields   查询字段
     * @param string            $split    输入分隔符
     * @param array|string|null $input    输入数据
     * @param string            $alias    别名分割符
     * @param callable|null     $callback 回调函数
     * @return $this
     */
    private function setBetweenWhere(array|string $fields , string $split = ' ' , array|string $input = null , string $alias = '#' , ?callable $callback = null): QueryHelper
    {
        $data = $this->getInput($input ?: $this->input);
        foreach ( is_array($fields) ? $fields : explode(',' , $fields) as $field ) {
            [$dk , $qk] = [$field , $field];
            if ( stripos($field , $alias) !== false ) {
                [$dk , $qk] = explode($alias , $field);
            }
            if ( isset($data[$qk]) && $data[$qk] !== '' ) {
                [$begin , $after] = explode($split , $data[$qk]);
                if ( is_callable($callback) ) {
                    $after = call_user_func($callback , $after , 'after');
                    $begin = call_user_func($callback , $begin , 'begin');
                }
                $this->query->whereBetween($dk , [$begin , $after]);
            }
        }
        return $this;
    }

    /**
     * 返回请求内容
     * @param null $input
     * @return array
     */
    private function getInput($input = null): array
    {
        if ( is_array($input) ) {
            return $input;
        } else {
            $input = $input ?: 'request';
            return $this->app->request->$input();
        }
    }

    /**
     * 清空数据库
     * @return int|false
     */
    public function clearTable(): bool|int
    {
        $table = $this->query->getTable();
        return Db::execute("truncate table `{$table}`");
    }

    /**
     * @param  $name
     * @param  $args
     * @return mixed
     */
    public function __call($name , $args)
    {

        if ( is_callable($callable = [$this->query , $name]) ) {
            $result = call_user_func_array($callable , $args);
            if ( in_array($name , $this->methods) ) {
                return $result;
            }
        }
        return $this;
    }

}