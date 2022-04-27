<?php
declare (strict_types = 1);

namespace hutphp\helper;

use think\Model;
use hutphp\Helper;
use think\db\BaseQuery;

class PageHelper extends Helper
{

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     */
    public function init(string|BaseQuery|Model $dbQuery , bool $page = true , bool $display = true , $total = false , int $limit = 0 , string $template = ''): array
    {
        if ( $page ) {
            $limits = [10 , 20 , 30 , 40 , 50 , 60 , 70 , 80 , 90 , 100 , 110 , 120 , 130 , 140 , 150 , 160 , 170 , 180 , 190 , 200];
            if ( $limit <= 1 ) {
                $limit = $this->app->request->get('limit' , $this->app->cookie->get('limit' , 20));
                if ( in_array($limit , $limits) && intval($this->app->request->get('not_cache_limit' , 0)) < 1 ) {
                    $this->app->cookie->set('limit' , ($limit = intval($limit >= 5 ? $limit : 20)) . '');
                }
            }
            $get    = $this->app->request->get();
            $prefix = '';
            // 生成分页数据
            $data   = ($paginate = $this->autoSortQuery($dbQuery)->paginate(['list_rows' => $limit , 'query' => $get] , $total))->toArray();
            $result = ['page' => ['limit' => $data['per_page'] , 'count' => $data['total'] , 'pages' => $data['last_page'] , 'current' => $data['current_page']] , 'data' => $data['data']];
            // 分页跳转参数
            $select = "<select onchange='location.href=this.options[this.selectedIndex].value'>";
            if ( in_array($limit , $limits) ) foreach ( $limits as $num ) {
                $url    = $this->app->request->baseUrl() . '?' . http_build_query(array_merge($get , ['limit' => $num , 'page' => 1]));
                $select .= sprintf(`<option data-num="%d" value="%s" %s >%d</option>` , $num , $prefix . $url , $limit === $num ? 'selected' : '' , $num);
            } else {
                $select .= "<option selected>{$limit}</option>";
            }
            $html = lang('hutphp_page_html' , [$data['total'] , "{$select}</select>" , $data['last_page'] , $data['current_page']]);
            $link = $paginate->render() ?: '';
            $this->class->assign('pagehtml' , "<div class='pagination-container nowrap'><span>{$html}</span>{$link}</div>");
        } else {
            $count  = (clone $this->autoSortQuery($dbQuery))->count();
            $result = ['data' => $this->autoSortQuery($dbQuery)->select()->toArray() , 'count' => $count];
        }
        if ( false !== $this->class->callback('_page_filter' , $result['list']) && $display ) {
            if ( $this->app->request->isJson() ) {
                $this->class->success('ok' , $result);
            } else {
                $this->class->fetch($template , $result);
            }
        }
        return $result;
    }
}