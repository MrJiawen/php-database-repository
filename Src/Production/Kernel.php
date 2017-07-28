<?php

namespace CjwRepository\Src\Production;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** 自动化构建代码的核心类
 * Class Kernel
 * @package CjwRepository\Src\Production
 */
class Kernel
{
    // 命令对象
    public $command;

    // 配置文件原始信息
    public $configOrigin;

    // 数据仓库配置容器
    public $databaseRepositoryConfig;


    /**
     * Kernel constructor.
     * @param Command $command
     */
    public function __construct(Command $command)
    {
        // 1. 第一步 初始化数据
        $this->command = $command;
        $this->configOrigin = config('database_repository');

        // 2. 第二步 开始构造配置文件数据
        $this->command->comment('>>>        database_repository 开始构建配置文件');

        $this->configInitModel();

        $this->command->info('>>>        database_repository 配置文件构建完成');

        // 3. 开支开始制作模板
        $this->command->comment('>>>        database_repository 开始制作模板');

        new TemplateProduction($this);
    }

    /**
     *  构造配置文件数据
     * @author chenjiawen
     */
    public function configInitModel()
    {
        // 1. 构建数据
        $this->databaseRepositoryConfig = $this->configOrigin['database_repository'];


        // 2. 循环构建配置
        foreach ($this->databaseRepositoryConfig as $key => $item) {

            if ($item['repository_type'] != 'independent') {
                continue;
            }

            // 3. 首先构建同级、子级、和关联模块的数据仓库
            foreach (array_merge($item['equality_repository'], $item['child_repository']) as $childRepository) {
                $this->configInitOne($childRepository, $this->configOrigin['database_repository'][$childRepository], $key);
            }

            // 4. 然后构建自己（独立的 database_repository ）
            $this->configInitOne($key, $item, $key);
        }
    }

    /**
     *  配置一个 database_repository
     * @param $databaseName
     * @param $repository
     * @param $fatherRepositoryName
     * @author chenjiawen
     */
    protected function configInitOne($databaseName, $repository, $fatherRepositoryName)
    {
        $databaseInfo = DB::select('describe ' . $databaseName);
        $fields = [];
        $fieldsNotNull = [];
        foreach ($databaseInfo as $item) {
            $fields[] = $item->Field;
            if ($item->Null == 'NO')
                $fieldsNotNull[] = $item->Field;
        }

        // 1. 存储表字段
        $this->databaseRepositoryConfig[$databaseName]['database_fields'] = $fields;
        $this->databaseRepositoryConfig[$databaseName]['database_fields_not_null'] = $fieldsNotNull;

        // 2. 构建主键索引
        $this->databaseRepositoryConfig[$databaseName]['primary_key'] = [
            'field' => $repository['primary_key'],
        ];

        if ($databaseName == $fatherRepositoryName)
            $this->databaseRepositoryConfig[$databaseName]['primary_key']['hash_index'] = 'HASH:' . strtoupper($fatherRepositoryName) . ':';

        // 3. 构建唯一索引
        $this->databaseRepositoryConfig[$databaseName]['string_key'] = [];
        foreach ($repository['string_key'] as $key => $item) {

            // 按照数据的顺序排序
            $item = is_array($item) ? array_intersect($fields, $item) : $item;

            $this->databaseRepositoryConfig[$databaseName]['string_key'][$key] = [
                'field' => $item,
                'string_index' => 'STRING:' . strtoupper($fatherRepositoryName) . ':' . (is_array($item) ? strtoupper(implode(':', $item)) : strtoupper($item)) . ':',
            ];
        }

        // 4.构建列表索引
        $this->databaseRepositoryConfig[$databaseName]['list_key'] = [];
        foreach ($repository['list_key'] as $key => $item) {

            // 按照数据的顺序排序
            $item = is_array($item) ? array_intersect($fields, $item) : $item;

            $this->databaseRepositoryConfig[$databaseName]['list_key'][$key] = [
                'field' => $item,
                'list_index' => 'LIST:' . strtoupper($fatherRepositoryName) . ':' . (is_array($item) ? strtoupper(implode(':', $item)) : strtoupper($item)) . ':',
            ];
        }

        // 5. 构建分页列表索引
        $this->databaseRepositoryConfig[$databaseName]['list_page_key'] = [];
        foreach ($repository['list_page_key'] as $key => $item) {

            // 按照数据的顺序排序
            $item = is_array($item) ? array_intersect($fields, $item) : $item;

            $this->databaseRepositoryConfig[$databaseName]['list_page_key'][$key] = [
                'field' => $item,
                'list_page_index' => 'LIST:PAGE:' . strtoupper($fatherRepositoryName) . ':' . (is_array($item) ? strtoupper(implode(':', $item)) : strtoupper($item)) . ':',
            ];
        }

        // 6. 构建 model 的基本信息
        $this->databaseRepositoryConfig[$databaseName]['database_model_info'] = [
            'repository_name' => $databaseName,
            'namespace_load' => 'use App\Model\\' . convertUnderline($databaseName) . 'Model',
            'model_member_property' => convertUnderline($databaseName, false) . 'Model',
            'model_object' => convertUnderline($databaseName) . 'Model',
        ];
    }

    /**
     * 在构造store前 首先对索引进行检查
     * @author chenjiawen
     */
    public function configBeforeConstructStore()
    {
        // 为每个独立的database_repository 构造配置参数
        foreach ($this->databaseRepositoryConfig as $key => $item) {

            if ($item['repository_type'] != 'independent') {
                continue;
            }

            $this->configOneBeforeConstructStore($key, $item);
        }
    }

    /**
     * 在构造store前 首先对索引进行检查
     * @param $databaseName
     * @param $databaseRepository
     */
    public function configOneBeforeConstructStore($databaseName, $databaseRepository)
    {
        // 1. 为独立厂库 新建一个容器 contain
        $contain = [];

        // 2. 归并字段
        $contain['database_fields'] = $databaseRepository['database_fields'];
        $contain['database_fields_not_null'] = $databaseRepository['database_fields_not_null'];
        foreach (array_merge($databaseRepository['equality_repository'], $databaseRepository['child_repository']) as $item) {
            $contain['database_fields'] = array_unique(array_merge($contain['database_fields'], $this->databaseRepositoryConfig[$item]['database_fields']));
            $contain['database_fields_not_null'] = array_unique(array_merge($contain['database_fields_not_null'], $this->databaseRepositoryConfig[$item]['database_fields_not_null']));
        }

        // 3. 检查 string_key是否重复 并进行存储
        $contain['string_key'] = [];
        foreach (array_merge([$databaseName], $databaseRepository['equality_repository'], $databaseRepository['child_repository']) as $item) {
            $item = array_map(function ($value) use ($item) {
                $value['database_name'] = $item;
                return $value;
            }, $this->databaseRepositoryConfig[$item]['string_key']);
            $contain['string_key'] = array_merge($contain['string_key'], $item);
        }
        $string_key = array_pluck($contain['string_key'], 'string_index');
        if (count($string_key) != count(array_unique($string_key))) {
            $this->command->error('在构建 store 的配置文件时，发现在' . $databaseName . '这个独立的 database_repository中，string_index 索引出现冲突，请检查原始配置文件！！！');
            exit;
        }

        // 4. 检查 list_key 是否重复 并进行存储
        $contain['list_key'] = [];
        foreach (array_merge([$databaseName], $databaseRepository['equality_repository'], $databaseRepository['child_repository']) as $item) {
            $item = array_map(function ($value) use ($item) {
                $value['database_name'] = $item;
                return $value;
            }, $this->databaseRepositoryConfig[$item]['list_key']);
            $contain['list_key'] = array_merge($contain['list_key'], $item);
        }

        $list_key = array_pluck($contain['list_key'], 'list_index');
        if (count($list_key) != count(array_unique($list_key))) {
            $this->command->error('在构建 store 的配置文件时，发现在' . $databaseName . '这个独立的 database_repository中，list_index 索引出现冲突，请检查原始配置文件！！！');
            exit;
        }

        // 5. 检查 list_page_key 是否重复 并进行存储
        $contain['list_page_key'] = [];
        foreach (array_merge([$databaseName], $databaseRepository['equality_repository'], $databaseRepository['child_repository']) as $item) {
            $item = array_map(function ($value) use ($item) {
                $value['database_name'] = $item;
                return $value;
            }, $this->databaseRepositoryConfig[$item]['list_page_key']);
            $contain['list_page_key'] = array_merge($contain['list_page_key'], $item);
        }

        $list_page_key = array_pluck($contain['list_page_key'], 'list_page_index');
        if (count($list_page_key) != count(array_unique($list_page_key))) {
            $this->command->error('在构建 store 的配置文件时，发现在' . $databaseName . '这个独立的 database_repository中，list_page_index 索引出现冲突，请检查原始配置文件！！！');
            exit;
        }

        $this->databaseRepositoryConfig[$databaseName]['contain'] = $contain;
    }

}