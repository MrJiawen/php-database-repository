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

        // 3.2 修改类名称
        $databaseStoreTemplate = str_replace('DatabaseCacheStore', $databaseCacheStoreName, $databaseStoreTemplate);

        // 3.3 写入相应的repository name
        $this->addStoreCode($databaseStoreTemplate, <<<ABC
    /**
     * 数据仓库名称
     * @var string
     */
    public \$repository_name = "$databaseName";
ABC
        );
        // 3.3 写入主键
        $this->addStoreCode($databaseStoreTemplate, <<<ABC
        
    /**
     * 数据仓库的主键
     * @var string
     */
    public \$primary_key = "{$databaseRepository['primary_key']['field']}";
ABC
        );
        // 3.4 写入 数据仓库的所有字段
        $temporary = arrayToString(array_values($databaseRepository['contain']['database_fields']));
        $this->addStoreCode($databaseStoreTemplate, <<<ABC
    
    /**
     * 数据仓库所有字段
     * @var array
     */
    public \$fields = {$temporary};
ABC
        );
        // 3.5 写入 数据仓库必填字段
        $temporary = arrayToString(array_values($databaseRepository['contain']['database_fields_not_null']));
        $this->addStoreCode($databaseStoreTemplate, <<<ABC
    
    /**
     * 数据仓库必填字段
     * @var array
     */
    public \$fields_not_null = {$temporary};
ABC
        );

        // 3.6 写入 构造方法
        $this->productionStoreConstructMethod($databaseStoreTemplate, $databaseName, $databaseRepository);

        // 3.7 写入 create 方法
        $this->productionStoreCreateMethod($databaseStoreTemplate, $databaseName, $databaseRepository);

        // 3.11 写入文件
        file_put_contents($this->appPath . '/Store/Cache/' . $databaseCacheStoreName . '.php', $databaseStoreTemplate);


        dd($databaseRepository, $databaseStoreTemplate);
        // 4. 提示语
        $this->command->info('           ' . $notice . $this->appPath . '/Store/Cache/' . $databaseCacheStoreName . '.php' . ' 模型缓存文件');


        //  dd($databaseRepository, $databaseName);
    }


    /**
     * 写入构造方法
     * @param $databaseStoreTemplate
     * @param $databaseName
     * @param $databaseRepository
     */
    protected function productionStoreConstructMethod(&$databaseStoreTemplate, $databaseName, $databaseRepository)
    {
        // 1. 构造方法 =》 添加命名空间
        $contain = explode("/**", $databaseStoreTemplate);
        $start = array_shift($contain);
        $contain = "\r\n/**" . implode("/**", $contain);
        foreach (array_merge([$databaseName], $databaseRepository['equality_repository'], $databaseRepository['child_repository']) as $item) {
            $namespaceLoad = $this->databaseRepositoryConfig[$item]['database_model_info']['namespace_load'];
            $contain = $namespaceLoad . ";\r\n" . $contain;
        }
        $databaseStoreTemplate = $start . $contain;

        // 2 构造方法 =》 添加成员变量方法的成员变量
        $modelMemberPropertyStr = '';
        foreach (array_merge([$databaseName], $databaseRepository['equality_repository'], $databaseRepository['child_repository']) as $item) {
            $modelMemberProperty = $this->databaseRepositoryConfig[$item]['database_model_info']['model_member_property'];
            $modelMemberPropertyStr .= <<<ABC
            
            
    /**
     * model object
     * @var
     */
    public \${$modelMemberProperty};
ABC;
 ;
        }
        $this->addStoreCode($databaseStoreTemplate,$modelMemberPropertyStr);

        // 3 构造方法 =》 构建构造方法
        $modelObjectStr = '';
        foreach (array_merge([$databaseName], $databaseRepository['equality_repository'], $databaseRepository['child_repository']) as $item) {
            $modelObject = $this->databaseRepositoryConfig[$item]['database_model_info']['model_object'];
            $modelMemberProperty = $this->databaseRepositoryConfig[$item]['database_model_info']['model_member_property'];
            $modelObjectStr .= tabConvertSpace(2).'$this->'.$modelMemberProperty .' = new '.$modelObject."();\r\n";
        }
        $modelObjectStr = trim($modelObjectStr,"\r\n");
        $databaseCacheStoreName = convertUnderline($databaseName).'CacheStore';
        $this->addStoreCode($databaseStoreTemplate,<<<ABC
  
    /**
     * {$databaseCacheStoreName} constructor.
     */  
    public function __construct()
    {
{$modelObjectStr}     
    }
ABC
);
    }

    protected function productionStoreCreateMethod(&$databaseStoreTemplate, $databaseName, $databaseRepository)
    {
        // 1. 构建写入数据
        $resultString = '';
        $createString = '';
        foreach (array_merge([$databaseName], $databaseRepository['equality_repository'], $databaseRepository['child_repository']) as $item){
            $databaseModelInfo = $this->databaseRepositoryConfig[$item]['database_model_info'];
            $databaseFields = $this->databaseRepositoryConfig[$item]['database_fields'];
            $createString .= tabConvertSpace(2) . '// create '.$databaseModelInfo['repository_name'] ." model data; \r\n";
            $createString .= tabConvertSpace(2) . '$create'.$databaseModelInfo['model_object'].
                ' = array_only($create, '.arrayToString($databaseFields).");\r\n";
            $createString .= tabConvertSpace(2) . '$result'.$databaseModelInfo['model_object'].' = $this->'.
                $databaseModelInfo['model_member_property'].'->create($create'.$databaseModelInfo['model_object'].");\r\n\r\n";

            $resultString .= '$result'.$databaseModelInfo['model_object'] .' & ';
        }
        $resultString = trim($resultString, '& ');
        // 写入模板
        $this->addStoreCode($databaseStoreTemplate,<<<ABC
    
    /**
     * create one record in this database repository
     * @param \$create
     * @return bool|int
     */
    public function create(\$create)
    {
        // 1. 字段过滤
        if (!array_keys_exists(\$this->fields_not_null, \$create))
            return false;
            
        // 2. 写入数据
{$createString} 
        return {$resultString};
    }
ABC
        );


    }

    /**
     *  构建 model层 代码
     */
    public function productionModelCode()
    {
        foreach ($this->databaseRepositoryConfig as $databaseName => $item) {

            if ($item['repository_type'] != 'independent') {
                continue;
            }
            // 1. 首先构建同级、子级、和关联模块的数据仓库
            foreach (array_merge($item['equality_repository'], $item['child_repository']) as $childRepository) {
                $this->productionOneModelCode($childRepository, $this->databaseRepositoryConfig[$childRepository]);
            }

            // 2. 然后构建自己（独立的 database_repository ）
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
        $field = is_array($field) ? convertUnderline(implode('_', $field)) : convertUnderline($field);

        $template = str_replace('Field', $field, $template);

        $contain = explode("\r\n", $databaseModelTemplate);
        $end = array_pop($contain);
        array_push($contain, $template, $end);

        $databaseModelTemplate = implode("\r\n", $contain);
    }

    /** 为 构建的 store  添加代码
     * @param $databaseStoreTemplate
     * @param $code
     */
    protected function addStoreCode(&$databaseStoreTemplate, $code)
    {
        $contain = explode("\r\n", $databaseStoreTemplate);

        $end = array_pop($contain);
        array_push($contain, $code, $end);

        $databaseStoreTemplate = implode("\r\n", $contain);
    }
}