<?php
/**
 * Ooba_Model_Paginator.php
 *
 * @category   Ooba
 * @package    Ooba_Model
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Model_Paginator extends Zend_Paginator
{
	/**
	 * The Iterator interface type
	 *
	 * @var iterator
	 */
	//private $_iterator;
	
	/**
	 * The object model class
	 *
	 * @var Ooba_Model_Abstract
	 */
	private $_class;
	
	public function __construct($class, $adapter)
	{
		$this->_class = $class;
		parent::__construct($adapter);
	}
	
	public function getItemsByPage($pageNumber)
	{
		$pageNumber = $this->normalizePageNumber($pageNumber);

        if ($this->_cacheEnabled()) {
            $data = self::$_cache->load($this->_getCacheId($pageNumber));
            if ($data !== false) {
                return $data;
            }
        }

        $offset = ($pageNumber - 1) * $this->getItemCountPerPage();

        $items = $this->_adapter->getItems($offset, $this->getItemCountPerPage());

        $filter = $this->getFilter();

        if ($filter !== null) {
            $items = $filter->filter($items);
        }

        if (!$items instanceof Traversable) {
			$items = new ArrayIterator($items);
            $items = new Ooba_Model_Storage_Results_Mysql($this->_class, $items);
        }

        if ($this->_cacheEnabled()) {
            self::$_cache->save($items, $this->_getCacheId($pageNumber), array($this->_getCacheInternalId()));
        }

        return $items;
	}
}