<?php

/**
 * Ooba_Model_Mapper_Abstract
 *
 * @category   Ooba
 * @package    Model
 * @subpackage Mapper
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
abstract class Ooba_Model_Mapper_Abstract
{
    /**
     * Relation type dependent
     *
     * Another table is dependent on my primary key
     *
     */
    const DEPENDENT = 'dependent';
    
    /**
     * Relation type dependent_onetoone
     *
     * Another table is dependent on my primary key
     *
     */
    const DEPENDENT_ONETOONE = 'dependent_onetoone';
    
    /**
     * Relation type reference
     *
     * A reference to another table's primary key
     *
     */
    const REFERENCE = 'reference';
    
    /**
     * Relation type manytomany
     *
     * A join table has my primary key
     * and primary keys to other tables
     * that I join to
     *
     */
    const MANYTOMANY = 'manytomany';
    
    /**
     * Mapper type
     *
     * @var string
     */
    private $_mapperType = null;

    /**
     * Flag whether mapper supports fetching an array of mappables
     *
     * @var boolean
     */
    protected $_supportsMulti = false;

    /**
     * Return whether mapper supports fetching multi
     *
     * @return boolean
     */
    public function supportsMulti()
    {
        return $this->_supportsMulti;
    }

    /**
     * Return the mapper type
     *
     * @return string
     */
    public function getType()
    {
        return $this->_mapperType;
    }

    /**
     * Set the mapper type
     *
     * @param  string $type The mapper type
     * @return void
     */
    protected function _setType($type)
    {
        $this->_mapperType = $type;
    }

    /**
     * Save Mappable to Data source
     *
     * @param  Core_Model_Abstract $mappable
     * @return void
     */
    abstract public function save(Core_Model_Abstract $mappable);

    /**
     * Find a Mappable object by id
     *
     * @param  Core_Model_Abstract $mappable
     * @param  mixed: $id  - The primary key to find
     * @return Core_Model_Mappable
     */
    abstract public function find(Core_Model_Abstract $mappable, $id);

    /**
     * Delete Mappable from Data source
     *
     * @param  Core_Model_Abstract $mappable
     * @return void
     */
    abstract public function delete(Core_Model_Abstract $mappable);

    /**
     * Fetch all mappable objects
     *
     * @return array
     */
    abstract public function fetchAll();
}