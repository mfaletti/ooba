<?php
/**
 * Ooba_Model_Storage_Adapter_Abstract.php
 *
 * @category   Ooba
 * @package    Model
 * @subpackage Storage
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */

abstract class Ooba_Model_Storage_Adapter_Abstract
{
	/**
	 * Type: Cache or Persistent
	 *
	 * @var string
	 */
	protected $_type;
	
	/**
     * NameSpace
     *
     * @var string
     */
    protected $_nameSpace;
	
	/**
     * Parser
     *
     * @var mixed
     */
    protected $_parser;
	
	/**
     * Set Adapter Type
     *
     * @param  string $type Type: cache, persistent
     * @return void
     */
	public function setType($type)
	{
		$this->_type = $type;
	}
	
	/**
     * Get Adapter Type
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

	/**
     * Get Name Space
     *
     * @return string
     */
    public function getNameSpace()
    {
        return $this->_nameSpace;
    }

	/**
     * Set Name Space
     *
     * @param  string $nameSpace Namespace
     * @return void
     */
    public function setNameSpace($nameSpace)
    {
        $this->_nameSpace = $nameSpace;
    }

	/**
	 * Build Cache Keys from model
	 *
	 * @param Ooba_Model_Abstract 	$model Model to build cache keys from
	 * @return string
	 */
	public static function generateCacheKey(Ooba_Model_Abstract $model)
    {
        return self::_generateKey($model->getNameSpace(), $model->uuid);
    }

	/**
     * Generate key for memcache
     *
     * @param  string $nameSpace The namespace to use in generating the cache key
     * @param  string $uuid      Uuid to load
     * @throws Ooba_Model_Storage_Exception_MissingNameSpaces If namespace not set
     * @return string Memcache key
     */
    static protected function _generateKey($nameSpace, $uuid)
    {
        $key = $nameSpace . '_' . $uuid;
        $key = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
        return $key;
    }

	/**
     * Get parser
     *
     * @return Ooba_Storage_Query_Parser_Interface
     */
    public function getParser()
    {
        return $this->_parser;
    }
}
?>