<?php
/**
 * Ooba_Storage_Mongo_Query
 *
 * @category   Ooba
 * @package    Storage
 * @subpackage Mongo
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Storage_Mongo_Query
{
    /**
     * List of predicates to be supplied to Mongo in required order
     *
     * @var array
     */
    static protected $_predicates = array('find', 'findOne', 'limit', 'skip', 'sort');
    
    /**
     * Data for ordering query
     *
     * @var array
     */
    protected $_sort;
    
    /**
     * Data for limiting query
     *
     * @var string
     */
    protected $_limit;
    
    /**
     * Data for offseting/skip query
     *
     * @var string
     */
    protected $_skip;
    
    /**
     * Array of where elements
     *
     * @var array
     */
    protected $_find;
    
    /**
     * Array of where elements
     *
     * @var array
     */
    protected $_findOne;
    
    /**
     * Array fields
     *
     * @var array
     */
    protected $_fields;
    
    /**
     * Set order data
     *
     * @param  array $sort Sort order data
     * @return void
     */
    public function setSort(array $sort)
    {
        $this->_sort = $sort;
    }
    
    /**
     * Gets order data
     *
     * @return array
     */
    public function getSort()
    {
        return $this->_sort;
    }
    
    /**
     * Set limit data
     *
     * @param  string $limit Limit data
     * @return void
     */
    public function setLimit($limit)
    {
        $this->_limit = $limit;
    }
    
    /**
     * Gets limit data
     *
     * @return string
     */
    public function getLimit()
    {
        return $this->_limit;
    }
    
    /**
     * Set skip data
     *
     * @param  string $skip Skip data
     * @return void
     */
    public function setSkip($skip)
    {
        $this->_skip = $skip;
    }
    
    /**
     * Gets skip data
     *
     * @return string
     */
    public function getSkip()
    {
        return $this->_skip;
    }
    
    /**
     * Sets the where array
     *
     * @param  array $find Mongo-specific array structure
     * @return void
     */
    public function setFind(array $find)
    {
        $this->_find = $find;
    }
    
    /**
     * Gets the array of conditions to pass to the find() method
     *
     * @return array
     */
    public function getFind()
    {
        return $this->_find;
    }
    
    /**
     * Sets the where array
     *
     * @param  string|array $findOne Mongo-specific array structure
     * @return void
     */
    public function setFindOne($findOne)
    {
        $this->_findOne = $findOne;
    }
    
    /**
     * Gets the array of conditions to pass to the find() method
     *
     * @return array
     */
    public function getFindOne()
    {
        return $this->_findOne;
    }
    
    /**
     * Sets the where array
     *
     * @param  array $fields Set fields to fetch
     * @return void
     */
    public function setFields(array $fields)
    {
        $this->_fields = $fields;
    }
    
    /**
     * Gets the array of conditions to pass to the find() method
     *
     * @return array
     */
    public function getFields()
    {
        return $this->_fields;
    }
    
    /**
     * Magic function for getting values.  Convenience method.
     *
     * @param  string $propertyName Property to get
     * @throws Ooba_Storage_Mongo_Exception_InvalidValue Unknown property
     * @return mixed
     */
    public function __get($propertyName)
    {
        $method = 'get' . ucwords($propertyName);
        if (method_exists($this, $method) === true) {
            return $this->$method();
        }
        
        throw new Ooba_Storage_Mongo_Exception_InvalidValue($propertyName . ' does not exist');
    }
    
    /**
     *  Get all predicates as associative array in order required by mongo
     *
     * @return array
     */
    public function getPredicates()
    {
        $return = array();
        foreach (self::$_predicates as $predicate) {
            if (is_null($this->$predicate) === false) {
                $return[$predicate] = $this->$predicate;
            }
        }
        
        return $return;
    }
}
