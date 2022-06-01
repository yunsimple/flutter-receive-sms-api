<?php

namespace app\rsapi\controller;


use app\common\model\FirebaseUserModel;
use app\common\model\PhoneModel;
use app\common\model\UserSmsModel;
use think\facade\Config;
use think\Request;
use think\Validate;

class UserController extends BaseController
{
    protected array $middleware = [
        'AuthApp' => ['only' => ['getMy']],
        //'AuthUserApp'=> ['only'=>['getMy']]
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
        return show('Success',
            [
                'upcomingTime' => (int)(new PhoneModel())->getUpcomingTime(),
                'version' => 1.6,
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
                'id' => 20220051112351,
                'description' => $description[$language],
                'type' => 'info',
                'isClose' => true
            ],
        ];
        return show('success', $notice);
    }
}