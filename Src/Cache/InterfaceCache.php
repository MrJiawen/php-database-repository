<?php

namespace CjwRepository\Src\Cache;

/**
 * 缓存驱动结构
 * Interface InterfaceCache
 * @package CjwRepository\Src\Cache
 */
interface InterfaceCache
{

    /**
     * 获取 hash
     * @param $key
     * @return mixed
     */
    public static function exists($key);

    /**
     * 获取 hash
     * @param $key
     * @return mixed
     */
    public static function delete($key);

    /**
     * 设置 或 更新 hash
     * @param $key
     * @param $value
     * @param $expire
     * @return mixed
     */
    public static function setHash($key, $value, $expire);

    /**
     * 获取 hash
     * @param $key
     * @return mixed
     */
    public static function getHash($key);

    /**
     * 设置 string 索引
     * @param $key
     * @param $value
     * @param $expire
     * @return mixed
     */
    public static function setString($key, $value, $expire);

    /**
     * 获取 string 索引
     * @param $key
     * @return mixed
     */
    public static function getString($key);

    /**
     * 从头部 开始 设置 list 索引
     * @param $key
     * @param $value
     * @param $expire
     * @return mixed
     */
    public static function setListFromLeft($key, $value, $expire);

    /**
     * 从尾部 开始 设置 list 索引
     * @param $key
     * @param $value
     * @param $expire
     * @return mixed
     */
    public static function setListFromRight($key, $value, $expire);

    /**
     * 获取全部索引
     * @param $key
     * @return mixed
     */
    public static function getList($key);

    /**
     * 获取部分索引
     * @param $key
     * @param $offset
     * @param $pageNum
     * @return mixed
     */
    public static function getListRange($key, $offset, $pageNum);
}

/**
 *
 *  notice :
 *          1. 目前缓存驱动一共有两种，第一种就是redis 驱动，  一种就是 laravel 自带的cache 驱动进行缓存；但是他们都有利弊。
 *
 *
 *  redis 驱动利弊问题：
 *          1. 无法存储一个空的list ，这样导致，如果某个list 索引查询到是的数据是一个空的数组， 则无法对其进行存储。
 *             则每次查询这个数据的时候，都会去查询数据库，但是基本上不会蛮多，如果数据库建立好索引后，对其也不会造成多大影响。
 *
 *
 *
 *  laravel 的cache 驱动 利弊问题：
 *          1. 每次在做list 查询时候，必须把整个list全部都给拉出来后，在做list做操作，这样对io 和内存都是一个巨大的消耗，
 *             相比于 redis 驱动 利弊来讲， 这个情况太严重。
 *
 *
 *
 */