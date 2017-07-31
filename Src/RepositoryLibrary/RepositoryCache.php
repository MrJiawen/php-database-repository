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


    /**
     * 处理 create 方法 对缓存的更新操作
     *      notice： 只对redis 中的list 进行操作， 如果不存在都可以自动读取
     * @param $primaryKey
     * @param $data
     * @param $listContain
     * @param $listPageContain
     * @param $driver
     * @param $expiration
     * @param $callback
     * @return bool
     */
    public static function createDealIndex($primaryKey, $data, $listContain, $listPageContain, $driver, $expiration, $callback)
    {
        // 1. 获取驱动
        self::selectCacheDriver($driver);
        $driver = self::$driver;

        if (empty($driver)) return false;

        // 1.2 list 只能使用redis缓存
        if ($driver != RedisCacheDriver::class) simpleError('list cache can only use RedisCacheDriver , please you modify configuration！！！', __FILE__, __LINE__);

        // 2. 判断回调函数是否存在
        if (!empty($callback)) return $callback($data, $listContain, $listPageContain, $driver);

        // 3. 为每个list 添加数据
        foreach (array_merge($listContain) as $item) {
            // 3.1 构建缓存键
            $index = array_only(toArray($data), $item['field']);
            $valueStr = implode(':', array_values($index));
            $index = $item['list_index'] . $valueStr;

            // 3.2 如果不存在则跳过
            if (empty($driver::exists($index))) continue;

            // 3.3 如果存在 则 压到头部
            $result = $driver::setListFromLeft($index, $data->$primaryKey, $expiration);

            if (empty($result)) return false;
        }

        // 4. 为每个list_page 添加数据
        foreach (array_merge($listPageContain) as $item) {
            // 4.1 构建缓存键
            $index = array_only(toArray($data), $item['field']);
            $valueStr = implode(':', array_values($index));
            $index_count = $item['list_page_total_index'] . $valueStr;
            $index_data = $item['list_page_index'] . $valueStr;

            // 4.2.1 为count修改数值
            if (!empty($driver::exists($index_count))) {

                $result = $driver::setString($index_count,$driver::getString($index_count) + 1,$expiration);

                if(empty($result)) return false;
            }

            // 4.3.1 为 list_page 看在开头压入数据
            if (!empty($driver::exists($index_data))) {
                $result = $driver::setListFromLeft($index_data, $data->$primaryKey, $expiration);

                if (empty($result)) return false;
            }
        }

        return true;
    }

    public static function updateAllIndex($index, $driver, $callback)
    {

    }

    /**
     * 通过find 查询 string 类型索引 的缓存内容
     * @param $index
     * @param $stringContain
     * @param $driver
     * @param $callback
     * @return bool
     */
    public static function findStringGet($index, $stringContain, $driver, $callback)
    {
        // 1. 获取驱动
        self::selectCacheDriver($driver);
        $driver = self::$driver;

        if (empty($driver)) return false;

        // 2. 判断回调函数是否存在
        if (!empty($callback)) return $callback($index, $stringContain, $driver);

        // 3. 判断是否存在索引键
        $indexStr = implode('_', array_keys($index));
        $valueStr = implode(':', array_values($index));
        $index = $stringContain[$indexStr]['string_index'] . $valueStr;

        if (empty($driver::exists($index))) return false;

        // 4. 查询并且返回
        return $driver::getString($index);
    }

    /**
     * 设置 string 缓存
     * @param $index
     * @param $value
     * @param $stringContain
     * @param $driver
     * @param $expiration
     * @param $callback
     * @return bool
     */
    public static function findStringSet($index, $value, $stringContain, $driver, $expiration, $callback)
    {
        // 1. 获取驱动
        self::selectCacheDriver($driver);
        $driver = self::$driver;

        if (empty($driver)) return false;

        // 2. 判断回调函数是否存在
        if (!empty($callback)) return $callback($index, $value, $stringContain, $driver, $expiration);

        // 3. 构造 索引键
        $indexStr = implode('_', array_keys($index));
        $valueStr = implode(':', array_values($index));
        $index = $stringContain[$indexStr]['string_index'] . $valueStr;

        // 4. 查询并且返回
        return $driver::setString($index, $value, $expiration);
    }


    /**
     * 通过find 查询 hash 类型索引 的缓存内容
     * @param $index
     * @param $hashContain
     * @param $driver
     * @param $callback
     * @return bool
     */
    public static function findHashGet($index, $hashContain, $driver, $callback)
    {
        // 1. 获取驱动
        self::selectCacheDriver($driver);
        $driver = self::$driver;

        if (empty($driver)) return false;

        // 2. 判断回调函数是否存在
        if (!empty($callback)) return $callback($index, $hashContain, $driver);

        // 3. 判断是否存在对键
        $index = $hashContain['hash_index'] . $index[$hashContain['field']];
        if (empty($driver::exists($index))) return false;

        // 4. 查询并且返回
        return $driver::getHash($index);
    }

    /**
     * 设置 hash 类型索引 的缓存内容
     * @param $index
     * @param $value
     * @param $hashContain
     * @param $driver
     * @param $expiration
     * @param $callback
     * @return bool
     */
    public static function findHashSet($index, $value, $hashContain, $driver, $expiration, $callback)
    {
        // 1. 获取驱动
        self::selectCacheDriver($driver);
        $driver = self::$driver;

        if (empty($driver)) return false;

        // 2. 判断回调函数是否存在
        if (!empty($callback)) return $callback($index, $value, $hashContain, $driver, $expiration);

        // 3. 构造哈希键
        $index = $hashContain['hash_index'] . $index[$hashContain['field']];

        // 4. 查询并且返回
        return $driver::setHash($index, toArray($value), $expiration);
    }

    /**
     * 获取整个list
     * @param $index
     * @param $listContain
     * @param $driver
     * @param $callback
     * @return bool
     */
    public static function getListGet($index, $listContain, $driver, $callback)
    {
        // 1. 获取驱动
        self::selectCacheDriver($driver);
        $driver = self::$driver;

        if (empty($driver)) return false;

        // 1.2 list 只能使用redis缓存
        if ($driver != RedisCacheDriver::class) simpleError('list cache can only use RedisCacheDriver , please you modify configuration！！！', __FILE__, __LINE__);

        // 2. 判断回调函数是否存在
        if (!empty($callback)) return $callback($index, $listContain, $driver);

        // 3. 判断是否存在对键
        $indexStr = implode('_', array_keys($index));
        $valueStr = implode(':', array_values($index));
        $index = $listContain[$indexStr]['list_index'] . $valueStr;

        if (empty($driver::exists($index))) return false;

        // 4. 查询并且返回
        return $driver::getList($index);
    }

    /**
     * 设置整个list （从前到后）
     * @param $index
     * @param $value
     * @param $listContain
     * @param $driver
     * @param $expiration
     * @param $callback
     * @return bool
     */
    public static function getListSet($index, $value, $listContain, $driver, $expiration, $callback)
    {
        // 1. 获取驱动
        self::selectCacheDriver($driver);
        $driver = self::$driver;

        if (empty($driver)) return false;

        // 1.2 list 只能使用redis缓存
        if ($driver != RedisCacheDriver::class) simpleError('list cache can only use RedisCacheDriver , please you modify configuration！！！', __FILE__, __LINE__);

        // 2. 判断回调函数是否存在
        if (!empty($callback)) return $callback($index, $value, $listContain, $driver, $expiration);

        // 3. 构造 索引键
        $indexStr = implode('_', array_keys($index));
        $valueStr = implode(':', array_values($index));
        $index = $listContain[$indexStr]['list_index'] . $valueStr;

        // 4. 查询并且返回
        return $driver::setListFromRight($index, $value, $expiration);
    }

    /**
     * 获取分页的总数
     * @param $index
     * @param $listPageContain
     * @param $driver
     * @param $callback
     * @return bool
     */
    public static function pageCountGet($index, $listPageContain, $driver, $callback)
    {
        // 1. 获取驱动
        self::selectCacheDriver($driver);
        $driver = self::$driver;

        if (empty($driver)) return false;

        // 2. 判断回调函数是否存在
        if (!empty($callback)) return $callback($index, $listPageContain, $driver);

        // 3. 判断是否存在对键
        $indexStr = implode('_', array_keys($index));
        $valueStr = implode(':', array_values($index));
        $index = $listPageContain[$indexStr]['list_page_total_index'] . $valueStr;

        if (empty($driver::exists($index))) return false;

        // 4. 查询并且返回
        return $driver::getString($index);
    }

    /**
     * 设置分页的总数
     * @param $index
     * @param $value
     * @param $listPageContain
     * @param $driver
     * @param $expiration
     * @param $callback
     * @return bool
     */
    public static function pageCountSet($index, $value, $listPageContain, $driver, $expiration, $callback)
    {
        // 1. 获取驱动
        self::selectCacheDriver($driver);
        $driver = self::$driver;

        if (empty($driver)) return false;

        // 2. 判断回调函数是否存在
        if (!empty($callback)) return $callback($index, $value, $listPageContain, $driver, $expiration);

        // 3. 构造 索引键
        $indexStr = implode('_', array_keys($index));
        $valueStr = implode(':', array_values($index));
        $index = $listPageContain[$indexStr]['list_page_total_index'] . $valueStr;

        // 4. 查询并且返回
        return $driver::setString($index, $value, $expiration);


    }

    /**
     * 获取分页数据
     * @param $index
     * @param $offset
     * @param $pageNum
     * @param $pageMaxNum
     * @param $listPageContain
     * @param $driver
     * @param $callback
     * @return array|bool
     */
    public static function pageListGet($index, $offset, $pageNum, $pageMaxNum, $listPageContain, $driver, $callback)
    {
        // 1. 获取驱动
        self::selectCacheDriver($driver);
        $driver = self::$driver;

        if (empty($driver)) return false;

        // 1.2 list 只能使用redis缓存
        if ($driver != RedisCacheDriver::class) simpleError('list cache can only use RedisCacheDriver , please you modify configuration！！！', __FILE__, __LINE__);

        // 2. 判断回调函数是否存在
        if (!empty($callback)) return $callback($index, $offset, $pageNum, $driver);

        // 3. 查看偏移量是否超过设置的最大阈值
        if ($offset + $pageNum > $pageMaxNum) return [];

        // 4. 判断是否存在对键
        $indexStr = implode('_', array_keys($index));
        $valueStr = implode(':', array_values($index));
        $index = $listPageContain[$indexStr]['list_page_index'] . $valueStr;

        if (empty($driver::exists($index))) return false;

        // 5. 查询并且返回
        return $driver::getListRange($index, $offset, $pageNum);
    }

    /**
     * 设置分页数据
     * @param $index
     * @param $value
     * @param $listPageContain
     * @param $driver
     * @param $expiration
     * @param $callback
     * @return bool
     */
    public static function pageListSet($index, $value, $listPageContain, $driver, $expiration, $callback)
    {
        // 1. 获取驱动
        self::selectCacheDriver($driver);
        $driver = self::$driver;

        if (empty($driver)) return false;

        // 1.2 list 只能使用redis缓存
        if ($driver != RedisCacheDriver::class) simpleError('list cache can only use RedisCacheDriver , please you modify configuration！！！', __FILE__, __LINE__);

        // 2. 判断回调函数是否存在
        if (!empty($callback)) return $callback($index, $value, $listPageContain, $driver, $expiration);

        // 3. 构造 索引键
        $indexStr = implode('_', array_keys($index));
        $valueStr = implode(':', array_values($index));
        $index = $listPageContain[$indexStr]['list_page_index'] . $valueStr;

        // 4. 查询并且返回
        return $driver::setListFromRight($index, $value, $expiration);
    }
}