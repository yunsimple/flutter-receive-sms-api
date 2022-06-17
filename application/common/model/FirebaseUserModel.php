<?php
namespace app\common\model;

use app\common\controller\RedisController;
use think\facade\Config;
use think\Model;
use think\Db;
use app\common\model\AdOrderModel;

class FirebaseUserModel extends BaseModel
{
    
    // 删除用户信息 firebaseUser AdOrder 收藏信息
    public function delete(){
        Db::startTrans();
        try {
            $user_id = $this->getUserInfoByAccessToken('', 'user_id');
            self::where('user_id', $user_id)->delete();
            (new AdOrderModel())->where('user_id', $user_id)->delete();
            //删除redis收藏信息
            $redis_favorites_key = Config::get('cache.prefix') . 'favorites:' . $user_id;
            RedisController::getInstance('master')->del($redis_favorites_key);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            trace('删除用户失败', 'error');
            trace($call_info, 'error');
            trace($e->getMessage(), 'error');
            return false;
        }
    }
    
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
            ->cache($user, 10*60)
            ->value($field);
    }

    public function getFieldValueByUserId($user_id, $field){
        return self::where('user_id', $user_id)
            ->cache($user_id, 10*60)
            ->value($field);
    }

    //后台显示所有帐户
    public function getAllUser($page, $limit){
        return self::where('type', '<>', 1)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();
    }

    //根据accessToken查询refreshToken里面存放的账户信息
    public function getUserInfoByAccessToken($access_token = '', $search = 'all'){
        if ($access_token == ''){
            $access_token = getallheaders()['Access-Token'];
        }
        $access_token_key = Config::get('cache.prefix') . 'accessToken:' . $access_token;
        $redis_sync = RedisController::getInstance('sync');
        $refresh_token = $redis_sync->hGet($access_token_key, 'refreshToken');
        $refresh_token_key = Config::get('cache.prefix') . 'refreshToken:' . $refresh_token;
        if ($search == 'all'){
            return $redis_sync->hGetAll($refresh_token_key);
        }else{
            return $redis_sync->hGet($refresh_token_key, $search);
        }
    }

    public function getRefreshTokenByAccessToken($access_token = ''){
        if ($access_token == ''){
            $access_token = getallheaders()['Access-Token'];
        }
        $access_token_key = Config::get('cache.prefix') . 'accessToken:' . $access_token;
        $redis_sync = RedisController::getInstance('sync');
        return $redis_sync->hGet($access_token_key, 'refreshToken');
    }

}