<?php
/**
 * Interface.php
 *
 * @category   Ooba
 * @package    Storage
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */

interface Ooba_Storage_Query_Parser_Interface
{
    /**
     * Build the query string
     *
     * @param  Ooba_Storage_Query $query Query object to parse
     * @return string
     */
    public function build(Ooba_Storage_Query $query);

    /**
     * Equals (this = that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function eq(Ooba_Storage_Query_Condition $condition);

    /**
     *  Not Equals (this != that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function notEq(Ooba_Storage_Query_Condition $condition);

    /**
     *  Like (this LIKE that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function like(Ooba_Storage_Query_Condition $condition);

    /**
     *  Not Like (this NOT LIKE that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function notLike(Ooba_Storage_Query_Condition $condition);

    /**
     *  IS NULL (this IS NULL)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function isNull(Ooba_Storage_Query_Condition $condition);

    /**
     *  IS NOT NULL (this IS NOT NULL)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function isNotNull(Ooba_Storage_Query_Condition $condition);

    /**
     *  Less than (this < that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function lt(Ooba_Storage_Query_Condition $condition);

    /**
     *  Less than or equal to (this =< that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function lte(Ooba_Storage_Query_Condition $condition);

    /**
     *  Greater than (this > that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function gt(Ooba_Storage_Query_Condition $condition);

    /**
     * Greater than or equal to (this >= that)
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function gte(Ooba_Storage_Query_Condition $condition);

    /**
     *  In (this IN (that,thatAlso,orThat,orMaybeThat))
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function in(Ooba_Storage_Query_Condition $condition);

    /**
     *  In (this NOT IN (that,thatAlso,orThat,orMaybeThat))
     *
     * @param  Ooba_Storage_Query_Condition $condition Condition object
     * @return string
     */
    public function notIn(Ooba_Storage_Query_Condition $condition);
}
