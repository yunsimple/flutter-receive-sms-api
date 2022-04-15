<?php

namespace app\common\model;


use think\facade\Request;

class CountryModel extends BaseModel
{
    protected $hidden = ['create_time', 'update_time', 'delete_time'];
    
    //APP 查询国家
    public function appGetCountry($page = 1, $limit = 20){
        $result = self::where('show', '=', 1)
            ->order('sort', 'desc')
            ->page($page, $limit)
            ->cache(3600)
            ->select();
        return $result->visible(['title','en_title', 'bh', 'id']);
    }
}