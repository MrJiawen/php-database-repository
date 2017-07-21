<?php

namespace CjwRepository\Src\Commands;

use CjwRepository\Src\Production\Kernel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepositoryModelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repositoryModel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'database model repository is auto structure command';


    /** config/database_repository 配置信息
     * @var
     */
    protected $config;


    public function __construct()
    {
        parent::__construct();
        $this->config = config('database_repository');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // 1. 检查配置文件

        $this->comment('>>> 第一步  开始检查database_repository 参数');

        $this->checkConfig();

        $this->info('>>> 第一步 database_repository 参数检查完毕');


        // 2. 开始生产代码
        $this->comment('>>> 第二步 database_repository 开始生产代码');

        new Kernel($this);

        $this->info('>>> 最后提示： success ，database_repository 生产完毕');
    }

    /**  检查配置文件
     * @author chenjiawen
     */
    public function checkConfig()
    {
        // 1. 常规参数检查

        // 2. database_repository 参数检查
        if (empty($this->config['database_repository'])) {
            $this->error('！！！请先填写database_repository配置信息');
            exit;
        }

        foreach ($this->config['database_repository'] as $databaseName => $item) {
            // 检查一个database_repository的非索引配置信息
            $this->checkOneDatabaseRepositoryGeneralInfo($databaseName, $item);

            //检查一个database_repository的非索引配置信息
            $this->checkOneDatabaseRepositoryKeyInfo($databaseName, $item);
        }
    }

    /**
     *  检查一个database_repository的非索引配置信息
     *          1. 检查 这个database_repository 名称是否正确
     *          2. 检查 这个database_repository 中 repository_type
     *          3. 检查 这个database_repository 中 equality_repository
     *          4. 检查 这个database_repository 中 child_repository
     * @param $databaseName
     * @param $config
     * @author chenjiawen
     */
    protected function checkOneDatabaseRepositoryGeneralInfo($databaseName, $config)
    {
        // 1. 查询本表的信息
        $tables = DB::select('show tables');

        if (empty($tables)) {
            $this->error('！！！本数据没有表结构');
            exit;
        }

        // 2. 检查表是否存在
        $tableExist = false;
        foreach ($tables as $key => $item) {
            $item_databaseName = array_values(toArray($item))[0];

            if ($item_databaseName == $databaseName) {
                $tableExist = true;
                break;
            }
        }

        if (empty($tableExist)) {
            $this->error('！！！' . $databaseName . '这个表不存在本数据中');
            exit;
        }

        // 3.检查一条repository非索引的配置项

        // 3.1 检查repository_type
        if (empty($config['repository_type']) || !in_array($config['repository_type'], ['equality_dependent', 'child_dependent', 'independent'])) {
            $this->error('！！！' . $databaseName . '这个database_repository的repository_typed参数配置异常，必须是equality_dependent、child_dependent、independent 三个选项');
            exit;
        }

        // 3.2 检查equality_repository 、 child_repository
        if ($config['repository_type'] != 'independent') {
            // 3.2.1如果不是独立模块，则不能有此个参数
            if (isset($config['equality_repository']) ||
                isset($config['child_repository'])
            ) {
                $this->error('！！！' . $databaseName . '这个database_repository不是独立模块，不能再设置equality_repository 、 child_repository 这两项参数');
                exit;
            }
        } else {
            // 3.2.2如果是独立模块，则需要一一验证相应的数据

            // 3.2.2.1 验证 equality_repository 中的数据是否存在相应的database_repository
            if (!isset($config['equality_repository']) || !array_value_exists($config['equality_repository'], array_keys($this->config['database_repository']))) {
                $this->error('！！！发现' . $databaseName . '这个database_repository独立模块中的equality_repository 属性异常，请检查相应的database_repository是否存在！！！');
                exit;
            }

            // 3.2.2.2 验证 equality_repository 中在相应的database_repository 的repository_type 是否正确
            foreach ($config['equality_repository'] as $item) {
                if (empty($this->config['database_repository'][$item]['repository_type']) || $this->config['database_repository'][$item]['repository_type'] != 'equality_dependent') {
                    $this->error('！！！在' . $databaseName . '这个database_repository独立模块中的equality_repository 属性检查中，发现' . $item . '这个database_repository并不是一个equality_dependent类型');
                    exit;
                }
            }

            // 3.2.2.3  验证 child_repository 中的数据是否存在相应的database_repository
            if (!isset($config['child_repository']) || !array_value_exists($config['child_repository'], array_keys($this->config['database_repository']))) {
                $this->error('！！！发现' . $databaseName . '这个database_repository独立模块中的child_repository 属性异常，请检查相应的database_repository是否存在！！！');
                exit;
            }

            // 3.2.2.4 验证 child_repository 中在相应的database_repository 的repository_type 是否正确
            foreach ($config['child_repository'] as $item) {
                if (empty($this->config['database_repository'][$item]['repository_type']) || $this->config['database_repository'][$item]['repository_type'] != 'child_dependent') {
                    $this->error('！！！在' . $databaseName . '这个database_repository独立模块中的child_repository 属性检查中，发现' . $item . '这个database_repository并不是一个child_dependent类型');
                    exit;
                }
            }
        }
    }


    /**
     *  检查一个database_repository的索引配置信息
     *          1. 检查 这个database_repository 中 primary_key
     *          2. 检查 这个database_repository 中 string_key
     *          3. 检查 这个database_repository 中 list_key
     * @param $databaseName
     * @param $config
     * @author chenjiawen
     */
    protected function checkOneDatabaseRepositoryKeyInfo($databaseName, $config)
    {
        $databaseInfo = DB::select('describe ' . $databaseName);

        $fields = [];
        $primaryKey = '';

        foreach ($databaseInfo as $item) {
            $fields[] = $item->Field;

            if ($item->Key == 'PRI')
                $primaryKey = $item->Field;
        }
        // 1. 首先判断对应的键是否存在
        if (!isset($config['primary_key']) || !isset($config['string_key']) || !isset($config['list_key']) || !isset($config['list_page_key'])) {
            $this->error('！！！发现' . $databaseName . '这个database_repository 这个模块的索引配置异常，请检查primary_key、string_key、list_key 和 list_page_key 是否都设置了！！！');
            exit;
        }

        // 2. 首先判断主键是否配对
        if ($config['primary_key'] != $primaryKey) {
            $this->error('！！！在' . $databaseName . '这个database_repository 的 primary_key 属性检查中，发现它与数据库属性不对');
            exit;
        }

        // 3.检查 string_key 唯一索引
        foreach ($config['string_key'] as $item) {
            if (!array_value_exists($item, $fields)) {
                $this->error('！！！在' . $databaseName . '这个database_repository 的 string_key 属性检查中，发现' . json_encode($item) . '索引属性与数据库字段不对应！！！');
                exit;
            }
        }

        // 4.检查 list_key 列表索引
        foreach ($config['list_key'] as $item) {
            if (!array_value_exists($item, $fields)) {
                $this->error('！！！在' . $databaseName . '这个database_repository 的 list_key 属性检查中，发现' . json_encode($item) . '索引属性与数据库字段不对应！！！');
                exit;
            }
        }

        // 5. 检查 list_page_key 列表索引
        foreach ($config['list_page_key'] as $item) {
            if (!array_value_exists($item, $fields)) {
                $this->error('！！！在' . $databaseName . '这个database_repository 的 list_page_key 属性检查中，发现' . json_encode($item) . '索引属性与数据库字段不对应！！！');
                exit;
            }
        }
    }
}