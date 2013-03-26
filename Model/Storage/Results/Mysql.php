<?php
/**
 * Ooba_Model_Storage_Results_Mysql.php
 *
 * @category   Ooba
 * @package    Ooba_Model_Storage
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */

class Ooba_Model_Storage_Results_Mysql extends Ooba_Model_Storage_Results_Abstract
{
	/**
	 * Data at current index object format
	 * 
	 * @var string
	 */
	private $_current;
	
	/**
     * Index
     *
     * @var integer
     */
    private $_index = null;
	
	/**
	 * Results from storage adapter
	 *
	 * @var Ooba_Model_Storage_Results_Abstract
	 */
	private $_storageResults;
	
	/**
     * Total items in results
     *
     * @var integer
     */
    private $_total;
    
    /**
     * Class of objects to build during iteration
     *
     * @var integer
     */
    private $_class;

	/**
     * Construct
     * 
     * @param mixed $class:	Model to laod data into
     * @param mixed $storageResults: Results from storageApi
     */
    public function __construct($class, $storageResults)
    {
        $this->_class          = $class;
        $this->_storageResults = $storageResults;  
     	$this->rewind();
    }

	/**
     * Get current Item
     *
     * @throws Ooba_Model_Storage_Results_Exception_OutOfBounds If not valid
     * @return Ooba_Model
     */
    public function current()
    {
        if (is_null($this->_index) or $this->_index >= $this->count()) {
            throw new Ooba_Model_Storage_Results_Exception_OutOfBounds("Index $this->_index");
        }
        
        if (is_null($this->_current) === true) {
            $data = $this->_storageResults->current();
            $this->_loodCurrentObject($data);
        }
        
        return $this->_current;
	}
	
	/**
     * Key
     *
     * @return integer
     */
    public function key()
    {
        return $this->_index;
    }

	/**
     * Next
     *
     * @return void
     */
    public function next()
    {
        $this->_storageResults->next();
        if (is_null($this->_index) === true ) {
            $this->_index = 0;
        } else {
            $this->_index++;
        }
        
        $this->_current = null;
    }

	/**
     * Rewind
     *
     * @return void
     */
    public function rewind()
    {
        $this->_index = 0;
        $this->_storageResults->rewind();
        $this->_current = null;
    }

	/**
     * Return true if current pointer is valid 
     *
     * @return boolean
     */
    public function valid()
    {
        if (is_null($this->_index) === true) {
            return false;
        }
        
        return (bool) ($this->_index < $this->count());
    }

	/**
     * Count
     *
     * @return integer
     */
    public function count()
    {
        if (is_null($this->_total) === false) {
            return $this->_total;
        }

        $this->_total = $this->_storageResults->count();
        return $this->_total;
    }

	/**
     * Takes data from storage API converts it for model and saves the new model as current
     *
     * @param  mixed $data Data to convert to Json from adapter
     * @return void
     */
    protected function _loodCurrentObject($data)
    {
        $obj  = new $this->_class;
        $data = Ooba_Model_Storage_Adapter_Mysql::prepareDataForModel($data);
        $obj->fromJson($data);
        $this->_current = $obj;
    }

	/**
     * Returns paginator adapter for factory method
     *
     * @return Ooba_Model_Storage_Results_Paginator_Adapter_Mysql
     */
    public function getPaginatorAdapter()
    {
        return new Ooba_Paginator_Adapter_Mysql($this);
    }

	/**
     * Get model class that is being populated
     *
     * @return string
     */
    public function getModelClass()
    {
        return $this->_class;
    }

	public function getStorageResults()
	{
		return $_storageResults;
	}
}
?>