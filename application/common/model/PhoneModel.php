<?php

namespace app\common\model;

use app\common\controller\RedisController;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Request;
use think\model\concern\SoftDelete;

class PhoneModel extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //关联模型
    public function country(){
        return $this->belongsTo('CountryModel', 'country_id', 'id');
    }
    public function warehouse(){
        return $this->belongsTo('WarehouseModel', 'warehouse_id', 'id');
    }
    //后台首页调用所有号码
    public function adminGetAllPhone($page, $limit){
        $result = self::with(['country', 'warehouse'])
            ->page($page, $limit)
            ->order('online', 'desc')
            ->order('sort', 'desc')
            ->order('country_id', 'asc')
            ->order('warehouse_id', 'desc')
            ->order('id', 'desc')
            ->select();
        $result = $result->hidden(['update_time','delete_time', 'country.id', 'country.bh', 'country.show', 'warehouse.id', 'warehouse.show', 'country_id', 'warehouse_id']);
        return $result;
    }
    
        //后台首页调用所有号码
    public function adminGetNormalPhone($page, $limit){
        $result = self::with(['country', 'warehouse'])
            ->where('show', '=', 1)
            ->where('online', '=', 1)
            ->page($page, $limit)
            ->order('online', 'desc')
            ->order('sort', 'desc')
            ->order('country_id', 'asc')
            ->order('warehouse_id', 'desc')
            ->order('id', 'desc')
            ->select();
        $result = $result->hidden(['update_time','delete_time', 'country.id', 'country.bh', 'country.show', 'warehouse.id', 'warehouse.show', 'country_id', 'warehouse_id']);
        return $result;
    }

    //后台搜索号码
    public function adminGetPhone($phone_num){
        $result = self::with(['country', 'warehouse'])
            ->where('phone_num', 'like', '%' . $phone_num . '%')
            ->order('online', 'desc')
            ->select();
        $result = $result->hidden(['update_time','delete_time', 'country.id', 'country.bh', 'country.show', 'warehouse.id', 'warehouse.show', 'country_id', 'warehouse_id']);
        return $result;
    }

    //后台根据仓库搜索号码
    public function searchWarehouse($warehouse, $page, $limit){
        $result = self::with(['country', 'warehouse'])
            ->where('warehouse_id', '=', $warehouse)
            ->order('online', 'desc')
            ->page($page, $limit)
            ->order('id', 'desc')
            ->order('country_id', 'asc')
            ->select();
        return $result;
    }
    
    //后台根据国家搜索号码
    public function searchCountry($country, $page, $limit){
        $result = self::with(['country', 'warehouse'])
            ->where('country_id', '=', $country)
            ->order('online', 'desc')
            ->page($page, $limit)
            ->order('id', 'desc')
            ->order('sort', 'desc')
            ->select();
        return $result;
    }

    //后台根据仓库搜索号码 总条数
    public function warehouseCount($id){
        $result = self::where('warehouse_id', '=', $id)
            ->count();
        return $result;
    }
    
    //后台根据国家搜索号码 总条数
    public function countryCount($id){
        $result = self::where('country_id', '=', $id)
            ->count();
        return $result;
    }

    //后台批量删除
    public function deleteMany($id){
        $result = self::destroy($id, true);
        return $result;
    }

    //后台调用数据总数
    public function adminGetCountNuber(){
        $result = self::count();
        return $result;
    }

    //前台调用数据总数
    public function getCountNuber(){
        $result = self::where('show', '=', 1)
            ->count();
        return $result;
    }
    //查询离线号码
    public function offlineNumber(){
        $result = self::where('online', '=', 0)
            ->where('show', '=', 1)
            ->count();
        return $result;
    }
    //查询最近一周新添加的号码
    public function monthCreateNuber(){
        $result = self::where('show', '=', 1)
            ->whereTime('create_time', 'month')
            ->count();
        return $result;
    }
    //前台查询所有号码信息
    public function getAllPhoneNum($page = 1, $limit = 21){
        $result = self::with('country')
            ->where('show', '=', 1)
            ->order('sort', 'desc')
            ->order('warehouse_id', 'desc')
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();
        return $result;
    }
    //前台按条件查询号码
    public function getPartPhoneNum($region){
        switch ($region){
            case 'dl':
                $result = self::with('country')
                    ->where('country_id', '=', 1)
                    ->where('show', '=', 1)
                    ->order('online', 'desc')
                    ->order('sort', 'desc')
                    //->order('warehouse_id', 'desc')
                    ->order('id', 'desc')
                    ->paginate(10, false, [
                        'page'=>input('param.page')?:1,
                        'path'=>Request::domain().'/dl/[PAGE].html'
                    ]);
                break;
            case 'gat':
                $result = self::with('country')
                    ->where('country_id', 'between', '7,8')
                    ->where('show', '=', 1)
                    ->order('online', 'desc')
                    ->order('sort', 'desc')
                    //->order('warehouse_id', 'desc')
                    ->order('id', 'desc')
                    ->paginate(10, false, [
                        'page'=>input('param.page')?:1,
                        'path'=>Request::domain().'/gat/[PAGE].html'
                    ]);
                break;
            case 'gw':
                $result = self::with('country')
                    ->where('country_id', '<>', 1)
                    ->where('country_id', '<>', 7)
                    ->where('country_id', '<>', 8)
                    ->where('show', '=', 1)
                    ->order('online', 'desc')
                    ->order('sort', 'desc')
                    //->order('warehouse_id', 'desc')
                    ->order('id', 'desc')
                    ->paginate(10, false, [
                        'page'=>input('param.page')?:1,
                        'path'=>Request::domain().'/gw/[PAGE].html'
                    ]);
        }
        return $result;
    }

    //小程序调用API
    public function xcxPartPhoneNum($region, $page = 1, $limit = 21){
        switch ($region){
            case 'dl':
                $result = self::with('country')
                    ->where('country_id', '=', 1)
                    ->where('show', '=', 1)
                    ->order('online', 'desc')
                    ->order('sort', 'desc')
                    ->order('id', 'desc')
                    ->page($page, $limit)
                    ->select();
                break;
            case 'gat':
                $result = self::with('country')
                    ->where('country_id', '=', 7)
                    ->where('show', '=', 1)
                    ->order('online', 'desc')
                    ->order('sort', 'desc')
                    ->order('id', 'desc')
                    ->page($page, $limit)
                    ->select();
                break;
            case 'gw':
                $result = self::with('country')
                    ->where('country_id', '<>', 1)
                    ->where('country_id', '<>', 7)
                    ->where('show', '=', 1)
                    ->order('online', 'desc')
                    ->order('sort', 'desc')
                    ->order('id', 'desc')
                    ->page($page, $limit)
                    ->select();
        }
        return $result;
    }


    //查询各地区号码总数,上一个方法的集合
    public function getRegionNumberCount($region = 'all'){
        switch ($region){
            case 'dl':
                $region_count = self::with('country')
                    ->where('country_id', '=', 1)
                    ->where('show', '=', 1)
                    ->count();
            break;
            case 'gat':
                $region_count = self::with('country')
                    ->where('country_id', '=', 7)
                    ->where('show', '=', 1)
                    ->count();
                break;
            case 'gw':
                $region_count = self::with('country')
                    ->where('country_id', '<>', 1)
                    ->where('country_id', '<>', 7)
                    ->where('show', '=', 1)
                    ->count();
                break;
            default:
                $region_count['dl'] = self::with('country')
                    ->where('country_id', '=', 1)
                    ->where('show', '=', 1)
                    ->count();
                $region_count['gat'] = self::with('country')
                    ->where('country_id', '=', 7)
                    ->where('show', '=', 1)
                    ->count();
                $region_count['gw'] = self::with('country')
                    ->where('country_id', '<>', 1)
                    ->where('country_id', '<>', 7)
                    ->where('show', '=', 1)
                    ->count();
        }
            return $region_count;
    }
    
    //前台随机获取一个号码显示
    public function getRandom(){
        $result = self::with('country')
            ->where('show', '=', 1)
            ->where('online', '=', 1)
            ->whereIn('warehouse_id', '26,27')
            ->orderRand()
            ->find();
        return $result;
    }

    /**
     * APP 根据条件获取号码列表
     */
    public function appGetPhone($country_id = null, $page = 1, $limit = 10){
        if (empty($country_id)){
            $result = self::with('country')
                ->where('show', '=', 1)
                ->where('type', '=', 1)
                ->where('online', '=', 1)
                ->order('online', 'desc')
                ->order('sort', 'desc')
                ->order('id', 'desc')
                ->page($page, $limit)
                ->select();
        }else{
            $result = self::with('country')
                ->where('country_id', 'in', $country_id)
                ->where('show', '=', 1)
                ->where('type', '=', 1)
                ->where('online', '=', 1)
                ->order('online', 'desc')
                ->order('sort', 'desc')
                ->order('id', 'desc')
                ->page($page, $limit)
                ->select();
        }
        if ($result && !$result->isEmpty()){
            return $result->visible(['id', 'phone_num', 'total_num', 'show', 'country.id', 'country.en_title', 'country.title', 'country.bh'])->toArray();
        }else{
            return 'null';
        }
    }

    //重构，查询号码详情，并缓存
    public function getPhoneDetail($phone_num){
        $phone_detail_key = Config::get('cache.prefix') . 'phone_detail:' . $phone_num;
        $result = RedisController::getInstance('sync')->get($phone_detail_key);
        if ($result){
            return unserialize($result);
        }else{
            $result = self::with(['country', 'warehouse'])
                ->where('phone_num', $phone_num)
                ->where('show', '=', 1)
                ->find();
            if ($result && !$result->isEmpty()){
                $result = $result->visible(['id', 'phone_num', 'total_num', 'show', 'country.id', 'country.en_title', 'country.title', 'country.bh', 'warehouse.title'])->toArray();
                RedisController::getInstance('master')->set($phone_detail_key, serialize($result));
                return $result;
            }else{
                return false;
            }
        }
    }
}