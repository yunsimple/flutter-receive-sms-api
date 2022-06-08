<?php
//自定义配置文件
return [
    'sync_host' => '127.0.0.1',
    'sync_auth' => '',
    'sync_db'   => 0,
    'sync_port' => 17480,
    'master_host' => '23.239.2.123',
    'master_auth' => 'PjKnXgXGTyp85Synh9wq',
    'master_db'   => 0,
    'master_port' => 17480,

    //AES加密信息
    'aes_key' => 0xFF345B9A,
    'aes_iv'  => [
        // 最新的iv要放在前面，否则会出错
        '7LEhgeWpLBuh26g0',
    ],
    'aes_iv_length'  => 16,
    'aes_mode'=> 'aes-256-cbc',
    'access_token_expires' => 30*60,
    'refresh_token_expires' => 30*86400,

    //状态码
    'empty' => 3000, //数据为空
    'auth' => 3001, //需要重新请求/login 获取token
    'upcoming_number' => 3003, // type = 2 预告号码
    'vip_number' => 3004, //type = 3 vip号码
    'not_enough_coins' => 3005, //金币不足
    'no_permission' => 4003, //没有权限

    'language' => ['tw','en', 'de', 'pt'],  //目前api开放的语言

];