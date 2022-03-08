<?php
namespace app\rsapi\controller;

use app\common\model\UserSmsModel;
use think\facade\Request;
use think\Validate;
use think\Exception;

class TokenController extends BaseController
{
    protected $middleware = ['AuthApp' => ['only' => ['loginOut']]];

    /**
     * 签发accessToken和refreshToken
     * @return \think\response\Json
     */
    public function getToken()
    {
        /**
         * 两种情况
         * 1.用帐号密码登陆，获取accessToken
         * 2.判断是否有一个ID字段，
         */
        $salt = 'ca-app-pub-3940256099942544~334759533';
        $data['uid'] = input('post.uid');
        //登陆成功后，发放access_token refresh_token expires从response中返回
        //token写入redis保存
        $access_token = getRandChar(18);
        $refresh_token = getRandChar(18);
        $redis = new RedisController();
        $access_token_key = 'app:accessToken:' . $access_token;
        $refresh_token_key = 'app:refreshToken:' . $refresh_token;
        //两种情况
        if ($data['uid']){
            $validate = Validate::make([
                'uid|uid' => 'require|alphaNum|max:32'
            ]);
            if (!$validate->check($data)) {
                return show($validate->getError(), $validate->getError(), 4000);
            }
            //根据device判断是否是手机请求
            $device = Request::header('device');
            if (!$device){
                return show('鉴权失败', '', 4003, '', 403);
            }
            if (md5($device . $salt) !== $data['uid']){
                return show('鉴权失败', '', 4003, '', 403);
            }
            //写入hash数据
            //写入hash的数据
            $access_data['deviceID'] = $device;
            $refresh_token_data['deviceID'] = $device;
        }else{
            //由于登陆封装在一起，第一次登陆不能使用中间件，直接在登陆这里判断，套用AuthApp,如果更改需要同步
            $headers = getallheaders();
            //比对Authorization
            //encode(md5(access_token + random_str + salt) + 'a' + random_str)
            try {
                $authorization = $headers['Authorization'];
                $authorization = base64_decode($authorization);
                $header_access_token = $headers['Access-Token'];
            }catch (Exception $e){
                return show('鉴权失败1', '', 4003, '', 403);
            }
            //防止重放攻击
            if (!$redis->sAddEx('app:auth:' . $authorization)){
                return show('鉴权失败2', '', 4003, '', 403);
            }
            //核对数据是否伪造
            //$salt = '兄台爬慢点可好';
            $random_str = substr($authorization, -9);
            $random_str_fake = 'a' . $random_str;
            if (md5($header_access_token . $random_str . $salt) . $random_str_fake !== $authorization) {
                return show('鉴权失败4', '', 4003, '', 403);
            }
            
            $data['username'] = input('post.username');
            $data['password'] = input('post.password');
            $validate = Validate::make([
                'username|用户名' => 'require|min:5|max:20',
                'password|密码' => 'require|min:6|max:20'
            ]);
            if (!$validate->check($data)) {
                return show($validate->getError(), $validate->getError(), 4000);
            }
            //取得该用户的salt进行比对
            $usersms_model = new UserSmsModel();
            $user_info = $usersms_model->getUserInfo($data['username']);
            $sql_md5 = md5($data['password'] . $user_info['salt']);
            if ($sql_md5 !== $user_info['password']) {
                return show('登陆失败', '', 4000);
            }
            //todo 如果是带access_token登陆的，没过期的话，需要删除之前的access_token
            $access_data['username'] = $data['username'];
            $refresh_token_data['username'] = $data['username'];
        }
        //写入hash的数据
        $access_data['refreshToken'] = $refresh_token;
        $access_data['requestNumber'] = 0;
        $access_data['ip'] = real_ip();
        $access_data['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
        $refresh_token_data['ip'] = real_ip();
        $refresh_token_data['requestNumber'] = 0;
        $refresh_token_data['refreshNuber'] = 0;
        $refresh_token_data['updateTime'] = time();

        $access = $redis->hMsetEx($access_token_key, $access_data, 3600);
        $refresh = $redis->hMsetEx($refresh_token_key, $refresh_token_data, 15 * 86400);
        if ($refresh && $access) {
            //清除之前的token
            $this->delTokenByAccess();
            return show('登陆成功', ['access_token' => $access_token, 'refresh_token' => $refresh_token], 0, ['Expires'=>$redis->ttl($access_token_key)]);
            //return show('登陆成功', ['access_token' => $access_token, 'refresh_token' => $refresh_token], 0, ['Expires'=>60]);
        } else {
            return show('登陆失败', '', 4000);
        }
    }

    /**
     * 根据refreshToken获取accessToken
     * @param $refresh
     */
    public function getAccessByRefresh()
    {
        $refresh_token = input('post.refreshToken');
        $refresh_token_key = 'app:refreshToken:' . $refresh_token;
        $validate = Validate::make([
            'refresh_token|Token' => 'require|alphaNum|length:18'
        ]);
        if (!$validate->check(['refresh_token' => $refresh_token])) {
            return show($validate->getError(), $validate->getError(), 4000);
        }
        //验证refresh
        $redis = new RedisController();
        if (!$redis->exists($refresh_token_key)) {
            return show('鉴权失败-refresh为空', '', 4004, '', 403);
        }
        //签发accessToken
        $access_token_data = [
            'refreshToken' => $refresh_token,
            'requestNumber' => 0,
            'ip' => real_ip(),
            'userAgent' => $_SERVER['HTTP_USER_AGENT']
        ];
        $access_token = getRandChar(18);
        $access = $redis->hMsetEx('app:accessToken:' . $access_token, $access_token_data, 3600);
        //验证refreshToken过期时间，如果过期，重新登陆。如果未过期，延期使用
        $refresh_ttl = $redis->ttl($refresh_token_key);
        if ($refresh_ttl < 86400) {
            $redis->expire($refresh_token_key, 15 * 86400);
        }
        if ($access) {
            $redis->hIncrBy($refresh_token_key, 'refreshNumber', 1);
            //删除旧的accessToken
            //$access_token_old = Request::header()['access-token'];
            //$redis->del('app:accessToken:' . $access_token_old);
            return show('签发成功', $access_token, 0, ['Expires'=>$redis->ttl('app:accessToken:' . $access_token)]);
            //return show('签发成功', $access_token, 0, ['Expires'=>60]);
        } else {
            return show('签发失败', '', 4000);
        }
    }

    public function loginOut()
    {
        $result = $this->delTokenByAccess();
        if ($result > 0) {
            return show('登出成功');
        } else {
            return show('登陆失败', '', 4000);
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
        $redis = new RedisController();
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