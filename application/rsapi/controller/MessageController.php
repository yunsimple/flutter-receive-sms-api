<?php

namespace app\rsapi\controller;

use app\common\controller\RedisController;
use app\common\model\AdOrderModel;
use app\common\model\FirebaseUserModel;
use app\common\model\PhoneModel;
use think\Db;
use think\Exception;
use think\facade\Config;
use think\Request;
use think\Validate;

class MessageController extends BaseController
{
    protected $middleware = ['AuthApp'];
    protected $header = []; //自定义response返回header

    public function getMessage(Request $request): \think\response\Json
    {
        $data['phone_num'] = input('post.phone');
        $data['page'] = input('post.page');
        //校验手机号码
        $validate = Validate::make([
            'phone_num|number' => 'require|number|min:5|max:15',
            'page|page' => 'require|number|min:1|max:100'
        ]);
        if (!$validate->check($data)) {
            return show('Fail', $validate->getError(), 4000);
        }

        $user_info = (new FirebaseUserModel())->getUserInfoByAccessToken();

        // 判断该号码是否被收藏
        // 上线，需要更改master为sync
        $redis_sync = RedisController::getInstance('sync');
        $is_favorites = $redis_sync->sIsMember(Config::get('cache.prefix') . 'favorites:' . $user_info['user_id'], $data['phone_num']);
        $phone_model = new PhoneModel();
        $online = $phone_model->getPhoneDetail($data['phone_num'], 'online') == 1;
        // 判断该号码类型，type = 1 正常 2 预告号码 3 vip号码
        $phone_detail = $phone_model->getPhoneDetail($data['phone_num']);
        if ($phone_detail['type'] == '2'){
            // 预告号码
            return show('Comping number',['info' =>
                [
                    'upcomingTime'=> $phone_model->getUpcomingTime(),
                    'favorites' => $is_favorites,
                    'online' => $online
                ]
            ], 3003);
        }

        // 判断该用户，是否已经付费了
        $isBuy = AdOrderModel::where('user_id',$user_info['user_id'])
            ->where('phone_num', $data['phone_num'])
            ->cache(3600)
            ->find();

        if ($phone_detail['type'] == '3' && !$isBuy){
            // vip号码，判断该用户是否有权使用，如果没有权限，则返回3004
            return show('vip number',['info' =>
                [
                    'favorites' => $is_favorites,
                    'online' => $online,
                    'price' => (int) $phone_model->getPhoneDetail($data['phone_num'], 'price'),
                    'coins' => (int) (new FirebaseUserModel())
                        ->where('user_id', $user_info['user_id'])
                        ->cache($user_info['user_id'] . 'coins', 3600)
                        ->value('coins')
                ]
            ], 3004);
        }

        // 获取短信
        if ($data['page'] == 1){
            // 请求爬虫数据
            $this->spiderQueue($phone_detail);
            $redis = RedisController::getInstance('sync');
            $message_data = RedisController::zSetLatestMsg($redis, 'message:' . $data['phone_num']);
            if ($message_data) {
                //trace('正确返回message', 'notice');
                if ($message_data == 'null'){
                    return show('No data',['info' =>
                        [
                            'favorites' => $is_favorites,
                            'online' => $online
                        ]
                    ], 3000);
                }
                
                return show('Success',
                    [
                        'message'=>$message_data,
                        'info'=>[
                            'favorites'=>$is_favorites,
                            'online' => $online
                        ]
                    ], 0, $request->header);
            }else{
                return show('Fail', '', 4000, $request->header);
            }
        }else{
            //历史记录
            $phone_id = $phone_model->getPhoneDetail($data['phone_num'], 'uid');
            try {
                $result = Db::connect('db_history')
                    ->table('collection_msg')
                    ->where('phone_id', $phone_id)
                    ->order('id','desc')
                    ->cache($phone_id . '_' . $data['page'], 86400)
                    ->page($data['page'], 20)
                    ->select();
                return show('Success', ['message' => $result]);
            } catch (\Exception $e) {
                return show('No data','' , 3000);
            }
        }


    }

        /**
     * 提交蜘蛛队列请求
    */
    protected function spiderQueue($phone_info){
        //trace($phone_info, 'notice');
        $key_phone_click = 'click:' . $phone_info['phone_num'];
        //频率限制
        //每个号码10秒钟只能请求一次
        $redis_local = RedisController::getInstance();
        if ($redis_local->exists($key_phone_click)){
            return false;
        }
        //如果号码离线，不提交
        if($phone_info['online'] == 0 || $phone_info['show'] == 0){
            return false;
        }
        //如果是爬虫，也不提交请求
        if ($redis_local->sIsMember('spider', real_ip())){
            return false;
        }
        //如果voice号，也不需要提交
        $warehouse = $phone_info['warehouse']['title'];
        if ($warehouse !== 'Storytrain' && $warehouse !== 'Receivesms'){
            return false;
        }
        
        if ($warehouse == 'Storytrain'){
            $mode = 'yi';
        }else{
            $mode = 'guzzle';
        }
        $params = [
            'from' => 'https://app20161108.iyunzhi.top/msg_queue/',
            'phone_num' => $phone_info['phone_num'],
            'phone_id' => array_key_exists('phone_id', $phone_info) ? $phone_info['phone_id'] : '',
            'bh' => $phone_info['country']['bh'],
            'site' => $phone_info['warehouse']['title'],
            'mode' => $mode
        ];
        
        $redis_local->lpush('spider_sync_queue', json_encode($params));
        $redis_local->setex($key_phone_click, 20, 1);
        /*$url = $redis->redisCheck(Config::get('cache.prefix') . 'curl_url');
        if ($url){
            try {
                asyncRequestFS($url, 'POST', $params);
                $redis->setex($key_phone_click, 30, 1);
            }catch (\Exception $e){
                trace($e->getMessage(), 'error');
                trace('远程请求地址请求出错', 'notice');
                curl_get("http://notice.bilulanlv.com/?key=qywsxxl&title=Crawler-connection-failed");
            }
        }else{
            trace('远程请求地址不存在', 'notice');
        }*/
    }
}
