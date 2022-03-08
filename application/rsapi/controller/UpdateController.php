<?php
namespace app\rsapi\controller;


use think\Controller;

class UpdateController extends Controller
{
    public function getUpdate(){
        /**
         * wgt (原生包更新数据)
         * native (原生包更新数据)
         * version (版本)
         * versionCode (版本号)
         * nativeVersionCode （原生应用版本号）
         * url (升级需要请求app包的地址)
         * updateType (1：用户同意更新，2：强制更新，3：静默更新)
         * type (资源包类型，1：wgt-android 2：wgt-ios  3：android，4：ios) 无用
         * status (资源包状态 0：禁用 1：启用)
         * changelog (更新日志)
         * remark (备注)
         *
         *`statusCode` <Number> 状态码，执行该方法之后的结果主要根据状态码进行判断
         * 251：需要更新原生版本 data、response、message
         * 252：需要更新wgt版本 data、response、message
         * 253：暂无更新 response、message
         * 254：请求成功，但接口响应返回失败 response、message
         * 451: 更新被关闭，用户手动配置关闭了 message
         * 452：用户未配置更新地址 message
         * 453：无项目ID或项目ID不正确 message
         * 473：正在检查更新 message
         * 474：正在静默更新 message
         * 475：已经静默更新完成，需要重启App生效 message
         * 476：正在更新中... message
         * 500：请求失败 message、error
         * 505：未知错误
         */
        //version仅用做标识，versionCode通过程序判断是否更新，wgt包的nativeVersionCode，要跟native同步
        //wgt热更新包
        $wgt_updateType = 1;
        $wgt_version = '1.0.1';
        $wgt_versionCode = 100001;
        //原生包用于整包更新
        $native_updateType = 1;
        $native_version = '0';
        $native_versionCode = 0;
        $data = [
            'wgt' => [
                "version" => $wgt_version,
                "versionCode" => $wgt_versionCode,
                "nativeVersionCode" => $native_versionCode,
                "url" => "https://www.yinsiduanxin.com/101.wgt",
                "updateType" => $wgt_updateType,
                "type" => 1,
                "status" => 1,
                "changelog" => "wgt-android",
                "remark" => "",
            ],
            'native' => [
                "version" => $native_version,
                "versionCode" => $native_versionCode,
                "nativeVersionCode" => 0,
                "url" => "https://www.yinsiduanxin.com/102.apk",
                "updateType" => $native_updateType,
                "type" => 3,
                "status" => 1,
                "changelog" => "整包更新",
                "remark" => "",
            ]
        ];
        $result = [
            'success' => true,
            'message' => '成功',
            'statusCode' => 200,
            'data' => $data
        ];
        return json($result, 200);
    }
}