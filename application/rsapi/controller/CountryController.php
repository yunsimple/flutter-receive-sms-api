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
        $language = $request->Language;
        $data['page'] = input('post.page');
        //数据校验
        $validate = Validate::make([
            'page|page' => 'integer|max:4'
        ]);
        if (!$validate->check($data)) {
            return show($validate->getError(), $validate->getError(), 4000);
        }
        $page = ($data['page']) ? $data['page'] : 1 ;
        $country_data = (new CountryModel())->appGetCountry($page, 12, $language);
        if ($country_data){
            if ($country_data == 'null'){
                return show('No data', '', 3000, $request->header);
            }
            return show('Success', $country_data, 0, $request->header);
        }else{
            return show('Fail', '', 4000);
        }
    }
}
