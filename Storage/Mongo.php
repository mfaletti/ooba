<?php
/**
 * Ooba_Storage_Mongo
 *
 * @category   Ooba
 * @package    Model
 * @subpackage Storage
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Storage_Mongo
{
    /**
     * Mongo object
     *
     * @var Mongo
     */
    private $_db;

	/**
     * Ooba_Storage_Mongo Instance
     *
     * @var Ooba_Storage_Mongo
     */
    private static $_mongo;

    /**
     * Mongo server host name
     *
     * @var string
     */
    protected static $_defaultHosts;

    /**
     * Mongo server options
     *
     * @var string
     */
    protected static $_defaultOptions = array();

    /**
     * Profiling options
     *
     * @var array
     */
    protected static $_profilerOptions = array('enabled' => false, 'doStore' => false);

    /**
     * Mongo server connect string
     *
     * @var string
     */
    protected $_server;

    /**
     * Sets options from resources
     *
     * @param  array $options Ini options
     * @return void
     */
    public static function setOptions(array $options)
    {
        if (isset($options['hosts']) === true) {
            self::$_defaultHosts = $options['hosts'];
        }

        if (empty($options['persist']) === false) {
            self::$_defaultOptions['persist'] = $options['persist'];
        }

        if (isset($options['replset']) === true) {
            self::$_defaultOptions['replicaSet'] = (bool) $options['replset'];
        }
    }

    /**
     * Construct
     *
     * @param  string $server (Optional) Connect string or Mongo object
     * @throws Ooba_Storage_Mongo_Exception_NoDefaultSettings Default host/port not set
     */
    public function __construct($server = null)
    {
        $constantKeeper = ConstantsKeeper::getInstance();
        
        if (isset($constantKeeper->mongoHost) === true) {
            self::$_defaultHosts = $constantKeeper->mongoHost;
        }

        if (empty($constantKeeper->mongoPersist) === false) {
            self::$_defaultOptions['persist'] = $constantKeeper->mongoPersist;
        }

        if (isset($constantKeeper->mongoReplicaset) === true) {
            self::$_defaultOptions['replicaSet'] = $constantKeeper->mongoReplicaset;
        }
        
        if (isset($constantKeeper->mongoProfilerEnabled) === true and
                  (bool) $constantKeeper->mongoProfilerEnabled === true) {
            self::$_profilerOptions['enabled'] = (bool) $constantKeeper->mongoProfilerEnabled;
        }
        
        if (isset($constantKeeper->mongoProfilerDoStore) === true and
                  (bool) $constantKeeper->mongoProfilerDoStore === true) {
            self::$_profilerOptions['doStore'] = (bool) $constantKeeper->mongoProfilerDoStore;
        }
        
        if (is_null($server) === false and is_object($server) === false) {
            $this->_server = $server;
        } else if (is_null($server) === false and ($server instanceof Mongo) === true) {
            $this->_db = $server;
        } else {
            if (empty(self::$_defaultHosts) === true ) {
                throw new Ooba_Storage_Mongo_Exception_NoDefaultSettings(
                    'Default host and/or port not set');
            }

            $this->_server = 'mongodb://' . self::$_defaultHosts;
        }
    }

	/**
     * Return the Ooba_Storage_Mongo singleton.
     *
     * @return Ooba_Storage_Mongo
     */
    public static function getInstance()
    {
        if (is_null(self::$_mongo) === true) {
            self::$_mongo = new self;
        }

        return self::$_mongo;
    }

    /**
     * Helper method to get db instance
     *
     * @return Mongo
     */
    protected function _getDb()
    {
        if (is_null($this->_db) === true) {
            $this->_db = new Mongo($this->_server, self::$_defaultOptions);
        }

        return $this->_db;
    }

    /**
     * Return the server string
     *
     * @return string
     */
    public function getServer()
    {
        return $this->_server;
    }

    /**
     * Passes through methods from this object to the encapsulated Mongo object
     *
     * @param  string $name      Method name
     * @param  array  $arguments Method arguments
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        return call_user_func_array(array($this->_getDb(), $name), $arguments);
    }

    /**
     * Query wrapper function passes all predicates to mongo in one fell swoop
     *
     * @param  string                   $db         MongoDb
     * @param  string                   $collection Mongo Collection
     * @param  Ooba_Storage_Mongo_Query $queryObj   Query Object containing necessary predicates
     * @return MongoSomething
     */
    public function query($db, $collection, Ooba_Storage_Mongo_Query $queryObj)
    {
        $mongo = $this->_getDb()->selectDB($db);
        $mongo = $mongo->selectCollection($collection);

        if (self::getProfilerEnabled() === true) {
            $profiled       = $this->_getDb()->selectDB($db);
            $profiled       = $profiled->selectCollection($collection);
            $profiledFields = array();
        }

        $predicates = $queryObj->getPredicates();

        foreach ($predicates as $predicate => $val) {
            if (is_null($val) === false) {
                if ($predicate === 'find' or $predicate === 'findOne') {
                    $fields      = array();
                    $mongoFields = $queryObj->getFields();
                    if (is_null($mongoFields) === false) {
                        $fields = $mongoFields;
                    }

                    if (self::getProfilerEnabled() === true) {
                        $profiled       = $profiled->find($val, $fields);
                        $profiledFields = array_merge($profiledFields, array_keys($val));
                    }

                    $mongo = $mongo->$predicate($val, $fields);
                } else {
                    if (self::getProfilerEnabled() === true and $predicate === 'sort') {
                        $profiled       = $profiled->sort($val);
                        $profiledFields = array_merge($profiledFields, array_keys($val));
                    }

                    $mongo = $mongo->$predicate($val);
                }
            }
        }

        // Profile if enabled
        if (self::getProfilerEnabled() === true) {
            try {
                $this->_profileCursor($profiled);
            } catch (Exception $e) {
                Ooba_Log::getInstance()->err('Mongo Profiling error: '
                                            . __FILE__ . ': ' . __LINE__
                                            . ' : ' . $e->getMessage());
            }
        }

        if ($mongo === null) {
            return false;
        }

        return $mongo;
    }

    /**
     * Profile
     *
     * @param  MongoCursor $cursor Cursor to profile
     * @return void
     */
    protected function _profileCursor(MongoCursor $cursor)
    {
        if (self::getProfilerEnabled() !== true) {
            return;
        }

        $backtrace = debug_backtrace();
        $level     = min(4, (count($backtrace) - 1));

        if (isset($backtrace[$level]['file']) === false) {
            $backtrace[$level]['file'] = 'No File in Stack Trace';
        }

        if (isset($backtrace[$level]['line']) === false) {
            $backtrace[$level]['line'] = 0;
        }

        $file    = $backtrace[$level]['file'];
        $line    = $backtrace[$level]['line'];
        $explain = $cursor->explain();
        $info    = $cursor->info();

        $ns     = $info['ns'];
        $fields = (array) $info['query']['$query'];
        $sort   = array();
        if (isset($info['query']['$orderby']) === true) {
            $sort = (array) $info['query']['$orderby'];
        }

        $nscanned        = $explain['nscanned'];
        $nscannedObjects = $explain['nscannedObjects'];

        $profiledFields = array_merge($fields, $sort);
        $fields         = array_keys($fields);
        $sortedFields   = $fields;
        asort($sortedFields);
        $fieldstring       = implode($fields, '|');
        $sortedfieldstring = implode($sortedFields, '|');

        $profiledFields       = array_keys($profiledFields);
        $sortedProfiledFields = $profiledFields;
        asort($sortedProfiledFields);
        $profiledFieldstring = implode($profiledFields, '|');
        
        // Regex and Not Eq data
        $regex = false;
        $notEq = false;
        foreach ($info['query']['$query'] as $key => $val) {
            if (is_array($val) === true AND isset($val['$ne']) === true) {
                $notEq = true;
                 Ooba_Log::getInstance()->warn(" {$file} {$line}"
                    . ' Query is using $ne');
            } else if ($val instanceof MongoRegex) {
                $regex = true;
                Ooba_Log::getInstance()->warn(" {$file} {$line}"
                    . ' Query is using $regex');
            }
        }
        
        // Need to take into acount clause as well as single queries
        if (isset($explain['clauses']) === true) {
             Ooba_Log::getInstance()->warn(" {$file} {$line}"
                . "Query uses '\$or' or other multiple clause operators: " . json_encode($fields));
            // Need to figure out how to analyze the $or
        }

        if (isset($explain['cursor']) === true
                and $explain['cursor'] === 'BasicCursor') {
            Ooba_Log::getInstance()->err(" {$file} {$line}"
            . " UnIndexed Query: $ns - nscanned {$explain['nscanned']} - "
            . " Cursor {$explain['cursor']} " . json_encode($fields));
        } else if (isset($explain['indexBounds']) === true
            and count(array_diff($profiledFields, array_keys($explain['indexBounds']))) > 0 ) {
            // Need to handle special case $elemMatch
            Ooba_Log::getInstance()->err(" {$file} {$line}"
            . " Index field count or order not optimal: $ns - nscanned {$explain['nscanned']} - "
            . " Cursor {$explain['cursor']} " . str_replace(',', ', ', json_encode($profiledFields)));
        }

        if (self::getProfilerDoStore() === true) {
            if (isset($explain['cursor']) === true
                            and $explain['cursor'] === 'BasicCursor') {
                $mongo = $this->_getDb()->selectDB('profiler');
                $mongo = $mongo->selectCollection('noIndex');
                $mongo->update(array('fieldsKey' => $sortedfieldstring, 'ns' => $ns),
                array('$inc'      => array('count'=>1),
                      '$addToSet' => array( 'queries' => array(
                                                'query'    => array('query'   => $fields,
                                                                    'orderby' => array_keys($sort)),
                                                'file'     => "$file : $line"),
                                           'profiledFields' => $profiledFieldstring,
                                           'fieldString'    => $fieldstring)),
                array('upsert' =>true));
            } else if (isset($explain['indexBounds']) === true
                        and count(array_diff($profiledFields, array_keys((array) $explain['indexBounds']))) > 0 ) {
                $mongo = $this->_getDb()->selectDB('profiler');
                $mongo = $mongo->selectCollection('badIndex');
                $mongo->update(array('fieldsKey' => $sortedfieldstring, 'ns' => $ns),
                array('$inc'      => array('count'=>1),
                      '$addToSet' => array('queries' => array(
                                                'cursor'   => $explain['cursor'],
                                                'query'    => array('query'   => $fields,
                                                                    'orderby' => array_keys($sort)),
                                                'file'     => "$file : $line"),
                                           'profiledFields' => $profiledFieldstring,
                                           'fieldString'    => $fieldstring)),
                array('upsert' =>true));
            } else if ($notEq === true OR $regex === true) {
                $mongo     = $this->_getDb()->selectDB('profiler');
                $mongo     = $mongo->selectCollection('badQueries');
                $queryType = ($notEq === true) ? '$notEq' : '$regex';
                $mongo->update(array('fieldsKey' => $sortedfieldstring, 'ns' => $ns),
                     array('$inc'      => array('count'=>1),
                              '$addToSet' => array('queries' => array(
                                                        'cursor'   => $explain['cursor'],
                                                        'query'    => array('query'   => $fields,
                                                                            'orderby' => array_keys($sort)),
                                                        'file'     => "$file : $line"),
                                                   'profiledFields' => $profiledFieldstring,
                                                   'fieldString'    => $fieldstring,
                                                   'queryType' => $queryType)),
                        array('upsert' =>true));
            }
            
            $mongo = $this->_getDb()->selectDB('profiler');
            $mongo = $mongo->selectCollection('queries');

            if (empty($fields) === false and isset($explain['cursor']) === true) {
                $mongo->update(array('fields' => array('$all' => $fields), 'ns' => $ns),
                array('$inc'      => array('count'=>1),
                      '$addToSet' => array('fields' => array('$each' => $fields),
                                           'queries' => array(
                                                'cursor'   => $explain['cursor'],
                                                'query'    => array('query'           => $fields,
                                                                    'orderby'         => array_keys($sort),
                                                                    'nscanned'        => $nscanned,
                                                                    'nscannedObjects' => $nscannedObjects),
                                                'file'     => "$file : $line"),
                                           'field_strings' => $sortedfieldstring)),
                array('upsert' =>true));
            }
        }
    }

    /**
     * Magic getter - returns property if it exists; MongoDB object otherwise
     *
     * @param  string $name Name of non-existent field
     * @return MongoDB
     */
    public function __get($name)
    {
        if (property_exists($this->_getDb(), $name) === true) {
            return $this->_getDb()->$name;
        }

        return $this->_getDb()->selectDB($name);
    }

    /**
     * Get default host
     *
     * @return string
     */
    static public function getDefaultHosts()
    {
        return self::$_defaultHosts;
    }

    /**
     * Set Profile Enabled
     *
     * @param  boolean $enable True or False
     * @return void
     */
    static public function setProfilerEnabled($enable)
    {
        self::$_profilerOptions['enabled'] = (bool) $enable;
    }

    /**
     * Get Profile Enabled
     *
     * @return boolean
     */
    static public function getProfilerEnabled()
    {
        return self::$_profilerOptions['enabled'];
    }

    /**
     * Set Profile doStore
     *
     * @param  boolean $doStore True or False
     * @return void
     */
    static public function setProfilerDoStore($doStore)
    {
        self::$_profilerOptions['doStore'] = (bool) $doStore;
    }

    /**
     * Get Profile doStore
     *
     * @return boolean
     */
    static public function getProfilerDoStore()
    {
        return self::$_profilerOptions['doStore'];
    }
}
