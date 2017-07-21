
    /**
     * get one page of record collection
     * @param $where
     * @param $offset
     * @param $pageNum
     * @param null $field
     * @return mixed
     */
    public function pageOfField($where, $offset, $pageNum, $field = null)
    {
        return parent::page($where, $offset, $pageNum, $field);
    }