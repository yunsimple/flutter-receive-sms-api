<?php
namespace app\common\controller;

use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\IdTokenVerifier;

class FirebaseJwtController
{
    public function decoded($jwt)
    {
        return ['user_id' => 'AgxWY278DKWeMmxLA0KMNaRqIaS2'];
        $app = 'flutter-receive-sms';
        $verifier = IdTokenVerifier::createWithProjectId($app);
        try {
            $token = $verifier->verifyIdToken($jwt);
            return json_decode(json_encode($token->payload()), true);
        } catch (IdTokenVerificationFailed $e) {
            trace($e->getMessage(), 'error');
            return false;
        }
    }
}