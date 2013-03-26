<?php
/**
 * Memcache.php
 *
 * @category   Ooba
 * @package    Model
 * @subpackage Storage
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */

class Ooba_Model_Storage_Adapter_Memcache extends Ooba_Model_Storage_Adapter_Abstract
                                          implements Ooba_Model_Storage_Adapter_Interface
{
	/**
     * Memcache Storage Engine
     *
     * @var Zend_Cache_Core
     */
	protected $_storageApi;
	
	/**
     * Default lifetime
     *
     * @var integer
     */
    private static $_defaultLifetime;

	/**
     * lifetime
     *
     * @var integer
     */
	protected $_lifetime;
	
	/**
     * Default Hosts loaded by the bootstrap
     *
     * @var array
     */
    private static $_defaultHosts;

	/**
     * Hosts
     *
     * @var array
     */
    protected $_hosts;

	/**
     * Construct
     *
     * @param  mixed   $storage  (Optional) Storage Api
     * @param  integer $lifetime (Optional) Lifetime for memcache
     * @throws Ooba_Model_Storage_Adapter_Memcache_Exception_NoHosts If defaulthosts have not been set
     * @throws Ooba_Model_Storage_Adapter_Exception_WrongStorageType If not Ooba_Storage_Mysql
     */
	public function __construct($storage = null, $lifetime = null)
	{
		$constantKeeper      = ConstantsKeeper::getInstance();
        self::$_defaultHosts = $constantKeeper->memcachedServersArray;
        if (empty($constantKeeper->constMemcacheDefaultLifetime) === false) {
            self::$_defaultLifetime = $constantKeeper->constMemcacheDefaultLifetime;
        }
		
		$this->setType('cache');
		
		if (is_null($lifetime) === false) {
            $this->_lifetime = $lifetime;
        } else if (is_null(self::$_defaultLifetime) === false ) {
            $this->_lifetime = self::$_defaultLifetime;
        } else {
            $this->_lifetime = 7200;
        }

		if (empty(self::$_defaultHosts)) {
            throw new Ooba_Model_Storage_Adapter_Memcache_Exception_NoHosts('No hosts defined');
        }
        
        $this->_hosts = self::$_defaultHosts;

		if (is_null($storage) === false) {
            if (($storage instanceof Zend_Cache_Core) === false
                or get_class($storage->getBackend()) !== 'Zend_Cache_Backend_Memcached' ) {
                throw new Ooba_Model_Storage_Adapter_Exception_WrongStorageType();
            }

            $this->_storageApi = $storage;
        } else {
            $this->_storageApi = Zend_Cache::factory('Core',
                                                'Memcached',
                                                array(
                                                        'lifetime' => $this->_lifetime,
                                                        'automatic_serialization' => true),
                                                array(
                                                        'servers' => $this->_hosts));
		}
	}
	
	/**
     * Query
     *
     * @param  string             $class   Object Class to populate
     * @param  Ooba_Storage_Query $query   Query
     * @param  array              $fields  (Optional) Fields to fetch
     * @throws Ooba_Model_Storage_Exception_MissingNameSpace If not set
     * @return boolean | Ooba_Storage_Results_Interface
     */
	public function query($class, Ooba_Storage_Query $query, array $fields = array())
	{
		// Not supported
		return false;
	}
	
	/**
     * Query for a single object instead of results set
     *
     * @param  string             $class  Object Class to populate
     * @param  Ooba_Storage_Query $query  Query
     * @param  array              $fields (Optional) Fields to fetch
     * @throws Ooba_Model_Storage_Exception_MissingNameSpace If not set
     * @return boolean | Ooba_Model_Abstract
     */
	public function queryOne($class, Ooba_Storage_Query $query, array $fields = array())
	{
		// Not supported
		return false;
	}
	
	/**
     * Save Object
     *
     * @param array  $data      Model data to save
     * @param string $nameSpace (Optional) Namespace to save to
	 * @param array  $options	save options
     * @throws Ooba_Model_Storage_Exception_MissingNameSpace If namespace not set
     * @return boolean
     */
	public function save(array $data, $namespace = null, array $options = array())
	{
		if (empty($this->_namespace) === true and empty($namespace) === true) {
	            throw new Ooba_Model_Storage_Adapter_Exception_NameSpaceNotSet('NameSpace must be set');
	    } else if (is_null($namespace) === false) {
	             $this->_namespace = $namespace;
	    }

	    if (isset($options['lifetime']) === true) {
            $lifetime = $options['lifetime'];
        } else {
            $lifetime = $this->_lifetime;
        }

        $jsonData         = Zend_Json::encode($data);
        $key              = $this->_generateKey($this->_namespace, $data['_uuid']);
        $res              = $this->_storageApi->save($jsonData, $key, array(), $lifetime);
        $this->_namespace = null;
        return $res;
	}
	
	/**
     * Load Object
     *
     * @param  Ooba_Model_Abstract 	$model  Model to load
     * @param  string 	$uuid   Uuid to load
     * @param  array 	$fields (Optional) Fields to fetch
     * @return boolean| Ooba_Model
     */
	public function load(Ooba_Model_Abstract $model, $uuid, array $fields = array())
	{
		$namespace = $model->getNameSpace();
        $data      = $this->_storageApi->load($this->_generateKey($namespace, $uuid));
        if ($data !== false) {
            $data = $this->prepareDataForModel($data);
            return $data;
        } else {
            return false;
        }
	}
	
	/**
     * Delete Object
     *
     * @param  array|string $uuids     Uuids to delete
     * @param  string       $nameSpace (Optional) NameSpace to delete from
     * @throws Ooba_Model_Storage_Exception_MissingNameSpace If namespace not set
     * @return boolean
     */
	public function delete($uuids, $namespace = null)
	{
		if (empty($this->_namespace) === true and empty($namespace) === true) {
            throw new Ooba_Model_Storage_Adapter_Exception_NameSpaceNotSet('NameSpace must be set');
        } else if (is_null($namespace) === false) {
            $this->_namespace = $namespace;
        }
        
        $success   = false;
        $failCount = 0;
        if (is_array($uuids) === false) {
            $uuids = array($uuids);
        }
        
        foreach ($uuids as $uuid) {
            $res = $this->_storageApi->remove($this->_generateKey($this->_namespace, $uuid));
            if ($res === false) {
                $failCount++;
            } else {
                $success = true;
            }
        }
        
        if ($failCount > 0) {
            $success = false;
        }
        
        $this->_namespace = null;
        
        return $success;
	}
	
	/**
     * @param  mixed $data   Data to prepare for model
     * @param  array $fields (Optional) Fields to return
     * @throws Ooba_Model_Storage_Adapter_Exception_InvalidData If data returned from StorageApi is bad
     * @return string
     */
    static public function prepareDataForModel($data, array $fields = array())
    {
        $data = json_decode($data, true);
        if (is_null($data) === true or is_array($data) === false ) {
            throw new Ooba_Model_Storage_Adapter_Exception_InvalidData;
        }
        
        if (empty($fields) === false) {
            $data = array_intersect($data, array_flip($fields));
        }
        
        // Add loadFrom to tell model this was loaded from an adapter
        $data['_loadedFrom'] = 'Ooba_Model_Storage_Adapter_Memcache';
        return json_encode($data);
        
    }

    /**
     * Static method to set the hosts via the bootstrap
     *
     * @param  array $defaultHosts Name Space
     * @return void
     */
    public static function setDefaultHosts(array $defaultHosts)
    {
        self::$_defaultHosts = $defaultHosts;
    }

    /**
     * Return the default hosts array
     *
     * @return array
     */
    public static function getDefaultHosts()
    {
        return self::$_defaultHosts;
    }

    /**
     * Return the default hosts array
     *
     * @param  integer $lifetime Lifetime
     * @return void
     */
    public static function setDefaultLifetime($lifetime)
    {
        self::$_defaultLifetime = $lifetime;
    }

	/**
     * Check to see if a single object exists
     *
     * @param  string	$class Object Class to populate
     * @param  Ooba_Storage_Query $query Query
     * @throws Ooba_Model_Storage_Adapter_Exception_InvalidData If not set
     * @return boolean
     */
    public function exists($class, Ooba_Storage_Query $query)
    {
        // Strategy: If the query is ONLY for UUID, extract it and attempt a load
        $where = $query->where;
        if (count($where) === 1 and $where[0]->key === '_uuid') {
            if (is_object($class) === false) {
                $obj = new $class;
            } else {
                $obj = $class;
            }
            
            $fields = array('_uuid');
            return $this->load($obj, $where[0]->value, $fields) !== false;
        }
        
        return false;
    }
	
}