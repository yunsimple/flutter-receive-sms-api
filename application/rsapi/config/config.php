<?php
//自定义配置文件
return [
    'sync_host' => '127.0.0.1',
    'sync_auth' => '',
    'sync_db'   => 0,
    'sync_port' => 17480,
    'master_host' => '127.0.0.1',
    'master_auth' => '',
    'master_db'   => 0,
    'master_port' => 17481,

    //AES加密信息
    'aes_key' => 0xFF345B9A,
    'aes_iv'  => 'All lay load on the willing horse',
    'aes_iv_length'  => 16,
    'aes_mode'=> 'aes-256-cbc',
    'access_token_expires' => 3600,
    'refresh_token_expires' => 30*86400,

    //状态码
    'empty' => 3000, //数据为空
    'auth' => 3001, //需要重新请求/login 获取token
    'no_permission' => 4003, //没有权限
];