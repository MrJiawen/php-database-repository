<?php

/** database_repository 配置文件
 *
 *  author： JiaWen Chen
 *
 *  配置文件分为两个部分：
 *      1. 一个为非database_repository 部分，主要是用于配置整个数据仓库的的配置信息，整个项目运行时候会使用到此信息
 *      2. 一个为database_repository 部分，主要是用于配置自动构建代码时候的参数信息
 *
 *  非database_repository 配置说明：
 *
 *  database_repository 配置说明：
 *      1. repository_type ： 它表示数据仓库的类型一共三种如下所示：
 *                                      equality_dependent(同级依赖模块)： 依赖于独立模块之下
 *                                      child_dependent(子级依赖模块)： 依赖于独立模块之下
 *                                      independent(独立模块)： 一个最小的独立数据仓库
 *
 *      2. 如果不是独立模块：则不能再设置 独立模块其他两个属性》
 *                                      equality_repository : 同级 repository 数据模块
 *                                      child_repository: 子级 repository 数据模块
 *
 *      3. primary_key ：为对应的database 的主键，如果此 database_repository 也是独立模块，则它为这个数据仓库的主键
 *
 *      4. string_key ： 为对应database_repository 的唯一索引
 *
 *      5. list_key ： 为对应 database_repository 的列表索引
 *
 *      6. list_page_key: 为对应 database_repository 的分页列表索引
 *
 *
 *   注意事项：
 *      1. 如果独立模块中 child_dependent 中存在元素， 则必须 有一个 child_repository 字段，来区别是否有相应的子集，它的值为对应的表名
 *
 */
return [
    //  项目运行时候所用的配置信息


    // 各种数据类型的驱动选择(redis：直接使用predis进行存储 ,cache：使用laravel的cache进行存储)
    'string_key_driver' => '',
    'list_key_driver' => '',
    'hash_key_driver' => '',

    // 过期时间单位设置(分钟)
    'log_expiration' => '3',
    'expiration' => '2',
    'short_expiration' => '1',


    // 数据仓库配置
    'database_repository' => [

        // repository 名称
        'database_name' => [

            // a. repository 类型 （equality_dependent、child_dependent、independent）
            'repository_type' => 'independent',

            // b. 同级 repository 数据模块
            'equality_repository' => [
                'database_repository'
            ],

            // c. 子级 repository 数据模块
            'child_repository' => [
                'database_repository'
            ],

            // d. 模块的主键
            'primary_key' => 'field',

            // e. 唯一索引
            'string_key' => [
                'field',
                ['field', 'field'],
            ],

            // f. 普通索引
            'list_key' => [
                'field',
                ['field', 'field'],
            ],

            // g.分页索引
            'list_page_key' => [
                'field',
                ['field', 'field'],
            ]
        ],
    ]
];