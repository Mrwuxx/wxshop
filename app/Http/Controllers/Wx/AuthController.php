<?php


namespace App\Http\Controllers\Wx;


use App\CodeResponse;
use App\Models\User;
use App\Services\UserServices;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends WxController
{
    /**
     * @param  Request  $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');
        $mobile = $request->input('mobile');
        $code = $request->input('code');
        // todo 验证参数是否为空
        if (empty($username) || empty($password) || empty($code) || empty($mobile)) {
            return $this->fail(CodeResponse::PARAM_ILLEGAL);
        }
        // todo 验证用户是否存在
        $user = UserServices::getInstance()->getByUsername($username);
        //$user = $this->userService->getByUsername($username);
        if (!is_null($user)) {
            return $this->fail(CodeResponse::AUTH_NAME_REGISTERED);
        }

        $validator = Validator::make(['mobile' => $mobile], ['mobile' => 'regex:/^1[345789]\d{9}$/']);
        if ($validator->fails()) {
            return $this->fail(CodeResponse::AUTH_INVALID_MOBILE);
        }
        $userMobile = UserServices::getInstance()->getByMobile($mobile);
        if (!is_null($userMobile)) {
            return $this->fail(CodeResponse::AUTH_MOBILE_REGISTERED,'验证码当天发送不能超10次');
        }

        //验证验证码是否正确
        $isPass = UserServices::getInstance()->checkCaptcha($mobile,$code);

        // todo 写入用户表
        $userTable = new User();
        $userTable->username = $username;
        $userTable->password = Hash::make($password);
        $userTable->mobile = $mobile;
        $userTable->avatar = "https://yanxuan.nosdn.127.net/80841d741d7fa3073e0ae27bf487339f.jpg?imageView&quality=90&thumbnail=64x64";
        $userTable->nickname = $username;
        $userTable->last_login_time = Carbon::now()->toDayDateTimeString(); //'Y-m-d H:i:s'
        $userTable->last_login_ip = $request->getClientIp();
        $userTable->save();

        return $this->success([
            'token' => '',
            'userInfo' => [
                'nickname' => $username,
                'avatarUrl' => $userTable->avater
            ]
        ]);
    }

    /**
     * @param  Request  $request
     * @return array|\Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function regCaptcha(Request $request)
    {
        // todo 获取手机号
        $mobile = $request->input('mobile');
        // todo 验证手机号是否合法
        if (empty($mobile)) {
            return $this->fail(CodeResponse::PARAM_ILLEGAL);
        }
        $validator = Validator::make(['mobile' => $mobile], ['mobile' => 'regex:/^1[0-9]{10}$/']);
        if ($validator->fails()) {
            return $this->fail(CodeResponse::AUTH_INVALID_MOBILE);
        }
        // todo 验证手机号是否合被注册
        $userMobile = UserServices::getInstance()->getByMobile($mobile);
        if (!is_null($userMobile)) {
            return $this->fail(CodeResponse::AUTH_MOBILE_REGISTERED);
        }
        // todo 防刷验证，一分钟内只能请求一次，一天只能请求十次
        $lock = Cache::add('register_captcha_lock_'.$mobile, 1, 60);
        if (!$lock) {
            return $this->fail(CodeResponse::AUTH_CAPTCHA_FREQUENCY);
        }
        $isPass = UserServices::getInstance()->checkMobileSendCaptchaCount($mobile);
        if (!$isPass) {
            return $this->fail(CodeResponse::AUTH_CAPTCHA_FREQUENCY);
        }

        // todo 发关短信
        $code = UserServices::getInstance()->setCaptcha($mobile);
        UserServices::getInstance()->sendCaptchaMsg($mobile,$code);

        return['errno'=>0,'errmsg'=>'发送成功'];
    }
}
