<?php
namespace app\rsapi\controller;

use app\common\controller\RedisController;
use app\common\model\ActivityModel;
use app\common\model\PhoneModel;
use app\common\model\FirebaseUserModel;
use think\Db;
use think\facade\Config;
use think\facade\Request;
use think\Model;
use think\response\Json;
use think\Validate;

class ActivityController extends BaseController
{
    //protected $middleware = ['AuthApp'];
    
    public function signIn(){
        // 判断当天是否有签到，
        $activity_model = new ActivityModel();
        $firebase_user_model = new FirebaseUserModel();
        $user_id = $firebase_user_model->getUserInfoByAccessToken('', 'user_id');
        $is_sign_in = $activity_model->where('user_id', $user_id)->whereTime('create_time', 'today')->find();
        
        // 判断连续签到天数
        $coins = config('config.sign_in_coins');
        $continuousDay = $this->continuousSignIn($user_id);
        if($continuousDay >= 6){
            $coins = 2 * $coins;
            $day = 7;
        }else{
            $day =  7 - $continuousDay;
        }
        $next_rewarded = date('Ynj', time() + (86400 * $day));
        
        if($is_sign_in){
            return show('sign in has been made today', ['coins' => (int)$is_sign_in['coins'], 'nextRewarded' => $next_rewarded, 'list' => $this->signInList($user_id)], 3007);
        }else {
            $time = time();
            $data['user_id'] = $user_id;
            $data['coins'] = $coins;
            $data['ip'] = real_ip();
            $data['type'] = 1;
            $data['version'] = getHeader('Version');
            $data['create_time'] = $time;
            $data['update_time'] = $time;
            Db::startTrans();
            try {
                $insert = $activity_model->insert($data);
                if($insert){
                    // 增加余额
                    $money = (new FirebaseUserModel())->save(['coins'  => ['inc', $coins]],['user_id' => $user_id]);
                    if($money){
                        // 清理缓存
                        $redis_local = RedisController::getInstance();
                        $redis_local->del(Config::get('cache.prefix') . $user_id . 'coins');
                        Db::commit();
                        return show('Sign in success today', ['coins' => $coins, 'nextRewarded' => $next_rewarded,  'list' => $this->signInList($user_id)]);
                    }
                }
                return show('Sign in fail today', '', 4000);
            } catch (\Exception $e){
                Db::rollback();
                return show('Sign in fail today', '', 4000);
            }
            
            
        }
    }
    
    // 计算连续签到天数，连续七天签到，积分翻倍
    public function continuousSignIn($user_id, $day = 6){
        // 提取最近7天的签到记录（倒序）
        // 判断相近的两个时间戳相隔是否相差86400
        // 如果小于 i+1 继续，否则记录这个i值，7-i值，就是翻倍积分这天
        // 还要确定当天是否是翻倍积分日
        $day = strtotime(date('Ymd', time() - (86400 * $day)));
        $sign_in_time = (new ActivityModel())->where('user_id', $user_id)->order('create_time', 'desc')->whereTime('create_time', '>', $day)->select()->visible(['coins', 'type', 'create_time']);
        $continuousDay = 1;
        //dump($sign_in_time);
        foreach ($sign_in_time as $key => $value){
            $last_time = strtotime(date('Ymd', $value->getData('create_time')));
            $next_key = $key + 1;
            if($key == count($sign_in_time) - 1){
                $next_key = $key;
            }else{
                $next_key = $key + 1;
            }
            $next_time = strtotime(date('Ymd', $sign_in_time[$next_key]->getData('create_time')));
            
            if($last_time - $next_time == 86400 && $value->coins == 10){
                $continuousDay++;
            }elseif($value->coins == 20){
                $continuousDay--;
                break;
            }else{
                break;
            }
            
        }
        return $continuousDay;
    }
    
    protected function signInList($user_id){
        $activity_model = new ActivityModel();
        $sign_in_time = $activity_model->where('user_id', $user_id)->order('create_time', 'asc')->whereTime('create_time', 'month')->column('create_time');
        $data = [];
        if(count($sign_in_time) > 0){
            foreach ($sign_in_time as $key=>$value){
                $data[$key] = date('Ynj', $value);
                //$data[$key]['coins'] = $value->coins;
            }
        }
        return $data;
    }

}