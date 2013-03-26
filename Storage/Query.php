<?php
/**
 * Ooba_Storage_Query.php
 *
 * @category   Ooba
 * @package    Storage
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Storage_Query
{
	/**
	 * Where conditions. Think of this as the "WhereHouse"
	 *
	 * @var array
	 */
	public $where = array();
	
	/**
	 * Sort order. Key is field, value is direction
	 *
	 * @var array
	 */
	public $order = array();
	
	/**
	 * The fields to retrieve
	 *
	 * @var array
	 */
	public $fields = array();
	
	/**
	 * Query limit
	 *
	 * @var interger
	 */
	public $limit = null;
	
	/**
     * Query offset
     *
     * @var integar
     */
    public $offset = null;
	
	/**
     * Storage Engine
     *
     * @var string
     */
	public $parser;
	
	/**
     * Conjunction for where (this will only be set if object is nested in another query)
     *
     * @var string
     */
    public $conjunction;
	
	/**
     * Construct
     *
     * @param object $parser (Optional) Parser to use
     */
	public function __construct($parser = null) 
	{
		if (is_null($parser) === false) {
            $this->_setParser($parser);
        }
	}
	
	/**
     * Set parser to use in creating query string
     *
     * @param  object $parser Parser object
     * @throws Ooba_Exception If parser object doesn't implement Ooba_Storage_Query_Parser_Interface
     * @return $this
     */
    public function setParser($parser)
    {
        if (($parser instanceof Ooba_Storage_Query_Parser_Interface) === false) {
            throw new Ooba_Exception('Ooba_Storage_Query received an invalid parser class: ' . (string) $parser);
        }
        
        $this->_parser = $parser;
        return $this;
    }

	/**
     * Calls parser and builds query string
     *
     * @throws Ooba_Exception If parser object isn't set
     * @return string
     */
    public function build()
    {
        if (is_null($this->_parser) === true) {
            throw new Ooba_Exception('No parser defined for query object');
        }
        
        return $this->_parser->build($this);
    }

	/**
     * Set fields you wish to fetch
     *
     * @param  mixed $fields Fields
     * @return $this
     **/
    public function fields($fields)
    {
        $this->fields = (array) $fields;
        return $this;
    }
	
	/**
     * Adds where condition to where attribute
     *
     * @param  string $key         Attribute/column of where condition
     * @param  string $value       (Optional) Value of where condition
     * @param  string $operator    (Optional) Operator to use
     * @param  string $conjunction (Optional) Conjunction to prefix condition
     * @return $this
     */
	public function where($key, $value = null, $operator = 'eq', $conjunction = 'AND')
	{
		if ($key instanceof Ooba_Storage_Query) {
			$key->conjunction = $conjunction;
			$this->where[]	  = $key;
		} else if ($key instanceof Ooba_Storage_Query_Condition) {
            $this->where[] = $key;
        } else {
            $this->where[] = new Ooba_Storage_Query_Condition($key, $value, $operator, $conjunction);
        }
        
        return $this;
	}
	
	/**
     * Convience method that wraps where method with AND conjunction
     *
     * @param  string $key      Attribute/column of where condition
     * @param  string $value    (Optional) Value of where condition
     * @param  string $operator (Optional) Operator to use
     * @return $this
     */
    public function andWhere($key, $value = null, $operator = 'eq')
    {
        return $this->where($key, $value, $operator, 'AND');
    }

	/**
     * Convenience method that wraps where method with OR conjunction
     *
     * @param  string $key      Attribute/column of where condition
     * @param  string $value    (Optional) Value of where condition
     * @param  string $operator (Optional) Operator to use
     * @return $this
     */
    public function orWhere($key, $value = null, $operator = 'eq')
    {
        return $this->where($key, $value, $operator, 'OR');
    }
	
	/**
     * Sets limit and offset
     *
     * @param  integer $limit  Row limit
     * @param  integer $offset (Optional) Row offset
     * @return $this
     */
    public function limit($limit, $offset = null)
    {
        $this->limit = $limit;
        if (is_null($offset) === false) {
            $this->offset = $offset;
        }
        
        return $this;
    }

	/**
     * Adds sort order to order attribute
     *
     * @param  string $field     Field name to sort by
     * @param  string $direction (Optional) Direction to sort by
     * @return Ooba_Storage_Query $this Self
     */
    public function order($field, $direction = 'desc')
    {
        $this->order[$field] = $direction;
        return $this;
    }

	/**
     * Set conjuction - used for nested Query objects
     *
     * @param  string $conjunction AND or OR
     * @return void
     */
    public function setConjunction($conjunction)
    {
        $this->conjunction = $conjunction;
    }
	
	/**
     * Checks current where conditions to see if a specific key is being used
     *
     * @param  string $key Name of the key you're looking for
     * @return boolean
     **/
    public function usingKey($key)
    {
        if (is_array($this->where) === false or count($this->where) === 0) {
            return false;
        }

        foreach ($this->where as $condition) {
            if ($condition instanceof Ooba_Storage_Query_Condition === true
                and $condition->key === $key) {
                return true;
            }
        }

        return false;
    }
}