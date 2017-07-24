<?php

namespace CjwRepository\Src\ModelLibrary;

use Illuminate\Support\Facades\DB;

/**
 * general model
 * Class BaseModel
 * @package CjwRepository\Src\ModelLibrary
 */
abstract class BaseModel
{
    /**
     *  database name
     * @var
     */
    protected $table;

    /**
     *  database callback
     * @var \Closure
     */
    protected $callback;


    /**
     * BaseModel constructor.
     */
    function __construct()
    {
        $this->callback = function ($db) {
            return $db;
        };
    }

    /**
     * create one record
     * @param $create
     */
    public function create($create)
    {
        return DB::table($this->table)->insert($create);
    }

    /**
     * update one record
     * @param $where
     * @param $update
     * @return mixed
     */
    public function update($where, $update)
    {
        return DB::table($this->table)->where($where)->update($update);
    }

    /**
     * get all record
     * @param null $field
     * @param null $callback
     * @return mixed
     */
    public function all($field = null, $callback = null)
    {
        $callback = empty($callback) ? $this->callback : $callback;

        return $callback(DB::table($this->table))->get($field);
    }

    /**
     * get one page of all record
     * @param $offset
     * @param $pageNum
     * @param null $field
     * @param null $callback
     * @return mixed
     */
    public function pageOfAll($offset, $pageNum, $field = null, $callback = null)
    {
        $callback = empty($callback) ? $this->callback : $callback;

        return $callback(DB::table($this->table))->skip($offset)->take($pageNum)->get($field);
    }

    /**
     *  find one record
     * @param $where
     * @param null $field
     * @param null $callback
     * @return mixed
     */
    protected function first($where, $field = null, $callback = null)
    {
        $callback = empty($callback) ? $this->callback : $callback;

        return $callback(DB::table($this->table)->where($where))->first($field);
    }


    /**
     * get record collection
     * @param $where
     * @param null $field
     * @param null $callback
     * @return mixed
     */
    protected function get($where, $field = null, $callback = null)
    {
        $callback = empty($callback) ? $this->callback : $callback;

        return $callback(DB::table($this->table)->where($where))->get($field);
    }

    /**
     * get total
     * @param $where
     * @param null $callback
     * @return mixed
     */
    protected function count($where, $callback = null)
    {
        $callback = empty($callback) ? $this->callback : $callback;

        return $callback(DB::table($this->table)->where($where))->count();
    }

    /**
     * get one page of record collection
     * @param $where
     * @param $offset
     * @param $pageNum
     * @param null $field
     * @param null $callback
     * @return mixed
     */
    protected function page($where, $offset, $pageNum, $field = null, $callback = null)
    {
        $callback = empty($callback) ? $this->callback : $callback;

        return $callback(DB::table($this->table)->where($where))->skip($offset)->take($pageNum)->get($field);
    }
}