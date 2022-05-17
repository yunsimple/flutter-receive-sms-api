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
            return show('传递参数异常', $phone, 4000);
        }

        $access_token = $request->header()['access-token'];
        $user_id = (new FirebaseUserModel())->getUserInfoByAccessToken($access_token, 'user_id');

        $favorites_key = Config::get('cache.prefix') . 'favorites:' . $user_id;
        $redis = RedisController::getInstance();
        try {
            $redis->sAdd($favorites_key, $phone);
            return show('收藏成功');
        } catch(\Exception $e){
            return show('收藏失败','', 4000);
        }
    }

    public function del(Request $request){
        $phone = input('post.phone');
        $validate = Validate::checkRule($phone, 'must|number|max:15|min:1');
        if (!$validate){
            return show('传递参数异常', $phone, 4000);
        }

        $access_token = $request->header()['access-token'];
        $user_id = (new FirebaseUserModel())->getUserInfoByAccessToken($access_token, 'user_id');

        $favorites_key = Config::get('cache.prefix') . 'favorites:' . $user_id;
        $redis = RedisController::getInstance();
        try {
            $redis->sRem($favorites_key, $phone);
            return show('移除收藏成功');
        } catch(\Exception $e){
            return show('移除收藏失败','', 4000);
        }

    }
}