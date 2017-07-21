
    /**
     * get record collection
     * @param $where
     * @param null $field
     * @return mixed
     */
    public function getOfField($where, $field = null)
    {
        return parent::get($where, $field);
    }