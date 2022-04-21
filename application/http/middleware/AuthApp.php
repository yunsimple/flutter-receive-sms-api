<?php
namespace app\http\middleware;

use think\Controller;
use app\common\controller\RedisController;
use think\facade\Config;
use think\facade\Request;

class AuthApp extends Controller
{
    public function handle($request, \Closure $next)
    {
        //todo 如果远程更改了密钥，返回的headers中可以给个提示，或者一个code码，提醒需要获取getSalt，不需要等到重启应用
        $redis = RedisController::getInstance();
        $salt = generateKey();
        $headers = getallheaders();
        trace($headers, 'notice');
        // Access_Token检查，过滤/login接口
        if (Request::url() !== '/login' && Request::url() !== '/access'){
            if (!array_key_exists('Access-Token', $headers)) {
                return show('鉴权失败，Access_Token不存在', '', Config::get('config.auth'));
            }
            $access_token = $headers['Access-Token'];
            $access_token_key = Config::get('cache.prefix') . 'accessToken:' . $access_token;
            if (!RedisController::getInstance()->exists($access_token_key)){
                return show('鉴权失败，Access_Token验证失败', '', Config::get('config.auth'));
            }
        }

        if (!array_key_exists('Authorization', $headers)) {
            return show('鉴权失败，Authorization不存在', '', 4003);
        }
        $authorization = $headers['Authorization'];
        //防止重放攻击 todo 调试关闭
/*        if (!RedisController::sAddEx($redis,Config::get('cache.prefix') . 'auth:' . $authorization)){
            return show('鉴权失败，Authorization重复', '', 4003, '', 403);
        }*/
        //验证authorization是否正确
        //如果在更新key期间，需要时间切换，12小时内需要验证两种情况。
        $salt_random = substr($authorization, -10);
        $new_authorization = md5($salt . $salt_random) . $salt_random;
        if ($new_authorization != $authorization){
            return show('鉴权失败，Authorization匹配错误', '', 4003, '', 403);
        }

        //验证通过 对access和refresh token进行请求统计，以便后面做限流
        if (array_key_exists('Access-Token', $headers) && Request::url() !== '/login' && Request::url() !== '/access'){
            $access_token = $headers['Access-Token'];
            $access_token_key = Config::get('cache.prefix') . 'accessToken:' . $access_token;
            $refresh_token = $redis->hGet($access_token_key, 'refreshToken');
            $refresh_token_key = Config::get('cache.prefix') . 'refreshToken:' . $refresh_token;

            $redis->hIncrBy($access_token_key, 'requestNumber', 1);
            $redis->hIncrBy($refresh_token_key, 'requestNumber', 1);

            //传出一个token的有效时间，其他控制器使用
            $request->header = ['Expires' => $redis->ttl($access_token_key)];
        }
        return $next($request);
    }
}