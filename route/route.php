<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\facade\Route;

$sub_domain = get_subdomain();

Route::get('test', 'rsapi/Test/index');
Route::post('phone', 'rsapi/Phone/getPhone');
Route::post('country', 'rsapi/Country/getCountry');
Route::post('message', 'rsapi/Message/getMessage');
Route::post('blog', 'rsapi/Country/getBlog');
Route::post('random', 'rsapi/Phone/getPhoneRandom');
Route::post('report', 'rsapi/Phone/report');
Route::post('email_get', 'rsapi/Email/emailGet');
Route::post('email_apply', 'rsapi/Email/emailApply');
Route::post('email_user_delete', 'rsapi/Email/emailUserDelete');
Route::get('email_delete', 'rsapi/Email/emailDelete');
Route::post('email_transpond', 'rsapi/Email/setTranspondEmail');
Route::post('email_site', 'rsapi/Email/getEmailSite');
Route::post('login', 'rsapi/Token/getToken');
Route::post('login_out', 'rsapi/Token/loginOut');
Route::post('access', 'rsapi/Token/getAccessByRefresh');
Route::post('register', 'rsapi/User/register');
Route::post('my', 'rsapi/User/getMy');
Route::get('update', 'rsapi/Update/getUpdate');
Route::post('getinfo', 'rsapi/Update/getInfo');
Route::post('countrys', 'rsapi/Phone/getPhones');
Route::post('params', 'rsapi/Params/getParams');
Route::post('notice', 'rsapi/User/notice');
Route::post('upcoming_phone', 'rsapi/Phone/getUpcomingPhone');
Route::post('new_phone', 'rsapi/Phone/getNewPhone');
Route::post('favorites', 'rsapi/Favorites/add');
Route::post('favorites_del', 'rsapi/Favorites/del');

if ($sub_domain == 'rsapi'){
    Route::get('test', 'rsapi/Test/index');
    Route::post('phone', 'rsapi/Phone/getPhone');
    Route::post('country', 'rsapi/Country/getCountry');
    Route::post('message', 'rsapi/Message/getMessage');
    Route::post('blog', 'rsapi/Country/getBlog');
    Route::post('random', 'rsapi/Phone/getPhoneRandom');
    Route::post('report', 'rsapi/Phone/report');
    Route::post('email_get', 'rsapi/Email/emailGet');
    Route::post('email_apply', 'rsapi/Email/emailApply');
    Route::post('email_user_delete', 'rsapi/Email/emailUserDelete');
    Route::get('email_delete', 'rsapi/Email/emailDelete');
    Route::post('email_transpond', 'rsapi/Email/setTranspondEmail');
    Route::post('email_site', 'rsapi/Email/getEmailSite');
    Route::post('login', 'rsapi/Token/getToken');
    Route::post('login_out', 'rsapi/Token/loginOut');
    Route::post('access', 'rsapi/Token/getAccessByRefresh');
    Route::post('register', 'rsapi/User/register');
    Route::post('my', 'rsapi/User/getMy');
    Route::post('update', 'rsapi/Update/getUpdate');
    Route::post('getinfo', 'rsapi/Update/getInfo');
    Route::post('countrys', 'rsapi/Phone/getPhones');
    Route::post('params', 'rsapi/Params/getParams');
    Route::post('notice', 'rsapi/User/notice');
}
