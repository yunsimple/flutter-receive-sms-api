<?php
namespace app\common\model;


class AdOrderModel extends BaseModel
{
    public function insertOrder($data): AdOrderModel
    {
        return self::create($data);
    }
}