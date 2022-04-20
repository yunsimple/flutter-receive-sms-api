<?php

namespace app\rsapi\controller;

use app\common\model\CountryModel;
use think\Request;
use think\Validate;

class CountryController extends BaseController
{
    protected $middleware = ['AuthApp'];

    protected $header = []; //自定义response返回header
    /**
     * 获取所有在线国家列表
     */
    public function getCountry(Request $request)
    {
        $data['page'] = input('post.page');
        //数据校验
        $validate = Validate::make([
            'page|页数' => 'integer|max:4'
        ]);
        if (!$validate->check($data)) {
            return show($validate->getError(), $validate->getError(), 4000);
        }
        $page = ($data['page']) ? $data['page'] : 1 ;
        $country_data = (new CountryModel())->appGetCountry($page);
        if ($country_data){
            if ($country_data->isEmpty()){
                return show('没有找到数据', '', 3000, $request->header);
            }
            return show('获取成功', $country_data, 0, $request->header);
        }else{
            return show('国家列表获取失败', '', 4000);
        }
    }
}
