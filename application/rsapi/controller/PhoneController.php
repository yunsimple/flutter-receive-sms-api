<?php
namespace app\rsapi\controller;

use app\common\controller\MailController;
use app\common\model\PhoneModel;
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
        $redis_local = new RedisController();
        $phone_page_key = 'app:cache:phone:page' . $data['page'] . ':country' . $data['country_id'];
        $redis_local_phone_value = $redis_local->get($phone_page_key);
        if ($redis_local_phone_value){
            //从redis取数据
            $phone_data = unserialize($redis_local_phone_value);
        }else{
            //从数据库取数据
            $phone_model = new PhoneModel();
            $phone_data = $phone_model->appGetPhone($data['country_id'], $data['page']);
            if ($phone_data){
                $redis_local = new RedisController();
                $redis_local->setexCache($phone_page_key, serialize($phone_data));
            }
        }
        if ($phone_data) {
            $phone_data = $phone_data->toArray();
            $redis = new RedisController('sync');
            foreach ($phone_data as $key => $value) {
                if ($key == 3 || $key == 7){
                    $phone_data[$key]['type'] = 'admob';
                }
                $update_time = $redis->zLast($value['phone_num'], -1, -1);
                $update_time = unserialize($update_time[0]);
                $phone_data[$key]['last_time'] = $update_time['smsDate'];
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
            return show('获取随机号码成功', ['phone_num'=>$phone_num, 'bh'=>$bh], 0, $request->header);
        }
    }

    public function report(Request $request){
        $phone_num = input('post.phone_num');
        $validate = Validate::make(['phone_num|电话号码'=>'must|number|max:15|min:6']);
        if(!$validate->check(['phone_num'=>$phone_num])){
            return show($validate->getError(), $validate->getError(), 4000);
        }
        //把提交的号码保存进入redis. respore_1814266666
        $redis = new RedisController('master');
        $return = $redis->getStringIncExpire('report_' . $phone_num, 86400);
        if (!$return){
            return show('提交反馈失败', '', 4000, $request->header);
        }
        if (($return == 20 || $return == 25 || $return == 30) && time_section('9:00', '23:00')){
            (new MailController())->noticeMail($phone_num . '第【'.$return.'】次反馈失败');
        }
        return show('反馈成功，等待处理', '', 0, $request->header);
    }
}