<?
/**
 * Condition.php
 *
 * @category   Ooba
 * @package    Storage
 * @subpackage Query
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Storage_Query_Condition
{
    /**
     * Column/attribute of where condition
     *
     * @var string
     */
    public $key;

    /**
     * Value to match key against
     *
     * @var string
     */
    public $value;

    /**
     * Operator for condition
     *
     * @var string
     */
    public $operator;

    /**
     * Conjunction to prefix condition (AND or OR)
     *
     * @var string
     */
    public $conjunction;

    /**
     * Constructor
     *
     * @param string $key         Column/attribute of where condition
     * @param string $value       Value to match key against
     * @param string $operator    (Optional) Operator for condition
     * @param string $conjunction (Optional) Conjunction to prefix condition (AND or OR)
     */
    public function __construct($key, $value, $operator = '=', $conjunction = 'AND')
    {
        $this->key         = $key;
        $this->value       = $value;
        $this->operator    = $operator;
        $this->conjunction = $conjunction;
    }
}

?>