<?php
namespace app\rsapi\controller;

use app\common\controller\MailController;
use app\common\controller\RedisController;
use app\common\model\PhoneModel;
use think\Exception;
use think\facade\Config;
use think\Request;
use think\response\Json;
use think\Validate;

class PhoneController extends BaseController
{
    protected array $middleware = ['AuthApp'];
    protected array $header = []; //自定义response返回header
    /**
     * 根据国家获取号码列表
     */
    public function getPhone(Request $request): \think\response\Json
    {
        $language = $request->Language;
        $data['country_id'] = input('post.country_id');
        $data['page'] = input('post.page');
        //验证country_id
        $validate = Validate::make([
            'country_id|Country ID' => 'alphaNum|max:10',
            'page|Page' => 'integer|max:4'
        ]);
        if (!$validate->check($data)) {
            return show($validate->getError(), $validate->getError(), 4000);
        }
        $redis_local = RedisController::getInstance();
        $phone_page_key = Config::get('cache.prefix') . 'cache:phone:page' . $data['page'] . ':country' . $data['country_id'];

        // 读取缓存
        // todo 上线需要更改缓存
        $redis_local_phone_value = false;//$redis_local->get($phone_page_key);
        if ($redis_local_phone_value){
            //从redis取数据
            $phone_data = unserialize($redis_local_phone_value);
        }else{
            //从数据库取数据
            $phone_model = new PhoneModel();
            $phone_data = $phone_model->appGetPhone($data['country_id'], $data['page'],10);
            if ($phone_data){
                $redis_local->setex($phone_page_key, 1800, serialize($phone_data));
            }
        }
        //halt($phone_data);
        if ($phone_data) {
            if ($phone_data == 'null'){
                return show('No data', '', 3000, $request->header);
            }
            $redis_sync = RedisController::getInstance('sync');
            foreach ($phone_data as $key => $value) {
                // 更改获取到的语言字段 en_title 改成title
                $title = $value['country'][$language . '_title'];
                $id = $value['country']['id'];
                $bh = $value['country']['bh'];
                unset($phone_data[$key]['country']);
                $phone_data[$key]['country']['id'] = $id;
                $phone_data[$key]['country']['bh'] = $bh;
                $phone_data[$key]['country']['title'] = $title;

                $update_time = $redis_sync->zRange('message:'.$value['phone_num'], -1, -1);
                if (count($update_time) > 0){
                    $update_time = unserialize($update_time[0]);
                }else{
                    $update_time['smsDate'] = (int) time() - 86400;
                }
                $phone_data[$key]['last_time'] = (int) $update_time['smsDate'];
            }
            return show('Success', $phone_data, 0, $request->header);
        }else{
            return show('Fail', '', 4000, $request->header);
        }
    }

    /**
     * 获取随机显示号码
     */
    public function getPhoneRandom(Request $request): Json
    {
        $phone_model = new PhoneModel();
        $phone_num_info = $phone_model->getRandom();
        $phone_num = $phone_num_info['phone_num'];
        $bh = $phone_num_info['country']['bh'];
        if (!$phone_num){
            return show('Fail', '', 4000, $request->header);
        }else{
            return show('Success', $phone_num_info->visible(['phone_num', 'total_num', 'country.id', 'country.bh', 'country.title', 'country.en_title']), 0, $request->header);
        }
    }

    public function report(Request $request): Json
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
            return show('Fail', '', 4000, $request->header);
        }
        if (($return == 20 || $return == 25 || $return == 30) && time_section('9:00', '23:00')){
            //失败反馈处理
        }
        return show('Success', '', 0, $request->header);
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