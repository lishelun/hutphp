<?php
/*
 * +----------------------------------------------------------------------
 *  | HUTPHP
 * +----------------------------------------------------------------------
 *  | Copyright (c) 2020-2022 http://hutcms.com All rights reserved.
 * +----------------------------------------------------------------------
 *  | Licensed ( https://mit-license.org )
 * +----------------------------------------------------------------------
 *  | Author: lishelun <lishelun@qq.com>
 * +----------------------------------------------------------------------
 */

/**
 * 写入系统日志
 * @param string $type    日志类型
 * @param string $content 日志内容
 * @return bool
 */
function hutlog(string $type , string $content): bool
{

    $log = [
        'node' => NodeService::instance()->getCurrent() ,
        '$type' => $$type , 'content' => $content ,
        'ip' => request()->ip() ?: '127.0.0.1' ,
        'port' => request()->port() ,
        'username' => AdminService::instance()->getUserName() ?: '-' ,
        'create_at' => time() ,
    ];
    //to do...
    return false;
}

/**
 * 系统配置
 * @param string|null $name
 * @param string|null $value
 * @return string|array|bool
 */
function hutconf(string $name = null , string $value = null): bool|array|string
{

    return false;
}


if ( !function_exists('virtual_model') ) {
    function virtual_model(string $name , array $data = [] , string $con = ''): \think\Model
    {
        return \hutphp\VirtualModel::create($name , $data , $con);
    }
}
if ( !function_exists('Model') ) {
    function Model(string $name , array $data = [] , string $con = ''): \think\Model
    {
        if ( strpos($name , '\\') !== false ) {
            if ( class_exists($name) ) return new $name($data);
            $name = basename(str_replace('\\' , '//' , $name));
        }
        $name = \think\helper\Str::studly($name);
        if ( !class_exists($class = "virtual\\model\\{$name}") ) {
            return virtual_model($name , $data , $con);
        } else {
            return new $class($data);
        }
    }
}
if ( !function_exists('put_debug') ) {
    /**
     * 打印输出数据到日志文件
     * @param object|array|string $data 打印内容
     * @param bool                $new  强制替换文件
     * @param string|null         $file 文件路径名称
     * @return false|int
     */
    function put_debug(object|array|string $data , bool $new = false , ?string $file = null): bool|int
    {
        if ( is_null($file) ) $file = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . date('Ymd') . '.log';
        $str = (is_string($data) ? $data : ((is_array($data) || is_object($data)) ? print_r($data , true) : var_export($data , true))) . PHP_EOL;
        return $new ? file_put_contents($file , $str) : file_put_contents($file , $str , FILE_APPEND);
    }
}
if ( !function_exists('optimize_runtime') ) {
    /**
     * 压缩优化项目运行文件
     */
    function optimize_runtime(): void
    {
        $connection = app()->db->getConfig('default');
        app()->console->call("optimize:schema" , ["--connection={$connection}"]);
        $base_path = app()->getBasePath();
        $data = [];
        foreach ( scandir($base_path) as $item ) if ( $item[0] !== '.' ) {
            if ( is_dir(realpath($base_path . $item)) ) $data[] = $item;
        }
        foreach ( $data as $module ) {
            $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . $module;
            file_exists($path) && is_dir($path) || mkdir($path , 0755 , true);
            app()->console->call("optimize:route" , [$module]);
        }
    }
}
if ( !function_exists('str2arr') ) {
    /**
     * 字符串转数组
     * @param string     $text     待转内容
     * @param string     $separate 分隔字符
     * @param null|array $allow    限定规则
     * @return array
     */
    function str2arr(string $text , string $separate = ',' , ?array $allow = null): array
    {
        $text = trim($text , $separate);
        $data = strlen($text) ? explode($separate , $text) : [];
        if ( is_array($allow) ) foreach ( $data as $key => $item ) {
            if ( !in_array($item , $allow) ) unset($data[$key]);
        }
        foreach ( $data as $key => $item ) {
            if ( $item === '' ) unset($data[$key]);
        }
        return $data;
    }
}
if ( !function_exists('arr2str') ) {
    /**
     * 数组转字符串
     * @param array      $data     待转数组
     * @param string     $separate 分隔字符
     * @param null|array $allow    限定规则
     * @return string
     */
    function arr2str(array $data , string $separate = ',' , ?array $allow = null): string
    {
        if ( is_array($allow) ) foreach ( $data as $key => $item ) {
            if ( !in_array($item , $allow) ) unset($data[$key]);
        }
        foreach ( $data as $key => $item ) {
            if ( $item === '' ) unset($data[$key]);
        }
        return $separate . join($separate , $data) . $separate;
    }
}

if ( !function_exists('http_get') ) {
    /**
     * 以get模拟网络请求
     * @param string       $url     HTTP请求URL地址
     * @param array|string $query   GET请求参数
     * @param array        $options CURL参数
     * @return boolean|string
     */
    function http_get(string $url , array|string $query = [] , array $options = []): bool|string
    {
        return \hutphp\extend\HttpExtend::get($url , $query , $options);
    }
}
if ( !function_exists('http_post') ) {
    /**
     * 以post模拟网络请求
     * @param string       $url     HTTP请求URL地址
     * @param array|string $data    POST请求数据
     * @param array        $options CURL参数
     * @return boolean|string
     */
    function http_post(string $url , array|string $data = [] , array $options = []): bool|string
    {
        return \hutphp\extend\HttpExtend::post($url , $data , $options);
    }
}
if ( !function_exists('data_save') ) {
    /**
     * 数据增量保存
     * @param think\db\BaseQuery|string|think\Model $query
     * @param array                                 $data  需要保存或更新的数据
     * @param string                                $pk    条件主键限制
     * @param array                                 $where 其它查询条件
     * @return boolean|integer
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    function data_save(string|\think\db\BaseQuery|\think\Model $query , array &$data , string $pk = 'id' , array $where = []): bool|int
    {
        $val = (isset($data[$pk]) && $data[$pk]) ? $data[$pk] : null;
        $query = \hutphp\Helper::buildQuery($query)->master()->strict(false)->where($where);
        if ( empty($where[$pk]) ) {
            if ( is_string($val) && str_contains($val , ',') ) {
                $query->whereIn($pk , explode(',' , $val));
            } elseif ( is_array($val) ) {
                $query->whereIn($pk , $val);
            } else {
                $query->where([$pk => $val]);
            }
        }
        $model = $query->findOrEmpty();
        $method = $model->isExists() ? 'onAdminUpdate' : 'onAdminInsert';
        if ( $model->save($data) === false ) return false;
        if ( $model instanceof \hutphp\Model && is_callable([$model , $method]) ) {
            $model->$method(strval($model[$pk] ?? ''));
        }
        $data = $model->toArray();
        return $model[$pk] ?? true;
    }
}
if ( !function_exists('format_bytes') ) {
    /**
     * 文件字节单位转换
     * @param integer|string $size
     * @return string
     */
    function format_bytes(int|string $size): string
    {
        if ( is_numeric($size) ) {
            $units = ['B' , 'KB' , 'MB' , 'GB' , 'TB' , 'PB' , 'EB' , 'ZB' , 'YB'];
            for ( $i = 0 ; $size >= 1024 && $i < 4 ; $i++ ) {
                $size /= 1024;
            }

            return round($size , 2) . ' ' . $units[$i];
        } else {
            return $size;
        }
    }
}
if ( !function_exists('format_datetime') ) {
    /**
     * 日期格式标准输出
     * @param int|string $datetime 输入日期
     * @param string     $format   输出格式
     * @return string
     */
    function format_datetime(int|string $datetime = '' , string $format = 'Y-m-d H:i:s'): string
    {
        if ( empty($datetime) ) {
            return date($format);
        }

        if ( is_numeric($datetime) ) {
            return date($format , $datetime);
        } else {
            return date($format , strtotime($datetime));
        }
    }
}

if ( !function_exists('format_clicks') ) {
    /**
     * 格式化点击数
     * @param $num
     * @return string
     */
    function format_clicks($num): string
    {
        if ( $num >= 1000 && $num < 100000 ) {
            $num = number_format($num / 1000 , 2) . 'k';
        } elseif ( $num >= 100000 && $num < 100000000 ) {
            $num = number_format($num / 10000 , 2) . 'w';
        } elseif ( $num >= 100000000 ) {
            $num = number_format($num / 100000000 , 2) . 'b';
        }
        return $num;
    }
}
if ( !function_exists('create_order_num') ) {
    /**
     * 创建订单号
     * @return string
     */
    function create_order_num(): string
    {
        return date('YmdHis') . substr(implode(null , array_map('ord' , str_split(substr(uniqid() , 7 , 13) , 1))) , 0 , 8);
    }
}
if ( !function_exists('get_article_intro') ) {
    /**
     * 获得文章摘要
     * @param     $article
     * @param int $length
     * @return string
     */
    function get_article_intro($article , int $length = 255): string
    {
        if ( empty($article) ) {
            return "";
        }

        preg_match_all("/<p(.*?)>([\s\S]*?)<\/p>/i" , $article , $list);
        $contents = "";
        if ( isset($list[0]) && count($list[0]) > 0 ) {
            foreach ( $list[0] as $html ) {
                $html = htmlspecialchars_decode($html);
                $html = str_replace([" " , "&nbsp;"] , "" , $html);
                $contents .= strip_tags($html) . "\r\n";
                $len = mb_strlen($contents);
                if ( $len > 0 ) {
                    if ( $len > $length ) {
                        $contents = mb_substr($contents , 0 , $length);
                        break;
                    }
                }
            }
        } else {
            $contents = mb_substr(strip_tags(htmlspecialchars_decode($article)) , 0 , $length);
        }
        return $contents;
    }
}
if ( !function_exists('encode2utf8') ) {
    /**
     * 把字符串转化成UTF8编码
     * @param $str
     * @return false|string
     */
    function encode2utf8($str): bool|string
    {
        $targetEncode = "UTF-8";
        $encode = mb_detect_encoding($str);
        if ( $encode != $targetEncode ) {
            return iconv($encode , $targetEncode , $str);
        }
        return $str;
    }
}

if ( !function_exists('str_start_width') ) {
    /**
     * 判断字符串是否以某个字符串开头
     * @param string $str
     * @param string $beginStr
     * @return boolean
     */
    function str_start_width(string $str , string $beginStr): bool
    {
        if ( !is_string($str) || !is_string($beginStr) ) {
            return false;
        }
        if ( empty($str) || empty($beginStr) ) {
            return false;
        }
        return strpos($str , $beginStr) === 0;
    }
}
if ( !function_exists('str_end_width') ) {
    /**
     * 判断字符串是否以某个字符串结束
     * @param string $str
     * @param string $endStr
     * @return boolean
     */
    function str_end_width(string $str , string $endStr): bool
    {
        if ( !is_string($str) || !is_string($endStr) ) {
            return false;
        }
        if ( empty($str) || empty($endStr) ) {
            return false;
        }
        if ( strlen($str) < strlen($endStr) ) {
            return false;
        }
        return strpos($str , $endStr , strlen($str) - strlen($endStr)) !== false;
    }
}
if ( !function_exists('cutstr') ) {
    /**
     * 截取字符串
     * @param string $string
     * @param int    $length
     * @param string $dot
     * @return string
     */
    function cutstr(string $string , int $length = 160 , string $dot = '...'): string
    {
        if ( strlen($string) <= $length ) {
            return $string;
        }
        $pre = chr(1);
        $end = chr(1);
        $string = str_replace(['&amp;' , '&quot;' , '&lt;' , '&gt;'] , [$pre . '&' . $end , $pre . '"' . $end , $pre . '<' . $end , $pre . '>' . $end] , $string);
        $strcut = '';
        if ( strtolower(CHARSET) == 'utf-8' ) {
            $n = $tn = $noc = 0;
            while ( $n < strlen($string) ) {
                $t = ord($string[$n]);
                if ( $t == 9 || $t == 10 || (32 <= $t && $t <= 126) ) {
                    $tn = 1;
                    $n++;
                    $noc++;
                } elseif ( 194 <= $t && $t <= 223 ) {
                    $tn = 2;
                    $n += 2;
                    $noc += 2;
                } elseif ( 224 <= $t && $t <= 239 ) {
                    $tn = 3;
                    $n += 3;
                    $noc += 2;
                } elseif ( 240 <= $t && $t <= 247 ) {
                    $tn = 4;
                    $n += 4;
                    $noc += 2;
                } elseif ( 248 <= $t && $t <= 251 ) {
                    $tn = 5;
                    $n += 5;
                    $noc += 2;
                } elseif ( $t == 252 || $t == 253 ) {
                    $tn = 6;
                    $n += 6;
                    $noc += 2;
                } else {
                    $n++;
                }
                if ( $noc >= $length ) {
                    break;
                }
            }
            if ( $noc > $length ) {
                $n -= $tn;
            }
            $strcut = substr($string , 0 , $n);

        } else {
            $_length = $length - 1;
            for ( $i = 0 ; $i < $length ; $i++ ) {
                if ( ord($string[$i]) <= 127 ) {
                    $strcut .= $string[$i];
                } elseif ( $i < $_length ) {
                    $strcut .= $string[$i] . $string[++$i];
                }
            }
        }
        $strcut = str_replace([$pre . '&' . $end , $pre . '"' . $end , $pre . '<' . $end , $pre . '>' . $end] , ['&amp;' , '&quot;' , '&lt;' , '&gt;'] , $strcut);
        $pos = strrpos($strcut , chr(1));
        if ( $pos !== false ) {
            $strcut = substr($strcut , 0 , $pos);
        }
        return $strcut . $dot;
    }
}
if ( !function_exists('get_client_ip') ) {
    /**
     * 获得客户端ip
     * @return string
     */
    function get_client_ip(): string
    {
        return request()->ip();
    }
}

if ( !function_exists('check_token') ) {
    /**
     * 检查token
     * @param string $name
     * @param array  $data
     * @return bool
     */
    function checktoken(string $name = '__token__' , array $data = []): bool
    {
        if ( !$data ) {
            $data = request()->param();
        }
        return request()->checkToken($name , $data);
    }
}
if ( !function_exists('safe64_encode') ) {
    /**
     * Base64Url 安全编码
     * @param string $text
     * @return string
     */
    function safe64_encode(string $text): string
    {
        return \hutphp\extend\CodeExtend::enSafe64($text);
    }
}
if ( !function_exists('safe64_decode') ) {
    /**
     * Base64Url 安全解码
     * @param string $text
     * @return string
     */
    function safe64_decode(string $text): string
    {
        return \hutphp\extend\CodeExtend::deSafe64($text);
    }
}
if ( !function_exists('ssl_encode') ) {
    /**
     * 数据加密处理
     * @param mixed  $data
     * @param string $key
     * @return string
     */
    function ssl_encode(mixed $data,string $key=''): string
    {
        return \hutphp\extend\CodeExtend::encrypt($data,$key);
    }
}
if ( !function_exists('ssl_decode') ) {
    /**
     * 数据解密处理
     * @param string  $data
     * @param string $key
     * @return string
     */
    function ssl_decode(string $data,string $key=''): string
    {
        return \hutphp\extend\CodeExtend::decrypt($data,$key);
    }
}
if ( !function_exists('utf8_encode') ) {
    /**
     * 加密 UTF8 字符串
     * @param string $content
     * @return string
     */
    function utf8_encode(string $content): string
    {
        return \hutphp\extend\CodeExtend::utf8Encode($content);
    }
}
if ( !function_exists('utf8_decode') ) {
    /**
     * 解密 UTF8 字符串
     * @param string $content
     * @return string
     */
    function utf8_decode(string $content): string
    {
        return hutphp\extend\CodeExtend::utf8Decode($content);
    }
}
if ( !function_exists('uniqid_number') ) {
    /**
     * 唯一数字编码
     * @param integer $size 编码长度
     * @param string $prefix 编码前缀
     * @return string
     */
    function uniqid_number(int $size = 12 , string $prefix = ''): string
    {
        return \hutphp\extend\CodeExtend::uniqidNumber($size , $prefix);
    }
}
if ( !function_exists('uniqid_date') ) {
    /**
     * 唯一日期编码
     * @param integer $size 编码长度
     * @param string $prefix 编码前缀
     * @return string
     */
    function uniqid_date(int $size = 16 , string $prefix = ''): string
    {
        return \hutphp\extend\CodeExtend::uniqidDate($size , $prefix);
    }
}
if ( !function_exists('random') ) {
    /**
     * 获取随机字符串编码
     * @param integer $size   编码长度
     * @param integer $type   编码类型(1纯数字,2纯字母,3数字字母)
     * @param string  $prefix 编码前缀
     * @return string
     */
    function random(int $size = 10 , int $type = 1 , string $prefix = ''): string
    {
        return \hutphp\extend\CodeExtend::random($size , $type , $prefix);
    }
}
if ( !function_exists('dispatch') ) {
    /**
     * 调用其他控制器方法
     * @param string|null $controller 控制器
     * @param string|null $action     方法
     * @param null|string $app        应用
     */
    function dispatch(string $controller = null , string $action = null , string $app = null): void
    {
        if ( !$controller ) {
            abort(500 , 'dispatch need param of controller');
        }
        if ( is_array($controller) ) {
            $action = $controller[1] ?? null;
            $controller = $controller[0];
        }
        if ( $app ) {
            app()->setNamespace('app\\' . $app);
            app('http')->name($app);
        }
        $dispatch = \think\facade\Route::url($controller . '/' . $action);
        $dispatch->init(app());
        $response = $dispatch->run();
        $response->send();
        app('http')->end($response);
    }
}
if ( !function_exists('pinyin') ) {
    /**
     * 汉字转拼音
     * @param string $str
     * @param string $type
     * @param bool   $up
     * @param string $exp
     * @param bool   $other_hide
     * @return string
     */
    function pinyin(string $str , string $type = 'default' , bool $up = false , string $exp = '' , bool $other_hide = true): string
    {
        //实例化拼音类 ,不重复初始化
        $pinyin = app()->make('\\hutphp\\extend\\Pinyin');
        //不带读音的拼音
        if ( $type == 'default' || $type == 'WithoutTone' ) {
            $p = $pinyin->TransformWithoutTone($str , $exp , $other_hide , $up);
        } //带读音的拼音
        else if ( $type == 'WithTone' || $type == 'Tone' || $type == '1' ) {
            $p = $pinyin->TransformWithTone($str , $exp , $other_hide , $up);
        } //转换首字母,不返回非汉字内容
        else if ( $type == 'szm' ) {
            $p = $pinyin->TransformUcwordsOnlyChar($str , $exp);
        } //转换首字母 包含非汉字内容
        else if ( $type == 'TransformUcwords' ) {
            $p = $pinyin->TransformUcwords($str , $exp , $other_hide);
        } //默认不带读音的拼音
        else {
            $p = $pinyin->TransformWithoutTone($str , $exp , $other_hide , $up);
        }
        return $p;
    }
}
if ( !function_exists('creat_password') ) {
    /**
     * 创建密码
     * @param string $password
     * @param string $salt
     * @return string
     */
    function creat_password(string $password , string $salt = ''): string
    {
        return md5(md5($salt . trim($password) . $salt));
    }
}
if ( !function_exists('dbtbpre') ) {
    /**
     * 获得数据表前缀
     * @param false $force
     * @return string
     */
    function dbtbpre(bool $force = false): string
    {
        if ( isset($GLOBALS['dbtbpre']) && $GLOBALS['dbtbpre'] && $force === false ) {
            return $GLOBALS['dbtbpre'];
        }
        return $GLOBALS['dbtbpre'] = dbconfig()['prefix'];
    }
}
if ( !function_exists('dbconfig') ) {
    /**
     * 获得数据库配置信息
     * @param false $force
     * @return array
     */
    function dbconfig(bool $force = false): array
    {
        if ( isset($GLOBALS['dbconfig']) && $GLOBALS['dbconfig'] && $force === false ) {
            return $GLOBALS['dbconfig'];
        }
        $config = app()->config->get("database");
        return $GLOBALS['dbconfig'] = $config['connections'][$config['default']];
    }
}
if ( !function_exists('query') ) {
    /**
     * 查询器助手
     * @param string|\think\db\BaseQuery|\think\Model $db
     * @param null                                    $input
     * @return \hutphp\helper\QueryHelper
     */
    function query(string|think\db\BaseQuery|think\Model $db , $input = null): \hutphp\helper\QueryHelper
    {
        return \hutphp\helper\QueryHelper::instance()->init($db , $input);
    }
}
if ( !function_exists('db') ) {
    /**
     * ThinkORM数据库查询对象
     * @param string $tbname
     * @param string $type
     * @return \think\Db|\think\db\BaseQuery
     */
    function db(string $tbname , string $type = 'name'): \think\db\BaseQuery|\think\Db
    {
        $query = app()->db;
        if ( str_contains($tbname , '.') ) {
            [$connect , $name] = explode('.' , $tbname , 2);
            $query->connect($connect);
        } else {
            $name = $tbname;
        }
        if ( $type == 'name' ) {
            return $query->name($name);
        } else if ( $type == 'table' ) {
            return $query->table($name);
        }
        return $query;
    }
}
if ( !function_exists('query') ) {
    /**
     * QueryHelper数据库查询对象
     * @param            $query
     * @param array|null $data
     * @return \hutphp\helper\QueryHelper
     */
    function query($query , array $data = null): \hutphp\helper\QueryHelper
    {
        return \hutphp\helper\QueryHelper::instance()->init($query , $data);
    }
}
if ( !function_exists('throw_error') ) {
    /**
     * 抛出异常
     * @throws \Exception
     */
    function throw_error(string $string , int $code = 0)
    {
        throw new Exception($string , $code);
    }
}
if ( !function_exists('sysuri') ) {
    /**
     * 生成最短 URL 地址
     * @param string         $url    路由地址
     * @param array          $vars   PATH 变量
     * @param boolean|string $suffix 后缀
     * @param boolean|string $domain 域名
     * @return string
     */
    function sysuri(string $url = '' , array $vars = [] , bool|string $suffix = false , bool|string $domain = false): string
    {
        $ext = app()->config->get('route.url_html_suffix' , 'html');
        $pre = app()->route->buildUrl('@')->suffix(false)->domain($domain)->build();
        $uri = app()->route->buildUrl($url , $vars)->suffix($suffix)->domain($domain)->build();
        // 默认节点配置数据
        $app = app()->config->get('app.default_app');
        $controller = \think\helper\Str::snake(app()->config->get('route.default_controller'));
        $action = \think\helper\Str::lower(app()->config->get('route.default_action'));
        // 替换省略链接路径
        return preg_replace([
            "#^({$pre}){$app}/{$controller}/{$action}(\.{$ext}|^\w|\?|$)?#i" ,
            "#^({$pre}[\w\.]+)/{$controller}/{$action}(\.{$ext}|^\w|\?|$)#i" ,
            "#^({$pre}[\w\.]+)(/[\w\.]+)/{$action}(\.{$ext}|^\w|\?|$)#i" ,
        ] , ['$1$2' , '$1$2' , '$1$2$3'] , $uri);
    }
}
if ( !function_exists('vali') ) {
    /**
     * 快速验证
     * @param array         $rules    验证规则（ 验证信息数组 ）
     * @param array|string  $input    输入类型或内容
     * @param callable|null $callable 异常处理操作
     * @return array
     */
    function vali(array $rules , array|string $input = '' , ?callable $callable = null): array
    {
        return \hutphp\helper\ValidateHelper::instance()->init($rules , $input , $callable);
    }
}
if ( !function_exists('systoken') ) {
    /**
     * 生成 CSRF-TOKEN 参数
     * @param null|string $node
     * @return string
     */
    function systoken(?string $node = null): string
    {
        $result = \hutphp\service\TokenService::instance()->buildFormToken($node);
        return $result['token'] ?? '';
    }
}
if ( !function_exists('systoken_check') ) {
    /**
     * 验证 CSRF-TOKEN 参数
     * @param bool $return
     * @return bool
     */
    function systoken_check(bool $return = false): bool
    {
        return \hutphp\helper\TokenHelper::instance()->init($return);
    }
}

if ( !function_exists('str_contains') ) {
    /**
     * 检查字符串中是否包含某些字符串
     * @param string       $haystack
     * @param array|string $needles
     * @return bool
     */
    function str_contains(string $haystack , array|string $needles): bool
    {
        foreach ( (array)$needles as $needle ) {
            if ( '' != $needle && mb_strpos($haystack , $needle) !== false ) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查字符串是否以某些字符串结尾
     * @param string       $haystack
     * @param array|string $needles
     * @return bool
     */
    function str_ends_with(string $haystack , array|string $needles): bool
    {
        foreach ( (array)$needles as $needle ) {
            if ( (string)$needle === substr($haystack , strlen($needle)) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查字符串是否以某些字符串开头
     * @param string       $haystack
     * @param array|string $needles
     * @return bool
     */
    function str_starts_with(string $haystack , array|string $needles): bool
    {
        foreach ( (array)$needles as $needle ) {
            if ( '' != $needle && mb_strpos($haystack , $needle) === 0 ) {
                return true;
            }
        }

        return false;
    }
}
if (!function_exists('xss_safe')) {
    /**
     * 文本内容XSS过滤
     * @param string $text
     * @return string
     */
    function xss_safe(string $text): string
    {
        $rules = ['#<script.*?<\/script>#is' => '', '#(\s)on(\w+=\S)#i' => '$1οn$2'];
        return preg_replace(array_keys($rules), array_values($rules), trim($text));
    }
}
if (!function_exists('storage')) {
    /**
     * 文本内容XSS过滤
     * @param string|null $type
     * @return \hutphp\Storage
     * @throws \Exception
     * @example  storage('local')->set($name,$file);
     */
    function storage(string $type=null): \hutphp\Storage
    {
        return \hutphp\Storage::instance($type);
    }
}