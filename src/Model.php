<?php

declare (strict_types = 1);

namespace hutphp;

use think\Container;
use hutphp\helper\SaveHelper;
use hutphp\helper\FormHelper;
use hutphp\helper\QueryHelper;
use hutphp\helper\DeleteHelper;

abstract class Model extends \think\Model
{
    /**
     * 日志类型
     *
     * @var string|null
     */
    protected ?string $logType = null;

    /**
     * 日志名称
     *
     * @var string|null
     */
    protected ?string $logName = null;

    /**
     * 日志过滤
     *
     * @var callable
     */
    public static $logCall;

    /**
     * 创建模型实例
     *
     * @param array $data
     *
     * @return static
     */
    public static function make(array $data = []): static
    {
        return new static($data);
    }

    public static function mk(array $data = []): static
    {
        return static::make($data);
    }

    public static function instance(array $data = []): static
    {
        return static::make($data);
    }

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->logName = $this->logName ?: $this->name;
    }

    protected function getLogMethodStringList(): array
    {
        return [
            'onAdminSave'   => "修改{$this->logName}[%s]状态" ,
            'onAdminUpdate' => "更新{$this->logName}[%s]记录" ,
            'onAdminInsert' => "增加{$this->logName}[%s]成功" ,
            'onAdminDelete' => "删除{$this->logName}[%s]成功" ,
            'onInsert'      => "增加{$this->logName}[%s]成功" ,
            'onUpdate'      => "更新{$this->logName}[%s]成功" ,
            'onDelete'      => "删除{$this->logName}[%s]成功" ,
            'onSave'        => "修改{$this->logName}[%s]成功"
        ];
    }

    /**
     * 调用魔术方法
     *
     * @param string $method 方法名称
     * @param array  $args   调用参数
     *
     * @return $this|false|mixed
     */
    public function __call($method , $args)
    {

        $list = $this->getLogMethodStringList();
        if ( isset($list[$method]) ) {
            if ( $this->logType && $this->logName ) {
                $ids = $args[0] ?? '';
                if ( is_callable(static::$logCall) ) {
                    $ids = call_user_func(static::$logCall , $method , $ids , $this);
                }
                if ( config('app.open_hutcms_model_log' , false) ) {
                    function_exists('hut_log') && hut_log($this->logType , sprintf($list[$method] , $ids));
                }
            }
            return $this;
        } else {
            return parent::__call($method , $args);
        }
    }

    /**
     * 静态魔术方法
     *
     * @param string $method 方法名称
     * @param array  $args   调用参数
     *
     * @return mixed|false|integer|QueryHelper
     */
    public static function __callStatic($method , $args)
    {
        $helpers = [
            '_form'   => [FormHelper::class , 'init'] ,
            '_save'   => [SaveHelper::class , 'init'] ,
            '_query'  => [QueryHelper::class , 'init'] ,
            '_delete' => [DeleteHelper::class , 'init']
        ];
        if ( isset($helpers[$method]) ) {
            [$class , $method] = $helpers[$method];
            return Container::getInstance()->invokeClass($class)->$method(static::class , ...$args);
        } else {
            return parent::__callStatic($method , $args);
        }
    }
}