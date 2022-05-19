<?php
namespace app\rsapi\controller;

use app\common\controller\MailController;
use app\common\controller\RedisController;
use app\common\model\PhoneModel;
use think\Exception;
use think\facade\Config;
use think\Request;
use think\Validate;

class PhoneController extends BaseController
{
    protected $middleware = ['AuthApp'];
    protected $header = []; //自定义response返回header
    /**
     * 根据国家获取号码列表
     */
    public function getPhone(Request $request){
        $language = $request->Language;
        $data['country_id'] = input('post.country_id');
        $data['page'] = input('post.page');
        //验证country_id
        $validate = Validate::make([
            'country_id|国家ID' => 'alphaNum|max:10',
            'page|页数' => 'integer|max:4'
        ]);
        if (!$validate->check($data)) {
            return show($validate->getError(), $validate->getError(), 4000);
        }
        $redis_local = RedisController::getInstance();
        $phone_page_key = Config::get('cache.prefix') . 'cache:phone:page' . $data['page'] . ':country' . $data['country_id'];
        // todo 上线需要更改
        $redis_local_phone_value = false;$redis_local->get($phone_page_key);
        if ($redis_local_phone_value){
            //从redis取数据
            $phone_data = unserialize($redis_local_phone_value);
        }else{
            //从数据库取数据
            $phone_model = new PhoneModel();
            $phone_data = $phone_model->appGetPhone($data['country_id'], $data['page'],10, $language);
            if ($phone_data){
                $redis_local->setex($phone_page_key, 1800, serialize($phone_data));
            }
        }
        if ($phone_data) {
            if ($phone_data == 'null'){
                return show('没有找到数据', '', 3000, $request->header);
            }
            $redis_sync = RedisController::getInstance('sync');
            foreach ($phone_data as $key => $value) {
                // 更改获取到的语言字段 en_title 改成title
                $old_language = $language . '_title';
                $phone_data[$key]['country']['title'] = $value['country'][$old_language];
                unset($phone_data[$key]['country'][$old_language]);

                $update_time = $redis_sync->zRange('message:'.$value['phone_num'], -1, -1);
                if (count($update_time) > 0){
                    $update_time = unserialize($update_time[0]);
                }else{
                    $update_time['smsDate'] = (int) time() - 86400;
                }
                $phone_data[$key]['last_time'] = (int) $update_time['smsDate'];
            }
            return show('获取成功', $phone_data, 0, $request->header);
        }else{
            return show('号码列表获取失败', '', 4000, $request->header);
        }
    }
    
   public function getPhones(Request $request){
        $data['country_id'] = input('post.country_id');
        $data['page'] = input('post.page');
        //验证country_id
        $validate = Validate::make([
            'country_id|国家ID' => 'integer|max:3',
            'page|页数' => 'integer|max:4'
        ]);
        if (!$validate->check($data)) {
            return show($validate->getError(), $validate->getError(), 4000);
        }
        $new_phone_data = [];        
        for($i = 0; $i < 10; $i++){
            if($i == 3 || $i == 7){
                $new_phone_data[$i]['type'] = 'admob';
            }
            $new_phone_data[$i]['country']['bh'] = 1;
        }        
        return show('获取成功', $new_phone_data, 0, $request->header);
    }

    /**
     * 获取随机显示号码
     */
    public function getPhoneRandom(Request $request){
        $phone_model = new PhoneModel();
        $phone_num_info = $phone_model->getRandom();
        $phone_num = $phone_num_info['phone_num'];
        $bh = $phone_num_info['country']['bh'];
        if (!$phone_num){
            return show('获取随机号码失败', '', 4000, $request->header);
        }else{
            return show('获取随机号码成功', $phone_num_info->visible(['phone_num', 'total_num', 'country.id', 'country.bh', 'country.title', 'country.en_title']), 0, $request->header);
        }
    }

    public function report(Request $request): \think\response\Json
    {
        $phone_num = input('post.phone');
        $validate = Validate::make(['phone_num|phone'=>'must|number|max:15|min:6']);
        if(!$validate->check(['phone_num'=>$phone_num])){
            return show($validate->getError(), $validate->getError(), 4000);
        }
        //把提交的号码保存进入redis. report:1814266666
        $redis = RedisController::getInstance();
        $return = RedisController::stringIncrbyEx($redis, Config::get('cache.prefix') . 'report:' . $phone_num, 86400);
        if (!$return){
            return show('提交反馈失败', '', 4000, $request->header);
        }
        if (($return == 20 || $return == 25 || $return == 30) && time_section('9:00', '23:00')){
            //失败反馈处理
        }
        return show('反馈成功，等待处理', '', 0, $request->header);
    }

    public function getUpcomingPhone(): \think\response\Json
    {
        $result = (new PhoneModel())->getUpcomingNumber();
        if ($result > 0){
            return show('Request success', $result);
        }else{
            return show('Request success,data is empty',$result, 1);
        }
    }

    public function getNewPhone(): \think\response\Json
    {
        $count = (new PhoneModel())->getNewPhone(15);
        if ($count > 0){
            return show('Request success', $count);
        }else{
            return show('Request success,data is empty',0, 1);
        }
    }
}