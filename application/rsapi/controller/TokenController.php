<?php
namespace app\rsapi\controller;

use app\common\controller\FirebaseJwtController;
use app\common\controller\RedisController;
use app\common\model\FirebaseUserModel;
use app\common\model\UserSmsModel;
use think\facade\Config;
use think\facade\Request;
use think\response\Json;
use think\Validate;
use think\Exception;

class TokenController extends BaseController
{
    protected $middleware = ['AuthApp'];

    /**
     * 签发accessToken和refreshToken /login
     * @return Json
     */
    public function getToken()
    {
        $salt = generateKey();
        $iv = generateIv();
        $aes_mode = Config::get('config.aes_mode');

        $token = input('post.token');
        //trace($token, 'notice');
        $jwt = openssl_decrypt($token, $aes_mode, $salt, 0, $iv);
        $firebase_user = (new FirebaseJwtController())->decoded($jwt);
        if (!$firebase_user){
            return show('Exception', '', 4003, '', 403);
        }

        //登陆成功后，发放access_token refresh_token expires从response中返回
        //token写入redis保存
        $access_token = getRandChar(18);
        $refresh_token = getRandChar(18);
        $redis_master = RedisController::getInstance('master');
        $access_token_key = Config::get('cache.prefix') . 'accessToken:' . $access_token;
        $refresh_token_key = Config::get('cache.prefix') . 'refreshToken:' . $refresh_token;

        //写入hash的数据
        $access_data['refreshToken'] = $refresh_token;
        $access_data['requestNumber'] = 0;
        $access_data['ip'] = real_ip();
        $access_data['updateTime'] = time();
        $refresh_token_data['ip'] = real_ip();
        $refresh_token_data['requestNumber'] = 0;
        $refresh_token_data['refreshNumber'] = 0;
        $refresh_token_data['updateTime'] = time();
        $refresh_token_data['email'] = array_key_exists('email', $firebase_user) ? $firebase_user['email'] : 'anonymous';
        $refresh_token_data['user_id'] = $firebase_user['user_id'];


        $access = RedisController::hMsetEx($redis_master, $access_token_key, $access_data, Config::get('config.access_token_expires'));
        $refresh = RedisController::hMsetEx($redis_master, $refresh_token_key, $refresh_token_data, Config::get('config.refresh_token_expires'));
        if ($refresh && $access) {
            //清除之前的token
            // todo 有必须删除之前的refresh token,是否需要删除，如果删除，被人大批量使用，那岂不是发现不了
            //$this->delTokenByAccess();
            //对token进行AES加密
            $access_token = openssl_encrypt($access_token, Config::get('config.aes_mode'), generateKey(), 0, generateIv());
            $refresh_token = openssl_encrypt($refresh_token, Config::get('config.aes_mode'), generateKey(), 0, generateIv());
            if ($access_token && $refresh_token){
                // trace('正确返回access refresh token', 'notice');
                return show('Success',
                    ['access_token' => $access_token,
                        'refresh_token' => $refresh_token,
                        'access_token_expire'=> $redis_master->ttl($access_token_key),
                        'refresh_token_expire'=> $redis_master->ttl($refresh_token_key)
                    ],
                    0,
                    ['Expires'=>$redis_master->ttl($access_token_key)]);
            }
            //return show('登陆成功', ['access_token' => $access_token, 'refresh_token' => $refresh_token], 0, ['Expires'=>60]);
        }
        return show('Fail', '', 4000, '', 403);
    }

    /**
     * 根据refreshToken获取accessToken
     * @return Json
     */
    public function getAccessByRefresh()
    {
        $cache_prefix = Config::get('cache.prefix');
        $refresh_token_code = input('post.token');
        $validate = Validate::make([
            'refresh_token|Token' => 'require|max:100'
        ]);
        if (!$validate->check(['refresh_token' => $refresh_token_code])) {
            return show((string)$validate->getError(), $validate->getError(), 4000);
        }
        //refresh_token 解密
        $ivs = Config::get('config.aes_iv');
        $num = 0;
        foreach ($ivs as $iv){
            //trace('密钥' . $iv, 'notice');
            $refresh_token = $this->checkRefreshToken($refresh_token_code, generateKey(), generateIv($iv));
            if($refresh_token){
                $num++;
                break;
            }
        }
        if ($num == 0){
            //鉴权失败-refresh错误
            return show('Exception', '', 4004);
        }

        $redis_master = RedisController::getInstance('master');
        $refresh_token_key = Config::get('cache.prefix') . 'refreshToken:' . $refresh_token;
        // todo 还可以进一步检查，hash里面其他参数，对安全性做健全处理
        // 签发accessToken
        $access_token_data = [
            'refreshToken' => $refresh_token,
            'requestNumber' => 1,
            'ip' => real_ip(),
            'updateTime' => time(),
        ];
        $access_token = getRandChar(18);

        $access = RedisController::hMsetEx($redis_master, $cache_prefix . 'accessToken:' . $access_token, $access_token_data, Config::get('config.access_token_expires'));

        if ($access) {
            $redis_master->hIncrBy($refresh_token_key, 'refreshNumber', 1);
            //验证refreshToken过期时间，如果过期，重新登陆。如果未过期，延期使用
            $refresh_ttl = $redis_master->ttl($refresh_token_key);
            if ($refresh_ttl < 86400 * 7) {
                $redis_master->expire($refresh_token_key, Config::get('config.refresh_token_expires'));
            }
            
            // 数据库写入access token 统计
            $user_id = RedisController::getInstance('sync')->hGet($refresh_token_key, 'user_id');
            (new FirebaseUserModel())->save(['access_token_number'  => ['inc', 1], 'ip' => real_ip(), 'version' => getHeader('Version')],['user_id' => $user_id]);
            
            return show('Success', ['accessToken' => $access_token, 'accessTokenExpire' => $redis_master->ttl($cache_prefix . 'accessToken:' . $access_token)]);
            //return show('签发成功', $access_token, 0, ['Expires'=>60]);
        } else {
            return show('Fail', '', 4000);
        }
    }

    // 解密refresh_token
    // 更换密钥后，有部分用户未及时获到到最新密钥，24小时内，允许使用旧的密钥，作兼容处理
    private function checkRefreshToken($refresh_token_code, $salt, $iv)
    {
        $aes_mode = Config::get('config.aes_mode');
        $decode_token = openssl_decrypt($refresh_token_code, $aes_mode, $salt, 0, $iv);
        $refresh_token_key = Config::get('cache.prefix') . 'refreshToken:' . $decode_token;
        $redis_sync = RedisController::getInstance('sync');
        if (!$redis_sync->exists($refresh_token_key)) {
            return false;
        }
        return $decode_token;
    }

    public function loginOut(): Json
    {
        $result = $this->delTokenByAccess();
        if ($result > 0) {
            return show('Success');
        } else {
            return show('Fail', '', 4000);
        }
    }

    /**
     * 根据当前header请求的access_token,删除access和refresh
     */
    private function delTokenByAccess(){
        $headers = getallheaders();
        if (!array_key_exists('Access-Token', $headers)){
            return true;
        }
        $redis = RedisController::getInstance();
        $access_token = $headers['Access-Token'];
        $access_token_key = 'app:accessToken:' . $access_token;
        $refresh_token = $redis->hGet($access_token_key, 'refreshToken');
        $access = $redis->del($access_token_key);
        $refresh = $redis->del('app:refreshToken:' . $refresh_token);
        return $access+$refresh;
    }

    public function getTime(){
        return time();
    }
}