<?php
namespace app\rsapi\controller;


class ParamsController extends BaseController
{
    //protected $middleware = ['AuthApp'];
    protected $header = []; //自定义response返回header

    /**
     * APP重新打开后需要获取的数据
     * banner_default = banner地址，
     * notice = 公告
     * subtitle = 副标题（可作临时公告）
     * cache = 请求失败后，是否启用缓存数据  布尔 true / false
     */
    public function getParams(){
        $data = [
            'banner_default' => '',
            'notice' => [
                ["title" => "天道酬勤，有你必行。"],
            ],
            'subtitle' => '永久免费的接码软件',
            'cache' => true
        ];
        return show('success', $data);
    }
}