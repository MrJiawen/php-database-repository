# php-database-repository 组件
```
composer require mr-jiawen/php-database-repository
```
## database repository 简介
它主要是帮助我们快速的生成数据仓库。在平时开发时候，不需花大量的精力去关心数据结构，把主要精力放在业务处理就好。

目前本组件正在开发之中，现在为v1.0 版本，主要完成的功能为代码构建基本功能。接下来，准备为整个数据仓库添加缓存，来健全数据库仓库的基本建设。

当然，此组件也可以为其添加热插件，来增添其功能，目前v1.0 添加热插件有：

* `Src\RepositoryLibrary\AllOrAllPageStoreTrait.php`: 让store数据仓库，获取所有的数据集合和对所有的数据进行分页处理。

## 使用手册：
### 第一步， 完成基本配置：
  1. 配置服务提供者：
  ```
    CjwRepository\RepositoryProvider::class
  ```
  2. 配置`artisan`命令行：
  ```
     \CjwRepository\Src\Commands\RepositoryModelCommand::class
  ```
  3. 执行命令，生成配置`config\database_repository.php`文件
  ```
    php artisan vendor:publish
  ```

### 第二步， 完成配置文件的配置：(完成此部分的配置)
```
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
```
其中需要注意的是：
1. `database_repository`（最外一层）：表示为整个database repository的基本配置信息；而与此层同级的配置信息，为缓存的全局配置信息(预留)。

2. `database_repository\database_name`(第二层): 它对应的是整个数据仓库的基础原子模块。换一句话说，一个`database_name` 对应一个数据表(必须与数据库的表名称对应)。

3. `database_name\repository_type`(第三层): 表示每个基础原子模块的类型，一共有三种类型
  * 独立的数据仓库`independent`(数据模型原始表): 此原子模块会独立的建立一个数据仓库，也就是会在对应的store中构建代码

  * 同级依赖数据仓库`equality_dependent`(数据模型一对一关系): 此原子模块不会建立独立厂库，它需要被某一个独立数据厂库给依赖，否则无法给这个原子模块新建一个model和把其数据结构融入到对应的独立模块中。
  * 子级依赖数据仓库`child_dependent`(数据模型一对一)： 此原子模块和`equality_dependent`类似，我们常常遇到一种情况就是，某原始数据信息表，会根据不同的数据类型具备不同的数据结构，但是他们又同属同一个模块，没有必要拆分为多个模块进行分别开发，则可以在底层进行数据结构整理。(其特性和`equality_dependent`一样)；
  * 补充：

  ```
    自动构建的代码的树形结构：
    - app/        #项目的app根目录
       - Model/       #model模型目录-> 每一个表一个模型
         - Cache/         #model 的缓存目录，对每个表的代码构建全部存放在此目录下(这里的代码是实时刷新的)
           - DatabaseCacheModel.php         
         - DatabaseModel.php    #model的入口文件(所有的自定义方法全部放在此处)
      - Store/         #store数据仓库目录，每个数据厂库存放处
        - Cache/          #repository的缓存目录，每个数据仓库自动构建的代码存放在此目录下(这里的代码是实时刷新额)
           - DatabaseCacheStore.php
        - DatabaseStore.php      #repository的入口文件(所有的自定义方法全部放在此处)
  ```
4. `database_name\equality_dependent`（第三层）: 设置他所依赖的同级原子模块。
  * 如果此原子模块是一个独立模块，则必须配置此项，如果没有同级依赖原子模型模块，则为其留空数组；
  * 如果此原子模块不是一个独立模块，则必须删除此项配置；

5. `database_name\child_dependent`（第三层）: 设置他所依赖的子级原子模块。
  * 如果此原子模块是一个独立模块，则必须配置此项，如果没有同级依赖原子模型模块，则为其留空数组；
  * 如果此原子模块不是一个独立模块，则必须删除此项配置；

6. `database_name\string_key`(第三层)： 设置其此原子模块的唯一索引。 
  * 必须是与对应的表字段对应。
  * 可以是单个字段形成的单一唯一索引；
  * 可以是多个字段形成个复合索引(多个字段给数组)；

7. `database_name\list_key`(第三层)： 设置其此原子模块的普通索引。
  * 同`database_name\string_key`；

8. `database_name\list_page_key`(第三层)： 设置其此原子模块的列表分页索引。
  * 同`database_name\string_key`；

## 常见的使用
1. 对列表进行排序，则我们可以对某个方法进行重写，如下所示：
```
public function getOfTel($where, $field = null)
{
    return parent::get($where, $field, function ($db) {
        return $db->orderBy('add_time', 'DSC');
    });
}
```
2. 遍历整个数据厂库，则可以进行引进 `Src\RepositoryLibrary\AllOrAllPageStoreTrait`;
```
class databaseStore  extends Cache\databaseCacheModel
{
      use AllOrAllPageStoreTrait;
}
```

## 后面将持续更新，尽情期待！！！
