<?php
/**
 * Abstract.php
 *
 * @category   Ooba
 * @package    Model
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
abstract class Ooba_Model_Abstract implements Ooba_Model_Interface, Ooba_Model_Json_Interface
{
	/**
	 * Uuid
	 * @var string
	 */
	protected $_uuid;
	
	/**
     * Properties not to persist in database
     *
     * @var array
     */
    protected $_notPersistent = array('_notPersistent', '_store');

	/**
     * Default Storage
     *
     * @var Ooba_Model_Storage
     */
	static protected $_defaultStore;
	
	/**
     * Storage Engine
     *
     * @var Ooba_Model_Storage
     */
    protected $_store;

	/**
     * Loaded from
     *
     * @var string
     */
    protected $_loadedFrom;
	
	/**
     * Default namespace
     * @var string
     */
	static protected $_defaultNamespacePrefix = '';
	
	/**
     * Is model read only
     * @var boolean
     */
	protected $_readOnly = false;
	
	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		
	}
	
	/**
	 * The string to return when to object is treated as a string
	 * @return string
	 */
	public function __toString()
	{
		return $this->_uuid;
	}
	
	/**
     * Get Unique Id for Object
     *
     * @return string
     */
	public function getUuid()
	{
		return $this->_uuid;
	}
	
	/**
     * Set Unique Id for Object
     *
     * @param string $uuid: The uuid for the object
     */
	public function setUuid($uuid)
	{
		$this->_uuid = $uuid;
	}
	
	/**
     * Set readOnly
     *
     * @param  boolean $readOnly
     * @return void
     */
	public function setReadOnly($readOnly)
	{
		$this->_readOnly = (boolean) $readOnly;
	}
	
	/**
     * Get readOnly
     * @return boolean
     */
    public function getReadOnly()
    {
        return $this->_readOnly;
    }

	/**
     * Get loadedFrom
     * @return string
     */
    public function getLoadedFrom()
    {
        return $this->_loadedFrom;
    }

	/**
     * set loadedFrom
     *
     * @param  string $loadedFrom Loaded From
     * @return void
     */
    public function setLoadedFrom($loadedFrom)
    {
         $this->_loadedFrom = $loadedFrom;
    }

	/**
     * Get non persistent properties
     *
     * @return array
     */
    public function getNotPersistent()
    {
        return $this->_notPersistent;
    }

	/**
     * set properties not to persist in database
     *
     * @param  array $notPersistent The list of not persistent props
     * @return array
     */
    public function setNotPersistent(array $notPersistent)
    {
        return $this->_notPersistent = $notPersistent;
    }
	
	/**
     * Set Default Storage
     *
     * @param  mixed $store Storage
     * @return void
     */
    public static function setDefaultStorage($store)
    {
       self::$_defaultStore = $store;
    }
    
    /**
     * Get Default Storage
     *
     * @return Ooba_Model_Storage | null
     */
    public static function getDefaultStorage()
    {
        return self::$_defaultStore;
    }

	/**
     * Get Storage
     * Returns default storage engine if one has not been set
     *
     * @throws Ooba_Model_Exception_NoStorageSet If no storage has been set
     * @return Ooba_Model_Storage
     */
    public function getStore()
    {
        if (is_null($this->_store) === false) {
            return $this->_store;
        } else if (is_null(self::$_defaultStore) === true) {
                throw new Ooba_Model_Exception_NoStorageSet();
        } else {
            return self::$_defaultStore;
        }
    }

	/**
     * Set Storage
     *
     * @param  Ooba_Model_Storage | null $store Storage
     * @return void
     */
    public function setStore($store)
    {
        $this->_store = $store;
    }
	
	/**
     * NameSpace from base and prefix
     *
     * @return string
     */
    public function getNameSpace()
    {
        $base = $this->getBaseNameSpace();
        $pre  = self::$_defaultNamespacePrefix;
        return $pre . $base;
    }
    
    /**
     * Get NameSpace without prefix;
     *
     * @throws Ooba_Model_Exception If base name cannot be determined
     * @return string
     */
    public function getBaseNameSpace()
    {
        if (preg_match('/Model_(.*)$/', get_class($this), $matches) === 0) {
            throw new Ooba_Model_Exception('Basename can not be determined');
        }

        return $matches[1];
    }

	/**
     * Default namespace prefix
     *
     * @param  string $pre Prefix to be appended to namespace
     * @return void
     */
    static public function setDefaultNamespacePrefix($pre)
    {
        self::$_defaultNamespacePrefix = $pre;
    }
    
    /**
     * Get namespace prefix
     *
     * @return string
     */
    static public function getDefaultNamespacePrefix()
    {
        return self::$_defaultNamespacePrefix;
    }

	/**
     * Return the base class name, with namespaces removed.
     *
     * @return string
     */
    public function getBaseClassName()
    {
        $className  = get_class($this);
        $nameSpaces = Core_Model_Loader::getNameSpaces();
        $parts      = explode('_', $className);

        foreach ($nameSpaces as $nameSpace) {
            if ($parts[0] === $nameSpace) {
                unset($parts[0]);
                break;
            }
        }

        return implode('_', $parts);
    }
	
	/**
     * Query Model
     * 
     * @param  Ooba_Storage_Query $query
     * @param  array $fields (Optional) Fields to retrieve
     * @return Ooba_Model_Storage_Results
     */
	public function query(Ooba_Storage_Query $query, array $fields = array())
	{
		return $this->store->query($this,$query,$fields);
	}
	
	/**
     * Query Model
     * 
     * @param  Ooba_Storage_Query $query
     * @param  array $fields (Optional) Fields to retrieve
     * @return Ooba_Model_Interface
     */
	public function queryOne(Ooba_Storage_Query $query, array $fields = array())
	{
		return $this->store->queryOne($this, $query, $fields);
	}
	
	/**
     * Check if a record exists
     *
     * @param  Ooba_Storage_Query|string $query        Query object or uuid string
     * @param  string                    $forceAdapter (Optional) Only check this adapter
     * @return boolean
     */
    public function exists($query, $forceAdapter = null)
    {
        return $this->store->exists($this, $query, $forceAdapter);
    }
	
	/** 
     * Magic function for getting values.
     *
     * @param  string $property: Property to get
     * @throws Ooba_Model_Exception_InvalidProperty If property does not exists
     * @return mixed
     */
    public function __get($property)
	{
		// If prefaced with underscore use non underscored name which will funnel back through
        if (mb_substr($property, 0, 1) === '_') {
            $property = mb_substr($property, 1);
        }
		
		if (method_exists($this, 'get' . ucfirst($property)) === true) {
            $method = 'get' . ucfirst($property);
            return $this->$method();
        } else if (property_exists($this, $property) === false) {
            throw new Ooba_Model_Exception_InvalidProperty("Can't Get: $property does not exists for "
            . get_class($this));
        } else {
            return $this->$property;
        }
	}

	/**
     * Magic function for setting values. 
     *
     * @param  string $property: The name of the property
     * @param  mixed  $value The Value of the property
     * @return void
     */
    public function __set($property, $value)
	{
		if (mb_substr($property, 0, 1) === '_') {
            $property = mb_substr($property, 1);
        }
		
		if (method_exists($this, 'set' . ucfirst($property)) === true) {
            $method = 'set' . ucfirst($property);
            $this->$method($value);
        } else {
            $this->$property = $value;
        }
	}

	/**
     * Magic function for checkin if a property is set
     *
     * @param  string $property: Property to check
     * @return boolean
     */
    public function __isset($property)
    {
        // If prefaced with underscore use non underscored name which will funnel back through
        if (mb_substr($property, 0, 1) === '_') {
            $property = mb_substr($property, 1);
        }
        
        if (method_exists($this, 'get' . ucfirst($property)) === true) {
            $method = 'get' . ucfirst($property);
            $val    = $this->$method();
            return !is_null($val);
        } else {
            return isset($this->$property);
        }
    }
}

?>