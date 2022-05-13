<?php
namespace app\rsapi\controller;

use app\common\controller\RedisController;
use bt\BtEmailServer;
use think\App;
use think\facade\Lang;
use think\facade\Session;
use think\facade\Validate;
use think\Request;

class EmailController extends BaseController
{
    protected $middleware = ['AuthApp'];
    protected $header = []; //自定义response返回header
    
    //获取当前生效的邮箱后坠
    public function getEmailSite(Request $request){
        return show('success', ['1655mail.com', 'mailscode.com', 'bestemail.online'], 0, $request->header);
    }
    
    //获取emal
    public function emailGet(Request $request)
    {
        $email = input('post.email');
        $validate = Validate::checkRule($email, 'must|email|max:30|min:10');
        if (!$validate){
            return show('传递参数异常', $email, 4000);
        }
        if ($email == 'admin@yinsiduanxin.com'){
            return show('传递参数异常', $email, 4000);
        }
        $bt = new BtEmailServer();
        $result = $bt->getEmail($email);
        if ($result['status']){
            if (count($result['data']) > 0){
                //判断是否存在需要过滤的邮件发布商
                $str = [
                    'alipay@mail.alipay.com',
                    'service@mc.mail.taobao.com',
                    'noreply@suningnews.com',
                ];
                foreach ($result['data'] as $key => $value){
                    (RedisController::getInstance())->incr('mail_receive_total');
                    foreach ($str as $k => $v){
                        $num = stristr($value['from'], $v);
                        if ($num){
                            $result['data'][$key]['html'] = '抱歉，不能提供这个验证码，请勿用于非法用途！';
                        }
                    }
                }
                return show('获取邮件成功', $result['data'], 0, $request->header);
            }else{
                return show('服务器还未收到邮件','', 3000, $request->header);
            }

        }else{
            return show('获取失败:', $email, 4000, $request->header);
        }
    }

    //指字用户名申请emal
    public function emailApply(Request $request)
    {
        trace(input('post.'), 'notice');
        $user = strtolower(trim(input('post.email_name')));
        $site = trim(input('post.site'));
        if (empty($user)){
            $user = $this->getEmailUser(5, 4);
        }else{
            $validate = Validate::checkRule($user, 'must|alphaDash|max:24|min:6');
            if(!$validate){
                return show('传递参数异常', $user, 4000);
            }
        }
        $bt = new BtEmailServer();
        $result = $bt->emailApply($user, $user . $site);
        if ($result['status']){
            Session::set('email', $user . $site);
            (RedisController::getInstance())->incr('mail_user_total');
            return show('申请成功', $user . $site, 0, $request->header);
        }else{
            return show('申请失败，请换个帐号试试', $result, 4000, $request->header);
        }
    }

    //删除email帐户
    public function emailUserDelete(Request $request)
    {
        $email = input('post.email');
        $transpond_email = input('post.transpond_email');
        $validate = Validate::checkRule($email, 'must|email|max:50|min:10');
        if (!$validate){
            return show(Lang::get('mail_alert_abnormal'), $email, 4000);
        }
        if ($email == 'admin@yinsiduanxin.com'){
            return show('传递参数异常', $email, 4000);
        }
        $bt = new BtEmailServer();
        if ($transpond_email){
            $validate = Validate::checkRule($transpond_email, 'email|max:50|min:10');
            if (!$validate){
                return show(Lang::get('mail_alert_abnormal'), $transpond_email, 4000);
            }
            $bt->deleteTranspondEmail('recipient', $email, $transpond_email);
        }
        $result = $bt->emailUserDelete($email);
        if ($result['status']){
            Session::delete('email');
            return show('邮箱销毁成功',$email, 0, $request->header);
        }else{
            return show('邮箱销毁失败，请重试:', $email, 4000, $request->header);
        }
    }

    //生成邮件用户名
    private function getEmailUser($letter_length, $number_length)
    {
        $str = null;
        $str_letter = "abcdefghijklmnopqrstuvwxyz";
        $str_number = "1234567890";
        $max_letter = strlen($str_letter) - 1;
        $max_number = strlen($str_number) - 1;
        for ($i = 0; $i < $letter_length; $i++) {
            $str .= $str_letter[rand(0, $max_letter)];
        }
        for ($i = 0; $i < $number_length; $i++) {
            $str .= $str_number[rand(0, $max_number)];
        }
        return $str;
    }


    /**
     * 设置邮件转发地址
     */
    public function setTranspondEmail(Request $request){
        //return show('此功能暂时关闭', '', 4000);
        $email = input('post.email');
        $transpond_email = input('post.transpond_email');
        $email_arr = explode('@', $email);
        if (count($email_arr) == 2){
            $email_domin = $email_arr[1];
        }else{
            return show(Lang::get('mail_alert_abnormal'), $email, 4000);
        }
        /*//判断是否重复
        $transpond = $this->checkTranspondEmail('recipient', $email);
        if ($transpond){
            return show(Lang::get('mail_main_transpond_repetition'), $email, 4000);
        }*/
        $validate = \think\Validate::make([
            'email' => 'must|email|max:30|min:10',
            'transpond_email' => 'must|email|max:30|min:10',
            'domain' => 'must|[a-z0-9]+\.com|max:30'
        ]);
        $data = [
            'email' => $email,
            'transpond_email' => $transpond_email,
            'domain' => $email_domin
        ];
        if (!$validate->check($data)){
            return show(Lang::get('mail_alert_abnormal'), $validate->getError(), 4000);
        }
        $bt = new BtEmailServer();
        $result = $bt->setTranspondEmail('recipient', $email_domin, $email, $transpond_email);
        //dump($result);
        if ($result['msg'] == '被转发用户已经存在'){
            return show(Lang::get('已经存在转发地址'), $validate->getError(), 4000, $request->header);
        }
        if ($result['status']){
            return show(Lang::get('mail_alert_transpond_email_success'), $transpond_email, 0, $request->header);
        }else{
            return show(Lang::get('mail_alert_transpond_email_failed'), $validate->getError(), 4000, $request->header);
        }
    }


    /**
     * 检查设置的转发邮件是否已经存在
     * @param $type  'sender' 发送 or 'recipient' 接收
     * @param $email
     * @return string
     */
    private function checkTranspondEmail($type, $email){
        $transpond_email_list = (new BtEmailServer())->getTranspondEmailList();
        dump($transpond_email_list);
        $transpond_email = '';
        foreach ($transpond_email_list[$type] as $value){
            if ($value['user'] == $email){
                $transpond_email = $email;
                break;
            }
        }
        return $transpond_email;
    }
}