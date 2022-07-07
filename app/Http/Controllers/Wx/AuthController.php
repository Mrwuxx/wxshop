<?php


namespace App\Http\Controllers\Wx;


use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserServices;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');
        $mobile = $request->input('mobile');
        $code = $request->input('code');

        $userInfo = new UserServices();
        // todo 验证参数是否为空
        if (empty($username) || empty($password) || empty($mobile) || empty($code)) {
            return ['errno' => 401, 'errmsg' => '参数不对'];
        }
        // todo 验证用户是否存在
        $user = $userInfo->getByUsername($username);
        if (!is_null($user)) {
            return ['errno' => 704, 'errmsg' => '用户名已注册'];
        }

        $validator = Validator::make(['mobile' => $mobile], ['mobile' => 'regex:/^1[0-9]{10}$']);
        if ($validator->fails()) {
            return ['errno' => 707, 'errmsg' => '手机格式不正确'];
        }
        $userMobile = $userInfo->getByMobile($mobile);
        if (!is_null($userMobile)) {
            return ['errno' => 705, 'errmsg' => '手机号已注册'];
        }

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

        return [
            'errno' => 0, 'errmsg' => '成功', 'data' => [
                'token' => '',
                'userInfo' => [
                    'nickname' => $username,
                    'avatarUrl' => $userTable->avater
                ]
            ]
        ];
    }
}
