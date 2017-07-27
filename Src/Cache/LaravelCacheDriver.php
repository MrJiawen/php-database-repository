<?php

namespace CjwRepository\Src\Cache;

use Illuminate\Support\Facades\Cache;

class LaravelCacheDriver implements InterfaceCache
{
    /**
     * 判断是否存在
     * @param $key
     * @return bool
     */
    public static function exists($key)
    {
        return Cache::has($key);
    }


    /**
     * 删除 redis 键
     * @param $key
     * @return bool
     */
    public static function delete($key)
    {
        return Cache::forget($key);
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
        return Cache::put($key, $value, $expire);
    }

    /**
     * 获取 hash
     * @param $key
     * @return mixed
     */
    public static function getHash($key)
    {
        return Cache::get($key);
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
        return Cache::put($key, $value, $expire);
    }

    /**
     * 获取 string
     * @param $key
     * @return mixed
     */
    public static function getString($key)
    {
        return Cache::get($key);
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
        $tem = Cache::get($key);
        $tem = empty($tem) ? [] : $tem;

        $value = is_array($value) ? $value : [$value];
        foreach ($value as $item)
            array_unshift($tem, $item);

        return Cache::put($key, $tem, $expire);
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
        $tem = Cache::get($key);
        $tem = empty($tem) ? [] : $tem;

        $value = is_array($value) ? $value : [$value];
        foreach ($value as $item)
            array_push($tem, $item);

        return Cache::put($key, $tem, $expire);
    }

    /**
     * 获取全部索引
     * @param $key
     * @return mixed
     */
    public static function getList($key)
    {
        return Cache::get($key);
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
        $tem = Cache::get($key);
        $tem = empty($tem) ? [] : $tem;

        return array_slice($tem, $offset, $pageNum);
    }
}