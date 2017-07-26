<?php

namespace CjwRepository\Src\RepositoryLibrary;

/**
 * Trait AllOrAllPageStoreTrait
 * @package CjwRepository\Src\RepositoryLibrary
 */
trait AllOrAllPageStoreTrait
{
    /**
     * 获取独立 database repository 对应的 基本 model 模型名称
     * @return string
     */
    protected function getBasicModelName()
    {
        return convertUnderline($this->repository_name, false) . 'Model';
    }

    /**
     * 获取独立 database repository 的主键方法名称
     * @return string
     */
    protected function getPrimaryKeyMethodName()
    {
        return 'findBy' . convertUnderline($this->primary_key);
    }

    /**
     * 对 database repository 进行检表
     * @return bool
     */
    public function all()
    {
        $basicModelName = $this->getBasicModelName();
        $primaryKeyMethodName = $this->getPrimaryKeyMethodName();
        $primaryKey = $this->primary_key;
        $result = $this->$basicModelName->all([$primaryKey]);

        if (empty($result)) return false;

        foreach ($result as $key => $item) {

            $result[$key] = $this->$primaryKeyMethodName([$primaryKey => $item->$primaryKey]);
        }
        return $result;
    }

    /**
     * 对 database repository 全库进行分页
     * @param $offset
     * @param $pageNum
     * @return bool
     */
    public function pageOfAll($offset, $pageNum)
    {
        $basicModelName = $this->getBasicModelName();
        $primaryKeyMethodName = $this->getPrimaryKeyMethodName();
        $primaryKey = $this->primary_key;
        $result = $this->$basicModelName->pageOfAll($offset, $pageNum, [$primaryKey]);

        if (empty($result)) return false;

        foreach ($result as $key => $item) {

            $result[$key] = $this->$primaryKeyMethodName([$primaryKey => $item->$primaryKey]);
        }
        return $result;
    }
}