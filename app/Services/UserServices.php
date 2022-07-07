<?php


namespace App\Services;


use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserServices
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
}
