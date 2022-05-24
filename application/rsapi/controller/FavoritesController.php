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
    protected array $middleware = ['AuthApp'];
    protected array $header = []; //自定义response返回header

    // 写入redis set
    public function add(Request $request): \think\response\Json
    {
        $phone = input('post.phone');
        $validate = Validate::checkRule($phone, 'must|number|max:15|min:1');
        if (!$validate){
            return show('Exception', $phone, 4000);
        }

        $user_id = (new FirebaseUserModel())->getUserInfoByAccessToken('', 'user_id');

        $favorites_key = Config::get('cache.prefix') . 'favorites:' . $user_id;
        $redis = RedisController::getInstance('master');
        try {
            $value = $redis->sAdd($favorites_key, $phone);
            return show('Success', $value);
        } catch(\Exception $e){
            return show('Fail','', 4000);
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
        $redis = RedisController::getInstance('master');
        try {
            $redis->sRem($favorites_key, $phone);
            return show('Success');
        } catch(\Exception $e){
            return show('Fail','', 4000);
        }

    }
}