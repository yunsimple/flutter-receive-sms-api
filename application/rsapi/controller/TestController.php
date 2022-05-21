<?php
namespace app\rsapi\controller;

use app\common\controller\FirebaseJwtController;
use app\common\controller\RedisController;
use think\facade\Request;

class TestController
{
    public function index(){
        $arr = ['id', 'phone_num', 'total_num', 'show', 'country.id', 'country_title', 'country.bh'];
        $config_arr = config('config.language');
        foreach ($config_arr as $value){
            $arr[] = $value;
        }
        print_r($arr);
    }
}