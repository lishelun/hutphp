<?php
declare (strict_types=1);

namespace hutphp\helper;

use hutphp\Helper;
use hutphp\service\TokenService;

class TokenHelper extends Helper
{

    /**
     * 初始化验证码器
     * @param boolean $return
     * @return boolean
     */
    public function init(bool $return = false): bool
    {
        $this->class->csrf_state = true;
        if ($this->app->request->isPost() && !TokenService::instance()->checkFormToken()) {
            if ($return) return false;
            $this->class->error($this->class->csrf_message ?: lang('hutphp_csrf_error'));
            return false;
        } else {
            return true;
        }
    }

    /**
     * 清理表单令牌
     */
    public function clear()
    {
        TokenService::instance()->clearFormToken();
    }

    /**
     * 返回视图内容
     * @param string $tpl 模板名称
     * @param array $vars 模板变量
     * @param string|null $node 授权节点
     */
    public function fetchTemplate(string $tpl = '', array $vars = [], ?string $node = null)
    {
        abort(view($tpl, $vars, 200, function ($html) use ($node) {
            return preg_replace_callback('/<\/form>/i', function () use ($node) {
                $csrf = TokenService::instance()->buildFormToken($node);
                return "<input type='hidden' name='_csrf_' value='{$csrf['token']}'></form>";
            }, $html);
        }));
    }

}