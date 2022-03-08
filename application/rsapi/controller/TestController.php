<?php
namespace app\rsapi\controller;

use app\common\controller\RedisController;
use app\common\controller\RedisController1;

class TestController
{
    public function index(){
        $redis1 = RedisController::getInstance();
        $redis2 = RedisController::getInstance('sync');
        $result = $redis1->set('rsApi:test', 1111);
        dump($result);
        $result2 = $redis2->set('rsApi:test', 2222);
        dump($result2);
        (RedisController::getInstance())->set('rsApi:test1', 333);
        (RedisController::getInstance())->set('rsApi:test3', 333);
        dump(RedisController::count());
    }
}