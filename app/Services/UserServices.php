<?php


namespace App\Services;


use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Leonis\Notifications\EasySms\Channels\EasySmsChannel;
use Overtrue\EasySms\PhoneNumber;

class UserServices extends BaseService
{
    /**
     * 根据用户名获取用户
     * @param $username
     * @return User|null|Model
     */
    public function getByUsername($username)
    {
        return User::query()
            ->where('username', $username)
            ->where('deleted', 0)
            ->first();
    }

    /**
     * 根据用户名获取手机
     * @param $mobile
     * @return User|null|Model
     */
    public function getByMobile($mobile)
    {
        return User::query()
            ->where('mobile', $mobile)
            ->where('deleted', 0)
            ->first();
    }

    public function checkMobileSendCaptchaCount(string $mobile)
    {
        $countKey = 'register_captcha_count_'.$mobile;
        if (Cache::has($countKey)) {
            $count = Cache::increment($countKey);
            if ($count > 10) {
                return false;
            }
        } else {
            Cache::put($countKey, 1, Carbon::tomorrow()->diffInSeconds(now()));
        }
        return true;
    }

    /**
     * 发送短信验证码
     * @param  string  $mobile
     * @param  string  $code
     */
    public function sendCaptchaMsg(string $mobile, string $code)
    {
        if (app()->environment('testing')) {
            return;
        }
        //发送短信
        Notification::route(
            EasySmsChannel::class,
            new PhoneNumber($mobile, 86)
        )->notify(new VerificationCode($code));
    }

    /**
     * 验证短信验证码
     * @param $mobile
     * @param $code
     * @return bool
     * @throws BusinessException
     */
    public function checkCaptcha($mobile, $code)
    {
        $key = 'register_captcha_'.$mobile;
        $isPass = $code === Cache::get($key);
        if ($isPass) {
            Cache::forget($key);
            return true;
        } else {
            throw new BusinessException(CodeResponse::AUTH_CAPTCHA_UNMATCH);
        }
    }

    /**
     * 设置手机短信验证码
     * @param  string  $mobile
     * @return int
     * @throws \Exception
     */
    public function setCaptcha(string $mobile)
    {
        // todo 保存手机号和验证码的关系
        // todo 随机生成6位验证码
        $code = random_int(100000, 999999);
        Cache::put('register_captcha_'.$mobile, strval($code), 600);
        return $code;
    }
}
