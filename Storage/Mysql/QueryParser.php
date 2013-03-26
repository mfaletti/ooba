<?php
/**
 * Ooba_Storage_Mysql_QueryParser.php
 *
 * @category   Ooba
 * @package    Model
 * @subpackage Storage
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */

class Ooba_Storage_Mysql_QueryParser implements Ooba_Storage_Query_Parser_Interface
{
	/**
	 * Query Object
	 * 
	 * @var Ooba_Storage_Query
	 */
	protected $_query;
	
	/**
	 * Select Object
	 * 
	 * @var Zend_Db_Select
	 */
	protected $_select;
	
	public function __construct()
	{
		//$this->_select = new Zend_Db_Select(Zend_Registry::get("db"));
	}
	
	/**
     * Build the query string
     *
     * @param  Ooba_Storage_Query $query Query object to parse
     * @return array
     */
	public function build(Ooba_Storage_Query $query)
	{
		$this->_select = new Zend_Db_Select(Zend_Registry::get("db")); 
		$this->_query = $query;
		$this->buildWhere();
		$this->buildLimit();
		$this->buildOrder();
		//$this->_select->columns($this->_query->fields);
		
		return $this->_select;
	}
	
	public function buildWhere()
	{
		$conditions = $this->_query->where;
		
		if (empty($conditions) === true) {
            return array();
        }

		foreach ($conditions as $index => $where) {
			$stmt = (string) $where->key . ' ' . $this->operatorMapper($where->operator) . ' ' . '?';
			$this->_select->where($stmt, $where->value);
		}
	}
	
	public function buildOrder()
	{
		if (empty($this->_query->order) !== true) {
            $order = array();
            foreach ($this->_query->order as $field => $direction) {
                $order[] = strtolower($field) . ' ' . $direction;
            }
			
			$this->_select->order($order);
        }
	}
	
	public function buildLimit()
	{
		if (is_null($this->_query->limit) !== true) {
			$this->_select->limit($this->_query->limit, $this->_query->offset);
        }
	}
	
	public function operatorMapper($operator)
	{
		switch($operator) 
		{
			case 'eq':
				return '=';
			break;
		}
	}
	
	/**
     * Equals (this = that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function eq(Ooba_Storage_Query_Condition $condition)
	{}
	
	/**
     *  Not Equals (this != that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function notEq(Ooba_Storage_Query_Condition $condition)
	{}
	
	/**
     *  Like (this LIKE that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function like(Ooba_Storage_Query_Condition $condition)
	{}
	
	/**
     *  Not Like (this NOT LIKE that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function notLike(Ooba_Storage_Query_Condition $condition)
	{}
	
	/**
     *  IS NULL (this IS NULL)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function isNull(Ooba_Storage_Query_Condition $condition)
	{}
	
	/**
     *  IS NOT NULL (this IS NOT NULL)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function isNotNull(Ooba_Storage_Query_Condition $condition)
	{}
	
	/**
     *  Less than (this < that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function lt(Ooba_Storage_Query_Condition $condition)
	{}
	
	/**
     *  Less than or equal to (this =< that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function lte(Ooba_Storage_Query_Condition $condition)
	{}
	
	/**
     *  Greater than (this > that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function gt(Ooba_Storage_Query_Condition $condition)
	{}
	
	/**
     * Greater than or equal to (this >= that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function gte(Ooba_Storage_Query_Condition $condition)
	{}
	
	/**
     *  In (this IN (that,thatAlso,orThat,orMaybeThat))
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function in(Ooba_Storage_Query_Condition $condition)
	{}
	
	/**
     *  In (this NOT IN (that,thatAlso,orThat,orMaybeThat))
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function notIn(Ooba_Storage_Query_Condition $condition)
	{}
}

?>