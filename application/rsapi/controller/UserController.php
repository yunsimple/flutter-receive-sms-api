<?php

namespace app\rsapi\controller;


use app\common\model\FirebaseUserModel;
use app\common\model\PhoneModel;
use app\common\model\UserSmsModel;
use think\facade\Config;
use think\Request;
use think\Validate;
use think\Db;
use app\common\controller\RedisController;
use app\common\model\AdOrderModel;

class UserController extends BaseController
{
    protected $middleware = [
        'AuthApp' => ['except' => ['notice']],
    ];

    public function register()
    {
        $data['username'] = input('post.username');
        $data['password'] = input('post.password');
        $validate = Validate::make([
            'username|用户名' => 'require|min:5|max:20',
            'password|密码' => 'require|min:6|max:20'
        ]);
        if (!$validate->check($data)) {
            return show($validate->getError(), $validate->getError(), 4000);
        }
        //判断该帐户是否存在
        $usersms_model = new UserSmsModel();
        $user_info = $usersms_model->getUserInfo($data['username']);
        if ($user_info) {
            return show($data['username'] . '已经被注册，请更换用户名', '', 4000);
        }
        $data['salt'] = getRandChar(5);
        $data['password'] = md5($data['password'] . $data['salt']);
        $result = $usersms_model->insertUser($data);
        if ($result) {
            return show('注册成功', $data['username']);
        } else {
            return show('注册失败', 'error', 4000);
        }
    }

    /**
     * 获取个人中心数据
     */
    public function getMy(): \think\response\Json
    {
        $firebase_user_model = new FirebaseUserModel();
        $user_info = $firebase_user_model->getUserInfoByAccessToken();
        //trace($user_info, 'notice');
        return show('Success',
            [
                'upcomingTime' => (int)(new PhoneModel())->getUpcomingTime(),
                'userInfo' => [
                    'coins' => $firebase_user_model
                        ->where('user_id', $user_info['user_id'])
                        ->cache($user_info['user_id'] . 'coins', 3600)
                        ->value('coins')
                ]
            ]
        );
    }

    /**
     * 获取顶部公告信息
     * type [info / danger]
     */
    public function notice(): \think\response\Json
    {
        return show('success');
        $description = [
            'en' => 'There are prizes for public beta, welcome to submit bugs, suggestions, and have a chance to win surprises.',
            'zh' => '公测有奖，欢迎提交bug、建议，有机会赢取惊喜。',
            'pt' => 'Há prêmios para o beta público, bem-vindo ao enviar bugs, sugestões e ter a chance de ganhar surpresas.',
            'de' => 'Es gibt Preise für die öffentliche Beta, Sie können gerne Fehler und Vorschläge einreichen und haben die Chance, Überraschungen zu gewinnen'
        ];
        $headers = getallheaders();
        $language = 'en';
        if (array_key_exists('Language', $headers)) {
            $language_header = $headers['Language'];
            if (array_key_exists($language_header, $description)){
                $language = $language_header;
            }
        }
        $notice = [
            [
                'id' => 202200511123511,
                'description' => $description[$language],
                'type' => 'info',
                'isClose' => true
            ],
        ];
        return show('success', $notice);
    }
    
    // 合并本地账号
    public function mergeUser()
    {
        $data['old_userid'] = input('post.oldUserId');
        // 验证old_userid
        $validate = Validate::make([
            'old_userid|old_userid' => 'require|alphaNum|max:32',
        ]);
        if (!$validate->check($data)) {
            return show((string)$validate->getError(), $validate->getError(), 4000);
        }
        // 合并金币
        $firebase_user_model = new FirebaseUserModel();
        $user_id = $firebase_user_model->getUserInfoByAccessToken('', 'user_id');
        $old_user_coins = $firebase_user_model->where('user_id', $data['old_userid'])->value('coins');
        $redis_local = RedisController::getInstance();

        Db::startTrans();
        try {
            if ($old_user_coins > 0){
                // 合并账户金币
                $firebase_user_model->where('user_id', $user_id)->setInc('coins', $old_user_coins);
                $firebase_user_model->where('user_id', $data['old_userid'])->delete();
                $redis_local->del(Config::get('cache.prefix') . $user_id . 'coins');
            }
            
            // 合并购买过的号码
            (new AdOrderModel())->where('user_id', $data['old_userid'])->setField('user_id', $user_id);

            // 合并收藏，先判断当前的user_id是否存在收藏，如果不存在，直接改名，存在的话，遍历写入新的user_id
            $favorites_key = Config::get('cache.prefix') . 'favorites:' . $user_id;
            $favorites_key_old = Config::get('cache.prefix') . 'favorites:' . $data['old_userid'];
            $redis_sync = RedisController::getInstance('sync');
            
            if ($redis_sync->exists($favorites_key_old)){
                $favorites_phones = $redis_sync->sMembers($favorites_key_old);
                $redis_master = RedisController::getInstance('master');
                $redis_master->sAddArray($favorites_key, $favorites_phones);
                // 删除老用户的收藏记录
                $redis_master->del($favorites_key_old);
                // 删除收藏缓存
                $phone_page_key = Config::get('cache.prefix') . 'cache:phone:favorites:' . $user_id;
                $redis_local->del($phone_page_key);
            }
            Db::commit();
            return show('Success');
        } catch (\Exception $e) {
            Db::rollback();
            trace('金币合并失败', 'notice');
            trace('$user_id = ' . $user_id . 'old_userid = ' .  $data['old_userid'] . '$old_user_coins = ' . $old_user_coins, 'notice');
            trace($favorites_phones, 'notice');
            trace($e->getMessage(), 'error');
            return show('Fail', '', 4000);
        }
    }
    
    public function deleteUser(){
        $result = (new FirebaseUserModel)->delete();
        if($result){
            return show('Success');
        }else{
            return show('Fail', '', 4000);
        }
    }
}