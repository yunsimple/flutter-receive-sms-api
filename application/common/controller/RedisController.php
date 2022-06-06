<?php
namespace app\common\controller;

use think\facade\Config;

class RedisController
{
    protected static $_instance = [];
    protected static $redis;

    /**
     * Redis constructor.
     * @param $host
     * @param $port
     * @param $auth
     * @param $db
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
     * @param string $select
     * @param int $db redis几号数据库
     * @param string|null $uniqID 当相同配置，但是想新开实例时，可以赋值
     * @return \Redis
     * @author limx
     */
    public static function getInstance(string $select = 'local', int $db = 0, string $uniqID = null): \Redis
    {
        if ($select == 'sync'){
            $host = Config::get('config.sync_host');
            $auth = Config::get('config.sync_auth');
            $db   = Config::get('config.sync_db');
            $port = Config::get('config.sync_port');
        }elseif($select == 'master'){
            $host = Config::get('config.master_host');
            $auth = Config::get('config.master_auth');
            $db   = Config::get('config.master_db');
            $port = Config::get('config.master_port');
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
            $redis->select((int)$db);
        }
        self::$redis = $redis;
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

    /**
     * 向set集合添加数据，并设置时间，如果有效期内存在，则返回false
     * @param \Redis $redis
     * @param string $key
     * @param mixed $value
     * @param int $expire
     * @return bool
     */
    public static function sAddEx(\Redis $redis, string $key, $value = 1, int $expire = 3600): bool
    {
        $result = $redis->sAdd($key, $value);
        if ($result){
            if ($expire > 0){
                $redis->expire($key, $expire);
            }
            return true;
        }else{
            return false;
        }
    }

    public static function zSetLatestMsg($redis, $key, $num = 19){
        $result = $redis->zRevRange($key, 0, $num);
        $data = [];
        foreach ($result as $key => $value){
            $data[$key] = unserialize($value);
            $data[$key]['smsDate'] = (int)$data[$key]['smsDate'];
        }
        if (count($data) > 0){
            return $data;
        }else{
            return 'null';
        }
    }

    public static function stringIncrbyEx($redis, $key, $expire = 1800, $incr = 1){
        $num = $redis->incrBy($key, $incr);
        if ($num && $expire > 0){
            $ttl = $redis->expire($key, $expire);
            if ($ttl){
                return $num;
            }
        }
        return false;
    }

    public static function hMsetEx($redis, $key, $value, $expire){
        if (!is_array($value))
            return 2;
        $redis->hMset($key, $value);
        return $redis->expire($key, $expire);
    }
}