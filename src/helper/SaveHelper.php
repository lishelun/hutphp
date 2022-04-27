<?php
declare (strict_types = 1);

namespace hutphp\helper;

use hutphp\Helper;

class SaveHelper extends Helper
{
    /**
     * 逻辑器初始化
     * @param        $dbQuery
     * @param array  $data  表单扩展数据
     * @param string $pk    数据对象主键
     * @param array  $where 额外更新条件
     * @return bool
     */
    public function init($dbQuery , array $data = [] , string $pk = '' , array $where = []): bool
    {
        $query = $this->buildQuery($dbQuery)->master()->strict(false);
        $pk    = $pk ?: ($query->getPk() ?: 'id');
        $data  = $data ?: $this->app->request->post();
        $value = $this->app->request->post($pk);

        // 主键限制处理
        if ( !isset($where[$pk]) && !is_null($value) ) {
            $query->whereIn($pk , str2arr($value));
            if ( isset($data) ) unset($data[$pk]);
        }

        // 前置回调处理
        if ( false === $this->class->callback('_save_filter' , $query , $data) ) {
            return false;
        }

        // 检查原始数据
        $model  = $query->where($where)->findOrEmpty();
        $result = $model->save($data);
        if ( $result && method_exists($model , 'onAdminSave') ) {
            $model->onAdminSave(strval($value));
        }

        // 结果回调处理
        if ( false === $this->class->callback('_save_result' , $result , $model) ) {
            return $result;
        }

        // 回复前端结果
        if ( $result !== false ) {
            $this->class->success(lang('hutphp_save_success'));
        } else {
            $this->class->error(lang('hutphp_save_error'));
        }
        return false;
    }
}