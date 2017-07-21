<?php

namespace CjwRepository;

use Illuminate\Support\ServiceProvider;

/** 数据厂库提供者
 * Class RepositoryProvider
 * @package CjwRepository
 */
class RepositoryProvider extends ServiceProvider
{
    /**
     *  运行注册后的启动服务器
     *
     * @return void
     */
    public function boot()
    {
        // 1.加载配置文件
        $database_repository =  realpath(__DIR__ . '/Src/Config/database_repository.php');
        $this->publishes([$database_repository => config_path('database_repository.php')]);
    }

    /**
     *  在容器中注册绑定
     *
     * @return void
     */
    public function register()
    {

    }
}