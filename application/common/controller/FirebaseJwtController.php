<?php
namespace app\common\controller;

use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\IdTokenVerifier;

class FirebaseJwtController
{
    public function decoded($jwt)
    {
        $app = 'flutter-mys';
        $verifier = IdTokenVerifier::createWithProjectId($app);
        try {
            return ['user_id'=> 'Lh0ZqCW8rKMEMnQiHXX9taRPrZ23', 'email' => 'a@163.com'];
            $token = $verifier->verifyIdToken($jwt);
            return $token->payload();
        } catch (IdTokenVerificationFailed $e) {
            trace($e->getMessage(), 'error');
            return false;
        }
    }
}