<?php
/**
 * Interface.php
 *
 * @category   Ooba
 * @package    Storage
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
interface Ooba_Model_Storage_Adapter_Interface
{
	/** 
     * Query
     *
     * @param Ooba_Model_Abstract $class  Object Class to populate
     * @param Ooba_Storage_Query $query   Query
     * @param  array $fields  (Optional) Fields to fetch
     * @return boolean | Ooba_Storage_Results_Interface|Model
     */
	public function query($class, Ooba_Storage_Query $query, array $fields = array());
	
	/** 
     * Query one
     *
     * @param  Ooba_Model_Abstract $class  Object Class to populate
     * @param  Corelib_Storage_Query $query  Query
     * @param  array $fields (Optional) Fields to fetch
     * @return boolean | Ooba_Model_Abstract
     */
    public function queryOne($class, Ooba_Storage_Query $query, array $fields = array());

	/**
     * Save 
     *
     * @param  array  $data:  The Model Data to save
     * @param  string $nameSpace (Optional) NameSpace to save to
     * @return mixed
     */

	public function save(array $data, $nameSpace = null);
	
	/**
     * Load data into class from uuid
     *
     * @param  Ooba_Model_Abstract $model  Model to populate with data
     * @param  string              $uuid   Uuid to search by
     * @param  array               $fields (Optional) Fields to fetch
     * @return mixed
     */
	public function load(Ooba_Model_Abstract $model, $uuid, array $fields = array());
	
	/**
     * Delete Object
     *
     * @param  array|Ooba_Model_Abstract $uuids     Uuids to delete
     * @param  string $nameSpace (Optional) NameSpace to delete from
     * @return boolean
     */
	public function delete($uuid, $nameSpace = null);
}
?>