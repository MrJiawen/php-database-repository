<?php

namespace App\Model\Cache;

use CjwRepository\Src\RepositoryLibrary\BaseModel;

/**
 * Class DatabaseCacheModel
 * @package App\Model
 */
abstract class DatabaseCacheModel extends BaseModel
{
    /**
     * table_name
     * @var string
     */
    protected $table = 'tableName';

    /**
     * primary_key
     * @var string
     */
    protected $primary_key = 'primaryKeyName';

}