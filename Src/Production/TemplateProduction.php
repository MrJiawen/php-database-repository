<?php

namespace CjwRepository\Src\Production;

/** 开始生产模板
 * Class TemplateProduction
 * @package CjwRepository\Src\Production
 */
class TemplateProduction
{
    // 命令对象
    public $command;

    // 配置文件原始信息
    public $configOrigin;

    // 数据仓库配置容器
    public $databaseRepositoryConfig;

    // 项目的 app 的文件路径;
    public $appPath;

    /**
     * TemplateProduction constructor.
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->command = $kernel->command;
        $this->configOrigin = $kernel->configOrigin;
        $this->databaseRepositoryConfig = $kernel->databaseRepositoryConfig;

        $this->appPath = app_path();

        // 1. 检查目录结构， 并且构建目录结构
        $this->checkDirectory();
        $this->command->info('>>>        整理目录结构完毕');

        // 2. 开始构建model
        $this->command->comment('>>> 第三步 开始为所有的 database_repository 建立Model代码!');

        $this->productionModelCode();

        $this->command->comment('>>>        database_repository 扫描Model代码完毕!');

        // 3. 开始构建store
        $this->command->comment('>>> 第四步 开始为所有的 database_repository 建立store代码!');

        $kernel->configBeforeConstructStore();
        $this->databaseRepositoryConfig = $kernel->databaseRepositoryConfig;
        $this->productionStoreCode();
    }

    /** 检查目录结构， 并且构建目录结构
     * @author chenjiawen
     */
    public function checkDirectory()
    {
        // 1. 检查 Model 目录
        if (!file_exists($this->appPath . '/Model'))
            mkdir($this->appPath . '/Model');


        // 2. 检查 Model/Cache 目录
        if (!file_exists($this->appPath . '/Model/Cache'))
            mkdir($this->appPath . '/Model/Cache');

        // 3. 检查 Store 目录
        if (!file_exists($this->appPath . '/Store'))
            mkdir($this->appPath . '/Store');

        // 4. 检查 Store/Cache目录
        if (!file_exists($this->appPath . '/Store/Cache'))
            mkdir($this->appPath . '/Store/Cache');
    }


    /**
     * 构建 store 层 代码
     */
    public function productionStoreCode()
    {
        // 2. 循环构建配置
        foreach ($this->databaseRepositoryConfig as $key => $item) {

            if ($item['repository_type'] != 'independent') {
                continue;
            }
            $this->productionOneStoreCode($key, $item);
        }
    }

    /**
     * 构建 一个 databaseRepository 的 Store 代码
     * @param $databaseName
     * @param $databaseRepository
     */
    protected function productionOneStoreCode($databaseName, $databaseRepository)
    {
        // 1. 参数提前
        $databaseStoreName = convertUnderline($databaseName) . 'Store';
        $databaseCacheStoreName = convertUnderline($databaseName) . 'CacheStore';

        // 2. 构建 DatabaseStore
        if (!file_exists($this->appPath . '/Store/' . $databaseStoreName . '.php')) {

            $databaseStoreTemplate = file_get_contents(__DIR__ . '/../Template/DatabaseStore.php');
            $databaseStoreTemplate = str_replace('DatabaseStore', $databaseStoreName, $databaseStoreTemplate);
            $databaseStoreTemplate = str_replace('DatabaseCacheStore', $databaseCacheStoreName, $databaseStoreTemplate);

            file_put_contents($this->appPath . '/Store/' . $databaseStoreName . '.php', $databaseStoreTemplate);
            $this->command->info('           新建了' . $this->appPath . '/Store/' . $databaseStoreName . '.php' . ' 模型文件');
        }

        // 3. 构建 DatabaseCacheStore
        $notice = file_exists($this->appPath . '/Store/Cache/' . $databaseCacheStoreName . '.php') ? "刷新了" : '新建了';


        // 3.1 获取模板
        $databaseStoreTemplate = file_get_contents(__DIR__ . '/../Template/DatabaseCacheStore.php');


        $databaseStoreTemplate = str_replace('DatabaseCacheStore', $databaseCacheStoreName, $databaseStoreTemplate);

        // 3.11 写入文件
        file_put_contents($this->appPath . '/Store/Cache/' . $databaseCacheStoreName . '.php', $databaseStoreTemplate);

        // 4. 提示语
        $this->command->info('           ' . $notice . $this->appPath . '/Store/Cache/' . $databaseCacheStoreName . '.php' . ' 模型缓存文件');


        dd($databaseRepository, $databaseName);
    }

    /**
     *  构建 model层 代码
     */
    public function productionModelCode()
    {
        foreach ($this->databaseRepositoryConfig as $databaseName => $item) {

            $this->productionOneModelCode($databaseName, $item);
        }
    }


    /**
     *  生产一个 databaseRepository 的model代码
     *              1. databaseName 配置文件中的 database_repository 名称，在这里对应表名
     *              2. databaseRepository ：某个 database_repository 下的处理后的所有配置信息
     * @param $databaseName
     * @param $databaseRepository
     */
    protected function productionOneModelCode($databaseName, $databaseRepository)
    {
        // 1. 参数提前
        $databaseModelName = convertUnderline($databaseName) . 'Model';
        $databaseCacheModelName = convertUnderline($databaseName) . 'CacheModel';

        // 2. 构建 DatabaseModel
        if (!file_exists($this->appPath . '/Model/' . $databaseModelName . '.php')) {

            $databaseModelTemplate = file_get_contents(__DIR__ . '/../Template/DatabaseModel.php');
            $databaseModelTemplate = str_replace('DatabaseModel', $databaseModelName, $databaseModelTemplate);
            $databaseModelTemplate = str_replace('DatabaseCacheModel', $databaseCacheModelName, $databaseModelTemplate);

            file_put_contents($this->appPath . '/Model/' . $databaseModelName . '.php', $databaseModelTemplate);
            $this->command->info('           新建了' . $this->appPath . '/Model/' . $databaseModelName . '.php' . ' 模型文件');
        }

        // 3. 构建 DatabaseCacheModel
        $notice = file_exists($this->appPath . '/Model/Cache/' . $databaseCacheModelName . '.php') ? "刷新了" : '新建了';

        // 3.1 获取模板
        $databaseModelTemplate = file_get_contents(__DIR__ . '/../Template/DatabaseCacheModel.php');
        $modelFindMethodTemplate = file_get_contents(__DIR__ . '/../Template/ModelFindMethod.php');
        $modelGetMethodTemplate = file_get_contents(__DIR__ . '/../Template/ModelGetMethod.php');
        $modelCountMethodTemplate = file_get_contents(__DIR__ . '/../Template/ModelCountMethod.php');
        $modelPageMethodTemplate = file_get_contents(__DIR__ . '/../Template/ModelPageMethod.php');


        // 3.2 更换文件名称
        $databaseModelTemplate = str_replace('DatabaseCacheModel', $databaseCacheModelName, $databaseModelTemplate);

        // 3.3 填写表名
        $databaseModelTemplate = str_replace('tableName', $databaseName, $databaseModelTemplate);

        // 3.4 填写主键
        $databaseModelTemplate = str_replace('primaryKeyName', $databaseRepository['primary_key']['field'], $databaseModelTemplate);

        // 3.5 通过主键查询一条数据
        $this->addModelMethodCode($databaseModelTemplate, $databaseRepository['primary_key']['field'], $modelFindMethodTemplate);

        // 3.6 获取所有的数据(在父类中)

        // 3.7 获取所有数据分页（在父类中）

        // 3.8 string 索引方法
        foreach ($databaseRepository['string_key'] as $item)
            $this->addModelMethodCode($databaseModelTemplate, $item['field'], $modelFindMethodTemplate);

        // 3.9 list 索引方法
        foreach ($databaseRepository['list_key'] as $item)
            $this->addModelMethodCode($databaseModelTemplate, $item['field'], $modelGetMethodTemplate);

        // 3.10 list_page 索引方法
        foreach ($databaseRepository['list_page_key'] as $item) {
            $this->addModelMethodCode($databaseModelTemplate, $item['field'], $modelCountMethodTemplate);
            $this->addModelMethodCode($databaseModelTemplate, $item['field'], $modelPageMethodTemplate);
        }

        // 3.11 写入文件
        file_put_contents($this->appPath . '/Model/Cache/' . $databaseCacheModelName . '.php', $databaseModelTemplate);

        // 4. 提示语
        $this->command->info('           ' . $notice . $this->appPath . '/Model/Cache/' . $databaseCacheModelName . '.php' . ' 模型缓存文件');
    }

    /**
     * 为 构建的 model 添加方法
     *          1. databaseModelTemplate 为对应构建的 model 模型代码
     *          2. 构建的方法制的字段
     *          3. 对应的模板名称
     * @param $databaseModelTemplate
     * @param $field
     * @param $template
     */
    protected function addModelMethodCode(&$databaseModelTemplate, $field, $template)
    {
        $field = is_array($field) ? convertUnderline(implode('_', $field)) : ucfirst($field);

        $template = str_replace('Field', $field, $template);

        $contain = explode("\r\n", $databaseModelTemplate);
        $end = array_pop($contain);
        array_push($contain, $template, $end);

        $databaseModelTemplate = implode("\r\n", $contain);
    }
}