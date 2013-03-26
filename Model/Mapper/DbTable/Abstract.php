<?php
/**
 * Abstract.php
 *
 * @category   Core
 * @package    Model
 * @subpackage DbTable_Mapper
 * @copyright  (c) Copyright 2010 Nigerian DJs. (http://www.nigeriandjs.com) All Rights Reserved.
 */
abstract class Ooba_Model_Mapper_DbTable_Abstract extends Ooba_Model_Mapper_Abstract
{
    /**
     * Hold current row
     *
     * @var Zend_Db_Table_Row_Abstract
     */
    protected $_currentRow = null;

    /**
     * Hold Supports fetching multiple objects
     *
     * @var boolean
     */
    protected $_supportsMulti = true;

    /**
     * Mappable
     *
     * @var string
     */
    protected $_mappable;

    /**
     * Table 
     *
     * @var string
     */
    protected $_dbTable;
    
    /**
     * Table Object singleton instance
     *
     * @var string
     */
    protected static $_dbTableObj;
    
    /**
     * Singleton instances of Mappers by Class 
     *
     * @var string
     */
    private static $_instances;

    /**
     * Factory generates singleton instances of mappers
     *
     * @param  string $class Class to instantiate
     * @return Core_Model_Mapper_DbTable
     */
    public static function factory($class)
    {
        if (isset(self::$_instances[$class]) === false or (self::$_instances[$class] instanceof $class) === false) {
            self::$_instances[$class] = new $class;
            self::$_instances[$class]->_setType('DbTable');
        }
        
        return self::$_instances[$class];
    }
    
    /**
     * Constructor
     *
     * @param string|Core_Model_Mappable_DbTable $mappable (Optional) The mappable object
     *                                                                or class name
     * @param string|Zend_Db_Table_Abstract      $dbTable  (Optional) The dbTable object
     *                                                                or class name
     */
    public function __construct($mappable = null, $dbTable = null)
    {
        if (is_null($dbTable) === false) {
            $this->_dbTable = $dbTable;
        }
        
        if (is_null($mappable) === false) {
            $this->_mappable = $mappable;
        }
        
        $this->_setType('DbTable');
    }

    /**
     * Get current row, Set the current row if not already set, return null otherwise
     *
     * @param  Core_Model_Abstract $mappable Get Row for mappable
     * @throws Zend_Exception Mappable must match that defined for mapper
     * @return mixed 
     */
    protected function _getCurrentRow(Core_Model_Abstract $mappable)
    {
        if (($mappable instanceof $this->_mappable) === false) {
            throw new Zend_Exception('Wrong mappable type for this mapper,expected ' .
                                     $this->_mappable . ' got:' . get_class($mappable));
        }
        
        if (is_null($mappable->getPrimaryKeyValue()) === true) {
                return null;
        } else if (is_null($this->_currentRow) === false) {
            $map   = $mappable->getMap();
            $pkCol = $map[$mappable->getPrimaryKey()];
            unset($map);
            if ($this->_currentRow->$pkCol === (string) $mappable->getPrimaryKeyValue()) {
                unset($pkCol);
                return $this->_currentRow;
            }
        }

        $table  = $this->getDbTableObj();
        $result = $table->find($mappable->getPrimaryKeyValue());
        unset($table);
        
        if (count($result) === 0) {
            return null;
        }
        
        $this->_currentRow = $result->current();
        unset($result);
        return $this->_currentRow;
    }


    /**
     * Empty delete method.
     *
     * @param  Core_Model_Abstract          $mappable Mappable
     * @param  Core_Model_Abstract          $obj      Related object needing updating
     * @param  Core_Model_Relation_Abstract $relation Relation to process
     * @return void
     */
    protected function _onDeleteRelation(Core_Model_Abstract $mappable,
                                         Core_Model_Abstract $obj,
                                         Core_Model_Relation_Abstract $relation)
    {

    }
    
    /** 
     * Function to run on a record addition
     * 
     * @param  Core_Model_Abstract          $mappable Mappable
     * @param  Core_Model_Abstract          $obj      Related object needing updating
     * @param  Core_Model_Relation_Abstract $relation Relation to process 
     * @return void  
     */
    protected function _onInsertRelation(Core_Model_Abstract $mappable,
                                         Core_Model_Abstract $obj,
                                         Core_Model_Relation_Abstract $relation)
    {

    }

    /**
     * Empty delete method.
     *
     * @param  Core_Model_Abstract $obj Related object needing updating
     * @return void
     */
    protected function _onDeleteRecord(Core_Model_Abstract $obj)
    {

    }

    /**
     * Empty insert method.
     *
     * @param  Core_Model_Abstract $obj Related object needing updating
     * @return void
     */
    protected function _onInsertRecord(Core_Model_Abstract $obj)
    {

    }
    
    /**
     * Check validity of $mappable
     *
     * @return boolean
     */
    protected function _validateMap()
    {
        $mappable = new $this->_mappable;
        $map      = $mappable->getMap();
        
        unset($mappable);
        return $this->_validateMappableMap($map);
    }
    
    /**
     * Validate map
     *
     * @param  array $map Map
     * @throws Zend_Exception Throw error if map is not set
     * @return boolean
     */
    protected function _validateMappableMap(array $map)
    {
        if (is_null($map) === true) {
                throw new Zend_Exception('Madel does not have valid map for DbTable Mapper');
        }
        
        // Needs implementation
        return true;
    }
    
    /**
     * Set CurrentRow
     *
     * @param  Core_Model_Abstract            $mappable Mappable
     * @param  null|Zend_Db_Table_Row_Absract $row      Row to set
     * @throws Zend_Exception Mappable must match mapper
     * @return Core_Model_Mapper_Abstract
     */
    public function setCurrentRow(Core_Model_Abstract $mappable, $row)
    {
        if (($mappable instanceof $this->_mappable) === false ) {
            throw new Zend_Exception('wrong mappable type for this mapper');
        }
        
        $this->_currentRow = $row;
        return $this;
    }
    
    /**
     * Return the dbTable Class
     *
     * @return null|string
     */
    public function getDbTable()
    {
        return $this->_dbTable;
    }
    
    /**
     * Set the dbTable object
     *
     * @param  string|Zend_Db_Table_Absract $dbTable Table to set
     * @throws Core_Model_Mapper_Exception_InvalidMappable Throw error if object is not Db_Table
     * @return null|Core_Model_Mapper_Abstract
     */
    public function setDbTable($dbTable)
    {
        if (is_string($dbTable) === true) {
            $dbTable = new $dbTable;
        }

        if (($dbTable instanceOf Zend_Db_Table_Abstract) === false) {
            throw new Core_Model_Mapper_Exception_InvalidMappable(
                'DbTable object must be an instance of Zend_Db_Table_Abstract');
        }

        $this->_dbTable = $dbTable;
        return $this;
    }
    
    /**
     * Return the Mappable
     *
     * @return null|Core_Model_Abstract
     */
    public function getMappable()
    {
        return $this->_mappable;
    }
    
    /**
     * Set Mappable
     *
     * @param  string|Core_Model_Abstract $mappable Mappable
     * @throws Core_Model_Mapper_Exception_InvalidMappable Throw error if Model is not mappable
     * @return null|Core_Model_Mapper_Abstract
     */
    public function setMappable($mappable)
    {
        if (is_string($mappable) === true) {
            $mappable = new $mappable;
        }

        if (($mappable instanceOf Core_Model_Mappable_Interface) === false) {
            throw new Core_Model_Mapper_Exception_InvalidMappable(
                'Mappable object must be an instance of Core_Model_Mappable_Interface');
        }

        $this->_mappable = $mappable;
        $this->_mappable->addMapper($this);
        return $this;
    }

    /**
     * Return the dbTable object
     *
     * @return null|Zend_Db_Table_Abstract
     */
    public function getDbTableObj()
    {
        if (is_object($this->_dbTable) === false) {
            if (isset(self::$_dbTableObj[get_class($this)]) === false) {
                self::$_dbTableObj[get_class($this)] = new $this->_dbTable;
            }
            
            return self::$_dbTableObj[get_class($this)];
        } else {
            return $this->_dbTable;
        }
        
    }
    
    /**
     * Clone the model, but do not copy related data
     *
     * @return void
     */
    public function __clone()
    {
        $this->_currentRow = null;
        $this->dbTableObj  = null;
    }
    
    /**
     * Sleep the model, save space
     *
     * @return array
     */
    public function __sleep()
    {
        return array('_currentRow', '_dbTable', '_mappable');
    }
}
