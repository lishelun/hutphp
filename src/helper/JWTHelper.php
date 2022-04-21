<?php
declare (strict_types=1);

namespace hutphp\helper;

use Firebase\JWT\JWT;
use hutphp\Controller;
use hutphp\Helper;
use think\App;
use think\Request;

class JWTHelper extends Helper
{
    protected string $secret;
    protected Request $request;

    public function __construct(Controller $class , App $app)
    {
        parent::__construct($class , $app);
        $this->request = $this->app->request;
        $this->secret = app()->config->get('app.JWT_secret' , '2nUS[1-TH^mL{dW3N>ZAfaJ:z&4l+jsXoCy@h0PI~');
    }

    /**
     * JWT检测用户登陆
     * @return array|false
     */
    public function checkLogin()
    {
        $token = $this->request->header('access-token');
        if ( empty($token) ) {
            return false;
        }
        try {
            $result = $this->decode($token);
            return (array)$result->data;

        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * 生成UserToken
     * @description：生成Token
     * @param array $data
     * @param int   $exp
     * @return string
     */
    public function encode(array $data , int $exp = 86400): string
    {
        $token = array(
            'iss' => $_SERVER['HTTP_HOST'] ,     //签发者
            'aud' => $_SERVER['HTTP_HOST'] ,     //jwt所面向的用户
            'iat' => time() ,                    //签发时间
            'nbf' => time() - 60 ,               //在什么时间之后该jwt才可用
            'exp' => time() + $exp ,           //过期时间
            'data' => $data
        );
        return JWT::encode($token , $this->secret);
    }

    /**
     * 解密Token
     * @description：解密Token
     * @param $jwtToken
     * @return false|object
     */
    public function decode($jwtToken): object|bool
    {
        try {
            return JWT::decode($jwtToken , $this->secret , array('HS256'));//进行解密算法
        } catch (\Exception $exception) {
            return false;
        }
    }
}