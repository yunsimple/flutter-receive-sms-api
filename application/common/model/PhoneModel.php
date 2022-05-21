<?php

namespace app\common\model;

use app\common\controller\RedisController;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Request;
use think\Model;
use think\model\concern\SoftDelete;

class PhoneModel extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //关联模型
    public function country(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo('CountryModel', 'country_id', 'id');
    }
    public function warehouse(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo('WarehouseModel', 'warehouse_id', 'id');
    }
    
    //前台随机获取一个号码显示
    public function getRandom(): PhoneModel
    {
        return self::with('country')
            ->where('show', '=', 1)
            ->where('online', '=', 1)
            ->where('type', '<', 3)
            ->whereIn('warehouse_id', '26,27')
            ->orderRand()
            ->find();
    }

    /**
     * APP 根据条件获取号码列表
     */
    public function appGetPhone($country_id = null, $page = 1, $limit = 10){
        if (empty($country_id)){
            $result = self::with('country')
                ->where('show', '=', 1)
                ->where('type', '=', 1)
                //->where('online', '=', 1)
                //->order('online', 'desc')
                ->order('sort', 'desc')
                ->order('id', 'desc')
                ->page($page, $limit)
                ->select();
        }elseif ($country_id == 'upcoming'){
            $result = self::with('country')
                ->where('show', '=', 1)
                ->where('type', '=', 2)
                //->where('online', '=', 1)
                //->order('online', 'desc')
                ->order('sort', 'desc')
                ->order('id', 'desc')
                ->page($page, $limit)
                ->select();
        }elseif ($country_id == 'favorites'){
            // 获取该用户收藏的号码
            $user_info = (new FirebaseUserModel())->getUserInfoByAccessToken('', 'user_id');
            $favorites_set_key = Config::get('cache.prefix') . 'favorites:' . $user_info;
            $redis_sync = RedisController::getInstance('master');
            $phones = $redis_sync->sMembers($favorites_set_key);
            $result = self::with('country')
                ->where('show', '=', 1)
                //->where('online', '=', 1)
                ->whereIn('phone_num', $phones)
                //->order('online', 'desc')
                ->order('sort', 'desc')
                ->order('id', 'desc')
                ->page($page, $limit)
                ->select();
        }elseif ($country_id == 'vip'){
            $result = self::with('country')
                ->where('show', '=', 1)
                ->where('type', '=', 3)
                //->where('online', '=', 1)
                //->order('online', 'desc')
                ->order('sort', 'desc')
                ->order('id', 'desc')
                ->page($page, $limit)
                ->select();
        }else{
            $result = self::with('country')
                ->where('country_id', 'in', $country_id)
                ->where('show', '=', 1)
                ->where('type', '=', 1)
                //->where('online', '=', 1)
                //->order('online', 'desc')
                ->order('sort', 'desc')
                ->order('id', 'desc')
                ->page($page, $limit)
                ->select();
        }
        if ($result && !$result->isEmpty()){
            // 取得所有当前支持的语言包，然后在缓存后供筛选
            $app_language = Config::get('config.language');
            $filed = ['id', 'phone_num', 'total_num', 'show', 'country.id', 'country.bh'];
            foreach ($app_language as $value){
                $filed[] = 'country.' . $value . '_title';
            }
            return $result->visible($filed)->toArray();
        }else{
            return 'null';
        }
    }

    //重构，查询号码详情，并缓存
    public function getPhoneDetail($phone_num, $field = ''){
        // todo web和app如果有重复的号码，那phone_detail就需要放在不同的目录
        $phone_detail_key = Config::get('cache.prefix') . 'phone_detail:' . $phone_num;
        $result = RedisController::getInstance('sync')->get($phone_detail_key);
        if ($result){
            if ($field){
                return unserialize($result)[$field];
            }
            return unserialize($result);
        }else{
            $result = self::with(['country', 'warehouse'])
                ->where('phone_num', $phone_num)
                ->where('show', '=', 1)
                ->find();
            if ($result && !$result->isEmpty()){
                $result = $result->visible(['id','online', 'phone_num','type', 'total_num', 'show', 'country.id', 'country.en_title', 'country.title', 'country.bh', 'warehouse.title'])->toArray();
                RedisController::getInstance('master')->set($phone_detail_key, serialize($result));
                if ($field){
                    return $result[$field];
                }
                return $result;
            }else{
                return false;
            }
        }
    }

    //获取upcoming号码数据
    public function getUpcomingNumber(){
        return self::where('display', 1)
            ->where('online', 1)
            ->where('show', 1)
            ->where('type', 2)
            ->cache('upcoming_number',1800)
            ->count();
    }

    // 获取最近多长时间最新上线的号码
    public function getNewPhone($day = 15){
        $time = time();
        return self::where('display', 1)
            ->where('online', 1)
            ->where('show', 1)
            //->where('type', 2)
            ->whereTime('create_time', 'between', [$time-($day*86400),$time])
            ->cache('upcoming_number',1800)
            ->count();
    }

    // 获取预告号码上线时间
    public function getUpcomingTime(){
        $redis = RedisController::getInstance('sync');
        return $redis->get(Config::get('cache.prefix') . 'phone_online_time');
    }

}