<?php
/**
 * Ooba_Storage_Mysql
 *
 * @category   Ooba
 * @package    Storage
 * @subpackage Mysql
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Storage_Mysql
{
	/**
     * Database Adapter object
     *
     * @var Zend_Db_Adapter_Abstract
     */
	protected $_db;
	
	/**
     * Database connection settings
     *
     * @var Zend_Ini_Config
     */
	protected $_config;
	
	/**
     * database options
     *
     * @var array
     */
	protected static $_options = array();
	
	public function __construct($config = null)
	{
		if (is_null($config) === false){
			if (($config instanceof Zend_Config_Ini) === false) {
				throw new Ooba_Exception('Invalid Config type passed to constructor');
			}
			
			$this->_config = $config;
		} else {
			$this->_config = new Zend_Config_Ini(
	            SITECORE_APPLICATION_PATH . '/configs/application.ini',
	            APPLICATION_ENV);
		}
		try {
			$this->_db = Zend_Db::factory($this->_config->resources->db);
			$this->_db->getConnection();
			Zend_Registry::set('db', $this->_db);
		} catch (Zend_Db_Adapter_Exception $e) {}
	}
	
	/**
     * Fetch query result rows as a sequential array.
     *
     * @param $namespace String:	namespace of model object
	 * @param An SQL SELECT Object
	 * @return array of 
     */
	public function query($namespace, $class, Zend_Db_Select $query)
	{
		$query->from($namespace);//->columns($query->fields);
		$adapter = new Zend_Paginator_Adapter_DbSelect($query);
		$paginator = new Ooba_Paginator($adapter);
		
		if (is_null($query->getPart(Zend_Db_Select::LIMIT_COUNT)) === false) {
			$paginator->setItemCountPerPage($query->getPart(Zend_Db_Select::LIMIT_COUNT));
		}
		
		return ($paginator->getCurrentItemCount() ? $paginator->getCurrentItems() : false);
	}
	
	public function queryOne($namespace, Zend_Db_Select $query)
	{
		$query->from($namespace)->limit(1);//->columns($query->fields);
		$result = $this->_db->fetchAll($query);
		return ($result!= null ? $result[0] : false);
	}
	
	/**
	 * Delete object
	 * @param int 		$uuid: the object with the uuid to delete 
	 * @param string	$namespace: The table from which to delete
	 * 
	 */
	public function delete($uuid, $namespace)
	{
		$where = "_uuid = " . $uuid;
		return $this->_db->delete($namespace, $where);
	}
	
	/**
     * Helper method to get db instance
     *
     * @return Zend_Db_Adapter_Abstract
     */
	public function getDb()
	{
		if(is_null($this->_db) === true){
			$this->_db = Zend_Db::factory($this->_config->resources->db);
		}
		return $this->_db;
	}
	
	public function getConfig()
	{
		return $this->_config;
	}
}

?>