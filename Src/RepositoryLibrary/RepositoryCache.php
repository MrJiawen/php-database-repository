<?php

namespace CjwRepository\Src\RepositoryLibrary;

use CjwRepository\Src\Cache\LaravelCacheDriver;
use CjwRepository\Src\Cache\RedisCacheDriver;

/**
 * Class RepositoryCache
 * @package CjwRepository\Src\RepositoryLibrary
 */
class RepositoryCache
{
    protected static $driver;

    /**
     * 选择对应的缓存驱动
     *      注意： 对应的缓存驱动必须继承   CjwRepository\Src\Cache\InterfaceCache.php 这个接口
     * @param $driverName
     */
    protected static function selectCacheDriver($driverName)
    {
        switch ($driverName) {
            case 'redis' :
                self::$driver = RedisCacheDriver::class;
                break;

            case 'laravel':
                self::$driver = LaravelCacheDriver::class;
                break;

            default:
                self::$driver = null;
        }
    }


    public static function createAllIndex($index, $driver, $callback)
    {

    }

    public static function updateAllIndex($index, $driver, $callback)
    {

    }

    /**
     *  通过find 查询 string 类型索引 的缓存内容
     * @param $index
     * @param $driver
     * @param $callback
     * @return bool
     */
    public static function findStringIndex($index, $driver, $callback)
    {
        // 1. 获取驱动
        self::selectCacheDriver($driver);
        $driver = self::$driver;

        if (empty($driver)) return false;

        // 2. 判断回调函数是否存在
        if (!empty($callback)) return $callback($index, $driver);

        // 3. 判断是否存在对键
        if (empty($driver::exists($index))) return false;

        // 4. 查询并且返回
        return $driver::getString($index);
    }


    /**
     *  通过find 查询 hash 类型索引 的缓存内容
     * @param $index
     * @param $driver
     * @param $callback
     * @return bool
     */
    public static function findHashIndex($index, $driver, $callback)
    {
        // 1. 获取驱动
        self::selectCacheDriver($driver);
        $driver = self::$driver;

        if (empty($driver)) return false;

        // 2. 判断回调函数是否存在
        if (!empty($callback)) return $callback($index, $driver);

        // 3. 判断是否存在对键
        if (empty($driver::exists($index))) return false;

        // 4. 查询并且返回
        return $driver::getHash($index);
    }

    /**
     * 通过find 查询 list 类型索引 的 缓存内容
     * @param $index
     * @param $driver
     * @param $callback
     * @return bool
     */
    public static function getList($index, $driver, $callback)
    {
        // 1. 获取驱动
        self::selectCacheDriver($driver);
        $driver = self::$driver;

        if (empty($driver)) return false;

        // 2. 判断回调函数是否存在
        if (!empty($callback)) return $callback($index, $driver);

        // 3. 判断是否存在对键
        if (empty($driver::exists($index))) return false;

        // 4. 查询并且返回
        return $driver::getList($index);
    }


    public static function pageList($index, $offset, $pageNum, $driver, $callback)
    {
        // 1. 获取驱动
        self::selectCacheDriver($driver);
        $driver = self::$driver;

        if (empty($driver)) return false;

        // 2. 判断回调函数是否存在
        if (!empty($callback)) return $callback($index, $offset, $pageNum, $driver);

        // 3. 判断是否存在对键
        if (empty($driver::exists($index))) return false;

        // 4. 查询并且返回
        return $driver::getListRange($index, $offset, $pageNum);
    }
}