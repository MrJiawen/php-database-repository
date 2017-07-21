
    /**
     * find one record
     * @param $where
     * @param null $field
     * @return mixed
     */
    public function findByField($where, $field = null)
    {
        return parent::first($where, $field);
    }