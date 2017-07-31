<?php

namespace CjwRepository\Src\Cache;

use Illuminate\Support\Facades\Redis;

class RedisCacheDriver implements InterfaceCache
{
    /**
     * 判断是否存在
     * @param $key
     * @return bool
     */
    public static function exists($key)
    {
        return (bool)Redis::exists($key);
    }


    /**
     * 删除 redis 键
     * @param $key
     * @return bool
     */
    public static function delete($key)
    {
        return (bool)Redis::del($key);
    }

    /**
     * 设置 或 更新 hash
     * @param $key
     * @param $value
     * @param $expire
     * @return bool
     */
    public static function setHash($key, $value, $expire)
    {
        $resultSet = Redis::hmset($key, $value);

        $resultExpire = Redis::expire($key, $expire * 60);

        return ((String)$resultSet == 'OK') && !empty($resultExpire);
    }

    /**
     * 获取 hash
     * @param $key
     * @return mixed
     */
    public static function getHash($key)
    {
        return Redis::hgetall($key);
    }


    /**
     * 设置 string
     * @param $key
     * @param $value
     * @param $expire
     * @return bool
     */
    public static function setString($key, $value, $expire)
    {
        $resultSet = Redis::set($key, $value);

        $resultExpire = Redis::expire($key, $expire * 60);

        return ((String)$resultSet == 'OK') && !empty($resultExpire);
    }

    /**
     * 获取 string
     * @param $key
     * @return mixed
     */
    public static function getString($key)
    {
        return Redis::get($key);
    }

    /**
     * 从头部 开始 设置 list 索引
     * @param $key
     * @param $value
     * @param $expire
     * @return bool
     */
    public static function setListFromLeft($key, $value, $expire)
    {
        $resultSet = Redis::lpush($key, $value);

        $resultExpire = Redis::expire($key, $expire * 60);

        return (bool)$resultSet && !empty($resultExpire);
    }

    /**
     * 从尾部 开始 设置 list 索引
     * @param $key
     * @param $value
     * @param $expire
     * @return bool
     */
    public static function setListFromRight($key, $value, $expire)
    {
        $resultSet = Redis::rpush($key, $value);

        $resultExpire = Redis::expire($key, $expire * 60);

        return (bool)$resultSet && !empty($resultExpire);
    }

    /**
     * 获取全部索引
     * @param $key
     * @return mixed
     */
    public static function getList($key)
    {
        return Redis::lrange($key, 0, -1);
    }

    /**
     * 获取部分索引
     * @param $key
     * @param $offset
     * @param $pageNum
     * @return mixed
     */
    public static function getListRange($key, $offset, $pageNum)
    {
        return Redis::lrange($key, $offset, $offset + $pageNum - 1);
    }
}
