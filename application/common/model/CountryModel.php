<?php

namespace app\common\model;


use think\facade\Config;
use think\facade\Request;

class CountryModel extends BaseModel
{
    protected $hidden = ['create_time', 'update_time', 'delete_time'];
    
    //APP 查询国家
    public function appGetCountry($page = 1, $limit = 12, $language = 'en'){
        $result = self::where('show', '=', 1)
            ->order('sort', 'desc')
            ->page($page, $limit)
            // todo 线上需要切换
            ->cache(3600)
            ->select();
        if ($result->isEmpty()){
            return 'null';
        }
        // 取得所有当前支持的语言包，然后在缓存后供筛选
        $app_language = Config::get('config.language');
        $filed = ['title', 'bh', 'id'];
        foreach ($app_language as $value){
            $filed[] = $value . '_title';
        }
        $data = $result->visible($filed);
        $new_data = [];
        foreach ($data as $key=>$value){
            //dump($value);
            // 更改获取到的语言字段 en_title 改成title
            $new_data[$key]['id'] = $value['id'];
            $new_data[$key]['bh'] = $value['bh'];
            $new_data[$key]['title'] = $value[$language . '_title'];
        }
        return $new_data;
    }
}