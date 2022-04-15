<?php


namespace app\http\middleware;

use think\Controller;
use think\Exception;
use app\common\controller\RedisController;

class AuthUserApp extends Controller
{
    public function handle($request, \Closure $next)
    {
        $headers = getallheaders();
        try {
            $access_token = $headers['Access-Token'];
            $user_name = $headers['User-Name'];
            $access_token_key = 'app:accessToken:' . $access_token;
        }catch (Exception $e){
            return show('鉴权失败', '', 4003, '', 403);
        }
        $redis = RedisController::getInstance();
        $redis_username = $redis->hGet($access_token_key, 'username');
        if ($user_name != $redis_username){
            return show('鉴权失败11', '', 4003, '', 403);
        }
        return $next($request);
    }
}