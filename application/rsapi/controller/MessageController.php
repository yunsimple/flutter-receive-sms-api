<?php

namespace app\rsapi\controller;

use app\common\controller\RedisController;
use app\common\model\FirebaseUserModel;
use think\facade\Config;
use think\Request;
use think\Validate;

class MessageController extends BaseController
{
    protected $middleware = ['AuthApp'];
    protected $header = []; //自定义response返回header

    public function getMessage(Request $request){
        $data['phone_num'] = input('post.phone');
        //校验手机号码
        $validate = Validate::make([
            'phone_num|手机号码' => 'require|number|min:5|max:15'
        ]);
        if (!$validate->check($data)) {
            return show('短信获取失败', $validate->getError(), 4000);
        }
        // 获取短信
        // type=3为vip号码，如果是未登陆的用户，返回3000
        $redis = RedisController::getInstance('sync');
        $message_data = RedisController::zSetLatestMsg($redis, 'message:' . $data['phone_num']);
        if ($message_data) {
            if ($message_data == 'null'){
                return show('没有找到数据', '', 3000, $request->header);
            }
            // 判断该号码是否被收藏
            $access_token = $request->header()['access-token'];
            $user_info = (new FirebaseUserModel())->getUserInfoByAccessToken($access_token);
            $is_favorites = false;
            if (array_key_exists('email', $user_info)){
                // 去redis缓存里面查询
                $redis6379 = RedisController::getInstance();
                $is_favorites = $redis6379->sIsMember(Config::get('cache.prefix') . 'favorites:' . $user_info['user_id'], $data['phone_num']);
            }
            return show('获取成功', ['message'=>$message_data,'info'=>['favorites'=>$is_favorites]], 0, $request->header);
        }else{
            return show('短信获取失败，请稍候再试', '', 4000, $request->header);
        }
    }
}
