<?php
namespace app\rsapi\controller;

use app\common\controller\FirebaseJwtController;
use app\common\controller\RedisController;
use app\common\model\AdOrderModel;
use think\facade\Request;

class TestController
{
    public function index(){
        dump(phpinfo());
    }
}