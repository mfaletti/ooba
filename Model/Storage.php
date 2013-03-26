<?php
/**
 * Ooba_Model_Storage
 *
 * @category   Ooba
 * @package    Model_Storage
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Model_Storage
{
	/**
	 * Array of storage adapters
	 *
	 * @var array
	 */
	protected $_adapters;
	
	/**
     * Array of nameSpaces to determineClass names
     *
     * @var array
     */
    protected $_nameSpaces;
	
	/**
     * Array of adapters to use by default if none are passed to constructor
     * @var array
     */
    static protected $_defaultAdapters;

	/**
     * Array of nameSpaces to determineClass names if none are passed to constructor
     *
     * @var array
     */
    static protected $_defaultNameSpaces;
	
	/**
	 * Array of types to write to; persistent or cache
	 *
	 * @var array
	 */
	static protected $_defaultWriteTypes = array();
	
	/**
	 * Array of types to write to; persistent or cache
	 *
	 * @var array
	 */
	protected $_writeTypes;
	
	/**
     * Construct
     *
     * @param  array $adapters   (Optional) Array of adapters to load/save from
     * @param  array $nameSpaces (Optional) Array of namespaces to determine class
 	 * @param  array $writeTypes (Optional) Array of adapter types to write to
     * @throws Ooba_Model_Storage_Exception_MissingAdapters   If no adapters are passed to constructor 
     *                                                        and defaults have not been set
     * @throws Ooba_Model_Storage_Exception_MissingNameSpaces If no namespaces are passed to constructor 
     *                                                        and defaults have not been set
     */
	public function __construct(array $adapters = null, array $nameSpaces = null, array $writeTypes = null)
	{
		if (is_null($adapters) === true) {
			$this->_adapters = self::$_defaultAdapters;
		} else {
			$this->_adapters = $adapters;
		}
		
		if (is_null($this->_adapters) === true) {
            throw new Ooba_Model_Storage_Exception_MissingAdapters();
        }
		
		if (is_null($nameSpaces) === true) {
            $this->_nameSpaces = self::$_defaultNameSpaces;
        } else {
            $this->_nameSpaces = $nameSpaces;
        }
        
        if (is_null($this->_nameSpaces) === true) {
            throw new Ooba_Model_Storage_Exception_MissingNameSpaces();
        }

		if (is_null($writeTypes) === true) {
			$this->_writeTypes = self::$_defaultWriteTypes;
		} else {
			$this->_writeTypes = $writeTypes;
		}
	}
	
	/**
     * Returns adapters
     *
     * @return array 
     */
    public function getAdapters()
    {
        return $this->_adapters;
    }
	
	/**
     * Set Default stores to use if none are passed to constructor
     *
     * @param  array $adapters Adapters to use  
     * @return void
     */
    public static function setDefaultAdapters(array $adapters)
    {
        self::$_defaultAdapters = array();
        foreach ($adapters as $adapter) {
            if (is_object($adapter) === false) {
                $adapter = new $adapter;
            }
            
            self::$_defaultAdapters[] = $adapter;
        }
    }

	/**
     * Returns default adapters
     *
     * @return array 
     */
    public static function getDefaultAdapters()
    {
        return self::$_defaultAdapters;
    }

	/**
     * Set Default namespaces
     *
     * @param  array $nameSpaces NameSpaces to use  
     * @return void
     */
    public static function setDefaultNameSpaces(array $nameSpaces)
    {
          self::$_defaultNameSpaces = $nameSpaces;
    }

	/**
     * Returns default nameSpaces
     *
     * @return array 
     */
    public static function getDefaultNameSpaces()
    {
        return self::$_defaultNameSpaces;
    }
	
	/** 
     * Query
     *
     * @param  string | Ooba_Model  $class: The Object Class to populate
     * @param  Ooba_Storage_Query $query: Query conditions 
     * @param  array $fields  (Optional) Fields to fetch
     * @throws Exception Throw exception on persitant failures
     * @return boolean | Ooba_Storage_Results_Interface | Model
     */
	public function query($class, Ooba_Storage_Query $query, array $fields = array())
	{
		$res = false;
		
		if (is_object($class) === false){
			$class = $this->_determineClass($class);
			$obj = new $class;
		} else {
			$obj = $class;
		}
		
		foreach ($this->_adapters as $adapter) {
			try {
				$res = $adapter->query($obj, $query, $fields);
			} catch (Exception $e) {
				throw $e;
			}
			
			if ($res !== false) {
				return $res;
			}
		}
		
		return false;
	}
	
	/** 
     * Query
     *
     * @param  string | Ooba_Model  $class: The Object Class to populate
     * @param  Ooba_Storage_Query $query: Query conditions 
     * @param  array $fields  (Optional) Fields to fetch
     * @throws Exception Throw exception on persitant failures
     * @return boolean | Ooba_Storage_Results_Interface | Model
     */
	public function queryOne($class, Ooba_Storage_Query $query, array $fields = array())
	{
		$res = false;
		if (is_object($class) === false){
			$class = $this->_determineClass($class);
			$obj = new $class;
		} else {
			$obj   = $class;
			$class = get_class($obj); 
		}
		
		foreach ($this->_adapters as $adapter) {
			try {
				$adapter->setNameSpace($obj->getNameSpace());
				$res = $adapter->queryOne($obj, $query, $fields);
			} catch (Exception $e) {
				if ($adapter->getType() === 'cache') {
					Ooba_Log::getInstance()->log($e->getMessage(), Zend_Log::INFO);
				} else {
					throw $e;
				}
			}
			
			if ($res !== false) {
				return $res;
			}
		}
		
		return false;
	}
	
	/**
     * Check if a record exists
     *
     * @param  string                    $class        Class to query against
     * @param  Ooba_Storage_Query|string $query        Query object or uuid string
     * @param  string                    $forceAdapter (Optional) Only check this adapter
     * @throws Exception Throw exception on persistant failures
     * @return boolean
     */
    public function exists($class, $query, $forceAdapter = null)
    {
        if (is_object($class) === false) {
            $class = $this->_determineClass($class);
            $obj   = new $class;
        } else {
            $obj   = $class;
            $class = get_class($obj);
        }
        
        // If we were passed a uuid, build a query
        if ($query instanceof Ooba_Storage_Query === false) {
            $queryer = new Ooba_Storage_Query();
            $queryer->where('_uuid', $query);
        } else {
            $queryer = $query;
        }
        
        // Normalize $forceAdapter
        if (is_null($forceAdapter) === false and mb_strpos($forceAdapter, 'Ooba_Model_Storage_Adapter') === false) {
            $forceAdapter = 'Ooba_Model_Storage_Adapter_' . ucfirst($forceAdapter);
        }
        
        foreach ($this->_adapters as $adapter) {
            if (is_null($forceAdapter) === true or get_class($adapter) === $forceAdapter) {
                try {
                    $adapter->setNameSpace($obj->getNameSpace());
                    if (method_exists($adapter, 'exists') === true) {
                        $res = $adapter->exists($class, $queryer);
                    } else {
                        $res = false;
                    }
                } catch (Exception $e) {
                    if ($adapter->getType() === 'cache') {
                        Ooba_Log::getInstance()->log($e->getMessage(), Zend_Log::INFO);
                    } else {
                        throw $e;
                    }
                }
                if ($res !== false) {
                    Ooba_Log::getInstance()->notice('Ooba_Model::exists - found ' . get_class($obj) .
                                                    ' record using ' . get_class($adapter));
                    return true;
                }
            }
        }
        
        // Didn't find anything
        return false;
    }
	
	/**
	 * Load data into model
	 * @param string | Ooba_Model $class: class of the object to load
	 * @param string $id: Unique id of the object to load
	 * @param array $options: (optional) list of options to retrieve
	 */
	
	public function load($class, $id, array $options = array())
	{
		$failedAdapters = array();
		
		if (isset($options['fields']) === true) {
            $fields = $options['fields'];
        } else {
            $fields = array();
        }
		
		if (isset($options['saveMiss']) === true) {
            $saveMiss = $options['saveMiss'];
        } else {
            $saveMiss = true;
        }
		
		if (isset($options['readFrom']) === true) {
            $readFrom = (array) $options['readFrom'];
        } else {
            $readFrom = array();
        }

		if (is_object($class) === false) {
			$class = $this->_determineClass($class);
			$obj = new $class;
		} else {
			$obj = $class;
			$class = get_class($obj);
		}
		
		foreach ($this->_adapters as $adapter) {
			try {
				$res = $adapter->load($obj, $id, $fields);
			} catch (Exception $e) {
				$res = false;
				if ($adapter->getType() === 'cache') {
                    Ooba_Log::getInstance()->log($e->getMessage(), Zend_Log::INFO);
                } else {
                    throw $e;
                }
			}
			
			if ($res === false and $adapter->getType() === 'cache') {
                $failedAdapters[] = $adapter;
                Ooba_Log::getInstance()->warn("cache miss: $class $id");
            }

			if ($res !== false and empty($fields) === true and $saveMiss === true) {
                foreach ($failedAdapters as $cacheAdapter) {
                    try {
                        $cacheAdapter->save(Zend_Json::decode($res), $obj->getNameSpace());
                    } catch (Exception $e) {
                        Ooba_Log::getInstance()->log($e->getMessage(), Zend_Log::INFO);
                    }
                }
                
                $obj->fromJson($res);
                return $obj;
            }
		}
		
		return false;
	}
	
	/**
     * Save object to DB
     *
     * @param  mixed $modelData: Model data to save as object, json string, or array
	 * @param  array|string $types     (Optional) Adapter Types to operate on
     * @param  string $nameSpace: (Optional) NameSpace, required when passing raw data
     * @param  string $options: (Optional) Options array
     * @throws Ooba_Model_Storage_Exception_InvalidData       Throw exception on persistent failures
     * @throws Ooba_Model_Storage_Exception_MissingNameSpaces When raw data is passed, but namespace is not
     * @throws Ooba_Model_Storage_Exception_NoUuid            When raw data is passed, but uuid is not
     * @throws Ooba_Model_Storage_Exception_ReadOnly          Model is read only, cant save
     * @return boolean
     */
	public function save($model, $types = null, $namespace = null, array $options = array())
	{
		if (is_null($types) === true) {
            $types = $this->_writeTypes;
        }
        
        $types = (array) $types;
		
		if (is_object($model) === true){
			if (($model instanceof Ooba_Model_Abstract) === false){
				throw new Ooba_Model_Storage_Exception_InvalidData('Object must be an instance of Ooba_Model_Abstract');
			}
			
			if ($model->getReadOnly() === true) {
                throw new Ooba_Model_Storage_Exception_ReadOnly('Model is read only.');
            }
			
			$uuid      = $model->uuid;
			$data      = Zend_Json::decode($model->toJson());
			$namespace = $model->getNameSpace();
		} else if (is_string($model) === true) {
	      	$data = Zend_Json::decode($model);
	    } else if (is_array($model) === false) {
	            throw new Ooba_Model_Storage_Exception_InvalidData(
	                    'data must be a model, a json string, or array');
	    }

	    if (is_null($namespace) === true or empty($namespace) === true) {
            throw new Ooba_Model_Storage_Exception_MissingNameSpaces(
                'Namespace must be passed when passing data instead of a model');
        }

        if (isset($data['_uuid']) === false) {
            throw new Ooba_Model_Storage_Exception_NoUuid(
                'Uuid must be set');
        }

		if (isset($data['_readOnly']) === true and $data['_readOnly'] === true) {
            throw new Ooba_Model_Storage_Exception_ReadOnly('Model is set to read only.');
        }

		foreach ($this->_adapters as $adapter) {
			$res = $adapter->save($data, $namespace);
			if ($res === false) {
                return false;
            }
		}
		
		return true;
	}
	
	/**
	 * Delete Object
	 * @param Ooba_Model_Abstract $obj: The object to delete
	 * @param array|string $types (optional): Adapter types to operate on
	 * @throws Ooba_Model_Storage_Exception_NoUuid
	 * @return boolean
	 */
	public function delete(Ooba_Model_Abstract $model, $types = null)
	{
		if (is_null($model->uuid) === true) {
            throw new Ooba_Model_Storage_Exception_NoUuid('Can\'t Delete an object that has no uuid');
        }
		
		if (is_null($types)) {
			$types = $this->_writeTypes;
		}
		
		$namespace = $model->getNameSpace();
		$types = (array) $types;
		$res = false;
		
		foreach ($this->_adapters as $adapter) {
			if (empty($types) or in_array($adapter->getType(), $types)) {
            	try {
	                $res = $adapter->delete($model->uuid, $namespace);
	            } catch (Exception $e) {
	              	$res = false;
	                if ($adapter->getType() === 'cache') {
	                	Ooba_Log::getInstance()->log($e->getMessage(), Zend_Log::INFO);
                    } else {
                        throw $e;
                    }
				}
			}
		}
        
        return $res;
    }
	
	/**
     * Returns class based on vertical
     *
     * @param  string $class
     * @throws Ooba_Model_Storage_Exception_NoClassFound
     * @return string 
     */
    protected function _determineClass($class)
    {
        foreach ($this->_nameSpaces as $nameSpace) {
            $newClass = $nameSpace . '_' . $class;
            if (class_exists($newClass) === true) {
                return $newClass;
            }
        }
        
        throw new Ooba_Model_Storage_Exception_NoClassFound(
            'The Storage Layer was unable to find a suitable class to load using: ' . $class);
    }

	/**
     * Set DefaultWriteTypes to use for all stores if none are passed on constructor
     *
     * @param  array $types Types as array of strings ie array('cache','persistent')
     * @return void
     */
    static public function setDefaultWriteTypes(array $types)
    {
        self::$_defaultWriteTypes = $types;
    }

	/**
     * Get DefaultWriteTypes to use for all stores if none are passed on constructor
     *
     * @return array
     */
    static public function getDefaultWriteTypes()
    {
        return self::$_defaultWriteTypes;
    }

	/**
     * Set WriteTypes to use for this store
     *
     * @param  array $types Types as array of strings ie array('cache','persistent')
     * @return void
     */
    public function setWriteTypes(array $types)
    {
        $this->_writeTypes = $types;
    }
    
    /**
     * Get WriteTypes to use for this store
     *
     * @return array
     */
    public function getWriteTypes()
    {
        return $this->_writeTypes;
    }

	/**
     * Delete models from cache
     *
     * @param  array|Ooba_Model_Abstract $models Array of models to delete
     * @return boolean
     */
    public function bustCache($models)
    {
        if (is_array($models) === false) {
            $models = array($models);
        }
        
        $return = true;
        
        foreach ($models as $model) {
            $res = $this->delete($model, array('cache'));
            if ($res === false) {
                $return = false;
            }
        }
        
        return $return;
    }

	/**
     * Remove/Unset a field
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  array|null         $options   (Optional) Options to pass to adapter
     * @param  string|array       $types     (Optional) Types of adapters to work with
     * @return boolean
     */
    public function remove($nameSpace, Ooba_Storage_Query $query, $field, $options = null, $types = null)
    {
        return $this->_doAtomic('remove', 'Ooba_Model_Storage_Adapter_Settable',
            $nameSpace, $query, $field, 1, $options, $types);
    }

	/**
     * Set a single field to a value
     *
     * @param  string             $namespace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  mixed              $value     Value to set field to
     * @param  array|null         $options   (Optional) Options to pass to adapter
     * @param  string|array       $types     (Optional) Types of adapters to work with
     * @return boolean
     */
    public function set($namespace, Ooba_Storage_Query $query, $field, $value, $options = null, $types = null)
    {
        return $this->_doAtomic('set', 'Ooba_Model_Storage_Adapter_Settable',
            $namespace, $query, $field, $value, $options, $types);
    }

	/**
     * Add values to array if they don't already exist. S
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  mixed              $values    Values to add as either a single instance or multiple values
     * @param  array              $options   (Optional) Options to pass to adapter
     * @param  string|array       $types     (Optional) Types of adapters to work with
     * @return boolean
     */
    public function addToSet($nameSpace, Ooba_Storage_Query $query, $field, $values,
                             array $options = null, $types = null)
    {
        return $this->_doAtomic('addToSet', 'Ooba_Model_Storage_Adapter_ArrayAccess',
            $nameSpace, $query, $field, $values, $options, $types);
    }

    /**
     * Increment a property of a model and save that property to the database
     *
     * @param  string             $nameSpace NameSpace to operate on
     * @param  Ooba_Storage_Query $query     Query Object for conditions
     * @param  string             $property  Property of model to change
     * @param  integer            $n         (Optional) Nnumber to increment by
     * @param  array              $options   (Optional) Options to pass to adapter
     * @param  string|array       $types     (Optional) Types of adapters to work with
     * @return boolean
     */
    public function increment($nameSpace, Ooba_Storage_Query $query, $property, $n = 1,
                              array $options = null, $types = null)
    {
        return $this->_doAtomic('increment', 'Ooba_Model_Storage_Adapter_Incrementable',
            $nameSpace, $query, $property, $n, $options, $types);
    }

	/**
     * Decrement a property of a model and save that property to the database
     *
     * @param  string             $nameSpace NameSpace to operate on
     * @param  Ooba_Storage_Query $query     Query Object for conditions
     * @param  string             $property  Property of model to change
     * @param  integer            $n         (Optional) Number to increment by
     * @param  array              $options   (Optional) Options to pass to adapter
     * @param  string|array       $types     (Optional) Types of adapters to work with
     * @return boolean
     */
    public function decrement($nameSpace, Ooba_Storage_Query $query, $property, $n = 1,
                              array $options = null, $types = null)
    {
        return $this->_doAtomic('decrement', 'Ooba_Model_Storage_Adapter_Incrementable',
            $nameSpace, $query, $property, $n, $options, $types);
    }

	/**
     * Perform an atomic operation on adapters
     *
     * @param  string             $method    Atomic Method to perform
     * @param  string             $interface Interface Required
     * @param  string             $nameSpace NameSpace to operate on
     * @param  Ooba_Storage_Query $query     Query Object for conditions
     * @param  string             $property  Property of model to change
     * @param  integer            $values    Value(s)
     * @param  array|null         $options   (Optional) Options to pass to adapter
     * @param  array|string       $types     (Optional) Adapter Types to operate on
     * @throws Exception Throw exception on persistent failures
     * @return boolean
     */
    protected function _doAtomic($method, $interface, $nameSpace, Ooba_Storage_Query $query, $property, $values,
        $options = array(), $types = null)
    {
        $success = true;
        if (is_null($types) === true) {
            $types = $this->_writeTypes;
        }
        
        $types = (array) $types;

        foreach ($this->_adapters as $adapter) {
            if (empty($types) === true or in_array($adapter->getType(), $types)) {
                if (($adapter instanceof $interface) === false) {
                    continue; //bypass adapters that do not implement ArrayAccess; ie _Storage_Adapter_Memcache
                }
                
                try {
                    $res = $adapter->{$method}($nameSpace, $query, $property, $values, $options);
                } catch (Exception $e) {
                    $res = false;
                    if ($adapter->getType() === 'cache') {
                        Ooba_Log::getInstance()->log($e->getMessage(), Zend_Log::INFO);
                    } else {
                        throw $e;
                    }
                }
                
                if ($res === false and $adapter->getType() !== 'cache') {
                    return false;
                }
            }
        }

        return $success;
    }

	/**
     * Push a single value 
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  mixed              $value     Value to add as individual array elements
     * @param  array              $options   (Optional) Options to pass to adapter
     * @param  string|array       $types     (Optional) Types of adapters to work with
     * @return boolean
     */
    public function push($nameSpace, Ooba_Storage_Query $query, $field, $value,
                         array $options = null, $types = null)
    {
        return $this->_doAtomic('push', 'Ooba_Model_Storage_Adapter_ArrayAccess',
            $nameSpace, $query, $field, $value, $options, $types);
    }

	/**
     * Push many values 
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  array              $values    Values to add as individual array elements
     * @param  array              $options   (Optional) Options to pass to adapter
     * @param  string|array       $types     (Optional) Types of adapters to work with
     * @return boolean
     */
    public function pushAll($nameSpace, Ooba_Storage_Query $query, $field, array $values,
                            array $options = null, $types = null)
    {
        return $this->_doAtomic('pushAll', 'Ooba_Model_Storage_Adapter_ArrayAccess',
            $nameSpace, $query, $field, $values, $options, $types);
    }

	/**
     * Pull a single value
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  mixed              $value     Value to remove from array
     * @param  array              $options   (Optional) Options to pass to adapter
     * @param  string|array       $types     (Optional) Types of adapters to work with
     * @return boolean
     */
    public function pull($nameSpace, Ooba_Storage_Query $query, $field, $value,
                         array $options = null, $types = null)
    {
        return $this->_doAtomic('pull', 'Ooba_Model_Storage_Adapter_ArrayAccess',
        	$nameSpace, $query, $field, $value, $options, $types);
    }

	/**
     * Pull many values 
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  array              $values    Values to remove as individual array elements
     * @param  array              $options   (Optional) Options to pass to adapter
     * @param  string|array       $types     (Optional) Types of adapters to work with
     * @return boolean
     */
    public function pullAll($nameSpace, Ooba_Storage_Query $query, $field, array $values,
                            array $options = null,  $types = null)
    {
        return $this->_doAtomic('pullAll', 'Ooba_Model_Storage_Adapter_ArrayAccess',
            $nameSpace, $query, $field, $values, null, $types);
    }

	/**
     * Pop a single item
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to update
     * @param  string             $field     Property of model to change
     * @param  mixed              $value     Val 1 to remove last element -1 to remove first element
     * @param  array              $options   (Optional) Options to pass to adapter
     * @param  string|array       $types     (Optional) Types of adapters to work with
     * @return boolean
     */
    public function pop($nameSpace, Ooba_Storage_Query $query, $field, $value,
                         array $options = null, $types = null)
    {
        return $this->_doAtomic('pop', 'Ooba_Model_Storage_Adapter_ArrayAccess',
            $nameSpace, $query, $field, $value, $options, $types);
    }
}
?>