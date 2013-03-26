<?php
/**
 * Ooba_Model_Storage_Results_Mongo_Paginator_Adapter
 *
 * @category   Ooba
 * @package    Ooba_Model
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Model_Storage_Results_Mongo_Paginator_Adapter implements Zend_Paginator_Adapter_Interface
{
    /**
     * Results to paginate
     *
     * @var Ooba_Model_Storage_Results_Mongo
     */
    protected $_results;

    /**
     * Construct
     *
     * @param Ooba_Model_Storage_Results_Mongo $results Result set to paginate
     */
    public function __construct(Ooba_Model_Storage_Results_Mongo $results)
    {
        $this->_results = $results;
    }

    /**
     * Get limited items based on previous results queries
     *
     * @param  integer $offset           Offset
     * @param  integer $itemCountPerPage Limit
     * @return Ooba_Model_Storage_Results_Mongo Limited Results
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $results = $this->_results->getStorageResults();
        $info    = $results->info();

        $skip  = ($info['skip'] + $offset);
        $limit = $itemCountPerPage;
        $limit = min(($this->count() - $offset), $limit);

        if ($info['skip'] === 0) {
            $results->skip($offset);
        }

        $results->limit($limit);

        return new Ooba_Model_Storage_Results_Mongo($this->_results->getModelClass(), $results);
    }

    /**
     * Get count from original set
     *
     * @return integer
     */
    public function count()
    {
        $results = $this->_results->getStorageResults();
        $info    = $results->info();
        return ($results->count(true) + $info['skip']);
    }
}
