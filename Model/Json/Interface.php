<?php
/**
 * Ooba_Model_Json_Interface
 *
 * @category   Ooba
 * @package    Model_Json
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
interface Ooba_Model_Json_Interface
{
	/**
	 * convert obj to json
	 * @return mixed
	 */
	public function toJson();
	
	/**
	 * load obj from a passed JSON string
	 * @param string
	 * @return void
	 */
	public function fromJson($data);
	
}
?>