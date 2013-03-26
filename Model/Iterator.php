<?php
/**
 * Ooba_Model_Iterator
 *
 * @category   Ooba
 * @package    Model
 * @copyright (c) Copyright 2010-2012, Michael Faletti. All Rights Reserved.
 */
class Ooba_Model_Iterator implements Iterator, ArrayAccess, Countable
{
    /**
     * Position
     *
     * @var integer
     */
    protected $_position;

    /**
     * Data Storage
     *
     * @var array
     */
    protected $_warehouse;

    /**
     * Class Type
     *
     * @var string
     */
    protected $_classType;

    /**
     * Flag if you want loaded objects or uuids
     *
     * @var boolean
     */
    protected $_loaded;

    /**
     * Object loader
     *
     * @var Ooba_Loader
     */
    protected $_loader = null;

    /**
     * Namespaces
     *
     * @var array
     */
    protected $_namespaces;

    /**
     * No Exceptions In Get Item Flag
     *
     * @var boolean
     */
    protected $_noExceptionsFlag = false;

    /**
     * Default Namespaces
     *
     * @var object
     */
    static protected $_defaultNamespaces;

    /**
     * Uuid Path
     *
     * @var string
     */
    protected $uuidPath = '_uuid';

    /**
     * Construct
     *
     * @param array  $tempArray  Array of Ooba_Model uuids or objects
     * @param string $modelName  Name of Class Models to instantiate
     * @param array  $namespaces (Optional) Namespaces that class exist in.
     */
    public function __construct(array $tempArray, $modelName, array $namespaces = array())
    {
        $this->_position   = 0;
        $this->_warehouse  = array_values($tempArray);
        $this->_classType  = $modelName;
        $this->_namespaces = $namespaces;
        if (count($namespaces) === 0) {
            $namespaces = self::$_defaultNamespaces;
        }

        if (count($namespaces) > 0) {
            $this->_loader = new Ooba_Loader($namespaces);
        }
    }

     /**
      *  Returns  the current object loaded
      *
      * @return Ooba_Model
      */
    public function current()
    {
        return $this->_getItem($this->_position);
    }

    /**
     * Get warehouse item
     *
     * @param  string $position Array key of warehouse to return
     * @throws Exception If it is not correct type of object
     * @return Ooba_Model
     */
    protected function _getItem($position)
    {
        // Check to make sure the loader exists and that the object to be loaded is not loaded
        if ((is_null($this->_loader) === false) AND (is_object($this->_warehouse[$position]) === false)) {
            $object = $this->_loader->factory($this->_classType)->load($this->_warehouse[$position]);
        } else if (is_object($this->_warehouse[$position]) === false) {
            $class  = $this->_classType;
            $object = new $class;
            $object = $object->load($this->_warehouse[$position]);
        } else {
             $object = $this->_warehouse[$position];
        }

        if (is_object($object) === false AND $this->_noExceptionsFlag === false) {
            $msg = 'Unable to load model ' . var_export($this->_warehouse[$position], true)
                    . 'Object is not an instance of: ' . $this->_classType;
            throw new Exception($msg);
        }

        return $object;
    }

     /**
      *  Returns the Current Position
      *
      * @return integer
      */
    public function key()
    {
        return $this->_position;
    }

     /**
      *  Increments the position of the iterator
      *
      * @return void
      */
    public function next()
    {
        ++$this->_position;
    }

     /**
      *  Rewinds the iterator back to original position
      *
      * @return void
      */
    public function rewind()
    {
        $this->_position = 0;
    }

     /**
      *  Checks if the current position has a valid element in the warehouse
      *
      * @return boolean
      */
    public function valid()
    {
        return isset($this->_warehouse[$this->_position]);
    }

    /**
     * Remove from warehouse
     *
     * @param  integer $index Position to remove
     * @throws Exception If index is out of bounds
     * @return void
     */
    public function remove($index)
    {
        if (count($this->_warehouse) > $index) {
            unset($this->_warehouse[$index]);
            $this->_warehouse = array_values($this->_warehouse);
        } else {
            throw new Exception('Out of Bounds');
        }
    }

    /**
     *  Add element to array
     *
     * @param  mixed $temp Object to add to array
     * @throws Exception If it is not correct type of object
     * @return void
     */
    public function add($temp)
    {
        if ((is_object($temp) === true) and (mb_eregi($this->_classType, get_class($temp)) === 1)) {
            $this->_warehouse[] = $temp;
        } else if (is_string($temp) === true) {
            $this->_warehouse[] = $temp;
        } else {
            throw new Exception('The object added is  ' .  get_class($temp) . ' and is not a ' . $this->_classType);
        }

    }

    /**
     *  Add element to beginning of array
     *
     * @param  mixed $temp Object to add to array
     * @throws Exception If it is not correct type of object
     * @return void
     */
    public function unshift($temp)
    {
        if ((is_object($temp) === true) and (mb_eregi($this->_classType, get_class($temp)) === 1)) {
            array_unshift($this->_warehouse, $temp);
        } else if (is_string($temp) === true) {
            array_unshift($this->_warehouse, $temp);
        } else {
            throw new Exception('The object added is  ' .  get_class($temp) . ' and is not a ' . $this->_classType);
        }
    }

    /**
     *  Count
     *
     * @return integer
     */
    public function count()
    {
        return count($this->_warehouse);
    }

    /**
     * Return first array item
     *
     * @return mixed, false if warehouse is empty
     */
    public function first()
    {
        if (empty($this->_warehouse) === true) {
            return false;
        }

        return $this->_getItem(0);
    }

    /**
     * Return last array item
     *
     * @return mixed, false if warehouse is empty
     */
    public function last()
    {
        if (empty($this->_warehouse) === true) {
            return false;
        }

        return $this->_getItem(($this->count() - 1));
    }

    /**
     *  Offset Set
     *
     * @param  integer $offset Offset
     * @param  mixed   $value  Value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset) === true) {
            $this->add($value);
        } else {
            $this->_warehouse[$offset] = $value;
        }
    }

    /**
     *  Offset Exists
     *
     * @param  integer $offset Offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->_warehouse[$offset]);
    }

    /**
     *  Offset Unset
     *
     * @param  integer $offset Offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     *  Offset Get
     *
     * @param  integer $offset Offset
     * @return  mixed
     */
    public function offsetGet($offset)
    {
        if (isset($this->_warehouse[$offset]) === true) {
            return $this->_warehouse[$offset];
        } else {
            return null;
        }
    }

    /**
     * Return obj in json
     *
     * @return string
     */
    public function toJson()
    {
        $encoded = array();

        foreach ($this->_warehouse as $item) {
            if (is_object($item) === true) {
                $encoded[] = $item->uuid;
            } else {
                $encoded[] = $item;
            }
        }

        return Zend_Json::encode($encoded);
    }

    /**
     * Get warehouse item uuids
     *
     * @throws Ooba_Model_Iterator_Exception_InvalidWarehouseData Invalid warehouse data
     * @return array
     */
    public function getUuids()
    {
        $uuids = array();
        foreach ($this->_warehouse as $key => $item) {
            if (($item instanceof Ooba_Model_Abstract) === true) {
                $uuids[$key] = $item->uuid;
            } else if (is_string($item) === true) {
                $uuids[$key] = $item;
            } else {
                throw new Ooba_Model_Iterator_Exception_InvalidWarehouseData('Data must be a uuid string or '
                . ' instance of Ooba_Model_Abstract');
            }
        }

        return $uuids;
    }

    /**
     * Clear the warehouse
     *
     * @return void
     */
    public function clear()
    {
        $this->_warehouse = array();
    }

    /**
     * Set the uuids in the warehouse
     *
     * @param  array $uuids Uuids
     * @return void
     */
    public function setUuids(array $uuids)
    {
        foreach ($uuids as $key => $uuid) {
            if (is_string($uuid) === false) {
                unset($uuids[$key]);
            }
        }

        $this->_warehouse = array_values($uuids);
    }

    /**
     * This method will shuffle or radomize the order of the items stored in the
     * warehouse.
     *
     * @return void
     */
    public function shuffleWarehouse()
    {
        if (is_array($this->_warehouse) === true) {
            shuffle($this->_warehouse);
        }
    }

    /**
     * Get the class type
     *
     * @return string
     */
    public function getClassType()
    {
        return $this->_classType;
    }

    /**
     * Set Default Namespaces
     *
     * @param  mixed $namespaces Namespaces
     * @return void
     */
    public static function setDefaultNamespaces($namespaces)
    {
        self::$_defaultNamespaces = $namespaces;
    }

    /**
     * Get Default Namespaces
     *
     * @return array
     */
    public static function getDefaultNamespaces()
    {
        return self::$_defaultNamespaces;
    }

    /**
     * Check if iterator contains an item
     *
     * @param  mixed $item Item to check for
     * @return boolean
     */
    public function contains($item)
    {
        if (($item instanceof Ooba_Model_Abstract) === true) {
            $item = $item->uuid;
        }

        foreach ($this->_warehouse as $entry) {
            if (($entry instanceof Ooba_Model_Abstract) === true) {
                $entry = $entry->uuid;
            }

            if ($entry === $item) {
                return true;
            }
        }

        return false;
    }

    /**
     * Throws NO EXCEPTIONS
     *
     * @param  boolean $booler Boolean Value
     * @return void
     */
    public function noExceptions($booler)
    {
        $this->_noExceptionsFlag = $booler;
    }

    /**
     * Get object namespace
     *
     * @return string
     */
    public function getNameSpace()
    {
        $object = $this->_loader->factory($this->_classType);

        return $object->getNameSpace();
    }
}