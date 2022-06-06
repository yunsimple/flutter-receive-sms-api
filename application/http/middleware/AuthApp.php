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
        $redis_sync = RedisController::getInstance('sync');
        $salt = generateKey();
        $headers = getallheaders();
        //trace($headers, 'notice');
        // Access_Token检查，过滤/login接口
        if (Request::url() !== '/login' && Request::url() !== '/access'){
            if (!array_key_exists('Access-Token', $headers)) {
                // Access_Token不存在
                return show('fail', '', Config::get('config.auth'));
            }
            $access_token = $headers['Access-Token'];
            $access_token_key = Config::get('cache.prefix') . 'accessToken:' . $access_token;
            if (!$redis_sync->exists($access_token_key)){
                // Access_Token验证失败
                return show('fail', '', Config::get('config.auth'));
            }
        }

        if (!array_key_exists('Authorization', $headers)) {
            // Authorization不存在
            return show('fail', '', 4003);
        }
        $authorization = $headers['Authorization'];
        //放在local 防止重放攻击
        $redis_local = RedisController::getInstance();
        if (!RedisController::sAddEx($redis_local,Config::get('cache.prefix') . 'auth:' . $authorization)){
            // Authorization重复
            return show('fail', '', 4003, '', 403);
        }
        //验证authorization是否正确
        //如果在更新key期间，需要时间切换，12小时内需要验证两种情况。
        $salt_random = substr($authorization, -10);
        $new_authorization = md5($salt . $salt_random) . $salt_random;
        if ($new_authorization != $authorization){
            // Authorization匹配错误
            return show('fail', '', 4003, '', 403);
        }

        //验证通过 对access和refresh token进行请求统计，以便后面做限流
        if (array_key_exists('Access-Token', $headers) && Request::url() !== '/login' && Request::url() !== '/access'){
            $access_token = $headers['Access-Token'];
            $access_token_key = Config::get('cache.prefix') . 'accessToken:' . $access_token;
            $refresh_token = $redis_sync->hGet($access_token_key, 'refreshToken');
            $refresh_token_key = Config::get('cache.prefix') . 'refreshToken:' . $refresh_token;

            $redis_master = RedisController::getInstance('master');
            $redis_master->hIncrBy($access_token_key, 'requestNumber', 1);
            $redis_master->hIncrBy($refresh_token_key, 'requestNumber', 1);

            //传出一个token的有效时间，其他控制器使用
            $request->header = ['expires' => $redis_sync->ttl($access_token_key)];
        }
        //传出app使用的语言，默认为英文
        if (array_key_exists('Language', $headers)){
            if ($headers['Language'] == 'zh'){
                $request->Language = 'tw';
            }else{
                $request->Language = $headers['Language'];
            }
        }else{
            $request->Language = 'en';
        }
        //trace('通过','notice');
        return $next($request);
    }
}