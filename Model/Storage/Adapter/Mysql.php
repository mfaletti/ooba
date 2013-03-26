<?php
/**
 * Ooba_Model_Storage_Adapter_Mysql
 *
 * @category   Ooba
 * @package    Model
 * @subpackage Storage
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */

/**
 * Wrapper class for connecting to MySQL databases and performing common CRUD operations.
 *
 */
class Ooba_Model_Storage_Adapter_Mysql extends Ooba_Model_Storage_Adapter_Abstract
                                       implements Ooba_Model_Storage_Adapter_Interface,
									   Ooba_Model_Storage_Adapter_Settable
{
	/**
     * Storage Engine Type
     *
     * @var Ooba_Storage_Mysqli
     */
	protected $_storageApi;
	
	/**
	 * @param $api (optional) 	Storage Api
	 * @param $parser (optional) Query Parser	
	 * @throws Ooba_Model_Storage_Adapter_Exception_WrongStorageType if not Ooba_Storage_Mysql
	 * @throws Ooba_Model_Storage_Adapter_Exception_WrongParserType if not Ooba_Storage_Mysql_QueryParser
	 *
	 */
	public function __construct($api = null, Ooba_Storage_Query_Parser_Interface $parser = null)
	{
		$this->setType('persistent');
		if (is_null($api) === false) {
			if (($api instanceof Ooba_Storage_Mysql) === false) {
				throw new Ooba_Model_Storage_Adapter_Exception_WrongStorageType();
			}
			
			$this->_storageApi = $api;
		} else {
				$this->_storageApi = new Ooba_Storage_Mysql;
		}
		
		if (is_null($parser) === false) {
			if (($parser instanceof Ooba_Storage_Mysql_QueryParser) === false) {
				throw new Ooba_Model_Storage_Adapter_Exception_WrongParserType();
			}
			
			$this->_parser = $parser;
		} else {
			$this->_parser = new Ooba_Storage_Mysql_QueryParser();
		}
	}
	
	/**
     * Query
     *
     * @param  string | Ooba_Model_Abstract $class:  The Object Class to populate
     * @param  Ooba_Storage_Query $query: Query
     * @param  array $fields  (Optional) Fields to fetch
     * @throws Ooba_Model_Storage_Exception_MissingNameSpace If not set
     * @return boolean | Ooba_Storage_Results_Interface
     */
	public function query($class, Ooba_Storage_Query $query, array $fields = array())
	{
		if (is_object($class) === false) {
			$obj = new $class;
		} else {
			$obj = $class;
		}
		
		if ($obj instanceof Ooba_Model_Abstract === false) {
			throw new Ooba_Model_Storage_Adapter_Exception_InvalidData('Invalid Object Type. ' .
			  'Should be instance of Ooba_Model_Abstract');
		}
		
		$query->setParser($this->_parser)->fields($fields);
		$results = $this->_storageApi->query($obj->getNameSpace(), $class, $query->build());
		$this->_log($this->_storageApi, "query {$obj->getNameSpace()} "
            .  $query->build()->assemble());
		if ($results === false ) {
            return false;
        }

		return new Ooba_Model_Storage_Results_Mysql($class, $results);
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
		if (is_object($class) === false) {
			$obj = new $class;
		} else {
			$obj = $class;
		}
		
		if ($obj instanceof Ooba_Model_Abstract === false) {
			throw new Ooba_Model_Storage_Adapter_Exception_InvalidData('Invalid Object Type. ' .
			  'Should be instance of Ooba_Model_Abstract');
		}
		
		$query->setParser($this->_parser)->fields($fields);
		$results = $this->_storageApi->queryOne($obj->getNameSpace(), $query->build());
		$this->_log($this->_storageApi, "query {$obj->getNameSpace()} "
            .  $query->build()->assemble());
		
		if ($results === false ) {
            return false;
        }
		
		$data = self::prepareDataForModel($results);
		$obj->fromJson($data);
		
		return $obj;
	}
	
	/**
     * Save Object
     *
     * @param  array  $data      Model data to save
     * @param  string $nameSpace (Optional) NameSpace to save to
     * @throws Ooba_Model_Storage_Exception_MissingNameSpace If namespace not set
     * @return boolean
     */
	public function save(array $data, $namespace = null)
	{	
		
		if (empty($this->_namespace) and empty($namespace)) {
            throw new Ooba_Model_Storage_Adapter_Exception_NameSpaceNotSet('NameSpace must be set');
        } else if (is_null($namespace) === false) {
            $this->_namespace = $namespace;
        }

		$opCache = Ooba_OpCache::getInstance();
		$cols = $opCache->load($namespace);
		if ($cols === false) {
			$cols = array_keys($this->_storageApi->getDb()->describeTable($namespace));

			foreach($cols as $key => $val){
				$cols[$val] = $val;
				unset($cols[$key]);
			}
			
			$opCache->save($cols, $namespace);
		}
		
		$data = array_intersect_key($data, $cols);	

		$data = $this->_removeNullValues($data);
		
		if (empty($data['id'])) {
			unset($data['id']);
			$res = $this->_storageApi->getDb()->insert($namespace, $data);
		} else {
			$where = ' id = ' . $data['id'];
			unset($data['id']);
			$res = $this->_storageApi->getDb()->update($namespace, $data, $where);
		}
		
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
		$query = new Ooba_Storage_Query();
		$query->setParser($this->_parser);
		$query->where('_uuid', $uuid);
		
		$results = $this->_storageApi->queryOne($model->getNameSpace(), $query->build());
		if ($results !== false) {
            return self::prepareDataForModel($results);
        }

		return false;
		
	}
	
	/**
     * Delete Object
     *
     * @param  array|string $uuids     Uuids to delete
     * @param  string       $nameSpace (Optional) NameSpace to delete from
     * @throws Ooba_Model_Storage_Exception_MissingNameSpace If namespace not set
     * @return boolean
     */
	public function delete($uuid, $namespace = null)
	{
		if (empty($namespace)) {
			throw new Ooba_Model_Storage_Adapter_Exception('Namespace is not set');
		}
		
		return $this->_storageApi->delete($uuid, $namespace);
	}
	
	/**
	 * @param mixed $data: Data to prepare for Model
	 * @return json string
	 */
	
	static public function prepareDataForModel($data)
	{
		if (is_null($data) === true){
			$data = array();
		}
		
		$data['_loadedFrom'] = 'Ooba_Model_Storage_Adapter_Mysql';
		return json_encode($data);
	}
	
	/**
     * Removes any fields in a multi-dimensional array that are set to NULL; used
     * to ensure null data not written to mysql.
     *
     * @param  mixed $input Data to clean
     * @return array Modified array
     */
    protected function _removeNullValues($input)
    {
        if (is_array($input) === false) {
            return $input;
        }

        $nonNull = array();
        foreach ($input as $key => $value) {
            if (is_null($value) === false) {
                $nonNull[$key] = $this->_removeNullValues($value);
            }
        }

        return $nonNull;
    }

	/**
     * Set an individual field value
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  mixed              $value     Value to set field to
     * @param  array|null         $options   (Optional) Options used for running commands
     * @return boolean
     */
    public function set($namespace, Ooba_Storage_Query $query, $field, $value, $options = null)
    {
        // Don't store null values in mysql
        if (is_null($value) === true) {
            return $this->remove($namespace, $query, $field, $value, $options);
        }

        $query->setParser($this->_parser);
        $conditions = $query->build()->getPart('where');
        $set        = array($field => $value);
        $results    = $this->_update($namespace, $conditions, $set);

        if ($results !== true) {
            return false;
        }

        return true;
    }

	/**
     * Call update on MySql and log
     *
     * @param  string $nameSpace  NameSpace
     * @param  array  $conditions Conditions
     * @param  array  $operation  Operation
     * @param  array  $options    (Optional) Options
     * @return mixed
     */
    protected function _update($nameSpace, array $conditions, array $set, array $options = array())
    {
        $results = $this->_storageApi->getDb()->update($namespace, $set, $conditions);
        $this->_log($this->_storageApi, "update $nameSpace "
            . Zend_Json::encode($conditions) . ' ' . Zend_Json::encode($operation));
        return $results;
    }

	/**
     * Remove/unset a field value
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  mixed              $value     Value to set field to
     * @param  array|null         $options   (Optional) Options used for running commands
     * @return boolean
     */
    public function remove($nameSpace, Ooba_Storage_Query $query, $field, $value, $options = null)
    {
        $query->setParser($this->_parser);
        $conditions = $query->build()->getPart('where');
        $unset      = array('$unset' => array($field => 1));
        $results    = $this->_update($nameSpace, $conditions, $unset);
        
        if ($results !== true) {
            return false;
        }
        
        return true;
    }

	/**
     * Log info about mysql operation
     *
     * @param  Ooba_Storage_Mysql $api Storage Api
     * @param  string             $msg Message
     * @return void
     */
    protected function _log(Ooba_Storage_Mysql $api, $msg)
    {
        Ooba_Log::getInstance()->debug("MySql: {$api->getConfig()->resources->db->params->host}  $msg", 4, 7);
    }
	
}  

?>