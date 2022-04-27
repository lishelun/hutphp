<?php

declare (strict_types = 1);

namespace hutphp;

use think\App;
use think\Model;
use think\Request;
use think\db\BaseQuery;
use hutphp\helper\FormHelper;
use hutphp\helper\SaveHelper;
use hutphp\helper\QueryHelper;
use hutphp\helper\TokenHelper;
use hutphp\helper\DeleteHelper;
use hutphp\helper\ValidateHelper;

class Controller extends \stdClass
{
    public bool       $csrf         = false;
    public string     $csrf_message = 'csrf error!';
    protected App     $app;
    protected Request $request;
    /**
     * 中间件
     * @var array
     */
    protected array $middleware = [];
    /**
     * 表名
     * @var string
     */
    protected string $table = '';
    /**
     * 表主键
     * @var string
     */
    protected string $pk = '';

    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $app->request;
        $this->app->bind('hutphp\Controller' , $this);
        if ( in_array($this->request->action() , get_class_methods(__CLASS__)) ) {
            $this->error('Access without permission.');
        }
        $this->initialize();
    }

    public function initialize()
    {
    }

    /**
     * json返回成功
     * @param string $msg
     * @param array  $data
     * @param int    $code
     */
    public function success(string $msg = '操作成功' , array $data = [] , int $code = 0)
    {
        $msg = $msg == '操作成功' ? lang('hutphp_success') : $msg;
        if ( $this->csrf ) {
            TokenHelper::instance()->clear();
        }
        abort(
            json(
                array_merge(['code' => $code , 'msg' => $msg] , $data)
            )
        );
    }

    /**
     * json返回失败
     * @param string $msg
     * @param array  $data
     * @param int    $code
     */
    public function error(string $msg = '操作失败' , array $data = [] , int $code = -1)
    {
        $msg = $msg == '操作失败' ? lang('hutphp_error') : $msg;
        abort(
            json(
                array_merge(['code' => $code , 'msg' => $msg] , $data)
            )
        );
    }

    /**
     * 跳转重定向
     * @param string $url
     * @param int    $code
     */
    public function redirect(string $url , int $code = 301)
    {
        abort(redirect($url , $code));
    }

    /**
     * 模板渲染
     * @param string      $tpl
     * @param array       $vars
     * @param string|null $node
     */
    public function fetch(string $tpl = '' , array $vars = [] , ?string $node = null): void
    {
        foreach ( $this as $name => $value ) {
            $vars[$name] = $value;
        }
        $this->csrf ? TokenHelper::instance()->fetchTemplate($tpl , $vars , $node) : abort(view($tpl , $vars));
    }

    /**
     * 模板赋值
     * @param array|string      $name
     * @param array|string|null $value
     * @return $this
     */
    public function assign(array|string $name , array|string $value = null): Controller
    {
        if ( is_string($name) ) {
            $this->$name = $value;
        } else if ( is_array($name) ) {
            foreach ( $name as $k => $v ) {
                if ( is_string($k) ) {
                    $this->$k = $v;
                }
            }
        }
        return $this;
    }

    /**
     * 获得数据库表名
     */
    protected function _getTableName($name = '')
    {
        return $name ?: ($this->table ?? null);
    }

    /**
     * 数据回调处理机制
     * @param string $name 回调方法名称
     * @param mixed  $one  回调引用参数1
     * @param mixed  $two  回调引用参数2
     * @param mixed  $thr  回调引用参数3
     * @return boolean
     */
    public function callback(string $name , &$one = [] , &$two = [] , &$thr = []): bool
    {
        if ( is_callable($name) ) return call_user_func($name , $this , $one , $two , $thr);
        foreach ( ["_{$this->app->request->action()}{$name}" , $name] as $method ) {
            if ( method_exists($this , $method) && false === $this->$method($one , $two , $thr) ) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @param string $table
     */
    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    /**
     * 查询助手
     * @param string|Model|BaseQuery $name
     * @param null                   $input
     * @return \hutphp\helper\QueryHelper
     */
    public function query(string|Model|BaseQuery $name = '' , $input = null): QueryHelper
    {
        return QueryHelper::instance()->init($this->_getTableName($name) , $input);
    }

    /**
     * 模型助手
     * @param string|Model|BaseQuery $name
     * @param array                  $data
     * @param string                 $connection
     * @return \think\Model
     */
    public function _model(string|Model|BaseQuery $name , array $data = [] , string $connection = ''): \think\Model
    {
        return Helper::buildModel($this->_getTableName($name) , $data , $connection);
    }

    /**
     * 快捷输入并验证（ 支持 规则 # 别名 ）
     * @param array         $rules 验证规则（ 验证信息数组 ）
     * @param array|string  $type  输入方式 ( post. 或 get. )
     * @param callable|null $callable
     * @return array
     */
    protected function _vali(array $rules , array|string $type = '' , callable $callable = null): array
    {
        return ValidateHelper::instance()->init($rules , $type , $callable);
    }

    /**
     * 快捷删除逻辑器
     * @param string|Model|BaseQuery $dbQuery
     * @param string                 $field 数据对象主键
     * @param array                  $where 额外更新条件
     * @return boolean|null
     * @throws \think\db\exception\DbException
     */
    protected function _delete(string|Model|BaseQuery $dbQuery , string $field = '' , array $where = []): ?bool
    {
        return DeleteHelper::instance()->init($dbQuery , $field , $where);
    }

    /**
     * 快捷表单逻辑器
     * @param string|Model|BaseQuery $dbQuery
     * @param string                 $template 模板名称
     * @param string                 $field    指定数据对象主键
     * @param array                  $where    额外更新条件
     * @param array                  $data     表单扩展数据
     * @return array|boolean
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function _form(string|Model|BaseQuery $dbQuery , string $template = '' , string $field = '' , array $where = [] , array $data = []): bool|array
    {
        return FormHelper::instance()->init($dbQuery , $template , $field , $where , $data);
    }

    /**
     * 快捷更新逻辑器
     * @param string|BaseQuery|Model $query
     * @param array                  $data  表单扩展数据
     * @param string                 $field 数据对象主键
     * @param array                  $where 额外更新条件
     * @return boolean
     */
    protected function _save(string|BaseQuery|Model $query , array $data = [] , string $field = '' , array $where = []): bool
    {
        return SaveHelper::instance()->init($query , $data , $field , $where);
    }

    /**
     * 检查表单令牌验证
     * @param boolean $return 是否返回结果
     * @return boolean
     */
    protected function _csrf(bool $return = false): bool
    {
        return TokenHelper::instance()->init($return);
    }
}