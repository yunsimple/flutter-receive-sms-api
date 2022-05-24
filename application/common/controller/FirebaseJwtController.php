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
            trace('firebase_user', 'notice');
            trace($firebase_user, 'notice');
            $this->firebaseUserInsert($firebase_user);
            return $firebase_user;
        } catch (IdTokenVerificationFailed $e) {
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
                ->cache($user_id, 10*60)
                ->find();

            if (!$is_register) {
                if (array_key_exists('email', $firebase_user)) {
                    $data['user'] = $firebase_user['email'];
                }
                $data['user_id'] = $user_id;
                $firebase_user_model->insertUser($data);
            }
        }
    }

    /*
     *
     * array (
  'name' => '深蓝',
  'picture' => 'https://lh3.googleusercontent.com/a-/AOh14GiTfrRk9934QPd7ZjDyfaP30kBUZt02YDpNugsO=s96-c',
  'iss' => 'https://securetoken.google.com/flutter-receive-sms',
  'aud' =>
  array (
    0 => 'flutter-receive-sms',
  ),
  'auth_time' => 1653373334,
  'user_id' => 'N3mAWR1Gr6OK1oikGSJn1hVuELq1',
  'sub' => 'N3mAWR1Gr6OK1oikGSJn1hVuELq1',
  'iat' => 1653373334,
  'exp' => 1653376934,
  'email' => 'bilulanlv168@gmail.com',
  'email_verified' => true,
  'firebase' =>
  array (
    'identities' =>
    array (
      'google.com' =>
      array (
        0 => '100185012922278892652',
      ),
      'email' =>
      array (
        0 => 'bilulanlv168@gmail.com',
      ),
    ),
    'sign_in_provider' => 'google.com',
  ),

     array (
      'provider_id' => 'anonymous',
      'iss' => 'https://securetoken.google.com/flutter-receive-sms',
      'aud' =>
      array (
        0 => 'flutter-receive-sms',
      ),
      'auth_time' => 1653372542,
      'user_id' => 'QqEIZjD1hDT4cRmAo8QUqsC9uIE2',
      'sub' => 'QqEIZjD1hDT4cRmAo8QUqsC9uIE2',
      'iat' => 1653372542,
      'exp' => 1653376142,
      'firebase' =>
      array (
        'identities' =>
        array (
        ),
        'sign_in_provider' => 'anonymous',
      ),

     */
}