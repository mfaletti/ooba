<?php
/**
 * Ooba_Model_Storage_Adapter_Mongo
 *
 * @category   Ooba
 * @package    Model
 * @subpackage Storage
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Model_Storage_Adapter_Mongo extends Ooba_Model_Storage_Adapter_Abstract
                                       implements Ooba_Model_Storage_Adapter_Interface,
                                       Ooba_Model_Storage_Adapter_Incrementable,
                                       Ooba_Model_Storage_Adapter_ArrayAccess,
                                       Ooba_Model_Storage_Adapter_Settable
{
    /**
     * Mongo Storage Engine
     *
     * @var Ooba_Storage_Mongo
     */
    protected $_storageApi;
    
    /**
     * Database
     *
     * @var string
     */
    static protected $_db;
    
    /**
     * Construct
     *
     * @param  mixed                               $api    (Optional) Storage Api
     * @param  Ooba_Storage_Query_Parser_Interface $parser (Optional) Query Parser
     * @throws Ooba_Model_Storage_Adapter_Exception_WrongStorageType If not Ooba_Storage_Mongo
     * @throws Ooba_Model_Storage_Adapter_Exception_WrongParserType  If not Ooba_Storage_Mongo_QueryParser
     */
    public function __construct($api = null, Ooba_Storage_Query_Parser_Interface $parser = null)
    {
        self::$_db = ConstantsKeeper::getInstance()->mongoDefaultDb;
        // Set slaveOkay for the current session
        MongoCursor::$slaveOkay = true;
        $this->setType('persistent');
        if (is_null($api) === false) {
            if (($api instanceof Ooba_Storage_Mongo) === false) {
                throw new Ooba_Model_Storage_Adapter_Exception_WrongStorageType();
            }
            
            $this->_storageApi = $api;
        } else {
                $this->_storageApi = Ooba_Storage_Mongo::getInstance();
        }
        
        if (is_null($parser) === false) {
            if (($parser instanceof Ooba_Storage_Mongo_QueryParser) === false ) {
                throw new Ooba_Model_Storage_Adapter_Exception_WrongParserType();
            }
            
            $this->_parser = $parser;
        } else {
            $this->_parser = new Ooba_Storage_Mongo_QueryParser();
        }
        
    }

    /**
     * Query
     *
     * @param  string             $class   Object Class to populate
     * @param  Ooba_Storage_Query $query   Query
     * @param  boolean            $grouped (Optional) Return all data collected in a single object
     * @param  array              $fields  (Optional) Fields to fetch from store
     * @throws Ooba_Model_Storage_Adapter_Exception_InvalidData If not set
     * @return boolean|Ooba_Storage_Results_Interface|Model
     */
    public function query($class, Ooba_Storage_Query $query, /*$grouped = false,*/ array $fields = array())
    {
        if (is_object($class) === false) {
            $obj = new $class;
        } else {
            $obj = $class;
        }
        
        if (($obj instanceof Ooba_Model_Abstract) === false and ($obj instanceof Ooba_Model_Iterator) === false) {
            throw new Ooba_Model_Storage_Adapter_Exception_InvalidData('Invalid Object Type,' .
                 ' should be instance of Ooba_Model_Abstract');
        }

        $query->setParser($this->_parser)->fields($fields);
        $results = $this->_storageApi->query($this->getDb(), $obj->getNameSpace(), $query->build());
        $this->_log($this->_storageApi, "query {$obj->getNameSpace()} "
            .  Zend_Json::encode($query->build()->getPredicates()));
        
        if ($results === false ) {
            return false;
        }
        
        /*if ($grouped === true) {
            if (($obj instanceof Ooba_Model_Iterator) === false) {
                throw new Ooba_Model_Storage_Adapter_Exception_InvalidData('You must pass a Ooba_Model_Iterator
                 when grouping objects');
            }
            
            $data = array();
            foreach ($results as $result) {
                $someUuids = (array) $this->_getPath($obj->uuidPath, $result);
                $data      = array_merge($data, $someUuids);
            }
            
            $data = array_unique($data);
            $obj->setUuids($data);

            return $obj;
        }*/
        
        return new Ooba_Model_Storage_Results_Mongo($class, $results, $this);
    }

    /**
     * Save Object
     *
     * @param  array  $data      Model data to save
     * @param  string $nameSpace (Optional) NameSpace to delete from
     * @throws Ooba_Model_Storage_Adapter_Exception_NameSpaceNotSet If namespace not set
     * @return boolean
     */
    public function save(array $data, $nameSpace = null)
    {
        if (empty($this->_nameSpace) === true and empty($nameSpace) === true) {
            throw new Ooba_Model_Storage_Adapter_Exception_NameSpaceNotSet('NameSpace must be set');
        } else if (is_null($nameSpace) === false) {
            $this->_nameSpace = $nameSpace;
        }
        
        $data['_id'] = $data['_uuid'];
        $data        = $this->_removeNullValues($data);
        $res         = $this->_storageApi->{$this->getDb()}->{$this->getNameSpace()}->save($data);
        $this->_log($this->_storageApi, "save $nameSpace "  . Zend_Json::encode($data));

        $this->_nameSpace = null;
        return $res;
    }
    
    /**
     * Delete Object
     *
     * @param  array|string $uuids     Uuids to delete
     * @param  string       $nameSpace (Optional) NameSpace to delete from
     * @throws Ooba_Model_Storage_Adapter_Exception_NameSpaceNotSet If namespace not set
     * @return boolean
     */
    public function delete($uuids, $nameSpace = null)
    {
        if (empty($this->_nameSpace) === true and empty($nameSpace) === true) {
            throw new Ooba_Model_Storage_Adapter_Exception_NameSpaceNotSet('NameSpace must be set');
        } else if (is_null($nameSpace) === false) {
            $this->_nameSpace = $nameSpace;
        }
        
        if (is_array($uuids) === false) {
            $uuids = array($uuids);
        }
        
        $api = $this->_storageApi;
        foreach ($uuids as $uuid) {
            $res = $api->{$this->getDb()}->{$this->getNameSpace()}->remove(array('_id' => $uuid));
            $this->_log($this->_storageApi, 'remove '
                . $nameSpace . ' ' . Zend_Json::encode(array('_id' => $uuid)));
        }
        
        $this->_nameSpace = null;
        
        return $res;
    }
    
    /**
     * Load Object
     *
     * @param  Ooba_Model_Abstract $model  Model to load
     * @param  string              $uuid   Uuid to load
     * @param  array               $fields (Optional) Fields to fetch
     * @throws Ooba_Model_Storage_Adapter_Exception_TooManyResults Load should only get one result from storage
     * @return boolean| Ooba_Model
     */
    public function load(Ooba_Model_Abstract $model, $uuid, array $fields = array())
    {
        $query = new Ooba_Storage_Query();
        $query->setParser($this->_parser);
        $query->findOne = true;
        $query->where('_uuid', $uuid)->fields($fields);

        $results = $this->_storageApi->query($this->getDb(), $model->getNameSpace(), $query->build());
        $this->_log($this->_storageApi, "load {$model->getNameSpace()} "
            .  Zend_Json::encode($query->build()->getPredicates()));

        if ($results !== false) {
            return self::prepareDataForModel($results);
        }

        return false;
    }
    
    /**
     * Get Db
     *
     * @throws Ooba_Model_Storage_Adapter_Exception_DatabaseNotSet Because its not set before funcs are called
     * @return string
     */
    static public function getDb()
    {
        if (is_null(self::$_db) === true) {
            throw new Ooba_Model_Storage_Adapter_Exception_DatabaseNotSet();
        }
        
        return self::$_db;
    }
    
    /**
     * Set Db
     *
     * @param  string $db Cdatabase to query and load from
     * @return void
     */
    static public function setDb($db)
    {
        self::$_db = $db;
    }
    
    /**
     * Never underestimate the power of the Schwartz!
     *
     * @param  mixed $data Data to prepare for model
     * @return string
     */
    static public function prepareDataForModel($data)
    {
        if (is_null($data) === true) {
            $data = array();
        }
        
        // Remove Mongo specific Data
        unset($data['_id']);
        
        // Add loadFrom to tell model this was loaded from an adapter
        $data['_loadedFrom'] = 'Ooba_Model_Storage_Adapter_Mongo';
        return json_encode($data);
    }
    
    /**
     * Query for a single object instead of results set
     *
     * @param  string             $class  Object Class to populate
     * @param  Ooba_Storage_Query $query  Query
     * @param  array              $fields (Optional) Fields to fetch
     * @throws Ooba_Model_Storage_Adapter_Exception_InvalidData If not set
     * @return boolean|Ooba_Model_Abstract
     */
    public function queryOne($class, Ooba_Storage_Query $query, array $fields = array())
    {
        if (is_object($class) === false) {
            $obj = new $class;
        } else {
            $obj = $class;
        }
        
        if (($obj instanceof Ooba_Model_Abstract) === false and ($obj instanceof Ooba_Model_Iterator) === false) {
            throw new Ooba_Model_Storage_Adapter_Exception_InvalidData('Invalid Object Type,' .
                 ' should be instance of Ooba_Model_Abstract');
        }

        $query->findOne = true;
        $query->setParser($this->_parser)->fields($fields);
        $res = $this->_storageApi->query($this->getDb(), $obj->getNameSpace(), $query->build());
        $this->_log($this->_storageApi, "query {$obj->getNameSpace()} "
            .  Zend_Json::encode($query->build()->getPredicates()));
        
        if (is_array($res) === false) {
            return false;
        }

        $data = self::prepareDataForModel($res);
        $obj->fromJson($data);
        return $obj;
    }
    
    /**
     * Check to see if a single object exists
     *
     * @param  string             $class Object Class to populate
     * @param  Ooba_Storage_Query $query Query
     * @throws Ooba_Model_Storage_Adapter_Exception_InvalidData If not set
     * @return boolean
     */
    public function exists($class, Ooba_Storage_Query $query)
    {
        if (is_object($class) === false) {
            $obj = new $class;
        } else {
            $obj = $class;
        }
        
        if (($obj instanceof Ooba_Model_Abstract) === false and ($obj instanceof Ooba_Model_Iterator) === false) {
            throw new Ooba_Model_Storage_Adapter_Exception_InvalidData('Invalid Object Type,' .
                 ' should be instance of Ooba_Model_Abstract');
        }
        
        $fields = array('_uuid');
        $query->setParser($this->_parser)->fields($fields)->limit(1);
        $built = $query->build();
        $res   = $this->_storageApi->query($this->getDb(), $obj->getNameSpace(), $built);
        $this->_log($this->_storageApi, "query {$obj->getNameSpace()} "
            .  Zend_Json::encode($query->build()->getPredicates()));
        
        return $res->hasNext();
    }
    
    /**
     * Increment a property of a model
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to update
     * @param  string             $field     Property of model to change
     * @param  integer            $n         (Optional) Number to increment by
     * @param  array|null         $options   (Optional) Options used for running commands
     * @return boolean
     */
    public function increment($nameSpace, Ooba_Storage_Query $query, $field, $n = 1, $options = null)
    {
        $query->setParser($this->_parser);
        $conditions = $query->build()->getFind();
        $increments = array('$inc' => array($field => $n));
        $results    = $this->_update($nameSpace, $conditions, $increments, array('multiple' => true));
        
        if ($results !== true) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Decrement a property of a model
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to update
     * @param  string             $field     Property of model to change
     * @param  integer            $n         (Optional) Number to decrement by
     * @param  array|null         $options   (Optional) Options used for running commands
     * @return boolean
     */
    public function decrement($nameSpace, Ooba_Storage_Query $query, $field, $n = 1, $options = null)
    {
        $query->setParser($this->_parser);
        $conditions = $query->build()->getFind();
        $increments = array('$dec' => array($field => $n));
        $results    = $this->_update($nameSpace, $conditions,
            $increments,
            array('multiple' => true));
        
        if ($results !== true) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Push a value on a property of a model
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  mixed              $value     Value to add
     * @param  array|null         $options   (Optional) Options used for running commands
     * @return boolean
     */
    public function push($nameSpace, Ooba_Storage_Query $query, $field, $value, $options = null)
    {
        $query->setParser($this->_parser);
        $conditions = $query->build()->getFind();
        $push       = array('$push' => array($field => $value));
        $results    = $this->_update($nameSpace, $conditions, $push);
        
        if ($results !== true) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Push many values 
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  array              $values    Values to add as individual array elements
     * @param  array|null         $options   (Optional) Options used for running commands
     * @throws Ooba_Model_Storage_Adpapter_Exception_InvalidData If you try to incemrent multiple model
     * @return boolean
     */
    public function pushAll($nameSpace, Ooba_Storage_Query $query, $field, array $values, $options = null)
    {
        $query->setParser($this->_parser);
        $conditions = $query->build()->getFind();
        $push       = array('$pushAll' => array($field => $values));
        $results    = $this->_storageApi->{$this->getDb()}->$nameSpace->update($conditions,
            $push);
        
        if ($results !== true) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Pull a value from an array
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  mixed              $value     Value to remove
     * @param  array|null         $options   (Optional) Options used for running commands
     * @return boolean
     */
    public function pull($nameSpace, Ooba_Storage_Query $query, $field, $value, $options = null)
    {
        $query->setParser($this->_parser);
        $conditions = $query->build()->getFind();
        if ($value instanceof Ooba_Model_Abstract) {
            $value = Zend_Json::decode($value->toJson());
        }
        
        $pull    = array('$pull' => array($field => $value));
        $results = $this->_update($nameSpace, $conditions, $pull);
        
        if ($results !== true) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Pull many values from an array
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  array              $values    Values to remove
     * @param  array|null         $options   (Optional) Options used for running commands
     * @return boolean
     */
    public function pullAll($nameSpace, Ooba_Storage_Query $query, $field, array $values, $options = null)
    {
        $query->setParser($this->_parser);
        $conditions = $query->build()->getFind();
        $push       = array('$pullAll' => array($field => $values));
        $results    = $this->_storageApi->{$this->getDb()}->$nameSpace->update($conditions,
            $push);
        
        if ($results !== true) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Pop a value from an array
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  mixed              $value     Value 1 = last element  -1 = first element
     * @param  array|null         $options   (Optional) Options used for running commands
     * @return boolean
     */
    public function pop($nameSpace, Ooba_Storage_Query $query, $field, $value, $options = null)
    {
        $query->setParser($this->_parser);
        $conditions = $query->build()->getFind();
        $push       = array('$pop' => array($field => $value));
        $results    = $this->_update($nameSpace, $conditions, $push);
        
        if ($results !== true) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Add values if they don't exist
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  mixed              $values    Values to add, if adding multiple values set $many to true
     * @param  array|null         $options   (Optional) Options used for running commands
     * @return boolean
     */
    public function addToSet($nameSpace, Ooba_Storage_Query $query, $field, $values, $options = null)
    {
        $query->setParser($this->_parser);
        $conditions = $query->build()->getFind();
        if (isset($options['each']) === true and $options['each'] === true) {
            $values = array('$each' => $values);
        }
        
        $push    = array('$addToSet' => array($field => $values));
        $results = $this->_update($nameSpace, $conditions, $push);
        
        if ($results !== true) {
            return false;
        }
        
        return true;
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
    public function set($nameSpace, Ooba_Storage_Query $query, $field, $value, $options = null)
    {
        // Don't store null values in mongo
        if (is_null($value) === true) {
            return $this->remove($nameSpace, $query, $field, $value, $options);
        }
        
        $query->setParser($this->_parser);
        $conditions = $query->build()->getFind();
        $set        = array('$set' => array($field => $value));
        $results    = $this->_update($nameSpace, $conditions, $set);
        
        if ($results !== true) {
            return false;
        }
        
        return true;
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
        $conditions = $query->build()->getFind();
        $unset      = array('$unset' => array($field => 1));
        $results    = $this->_update($nameSpace, $conditions, $unset);
        
        if ($results !== true) {
            return false;
        }
        
        return true;
    }

    /**
     * Encode an object to raw Json
     *
     * @param  Ooba_Model_Abstract $obj Object to encode
     * @return string
     */
    static public function rawEncode(Ooba_Model_Abstract $obj)
    {
        $data = Zend_Json::decode($obj->toJson());

        $data['_id'] = $data['_uuid'];

        return Zend_Json::encode($data);
    }
    
    /**
     * Log info about mongo operation
     *
     * @param  Ooba_Storage_Mongo $api Storage Api
     * @param  string             $msg Message
     * @return void
     */
    protected function _log(Ooba_Storage_Mongo $api, $msg)
    {
        Ooba_Log::getInstance()->debug("MONGO: {$api->getServer()}  $msg", 4, 7);
    }
    
    /**
     * Call update on mongo and log
     *
     * @param  string $nameSpace  NameSpace
     * @param  array  $conditions Conditions
     * @param  array  $operation  Operation
     * @param  array  $options    (Optional) Options
     * @return mixed
     */
    protected function _update($nameSpace, array $conditions, array $operation, array $options = array())
    {
        $results = $this->_storageApi->{$this->getDb()}->$nameSpace->update($conditions,
            $operation/*, $options*/);
        $this->_log($this->_storageApi, "update $nameSpace "
            . Zend_Json::encode($conditions) . ' ' . Zend_Json::encode($operation));
        return $results;
    }
    
    /**
     * Get value from data array with given key path
     *
     * @param  string $path  String representing keys of data array to fine value
     * @param  array  $array Data array to search for value
     * @return mixed
     */
    protected function _getPath($path, array $array)
    {
        $path = explode('.', $path);
        foreach ($path as $key) {
            if (isset($array[$key]) === true) {
                $array = $array[$key];
            } else {
                return false;
            }
        }
        
        return $array;
    }
    
    /**
     * Removes any fields in a multi-dimensional array that are set to NULL; used
     * to ensure null data not written to mongo.
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
}
