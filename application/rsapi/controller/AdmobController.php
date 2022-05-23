<?php
namespace app\rsapi\controller;

use app\common\controller\RedisController;
use app\common\model\AdOrderModel;
use app\common\model\FirebaseUserModel;
use app\common\model\PhoneModel;
use think\Db;
use think\facade\Config;
use think\facade\Request;
use think\Model;
use think\response\Json;
use think\Validate;

class AdmobController extends BaseController
{
    // rewarded回调
    public function admobRewardedCall()
    {
        // todo 安全性处理，根据signature，进行解密验证，反向DNS验证
        // todo 是否会重复回调，导致金额重复增加
        /**
         * 'ad_network' => '5450213213286189855',
         * 'ad_unit' => '5892060081',
         * 'reward_amount' => '10',
         * 'reward_item' => 'coins',
         * 'timestamp' => '1653274506843',
         * 'transaction_id' => '0005dfa4e01d1f640516c6aca9041d6d',
         * 'user_id' => 'N3mAWR1Gr6OK1oikGSJn1hVuELq1',
         * 'signature' => 'MEYCIQCVQ0BAhj1f1643FsSO3GHeUcpGMcAsTV5dwFKIAP4EwAIhAOyLRHVWQv6l2UP1rBU5RVyLoNNpd_LOQ9p-nYws1LU2',
         * 'key_id' => '3335741209',
         */
        $call_info = Request::param('');
        //trace($call_info, 'notice');
        $mysql_data = [
            'user_id' => $call_info['user_id'],
            'coins' => $call_info['reward_amount'],
            'type' => 1,
            'json' => json_encode($call_info)
        ];
        // 增加余额，然后写入订单凭证
        Db::startTrans();
        try {
            $result = (new FirebaseUserModel())->where('user_id', '=', $call_info['user_id'])->setInc('coins', $call_info['reward_amount']);
            if ($result){
                // 获取accessToken,找到refreshToken
                (new AdOrderModel())->insertOrder($mysql_data);
                $refresh_token = (new FirebaseUserModel())->getRefreshTokenByAccessToken();
                $redis_sync = RedisController::getInstance();
                $redis_sync->hIncrBy(Config::get('cache.prefix') . 'refreshToken:' . $refresh_token, 'coins', $call_info['reward_amount']);
                Db::commit();
            }
        } catch (\Exception $e) {
            Db::rollback();
            trace('coins增加失败', 'error');
            trace($call_info, 'error');
            trace($e->getMessage(), 'error');
        }
    }

    // 购买号码
    public function buyNumber(): Json
    {
        $data['phone_num'] = input('post.phone');
        //校验手机号码
        $validate = Validate::make([
            'phone_num|number' => 'require|number|min:5|max:15'
        ]);
        if (!$validate->check($data)) {
            return show('Fail', $validate->getError(), 4000);
        }

        // 查看该用户Coins是否足够支付
        $firebase_user_model = new FirebaseUserModel();
        $coins = (int) $firebase_user_model->getUserInfoByAccessToken('', 'coins');
        $price = (int) (new PhoneModel())->getPhoneDetail($data['phone_num'], 'price');
        if (!$coins || !$price || $coins < $price){
            return show('Not enough coins', ['coins' => $coins, 'price' => $price], 3005);
        }

        // 检查通过，扣除金币
        Db::startTrans();
        try{
            $user_id = $firebase_user_model->getUserInfoByAccessToken('', 'user_id');
            $firebase_user_model->where('user_id', $user_id)->setDec('coins', $price);
            $refresh_token = (new FirebaseUserModel())->getRefreshTokenByAccessToken();
            $redis_sync = RedisController::getInstance();
            $refresh_token_key = Config::get('cache.prefix') . 'refreshToken:' . $refresh_token;
            $redis_sync->hIncrBy($refresh_token_key, 'coins', -$price);

            // 记录购买号码订单
            $buy_data_order = [
                'user_id' => $user_id,
                'coins' => $price,
                'phone_num' => $data['phone_num'],
                'create_time' => time(),
                'update_time' => time(),
                'type' => 2,
            ];
            $result = (new AdOrderModel())->insert($buy_data_order);
            if ($result == 1){
                Db::commit();
                return show('Success', ['info' => ['coins' => (int) $redis_sync->hGet($refresh_token_key,'coins')]]);
            }else{
                return show('Fail');
            }
        }catch (\Exception $e){
            Db::rollback();
            trace('coins扣除失败', 'error');
            trace($e->getMessage(), 'error');
            return show('Fail');
        }
    }

}