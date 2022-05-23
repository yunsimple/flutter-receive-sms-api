<?php
namespace app\rsapi\controller;

use app\common\controller\FirebaseJwtController;
use app\common\controller\RedisController;
use app\common\model\AdOrderModel;
use think\facade\Request;

class TestController
{
    public function index(){
        $isBuy = AdOrderModel::where('user_id','N3mAWR1Gr6OK1oikGSJn1hVuELq11')->cache(3600)->find();
        if ($isBuy){
            echo 1;
        }else{
            echo 0;
        }
    }
}