<?php
namespace app\rsapi\controller;


use app\common\model\UserSmsModel;
use think\Validate;

class UserController extends BaseController
{
    protected $middleware = [
        //'AuthApp',
        //'AuthUserApp'=> ['only'=>['getMy']]
    ];
    
    public function register(){
        $data['username'] = input('post.username');
        $data['password'] = input('post.password');
        $validate = Validate::make([
            'username|用户名'=> 'require|min:5|max:20',
            'password|密码'=> 'require|min:6|max:20'
        ]);
        if (!$validate->check($data)){
            return show($validate->getError(), $validate->getError(), 4000);
        }
        //判断该帐户是否存在
        $usersms_model = new UserSmsModel();
        $user_info = $usersms_model->getUserInfo($data['username']);
        if ($user_info){
            return show($data['username'] . '已经被注册，请更换用户名', '', 4000);
        }
        $data['salt'] = getRandChar(5);
        $data['password'] = md5($data['password'] . $data['salt']);
        $result = $usersms_model->insertUser($data);
        if ($result){
            return show('注册成功', $data['username']);
        }else{
            return show('注册失败', 'error', 4000);
        }
    }
    
    /**
     * 获取个人中心数据
     */
    public function getMy(){
        return json_encode(['email'=>'deepblue@163.com', 'info'=>'天空没有我的痕迹，我已经飞过']);
        return show('获取成功', ['email'=>'deepblue@163.com', 'info'=>'天空没有我的痕迹，我已经飞过']);
    }

    /**
     * 获取顶部公告信息
     */
    public function notice(){
        $notice = [
            [
                'id' => 2022031903,
                'title' => '系统将于4月1日公测，欢迎提交建议，BUG。',
                'description' => '有奖公测，欢迎提交BUG，建议，有机会赢得私有号码。',
                'type' => 'success',
                'isClose' => true
            ]
        ];
        return show('success', $notice);
    }
}