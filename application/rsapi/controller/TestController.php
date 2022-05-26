<?php
namespace app\rsapi\controller;

use app\common\controller\FirebaseJwtController;
use app\common\controller\RedisController;
use app\common\model\AdOrderModel;
use app\common\model\FirebaseUserModel;
use app\common\model\UserModel;
use think\facade\Request;

class TestController
{
    public function index(){
        $userid= 'N3mAWR1Gr6OK1oikGSJn1hVuELq1';
        $test = (new FirebaseUserModel())
            ->where('user_id', $userid)
            ->cache($userid . 'coins', 3600)
            ->value('coins');
        dump($test);
    }
}