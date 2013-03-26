<?php
/**
 * Ooba_Storage_Mongo_QueryParser
 *
 * @category   Ooba
 * @package Storage
 * @subpackage Mongo
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */ 
class Ooba_Storage_Mongo_QueryParser implements Ooba_Storage_Query_Parser_Interface
{
    /**
     * Query object passed in
     *
     * @var Ooba_Storage_Query
     */
    protected $_query;

	/**
     * Build the query string
     *
     * @param  Ooba_Storage_Query $query Query object to parse
     * @return array
     */
    public function build(Ooba_Storage_Query $query)
    {
        $mongoQuery = new Ooba_Storage_Mongo_Query();
        
        $this->_query = $query;
        $this->buildOrder($mongoQuery);
        $this->buildLimit($mongoQuery);
        $this->buildSkip($mongoQuery);
        $where = $this->buildWhere();
        $mongoQuery->setFields($query->fields);

        if (empty($query->findOne) === false and $query->findOne === true) {
            $mongoQuery->sort  = null;
            $mongoQuery->limit = null;
            $mongoQuery->setFindOne($where);
        } else {
            $mongoQuery->setFind($where);
        }

        return ($mongoQuery);
    }
    
    /**
     * Build where portion of query string
     *
     * @param  object $conditions (Optional) Query object to create as a nested set of conditions
     * @throws Ooba_Storage_Mongo_Exception_ParseError Unable to parse
     * @return Mongo query array
     */
    public function buildWhere($conditions = null)
    {
        if (is_null($conditions) === true) {
            $conditions = $this->_query->where;
        }
        
        if (empty($conditions) === true) {
            return array();
        }

        $isOr = false;
        
        $statements = array();
        foreach ($conditions as $index => $where) {
            if ($where instanceof Ooba_Storage_Query) {
                $clause = $this->buildWhere($where->where);
            } else if ($where instanceof Ooba_Storage_Query_Condition) {
                if (method_exists($this, $where->operator) === true) {
                    $clause = $this->{$where->operator}($where);
                    $isOr   = ($where->conjunction === 'OR');
                } else {
                    throw new Ooba_Storage_Mongo_Exception_ParseError('Invalid operator method called: '
                                                                      . $where->operator);
                }
            } else {
                throw new Ooba_Storage_Mongo_Exception_ParseError('Where condition called in Query Parser '
                                        . 'is not an instance'
                                        . ' of Ooba_Storage_Query or Ooba_Storage_Query_Condition');
            }
            
            $newValue = reset($clause);
            $newKey   = key($clause);

            if (array_key_exists($newKey, $statements) === true) {
                if (is_array($statements[$newKey]) === true) {
                    if (is_array($newValue) === true) {
                        $subValue                     = reset($newValue);
                        $subKey                       = key($newValue);
                        $statements[$newKey][$subKey] = $subValue;
                    } else {
                        $statements[$newKey][] = $newValue;
                    }
                } else {
                    $existingValue       = $statements[$newKey];
                    $statements[$newKey] = array($existingValue, $newValue);
                }
            } else if ($isOr === true) {
                $statements[] = $clause;
            } else {
                $statements = array_merge($statements, $clause);
            }
        }

        if ($isOr === true) {
            $statements = array('$or' => $statements);
        }

        return $statements;
    }
    
    /**
     * Build order portion of query string
     *
     * @param  Ooba_Storage_Mongo_Query $mongoQuery Mongo query object
     * @return void
     */
    public function buildOrder(Ooba_Storage_Mongo_Query $mongoQuery)
    {
        if (empty($this->_query->order) !== true) {
            $order = array();
            foreach ($this->_query->order as $field => $direction) {
                $order[$field] = ((strtolower($direction) === 'asc') ? 1 : (integer) '-1');
            }
            
            $mongoQuery->setSort($order);
        }
    }
    
    /**
     * Create limit portion of query string
     *
     * @param  Ooba_Storage_Mongo_Query $mongoQuery Mongo query object
     * @return void
     */
    public function buildLimit(Ooba_Storage_Mongo_Query $mongoQuery)
    {
        if (is_null($this->_query->limit) !== true) {
            $mongoQuery->setLimit($this->_query->limit);
        }
    }
    
    /**
     * Create skip portion of query string
     *
     * @param  Ooba_Storage_Mongo_Query $mongoQuery Mongo query object
     * @return void
     */
    public function buildSkip(Ooba_Storage_Mongo_Query $mongoQuery)
    {
        if (is_null($this->_query->offset) !== true) {
            $mongoQuery->setSkip($this->_query->offset);
        }
    }

    /**
     * Equals (this = that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function eq(Ooba_Storage_Query_Condition $condition)
    {
        return array($condition->key => $condition->value);
    }

    /**
     *  Not Equals (this != that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function notEq(Ooba_Storage_Query_Condition $condition)
    {
        return array($condition->key => array('$ne' => $condition->value));
    }

    /**
     *  Like (this LIKE that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @throws Ooba_Storage_Mongo_Exception_InvalidValue Invalid value
     * @return string
     */
    public function like(Ooba_Storage_Query_Condition $condition)
    {
        return array($condition->key => $this->_getLikeValue($condition->value));
    }

    /**
     *  Not Like (this NOT LIKE that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function notLike(Ooba_Storage_Query_Condition $condition)
    {
        return array($condition->key => array('$not' => $this->_getLikeValue($condition->value)));
    }

    /**
     *  IS NULL (this IS NULL)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function isNull(Ooba_Storage_Query_Condition $condition)
    {
        return array($condition->key => array('$exists' => false));
    }

    /**
     *  IS NOT NULL (this IS NOT NULL)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function isNotNull(Ooba_Storage_Query_Condition $condition)
    {
        return array($condition->key => array('$exists' => true));
    }

    /**
     *  Less than (this < that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function lt(Ooba_Storage_Query_Condition $condition)
    {
        return array($condition->key => array('$lt' => $condition->value));
    }

    /**
     *  Less than or equal to (this =< that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function lte(Ooba_Storage_Query_Condition $condition)
    {
        return array($condition->key => array('$lte' => $condition->value));
    }

    /**
     *  Greater than (this > that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function gt(Ooba_Storage_Query_Condition $condition)
    {
        return array($condition->key => array('$gt' => $condition->value));
    }

    /**
     * Greater than or equal to (this >= that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function gte(Ooba_Storage_Query_Condition $condition)
    {
        return array($condition->key => array('$gte' => $condition->value));
    }

    /**
     *  In (this IN (that,thatAlso,orThat,orMaybeThat))
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function in(Ooba_Storage_Query_Condition $condition)
    {
        if (is_array($condition->value) === true) {
            return array($condition->key => array('$in' => $condition->value));
        } else {
            return array($condition->key => array('$in' => explode(',', $condition->value)));
        }
    }

    /**
     *  In (this NOT IN (that,thatAlso,orThat,orMaybeThat))
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function notIn(Ooba_Storage_Query_Condition $condition)
    {
        if (is_array($condition->value) === true) {
            return array($condition->key => array('$nin' => $condition->value));
        } else {
            return array($condition->key => array('$nin' => explode(',', $condition->value)));
        }
    }

    /**
     *  Regex
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return array
     */
    public function regex(Ooba_Storage_Query_Condition $condition)
    {
            return array($condition->key => new MongoRegex($condition->value));
    }

    /**
     * Gets the equivalent regex for a "like" value; "%this" returns '/this$/'
     *
     * @param  string $value Sql-like string to convert to a regex
     * @throws Ooba_Storage_Mongo_Exception_InvalidValue Poorly formatted string
     * @return MongoRegex
     */
    protected function _getLikeValue($value)
    {
        if (substr($value, 0, 1) === '%') {
            $wildcardFront = true;
            $value         = substr($value, 1);
        } else {
            $wildcardFront = false;
        }
        
        if (substr($value, -1, 1) === '%') {
            $wildcardBack = true;
            $value        = substr($value, 0, -1);
        } else {
            $wildcardBack = false;
        }

        if ($wildcardFront === true and $wildcardBack === true) {
            $regex = '/' . $value . '/';
        } else if ($wildcardFront === true) {
            $regex = '/' . $value . '$/';
        } else if ($wildcardBack === true) {
            $regex = '/^' . $value . '/';
        } else {
            throw new Ooba_Storage_Mongo_Exception_InvalidValue('like comparators require leading ' .
                                                                'or trailing percent signs');
        }

        return new MongoRegex($regex);
    }
}
