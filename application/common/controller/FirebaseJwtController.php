<?php

namespace app\common\controller;

use app\common\model\FirebaseUserModel;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\IdTokenVerifier;
use think\facade\Config;

class FirebaseJwtController
{
    public function decoded($jwt)
    {
        //return ['user_id' => 'AgxWY278DKWeMmxLA0KMNaRqIaS2'];
        $app = 'flutter-receive-sms';
        $verifier = IdTokenVerifier::createWithProjectId($app);
        try {
            $token = $verifier->verifyIdToken($jwt);
            $firebase_user = json_decode(json_encode($token->payload()), true);
            //trace('firebase_user', 'notice');
            //trace($firebase_user, 'notice');
            $this->firebaseUserInsert($firebase_user);
            return $firebase_user;
        } catch (IdTokenVerificationFailed $e) {
            trace('decoded IdTokenVerificationFailed 错误');
            trace($e->getMessage(), 'error');
            return false;
        }
    }

    // 经过验证的firebase用户，写入本地数据库
    private function firebaseUserInsert($firebase_user)
    {
        if (array_key_exists('user_id', $firebase_user)) {
            $firebase_user_model = new FirebaseUserModel();
            $user_id = $firebase_user['user_id'];
            $is_register = $firebase_user_model
                ->where('user_id', $user_id)
                //->cache($user_id, 10*60)
                ->find();

            if (!$is_register) {
                if (array_key_exists('email', $firebase_user)) {
                    $data['user'] = $firebase_user['email'];
                }
                $data['user_id'] = $user_id;
                $data['ip'] = real_ip();
                $data['refresh_token_number'] = 1;
                $data['access_token_number'] = 1;
                $data['version'] = getHeader('Version');
                try{
                    $firebase_user_model->insertUser($data);
                } catch (\Exception $e){
                    trace('firebaseUserInsert 写入异常捕获', 'notice');
                    trace($data, 'notice');
                    trace($is_register, 'notice');
                    trace($e->getMessage(), 'error');
                }
                
            }else {
                // 数据库写入refresh token 统计
                $is_register->refresh_token_number = ['inc', 1];
                $is_register->ip = real_ip();
                $is_register->version = getHeader('Version');
                $is_register->save();
            }
        }
    }
}