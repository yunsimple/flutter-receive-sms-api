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
            $this->firebaseUserInsert($firebase_user);
            return $firebase_user;
        } catch (IdTokenVerificationFailed $e) {
            trace($e->getMessage(), 'error');
            return false;
        }
    }

    // 经过验证的firebase用户，写入本地数据库
    private function firebaseUserInsert($firebase_user){
        if (array_key_exists('user_id', $firebase_user) && array_key_exists('email', $firebase_user)){
            $firebase_user_model = new FirebaseUserModel();
            $user = $firebase_user_model->getFieldValueByUser($firebase_user['email'], 'user');
            if (!$user){
                $data['user'] = $firebase_user['email'];
                $data['user_id'] = $firebase_user['user_id'];
                $firebase_user_model->insertUser($data);
            }
        }
    }
}