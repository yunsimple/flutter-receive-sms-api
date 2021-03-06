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
use app\common\model\FirebaseUserModel;

class PhoneController extends BaseController
{
    protected $middleware = ['AuthApp'];
/*    protected $middleware = [
        'AuthApp' => ['except' => ['getNewPhone']],
    ];*/
    protected $header = []; //自定义response返回header
    /**
     * 根据国家获取号码列表
     */
    public function getPhone(Request $request)
    {
        //1+1
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
        if ($data['country_id'] == 'favorites'){
            $user_id = (new FirebaseUserModel())->getUserInfoByAccessToken('', 'user_id');
            $phone_page_key = Config::get('cache.prefix') . 'cache:phone:favorites:' . $user_id;
        }else{
            $phone_page_key = Config::get('cache.prefix') . 'cache:phone:page' . $data['page'] . ':country:' . $data['country_id'];
        }
        // 读取缓存
        // 上线需要更改缓存
        $redis_local_phone_value = $redis_local->get($phone_page_key);
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
                //trace($value, 'notice');
                // 更改获取到的语言字段 en_title 改成title
                try{
                    $title = $value['country'][$language . '_title'];
                }catch (\Exception $e){
                    $title = $value['country']['en_title'];
                    trace('app获取对应语言错误', 'notice');
                    trace(json($e->getMessage()), 'error');
                }
                
                $id = $value['country']['id'];
                $bh = $value['country']['bh'];
                unset($phone_data[$key]['country']);
                $phone_data[$key]['country']['id'] = $id;
                $phone_data[$key]['country']['bh'] = $bh;
                $phone_data[$key]['country']['title'] = $title;

                // 最后更新时间
                $update_time = $redis_sync->zRange('message:'.$value['phone_num'], -1, -1);
                if (is_array($update_time) && count($update_time) > 0){
                    $update_time = unserialize($update_time[0]);
                }else{
                    $update_time['smsDate'] = (int) time() - 86400;
                }
                $phone_data[$key]['last_time'] = (int) $update_time['smsDate'];

                // 短信数量

                if ($value['type'] == '2'){
                    $phone_data[$key]['receive_total'] = 0;
                }else{
                    $phone_data[$key]['receive_total'] = (int) $this->getPhoneReceiveNumber($value['phone_num']);
                }

            }
            //trace('phone返回成功','notice');
            return show('Success', $phone_data, 0, $request->header);
        }else{
            return show('Fail', '', 4000, $request->header);
        }
    }

    //获取每个号码短信接收总数，用于前台显示
    public function getPhoneReceiveNumber($phone_uid){
        $redis_sync = RedisController::getInstance('sync');
        $number = $redis_sync->hGet('phone_receive', $phone_uid);
        if ($number){
            return numberDim($number);
        }
        return 0;

    }

    /**
     * 获取随机显示号码
     */
    public function getPhoneRandom(Request $request)
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

    public function report(Request $request)
    {
        $phone_num = input('post.phone');
        $validate = Validate::make(['phone_num|phone'=>'must|number|max:15|min:6']);
        if(!$validate->check(['phone_num'=>$phone_num])){
            return show($validate->getError(), $validate->getError(), 4000);
        }
        //把提交的号码保存进入redis. report:1814266666
        $redis = RedisController::getInstance('master');
        $return = RedisController::stringIncrbyEx($redis, 'report:' . $phone_num, 172800);
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

    // 新号码数量，vip号码数量，预告号码数量
    public function getNewPhone()
    {
        $phone_model = (new PhoneModel());
        $new_phone_count = $phone_model->getNewPhone(15);
        $firebase_uid = (new FirebaseUserModel())->getUserInfoByAccessToken('', 'user_id');
        $refresh_redis_key = Config::get('cache.prefix') . 'favorites:' . $firebase_uid;
        return show('Request success',
            [
                'newPhoneCount' => (int) $new_phone_count,
                'upcomingPhoneCount' => (int) $phone_model->getUpcomingNumber(),
                'vipPhoneCount' => (int) $phone_model->where([['type', '=', 3], ['display', '=', 1], ['show', '=', 1]])->cache('vip_phone_count', 3600)->count(),
                'favoritesPhoneCount' => (int) RedisController::getInstance('sync')->sCard($refresh_redis_key)
            ]
        );
    }
}