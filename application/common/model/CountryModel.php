<?php

namespace app\common\model;


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
            //->cache(3600)
            ->select();

        // app需要当前语言的名称
        $app_language = $language . '_title';
        $data = $result->visible(['title',$app_language, 'bh', 'id']);
        foreach ($data as $key=>$value){
            $data[$key]['title'] = $value[$language . '_title'];
            unset($data[$key][$language . '_title']);
        }
        return $data;
    }
}