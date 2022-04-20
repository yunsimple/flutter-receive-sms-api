<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 应用公共文件
/**
 * 通用API返回接口
 * @param $error_code int 是否存在错误
 * @param $message string 返回具体信息
 * @param array $data 返回数据
 * @param int $httpCode HTTP状态码
 *
 * @return \think\response\Json
 */
function show($message, $data = [],$error_code = 0, $header = [], $httpCode = 200)
{
    $result = [
        'error_code' => $error_code,
        'msg' => $message,
        'data' => $data
    ];
    //如果data没有值,$data将不显示
    if (empty($data)){
        unset($result['data']);
    }
    if ($header){
        return json($result, $httpCode)->header($header);
    }else{
        return json($result, $httpCode);
    }
}

function curl_post($url = '', $param = '') {
    if (empty($url) || empty($param)) {
        return false;
    }
    $postUrl = $url;
    $curlPost = $param;
    $UserAgent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.4.2661.102 Safari/537.36; 360Spider";
    $ch = curl_init();//初始化curl
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 设置超时限制防止死循环
    curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    curl_setopt($ch, CURLOPT_USERAGENT, $UserAgent);
    //curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'. generateIP(), 'CLIENT-IP:' . generateIP())); //构造IP
    curl_setopt($ch, CURLOPT_REFERER, $url);//模拟来路
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($curlPost));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10 );//连接超时，这个数值如果设置太短可能导致数据请求不到就断开了
    $data = curl_exec($ch);//运行curl
    curl_close($ch);
    return $data;
}

function curl_get($url = '') {
    if (empty($url)) {
        return false;
    }
    $szUrl = $url;
    $UserAgent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.4.2661.102 Safari/537.36; 360Spider";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $szUrl);
    curl_setopt($curl, CURLOPT_HEADER, 0);  //0表示不输出Header，1表示输出
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_ENCODING, '');
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'. generateIP(), 'CLIENT-IP:' . generateIP())); //构造IP
    curl_setopt($curl, CURLOPT_REFERER, $url);//模拟来路
    curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10 );//连接超时，这个数值如果设置太短可能导致数据请求不到就断开了
    $data = curl_exec($curl);
    curl_close($curl);
    return $data;
}

function asyncRequest($url, $method = 'POST', array $param = []) {
    if (empty($url) || empty($param)) {
        return false;
    }
    $postUrl = $url;
    $curlPost = $param;
    $ch = curl_init();//初始化curl
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($ch, CURLOPT_NOSIGNAL,true);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 200); // 设置超时限制防止死循环
    curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    curl_setopt($ch, CURLOPT_REFERER, $url);//模拟来路
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($curlPost));
    $data = curl_exec($ch);//运行curl
    curl_close($ch);
    return $data;
}

function generateIP(){
    $ip2id= round(rand(600000, 2550000) / 10000); //第一种方法，直接生成
    $ip3id= round(rand(600000, 2550000) / 10000);
    $ip4id= round(rand(600000, 2550000) / 10000);
    //下面是第二种方法，在以下数据中随机抽取
    $arr_1 = array("218","218","66","66","218","218","60","60","202","204","66","66","66","59","61","60","222","221","66","59","60","60","66","218","218","62","63","64","66","66","122","211");
    $randarr= mt_rand(0,count($arr_1)-1);
    $ip1id = $arr_1[$randarr];
    return $ip1id.".".$ip2id.".".$ip3id.".".$ip4id;
}

//使用cdn后获取真实Ip
function real_ip(){
    if (empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER["REMOTE_ADDR"];
    }else {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $ip;
}

/**
 * 获取子域名
 * @return string
 */
function get_subdomain(){
    $sub_domain = \think\facade\Request::subDomain();
    if (empty($sub_domain)){
        $sub_domain = 'www';
    }
    return $sub_domain;
}

/**
 * 获取子域名
 * @return string
 */
function get_domain(){
    $domain = \think\facade\Request::rootDomain();
    $domain = explode('.', $domain);
    return $domain[0];
}


/**
 * 获取随机位token字符
 */
function getRandChar($length)
{
    $str = null;
    $strPol = "A0B1C3D5E9cGHIJeKLq5MN7PQR63STeytUV9aWXYZ012aB345C6Q789abcdefFghGijkAlmn6opqErstuDvwxSyz";
    $max = strlen($strPol) - 1;
    for($i=0 ; $i<$length ; $i++){
        $str .= $strPol[rand(0, $max)];
    }
    return $str;
}

/**
 * 获取随机位token字符
 */
function getRandNum($length)
{
    $str = null;
    $strPol = "55987412032659874512355487896321587452123002365987451254632598874514523659874120325698445";
    $max = strlen($strPol) - 1;
    for($i=0 ; $i<$length ; $i++){
        //$str .= $strPol[rand(0, $max)];
        $num = $strPol[rand(0, $max)];
        if($i == 0 && $num == 0){
            $num = 8;
        }
        $str .= $num;
    }
    return $str;
}

//判断时间是否在某一区间
function time_section($begin, $end)
{
    $checkDayStr = date('Y-m-d ',time());
    //return $checkDayStr;
    $timeBegin1 = strtotime($checkDayStr. $begin .":00");
    $timeEnd1 = strtotime($checkDayStr. $end .":00");

    $curr_time = time();

    if($curr_time >= $timeBegin1 && $curr_time <= $timeEnd1)
    {
        return true;
    }
    return false;
}

//判断一个字符串是否存在,不区分大小写
function strIsExist($str, array $str_arr){
    foreach ($str_arr as $key=>$value){
        $exist = stristr($str, $str_arr[$key]);
        if ($exist){
            return true;
        }
    }
    return false;
}

/**
 * ip2region库返回拼接数据
 */
function getIpRegion($region)
{
    if (!$region) {
        return false;
    }
    $regions = explode('|', $region);
    $val = '';
    foreach ($regions as $value) {
        if ($value) {
            $val = $val . $value;
        }
    }
    if ($val) {
        return $val;
    } else {
        return false;
    }
}

/**
 * 返回间隔时间多少秒
 * 计划之前的时候，$t 取反值即可
 * @param $time 需要比较的时间撮
 * @param string $lang 中文/英文
 * $type 计算之前还是之后 later
 * @return string
 */
function gap_times($time, $lang = 'zh', $type = 'later')
{
    if($type == 'later'){
        $zh_later_value = '后';
        $en_later_value = ' later';
        $t = $time - time();
    }else{
        $zh_later_value = '前';
        $en_later_value = ' ago';
        $t = time() - $time;
    }
    $langs = [
        ['zh' => '年', 'en' => 'year'],
        ['zh' => '个月', 'en' => 'months'],
        ['zh' => '星期', 'en' => 'week'],
        ['zh' => '天', 'en' => 'day'],
        ['zh' => '小时', 'en' => 'hour'],
        ['zh' => '分钟', 'en' => 'min'],
        ['zh' => '秒', 'en' => 'second'],
        ['zh' => $zh_later_value, 'en' => $en_later_value],
    ];
    $f = array(
        '31536000'=> $langs[0][$lang],
        '2592000' => $langs[1][$lang],
        '604800'  => $langs[2][$lang],
        '86400'   => $langs[3][$lang],
        '3600'    => $langs[4][$lang],
        '60'      => $langs[5][$lang],
        '1'       => $langs[6][$lang],
    );
    foreach ($f as $k=>$v){
        if (0 != $c = floor($t / (int)$k)){
            if ($c > 0){
                return $c . ' ' . $v . $langs[7][$lang];
            }else{
                return 0;
            }
        }
    }
}

//一个数字，显示成整数舍入模式 51520 显示成50000+
function numberDim($number){
    if (!$number || $number < 100){
        return '...';
    }
    $number = (string)$number;
    $new_number = substr($number, 0, 1);
    $new_number .= str_repeat('0', strlen($number) - 1);
    return $new_number . '+';
}

// 密钥key生成算法
function generateKey(): string
{
    return md5(config('config.aes_key'));
}

// 密钥iv生成算法
function generateIv(){
    $iv = str_replace(" ", "", config('config.aes_iv'));
    $aes_iv_length = config('config.aes_iv_length');
    $iv_length = strlen($iv);
    if ($iv_length < $aes_iv_length){
        $add = generateKey();
        $add = substr($add, 0, $aes_iv_length - $iv_length);
        $iv .= $add;
    }elseif ($iv_length > $aes_iv_length){
        $iv = substr($iv, 0 , $aes_iv_length);
    }
    return $iv;
}
