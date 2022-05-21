<?php
namespace app\common\model;

use app\common\controller\RedisController;
use think\facade\Config;

class FirebaseUserModel extends BaseModel
{
    //登陆查询用户登陆信息
    public function getUserInfo($user): FirebaseUserModel
    {
        return self::where('user', $user)->find();
    }

    public function insertUser($data): FirebaseUserModel
    {
        return self::create($data);
    }

    //获取某个字段的值
    public function getFieldValueByUser($user, $field){
        return self::where('user', $user)
            ->value($field);
    }

    public function getFieldValueByUserId($user_id, $field){
        return self::where('user_id', $user_id)
            ->value($field);
    }

    //后台显示所有帐户
    public function getAllUser($page, $limit){
        return self::where('type', '<>', 1)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();
    }

    //根据accessToken查询账户信息
    public function getUserInfoByAccessToken($access_token = '', $search = 'all'){
        if ($access_token == ''){
            $access_token = getallheaders()['Access-Token'];
        }
        $access_token_key = Config::get('cache.prefix') . 'accessToken:' . $access_token;
        $redis = RedisController::getInstance();
        $refresh_token = $redis->hGet($access_token_key, 'refreshToken');
        $refresh_token_key = Config::get('cache.prefix') . 'refreshToken:' . $refresh_token;
        if ($search == 'all'){
            return $redis->hGetAll($refresh_token_key);
        }else{
            return $redis->hGet($refresh_token_key, $search);
        }
    }
}