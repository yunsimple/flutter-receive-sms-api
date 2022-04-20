<?php
namespace app\rsapi\controller;

use app\common\controller\FirebaseJwtController;
use app\common\controller\RedisController;
use think\facade\Request;

class TestController
{
    public function index(){
        return Request::url();
/*        dump(openssl_get_cipher_methods());
        foreach (openssl_get_cipher_methods(true) as $m) {
            echo $m . ' => ' . openssl_cipher_iv_length($m) . PHP_EOL;
        }*/
        //{key: 15f654af5addbd856e4c2bc32ff22ffc, iv: LifeIsButASpan57}
        // 要加密的字符串
/*        $data = '我是中国人';
        // 密钥
        $key = '15f654af5addbd856e4c2bc32ff22ffc';
        // 加密数据 'AES-128-ECB' 可以通过openssl_get_cipher_methods()获取
        $encrypt = openssl_encrypt($data, 'aes-256-cbc', $key, 0, 'LifeIsButASpan57');
        echo (($encrypt));*/
/*        $key = '15f654af5addbd856e4c2bc32ff22ffc';
        $string ="MZGhpgZkQjFxYPPhe1/MQbtbS7RQEfWB05wkBQswBVesYkIgsEcASjvhdhaYNz59p/fojkmLQO5wz2lew3B/niKCTgJ83b2BAlVm9HPP3LJSM9A2U8TLJ1W5LcoPJe62TYqm5sqQE4pn4RYhJjfA/JeB2TGljzp7qd9KuSzM1ulHlCQm5W78yfuZZ9NF5T6Fk0Q3Bsvj0LAuKtDeB+ZYtYUacWq8e98TGr8w1bseEb2qlGn7X+roR/K+WqBRDLr1msgTrPC6W0ykKNJ3sY6SB5agjexnPLzyIGSPhCJ3i2y8fxoSq7DBnHrlZ5yNykbKhTcc0uPhvWVICgYt52D6Hc5pN6suyvjiU3+xlY1j1l7guOrP4v876Ld0K8OfgNsXEd3aGr4WJJQFznVJWNGDJxW755bsaGFIJg9bOrTlzK8PbQWc/4AzE9Yc1QCV4KOy7djgyFiGTqWZAniQ/kXtMVQw3QZyGorvgTJr00TrZPUN2zsPnVmzmzlUWiFJWiIfOSBI3erjLr82XfmAAy1RlXfBeILeWDYkSME7RHPsLznPKJo8mRQcCdhFAGyEsuMd+LBfAVkgz6Xyy/Yxj1BGR2LzqymZTrUQvzABfqlz8dJnX9ozIjbEm52jO4WP2wX+THyVW0n7/NkB3JQyMcJBn8sysPp3ce5FlveQoH/9fbcNS5HY00rzI34w3BLdVrPmjL7z+v3a8ceY2e9u7C3LnsYEtpFt6WXkRvSRt7FYZLtCBixOt2c5SrmME1k3RO2WJRPlnuicqwVWFhVwAQHISatN+SELjWP2VIVmHFsOB8v7TFv1Jvs4ekYuM2mYcg+qMDJWimTvxL9CGE1NIQM9/x998XEtHIm7kzcMzD5O7KtaKxHe6L/aTsJXaqAOUmObX2RMw8t3wGdsM8suIth76t266Cvxor7ycGF5e+uHnlwgZVd+SsPOXk9V+BdC4IG/j3ZRvN2CnY5hoqaFH6RZoEt5aewHNN0yD2KVl/+y2bAqSyoSXXTzOcW1aIrfBye";
        $decrypt = openssl_decrypt($string, 'aes-256-cbc', $key, 0, "LifeIsButASpan57");
        dump($decrypt);*/
        $result = (new FirebaseJwtController())->decoded(input('post.jwt'));
        dump($result);
    }
}