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

        $redis_local = new RedisController();
        $redis_key = 'app:cache:country:page:' . $page;
        $redis_cache_value = $redis_local->get($redis_key);
        if ($redis_cache_value){
            $country_data = unserialize($redis_cache_value);
        }else{
            $country_data = (new CountryModel())->appGetCountry($page);
            //国家名称小写字母
            if ($country_data) {
                foreach ($country_data as $key => $value) {
                    $country_data[$key]['en_title'] = strtolower($country_data[$key]['en_title']);
                }
            }
            $redis_local->setexCache($redis_key, serialize($country_data));
        }
        if ($country_data){
            return show('获取成功', $country_data, 0, $request->header);
        }else{
            return show('国家列表获取失败', '', 4000);
        }
    }
}
