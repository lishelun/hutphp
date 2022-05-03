<?php

namespace hutphp;

use think\App;
use think\Request;
use think\Container;
use think\db\BaseQuery;

abstract class Helper
{

    /**
     * @var \think\App
     */
    protected App $app;
    /**
     * @var \hutphp\Controller
     */
    protected Controller $class;
    /**
     * @var \think\Request
     */
    protected Request $request;

    /**
     * 初始化
     * @param \hutphp\Controller $controller
     * @param \think\App         $app
     */
    public function __construct(Controller $controller , App $app)
    {
        $this->app     = $app;
        $this->class   = $controller;
        $this->request = $app->request;
    }

    /**
     * 获取工具实例
     * @param ...$args
     * @return static
     */
    public static function instance(...$args): static
    {
        return Container::getInstance()->invokeClass(static::class , $args);
    }

    /**
     * 创建数据库查询对象
     * @param string|\think\db\BaseQuery|\think\Model $query 数据库名|数据库查询对象|模型
     * @return \think\db\BaseQuery 数据库查询对象
     */
    public static function buildQuery(string|BaseQuery|\think\Model $query): BaseQuery
    {
        if ( is_string($query) ) {
            return static::buildModel($query)->db();
        } else if ( $query instanceof \think\Model ) {
            return $query->db();
        } else if ( $query instanceof BaseQuery ) {
            if ( !$query->getModel() ) {
                $query->model(static::buildModel($query->getName()));
            }
        }
        return $query;
    }


    /**
     * 创建模型
     * @param string $name       模型名称
     * @param array  $data       初始数据
     * @param string $connection 数据库链接
     * @return \think\Model 模型
     */
    public static function buildModel(string $name , array $data = [] , string $connection = ''): \think\Model
    {
        if ( str_contains($name , '\\') ) {
            if ( class_exists($name) ) {
                $model = new $name($data);
                if ( $model instanceof Model ) return $model;
            }
            $name = basename(str_replace('\\' , '/' , $name));
        }
        return VirtualModel::create($name , $data , $connection);
    }

    /**
     * 获得输入数据
     *
     * @param array|string|null $data 可以为设置的数组内容也可为get|post等request类的方法名
     *
     * @return array
     */
    public static function getRequestData(array|string|null $data = ''): array
    {
        if ( is_array($data) ) {
            return $data;
        } else {
            $input = $data ?: 'request';
            return app()->request->$input();
        }
    }

    /**
     * 获得数据库查询对象并自动对action为sort和含有sort字段的表更新排序
     * @throws \think\db\exception\DbException
     */
    public function autoSortQuery($table): BaseQuery
    {
        $query = static::buildQuery($table);
        if ( app('request')->isPost() && app('request')->post('action') === 'sort' ) {
            if ( method_exists($query , 'getTableFields') && in_array('sort' , $query->getTableFields()) ) {
                if ( app('request')->has($pk = $query->getPk() ?: 'id' , 'post') ) {
                    $map  = [$pk => app('request')->post($pk , 0)];
                    $data = ['sort' => intval(app('request')->post('sort' , 0))];
                    if ( $query->newQuery()->where($map)->update($data) !== 0 ) {
                        $this->class->success(lang('hutphp_sort_success'));
                    }
                }
            }
            $this->class->error(lang('hutphp_sort_success'));
        }
        return $query;
    }
}