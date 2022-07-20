<?php


namespace App\Services;

class BaseService
{
    protected static $instance;
    /*private function __construct()
    {
    }*/
    /**
     * @return 返回实例static
     */
    public static function getInstance()
    {
        if (static::$instance instanceof static) {
            return static::$instance;
        }
        static::$instance = new static();
        return static::$instance;
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }
}
