<?php
namespace app\rsapi\controller;

use app\common\controller\RedisController;

class TestController
{
    public function index(){
        halt(2222);
    }

    public function r($redis){
        $redis->set('rsApi:ttl',5);
        $redis->expire('rsApi:ttl', 1000);
        return $redis->get('rsApi:ttl');
    }
}