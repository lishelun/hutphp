<?php
declare (strict_types=1);

namespace hutphp\helper;

use hutphp\Helper;
use think\Model;
use think\db\BaseQuery;

/**
 * 表单助手
 * Class FormHelper
 */
class FormHelper extends Helper
{
    /**
     * 逻辑器初始化
     * @param string|BaseQuery|Model $dbQuery
     * @param string                 $template 视图模板名称
     * @param string                 $field    指定数据主键
     * @param array                  $where    额外更新条件
     * @param array                  $edata    表单扩展数据
     * @return array|boolean
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function init(string|BaseQuery|Model $dbQuery , string $template = '' , string $field = '' , array $where = [] , array $edata = []): bool|array
    {
        $query = $this->buildQuery($dbQuery);
        $field = $field ?: ($query->getPk() ?: 'id');
        $value = $edata[$field] ?? input($field);
        if ( $this->app->request->isGet() ) {
            if ( $value !== null ) {
                $exist = $query->where([$field => $value])->where($where)->find();
                if ( $exist instanceof Model ) $exist = $exist->toArray();
                $edata = array_merge($edata , $exist ?: []);
            }
            if ( false !== $this->class->callback('_form_filter' , $edata) ) {
                $this->class->fetch($template , ['r' => $edata]);
            } else {
                return $edata;
            }
        }
        else if ( $this->app->request->isPost() ) {
            $edata = array_merge($this->app->request->post() , $edata);
            if ( false !== $this->class->callback('_form_filter' , $edata , $where) ) {
                $result = data_save($query , $edata , $field , $where);
                if ( false !== $this->class->callback('_form_result' , $edata , $result) ) {
                    if ( $result === false ) {
                        $this->class->success(lang('hutphp_form_success'));
                    } else {
                        $this->class->error(lang('hutphp_form_error'));
                    }
                }
                return $result;
            }
        }
        return false;
    }

}