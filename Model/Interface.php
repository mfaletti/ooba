<?php
/**
 * Ooba_Model_Interface
 *
 * @category   Ooba
 * @package    Model
 * @subpackage Interface
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
interface Ooba_Model_Interface
{
	/**
	 * Load Model
	 * Returns Ooba_Model instance, false otherwise
	 * @param string $id (optional).
	 * @return Ooba_Model | boolean
	 */
	public function load($uuid);
	
	/**
	 * Save Model
	 * Returns instance of self on success, false otherwise
	 * @param array $options (optional).
	 * @return Ooba_Model | boolean
	 */
	public function save(array $options);
	
	/**
	 * delete Model
	 * @return boolean
	 */
	public function delete();
}
?>