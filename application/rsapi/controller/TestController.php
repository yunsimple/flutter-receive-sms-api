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

class TestController
{
    public function index(){
        $phone_model = new PhoneModel();
        $phone_num = 9092337178;
        $page = 1;
        $phone_id = 9354182739;//$phone_model->getPhoneDetail($phone_num, 'id');
        dump($phone_id);
        $result = Db::connect('db_history')
            ->table('collection_msg')
            ->where('phone_id', '=', $phone_id)
            //->cache($phone_id . '_' . $page, 86400)
            ->page($page, 20)
            ->select();
        dump($result);
    }
}