<?php
namespace app\rsapi\controller;

use app\common\controller\RedisController;
use app\common\model\FirebaseUserModel;
use think\Exception;
use think\facade\Config;
use think\facade\Validate;
use think\Request;

class FavoritesController extends BaseController
{
    protected $middleware = ['AuthApp'];
    protected $header = []; //自定义response返回header

    // 写入redis set
    public function add(Request $request): \think\response\Json
    {
        $phone = input('post.phone');
        $validate = Validate::checkRule($phone, 'must|number|max:15|min:1');
        if (!$validate){
            return show('Exception', $phone, 4000);
        }
        $add = $this->favorites($phone);
        if ($add){
            return show('Success');
        }else{
            return show('Fail','', 4000);
        }
    }

    public function favorites($phone)
    {
        $user_id = (new FirebaseUserModel())->getUserInfoByAccessToken('', 'user_id');

        $favorites_key = Config::get('cache.prefix') . 'favorites:' . $user_id;
        $redis_master = RedisController::getInstance('master');
        try {
            //删除缓存
            $favorites_cache_key = Config::get('cache.prefix') . 'cache:phone:favorites:' . $user_id;
            RedisController::getInstance()->del($favorites_cache_key);
            return $redis_master->sAdd($favorites_key, $phone);
        } catch(\Exception $e){
            trace($e->getMessage(), 'error');
            return false;
        }
    }

    public function del(Request $request): \think\response\Json
    {
        $phone = input('post.phone');
        $validate = Validate::checkRule($phone, 'must|number|max:15|min:1');
        if (!$validate){
            return show('Exception', $phone, 4000);
        }

        $user_id = (new FirebaseUserModel())->getUserInfoByAccessToken('', 'user_id');

        $favorites_key = Config::get('cache.prefix') . 'favorites:' . $user_id;
        $redis_master = RedisController::getInstance('master');
        try {
            $redis_master->sRem($favorites_key, $phone);
            //删除缓存
            $favorites_cache_key = Config::get('cache.prefix') . 'cache:phone:favorites:' . $user_id;
            RedisController::getInstance()->del($favorites_cache_key);
            return show('Success');
        } catch(\Exception $e){
            return show('Fail','', 4000);
        }

    }
}