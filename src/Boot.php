<?php

declare (strict_types=1);

namespace hutphp;

use Closure;
use hutphp\service\AdminService;
use think\middleware\LoadLangPack;
use think\middleware\SessionInit;
use think\Request;
use think\Service;

class Boot extends Service
{
    const VERSION = "6.0.0";

    public function boot()
    {
        $this->app->event->listen('HttpRun' , function (Request $request) {
            $request->filter([function ($value) {
                return is_string($value) ? trim(xss_safe($value)) : $value;
            }]);
            if ( $request->isCli() ) {
                if ( empty($_SERVER['REQUEST_URI']) && isset($_SERVER['argv'][1]) ) {
                    $request->setPathinfo($_SERVER['argv'][1]);
                }
            } else {
                $request->setHost($request->host());
            }
        });
    }

    /**
     * 初始化服务
     */
    public function register()
    {
        define('BASE_PATH' , base_path());
        define('APP_PATH' , app_path());
        define('CONFIG_PATH' , config_path());
        define('PUBLIC_PATH' , public_path());
        define('HUTCMS_PATH' , base_path() . 'hutcms' . DIRECTORY_SEPARATOR);
        define('RUNTIME_PATH' , runtime_path());
        define('ROOT_PATH' , root_path());
        define('CHARSET' , 'utf-8');
        define('DS' , DIRECTORY_SEPARATOR);
        $this->loadLang();
        [$ds , $base] = [DIRECTORY_SEPARATOR , $this->app->getBasePath()];
        if ( !$this->app->request->isCli() ) {
            $this->app->middleware->add(LoadLangPack::class);
            $this->app->middleware->add(SessionInit::class);
            $this->app->middleware->add(function (Request $request , Closure $next) {
                $header = [];
                if ( ($origin = $request->header('origin' , '*')) !== '*' ) {
                    $header['Access-Control-Allow-Origin'] = $origin;
                    $header['Access-Control-Allow-Methods'] = 'GET,PUT,POST,PATCH,DELETE';
                    $header['Access-Control-Allow-Headers'] = 'Authorization,Content-Type,If-Match,If-Modified-Since,If-None-Match,If-Unmodified-Since,X-Requested-With,Api-Name,Api-Type,Api-Token,User-Form-Token,User-Token,Token';
                    $header['Access-Control-Expose-Headers'] = 'Api-Name,Api-Type,Api-Token,User-Form-Token,User-Token,Token';
                    $header['Access-Control-Allow-Credentials'] = 'true';
                }
                if ( in_array(app()->http->getName() , app()->config->get('app.auth_app' , [])) ) {

                    $check = app()->http->getName() . '@' . $request->controller(true) . '/' . $request->action(true);
                    //排除验证
                    if($request->isOptions()){
                        return response()->code(204)->header($header);
                    }
                    else if ( in_array(strtolower($check) , array_map(function ($val) {
                        return strtolower($val);
                    } , app()->config->get('app.deny_auth_list'))) ) {
                        return $next($request)->header($header);
                    } else if ( AdminService::instance()->isLogin() == false ) {
                        //未登录
                        return json(['code' => 4001 , 'message' => lang('hutphp_not_login')])->header($header);
                    } else if ( AdminService::instance()->checkPermissions() == false ) {
                        //未授权
                        return json(['code' => 4002 , 'message' => lang('hutphp_not_auth')])->header($header);
                    }
                }
                return $next($request)->header($header);
            } , 'route');
        }
    }

    protected function loadLang($file = []): void
    {
        $range = $this->app->lang->getLangSet();
        $this->app->lang->load(__DIR__ . '/lang/' . strtolower($range) . '.php' , $range);
        if ( !empty($file) ) {
            if ( is_array($file) ) {
                foreach ( $file as $item ) {
                    $this->app->lang->load($item , $range);
                }
            } else if ( is_string($file) ) {
                $this->app->lang->load($file , $range);
            }
        }
    }
}