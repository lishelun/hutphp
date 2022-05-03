<?php
declare (strict_types = 1);

namespace hutphp\helper;

use think\Model;
use hutphp\Helper;
use think\db\BaseQuery;

class DeleteHelper extends Helper
{
    /**
     * 逻辑器初始化
     * @param string|\think\db\BaseQuery|\think\Model $name
     * @param string                                  $pk
     * @param array                                   $where 额外更新条件
     * @return boolean|null
     */
    public function init(string|BaseQuery|Model $name , string $pk = '' , array $where = []): ?bool
    {
        $query = $this->buildQuery($name);
        $pk    = $pk ?: ($query->getPk() ?: 'id');
        $value = $this->app->request->param($pk , null);

        // 查询限制处理
        if ( !empty($where) ) $query->where($where);

        if ( !isset($where[$pk]) && is_string($value) ) {
            $query->whereIn($pk , str2arr($value));
        }
        if ( !isset($where[$pk]) && is_array($value) ) {
            $query->whereIn($pk , $value);
        }
        //兼容layui table 批量处理
        if ( !isset($where[$pk]) && $value == null ) {
            $post = $this->app->request->post('data' , null);
            if ( is_array($post) && count($post) > 0 ) {
                $ids = [];
                foreach ( $post as $v ) {
                    if ( isset($v[$pk]) && $v[$pk] ) {
                        $ids[] = intval($v[$pk]);
                    }
                }
                if ( $ids ) {
                    $query->whereIn($pk , $ids);
                }
            }
        }
        // 前置回调处理
        if ( false === $this->class->callback('_delete_filter' , $query , $where) ) {
            return false;
        }

        // 阻止危险操作
        if ( !$query->getOptions('where') ) {
            $this->class->error(lang('hutphp_disallow_unconditional_deletion'));
        }

        // 组装执行数据
        $data = [];
        if ( method_exists($query , 'getTableFields') ) {
            $fields = $query->getTableFields();
            if ( in_array('deleted' , $fields) ) $data['deleted'] = 1;
            if ( in_array('is_deleted' , $fields) ) $data['is_deleted'] = 1;
            if ( isset($data['deleted']) || isset($data['is_deleted']) ) {
                if ( in_array('deleted_at' , $fields) ) $data['deleted_at'] = date('Y-m-d H:i:s');
                if ( in_array('deleted_time' , $fields) ) $data['deleted_time'] = time();
            }
        }

        // 执行删除操作
        if ( $result = (empty($data) ? $query->delete() : $query->update($data)) !== 0 ) {
            // 模型自定义事件回调
            $model = $query->getModel();
            if ( method_exists($model , 'onAdminDelete') ) {
                $model->onAdminDelete(strval($value));
            }
        }

        // 结果回调处理
        if ( false === $this->class->callback('_delete_result' , $result) ) {
            return $result;
        }

        // 回复返回结果
        if ( $result !== false ) {
            $this->class->success(lang('hutphp_delete_success'));
        } else {
            $this->class->error(lang('hutphp_delete_error'));
        }
        return false;
    }
}