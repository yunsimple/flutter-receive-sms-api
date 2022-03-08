<?php
namespace app\common\controller;

use think\facade\Env;

class RedisController
{
    protected static $_instance = [];
    protected $redis;

    /**
     * Redis constructor.
     * @param $host
     * @param $port
     * @param $auth
     */
    private function __construct($host, $port, $auth, $db)
    {
    }

    /**
     * 防止克隆
     */
    private function __clone()
    {
    }

    /**
     * @desc   获取redis单例
     * @author limx
     * @param int $db redis几号数据库
     * @param string $uniqID 当相同配置，但是想新开实例时，可以赋值
     * @return \Redis
     */
    public static function getInstance($select = 'local', $db = 0, $uniqID = null)
    {
        if ($select == 'sync'){
            $host = Env::get('sync_host');
            $auth = Env::get('sync_auth');
            $db   = Env::get('sync_db');
            $port = Env::get('sync_port');
        }elseif($select == 'master'){
            $host = Env::get('master_host');
            $auth = Env::get('master_auth');
            $db   = Env::get('master_db');
            $port = Env::get('master_port');
        }else{
            $host = '127.0.0.1';
            $auth = null;
            $port = 6379;
        }

        $key = md5(json_encode([$host, $auth, $db, $port, $uniqID]));
        if (isset(static::$_instance[$key]) && static::$_instance[$key] instanceof \Redis) {
            return static::$_instance[$key];
        }
        return static::$_instance[$key] = static::getClient($host, $port, $auth, $db);
    }

    /**
     * @desc   获取Redis实例
     * @author limx
     * @param $host ip地址
     * @param $port 端口
     * @param $auth 密码
     * @param $db   默认DB库
     * @return \Redis
     */
    protected static function getClient($host, $port, $auth, $db)
    {
        $redis = new \Redis();
        $redis->connect($host, $port);
        if (!empty($auth)) {
            $redis->auth($auth);
        }
        if ($db > 0) {
            $redis->select($db);
        }
        return $redis;
    }

    /**
     * @desc   返回实例数
     * @author limx
     * @return int
     */
    public static function count()
    {
        return count(static::$_instance);
    }
}