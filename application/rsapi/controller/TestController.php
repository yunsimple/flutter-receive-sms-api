<?php
namespace app\rsapi\controller;

use app\common\controller\FirebaseJwtController;
use app\common\controller\RedisController;
use app\common\model\AdOrderModel;
use app\common\model\FirebaseUserModel;
use app\common\model\PhoneModel;
use app\common\model\UserModel;
use think\Db;
use think\facade\Request;
use think\facade\Config;

class TestController
{
    public function index(){
        $phone_model = (new PhoneModel());
        $result = $phone_model->where([['type', '=', 3], ['display', '=', 1], ['show', '=', 1]])->count();
        dump($result);
    }
    
}